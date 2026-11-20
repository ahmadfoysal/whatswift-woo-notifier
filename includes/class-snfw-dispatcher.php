<?php
/**
 * Decides when a message is due, protects against duplicates, and delivers.
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;

/**
 * Order events → log rows → API.
 *
 * Every message is first written to the log as "queued". It is sent right after the page
 * response has been handed to the visitor, with Action Scheduler as the safety net and
 * retry mechanism. A row can only be claimed once, so a message is never sent twice.
 */
class SNFW_Dispatcher {

	const ACTION       = 'snfw_send';
	const GROUP        = 'snfw';
	const MAX_ATTEMPTS = 3;

	/**
	 * Seconds after the order-placed message during which the first status message is held back.
	 */
	const FIRST_STATUS_WINDOW = 120;

	/**
	 * Seconds during which identical text to the same number counts as a duplicate.
	 */
	const SAME_TEXT_WINDOW = 600;

	/**
	 * Log IDs queued during this request.
	 *
	 * @var int[]
	 */
	private static $pending = array();

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'on_classic_checkout' ), 20, 3 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'on_placed' ), 20, 1 );
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'on_status_changed' ), 20, 4 );
		add_action( self::ACTION, array( __CLASS__, 'process' ), 10, 1 );
		add_action( 'shutdown', array( __CLASS__, 'flush' ), 100 );
	}

	/**
	 * Classic checkout.
	 *
	 * @param int      $order_id Order ID.
	 * @param array    $posted   Posted data.
	 * @param WC_Order $order    Order.
	 */
	public static function on_classic_checkout( $order_id, $posted = array(), $order = null ) {
		self::on_placed( $order instanceof WC_Order ? $order : wc_get_order( $order_id ) );
	}

	/**
	 * A new order exists: message the customer and the sellers. Safe to call more than once.
	 *
	 * @param WC_Order|false $order Order.
	 */
	public static function on_placed( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$id = $order->get_id();

		$customer = SNFW_Settings::get( 'customer_placed' );
		if ( ! empty( $customer['enabled'] ) && '' !== trim( $customer['template'] ) ) {
			self::queue(
				array(
					'order'     => $order,
					'event'     => 'placed',
					'recipient' => 'customer',
					'phone'     => $order->get_billing_phone(),
					'message'   => SNFW_Placeholders::render( $customer['template'], $order ),
					'file'      => ! empty( $customer['attach_image'] ) ? self::first_product_image( $order ) : '',
					'key'       => "o{$id}:placed:c",
				)
			);
		}

		$seller = SNFW_Settings::get( 'seller_placed' );
		if ( ! empty( $seller['enabled'] ) && '' !== trim( $seller['template'] ) ) {
			$message = SNFW_Placeholders::render( $seller['template'], $order );
			foreach ( SNFW_Settings::seller_numbers() as $number ) {
				self::queue(
					array(
						'order'     => $order,
						'event'     => 'placed',
						'recipient' => 'seller',
						'phone'     => $number,
						'message'   => $message,
						'key'       => "o{$id}:placed:s:{$number}",
					)
				);
			}
		}
	}

	/**
	 * Order status changed.
	 *
	 * @param int      $order_id Order ID.
	 * @param string   $old      Old status slug.
	 * @param string   $new      New status slug.
	 * @param WC_Order $order    Order.
	 */
	public static function on_status_changed( $order_id, $old, $new, $order = null ) {
		$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order || 'checkout-draft' === $new ) {
			return;
		}
		$id = $order->get_id();

		// Orders that did not come through the checkout (made by staff, the REST API, imports)
		// count as placed when they first become a real order.
		$via_checkout = in_array( $order->get_created_via(), array( 'checkout', 'store-api' ), true );
		$created      = $order->get_date_created();
		if (
			! $via_checkout
			&& in_array( $old, array( 'pending', 'auto-draft', 'draft' ), true )
			&& in_array( $new, array( 'on-hold', 'processing', 'completed' ), true )
			&& $created && $created->getTimestamp() >= (int) get_option( 'snfw_v2_since', 0 )
		) {
			self::on_placed( $order );
		}

		$once = (bool) SNFW_Settings::get( 'once_per_status' );

		$config = SNFW_Settings::status_config( $new );
		if ( ! empty( $config['enabled'] ) && '' !== trim( $config['template'] ) ) {
			$message = SNFW_Placeholders::render( $config['template'], $order, $new );
			$args    = array(
				'order'     => $order,
				'event'     => 'status:' . $new,
				'recipient' => 'customer',
				'phone'     => $order->get_billing_phone(),
				'message'   => $message,
				'key'       => $once ? "o{$id}:st:{$new}:c" : '',
			);

			if ( self::arrives_with_placed_message( $id, $new ) ) {
				self::log_skip( $args, __( 'Held back: the order-placed message was sent moments ago, so this first status message would be a second message for the same thing.', 'whatswift-woo-notifier' ) );
			} else {
				self::queue( $args );
			}
		}

		$seller = SNFW_Settings::get( 'seller_status' );
		if ( ! empty( $seller['enabled'] ) && '' !== trim( $seller['template'] ) && in_array( $new, (array) $seller['statuses'], true ) ) {
			$message = SNFW_Placeholders::render( $seller['template'], $order, $new );
			foreach ( SNFW_Settings::seller_numbers() as $number ) {
				self::queue(
					array(
						'order'     => $order,
						'event'     => 'status:' . $new,
						'recipient' => 'seller',
						'phone'     => $number,
						'message'   => $message,
						'key'       => $once ? "o{$id}:st:{$new}:s:{$number}" : '',
					)
				);
			}
		}
	}

	/**
	 * Would this status message land together with the order-placed message?
	 *
	 * @param int    $order_id Order ID.
	 * @param string $status   New status.
	 * @return bool
	 */
	private static function arrives_with_placed_message( $order_id, $status ) {
		if ( ! SNFW_Settings::get( 'skip_first_status' ) || ! in_array( $status, array( 'pending', 'on-hold', 'processing' ), true ) ) {
			return false;
		}
		$placed = SNFW_Logger::get_by_key( "o{$order_id}:placed:c" );
		return $placed && ( time() - strtotime( $placed->created_at . ' UTC' ) ) <= self::FIRST_STATUS_WINDOW;
	}

	/**
	 * Queue one automatic message, unless it is a duplicate or cannot be delivered.
	 *
	 * @param array $args order, event, recipient, phone, message, file, key.
	 * @return int Log ID of the queued message, 0 when nothing was queued.
	 */
	private static function queue( array $args ) {
		$order = $args['order'];

		if ( ! SNFW_Settings::has_keys() || '' === trim( $args['message'] ) ) {
			return 0;
		}

		if ( 'customer' === $args['recipient'] ) {
			if ( ! SNFW_Optin::allows( $order ) ) {
				return 0;
			}
			$phone = SNFW_Phone::normalize( $args['phone'], $order->get_billing_country() );
			if ( '' === $phone ) {
				$args['key'] = '';
				self::log_skip(
					$args,
					'' === trim( (string) $args['phone'] )
						? __( 'The order has no phone number.', 'whatswift-woo-notifier' )
						: __( 'The phone number on the order is not a valid WhatsApp number.', 'whatswift-woo-notifier' )
				);
				return 0;
			}
			$args['phone'] = $phone;
		}

		if ( ! apply_filters( 'snfw_should_send', true, $args, $order ) ) {
			return 0;
		}

		$key = isset( $args['key'] ) ? (string) $args['key'] : '';

		// Rule 1: one message per order, event and recipient.
		if ( '' !== $key && SNFW_Logger::get_by_key( $key ) ) {
			if ( 'placed' !== $args['event'] ) {
				self::log_skip( $args, __( 'Duplicate: this order already got the message for this status.', 'whatswift-woo-notifier' ) );
			}
			return 0;
		}

		// Rule 2: never the same text to the same number twice in a short time.
		if ( SNFW_Logger::recently_sent( $args['phone'], $args['message'], self::SAME_TEXT_WINDOW ) ) {
			self::log_skip( $args, __( 'Duplicate: the same text was sent to this number in the last 10 minutes.', 'whatswift-woo-notifier' ) );
			return 0;
		}

		$log_id = SNFW_Logger::insert(
			array(
				'order_id'   => $order->get_id(),
				'event'      => $args['event'],
				'recipient'  => $args['recipient'],
				'phone'      => $args['phone'],
				'message'    => $args['message'],
				'file'       => isset( $args['file'] ) ? (string) $args['file'] : '',
				'status'     => 'queued',
				'dedupe_key' => $key,
			)
		);

		if ( ! $log_id ) {
			return 0; // Another request stored the same key a moment earlier.
		}

		self::$pending[] = $log_id;
		self::schedule( $log_id, MINUTE_IN_SECONDS ); // Safety net if this request dies before sending.

		return $log_id;
	}

	/**
	 * Record a message that was deliberately not sent.
	 *
	 * @param array  $args   Message args.
	 * @param string $reason Why.
	 */
	private static function log_skip( array $args, $reason ) {
		$clean = SNFW_Phone::normalize( $args['phone'], $args['order']->get_billing_country() );
		SNFW_Logger::insert(
			array(
				'order_id'  => $args['order']->get_id(),
				'event'     => $args['event'],
				'recipient' => $args['recipient'],
				'phone'     => $clean ? $clean : preg_replace( '/[^0-9+]/', '', (string) $args['phone'] ),
				'message'   => $args['message'],
				'status'    => 'skipped',
				'response'  => $reason,
			)
		);
	}

	/**
	 * Schedule background delivery of a log row.
	 *
	 * @param int $log_id Log ID.
	 * @param int $delay  Seconds from now.
	 */
	private static function schedule( $log_id, $delay ) {
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time() + $delay, self::ACTION, array( (int) $log_id ), self::GROUP );
		} else {
			wp_schedule_single_event( time() + $delay, self::ACTION, array( (int) $log_id ) );
		}
	}

	/**
	 * End of request: hand the response to the visitor first, then send what was queued.
	 */
	public static function flush() {
		if ( empty( self::$pending ) ) {
			return;
		}

		$can_finish = function_exists( 'fastcgi_finish_request' ) || function_exists( 'litespeed_finish_request' );
		if ( ! $can_finish && ! apply_filters( 'snfw_send_before_response_ends', true ) ) {
			return; // Left to the background queue.
		}

		ignore_user_abort( true );
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		} elseif ( function_exists( 'litespeed_finish_request' ) ) {
			litespeed_finish_request();
		}

		$ids           = self::$pending;
		self::$pending = array();
		foreach ( $ids as $log_id ) {
			self::process( $log_id );
		}
	}

	/**
	 * Deliver one queued row. Does nothing when the row was already taken.
	 *
	 * @param int $log_id Log ID.
	 * @return array|null API result, or null when there was nothing to do.
	 */
	public static function process( $log_id ) {
		$log_id = (int) $log_id;
		if ( ! SNFW_Logger::claim( $log_id ) ) {
			return null;
		}

		$row    = SNFW_Logger::get( $log_id );
		$result = SNFW_API::send( $row->phone, $row->message, $row->file );

		if ( $result['success'] ) {
			SNFW_Logger::update(
				$log_id,
				array(
					'status'   => 'sent',
					'response' => $result['detail'],
				)
			);
			self::add_order_note( $row, true, '' );
			return $result;
		}

		if ( $result['retry'] && (int) $row->attempts < self::MAX_ATTEMPTS ) {
			$delays = array( 1 => MINUTE_IN_SECONDS, 2 => 5 * MINUTE_IN_SECONDS );
			$delay  = isset( $delays[ (int) $row->attempts ] ) ? $delays[ (int) $row->attempts ] : 15 * MINUTE_IN_SECONDS;
			SNFW_Logger::update(
				$log_id,
				array(
					'status'   => 'retry',
					'response' => $result['detail'],
				)
			);
			self::schedule( $log_id, $delay );
			return $result;
		}

		SNFW_Logger::update(
			$log_id,
			array(
				'status'   => 'failed',
				'response' => $result['detail'],
			)
		);
		self::add_order_note( $row, false, $result['detail'] );

		return $result;
	}

	/**
	 * Send a message immediately (manual message, test, resend). No duplicate rules apply.
	 *
	 * @param string $phone    Raw number.
	 * @param string $message  Text.
	 * @param array  $extra    Optional: file, event, recipient, order_id.
	 * @return array success (bool), detail (string), log_id (int).
	 */
	public static function send_now( $phone, $message, array $extra = array() ) {
		$order_id = isset( $extra['order_id'] ) ? (int) $extra['order_id'] : 0;
		$order    = $order_id ? wc_get_order( $order_id ) : false;
		$number   = SNFW_Phone::normalize( $phone, $order ? $order->get_billing_country() : '' );

		if ( ! SNFW_Settings::has_keys() ) {
			return array(
				'success' => false,
				'detail'  => __( 'Add your App Key and Auth Key first.', 'whatswift-woo-notifier' ),
				'log_id'  => 0,
			);
		}
		if ( '' === $number ) {
			return array(
				'success' => false,
				'detail'  => __( 'That is not a valid WhatsApp number. Use the full number with country code.', 'whatswift-woo-notifier' ),
				'log_id'  => 0,
			);
		}
		if ( '' === trim( $message ) ) {
			return array(
				'success' => false,
				'detail'  => __( 'The message is empty.', 'whatswift-woo-notifier' ),
				'log_id'  => 0,
			);
		}

		$log_id = SNFW_Logger::insert(
			array(
				'order_id'  => $order_id,
				'event'     => isset( $extra['event'] ) ? $extra['event'] : 'manual',
				'recipient' => isset( $extra['recipient'] ) ? $extra['recipient'] : 'manual',
				'phone'     => $number,
				'message'   => $message,
				'file'      => isset( $extra['file'] ) ? (string) $extra['file'] : '',
				'status'    => 'queued',
			)
		);

		$result = self::process( $log_id );
		$row    = SNFW_Logger::get( $log_id );

		return array(
			'success' => $row && 'sent' === $row->status,
			'detail'  => $result ? $result['detail'] : '',
			'log_id'  => $log_id,
			'pending' => $row && 'retry' === $row->status,
		);
	}

	/**
	 * Add a note to the order timeline.
	 *
	 * @param object $row     Log row.
	 * @param bool   $success Delivered or not.
	 * @param string $detail  Failure reason.
	 */
	private static function add_order_note( $row, $success, $detail ) {
		if ( ! $row->order_id || ! SNFW_Settings::get( 'order_notes' ) ) {
			return;
		}
		$order = wc_get_order( (int) $row->order_id );
		if ( ! $order ) {
			return;
		}

		$who = 'seller' === $row->recipient ? __( 'store staff', 'whatswift-woo-notifier' ) : __( 'customer', 'whatswift-woo-notifier' );
		if ( $success ) {
			/* translators: 1: recipient type, 2: masked phone number, 3: event name */
			$note = sprintf( __( 'WhatsApp message sent to %1$s (%2$s): %3$s.', 'whatswift-woo-notifier' ), $who, SNFW_Phone::mask( $row->phone ), self::event_label( $row->event ) );
		} else {
			/* translators: 1: recipient type, 2: masked phone number, 3: event name, 4: failure reason */
			$note = sprintf( __( 'WhatsApp message to %1$s (%2$s) failed: %3$s. %4$s', 'whatswift-woo-notifier' ), $who, SNFW_Phone::mask( $row->phone ), self::event_label( $row->event ), $detail );
		}
		$order->add_order_note( $note );
	}

	/**
	 * Human name of an event.
	 *
	 * @param string $event Event key.
	 * @return string
	 */
	public static function event_label( $event ) {
		if ( 0 === strpos( $event, 'status:' ) ) {
			return wc_get_order_status_name( substr( $event, 7 ) );
		}
		$labels = array(
			'placed' => __( 'Order placed', 'whatswift-woo-notifier' ),
			'manual' => __( 'Manual message', 'whatswift-woo-notifier' ),
			'test'   => __( 'Test message', 'whatswift-woo-notifier' ),
			'resend' => __( 'Resent', 'whatswift-woo-notifier' ),
		);
		return isset( $labels[ $event ] ) ? $labels[ $event ] : $event;
	}

	/**
	 * Image URL of the first product in the order, when it is a type WhatsApp accepts.
	 *
	 * @param WC_Order $order Order.
	 * @return string
	 */
	private static function first_product_image( $order ) {
		foreach ( $order->get_items() as $item ) {
			$product = is_callable( array( $item, 'get_product' ) ) ? $item->get_product() : false;
			if ( $product && $product->get_image_id() ) {
				$url = wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_single' );
				if ( $url && preg_match( '/\.(jpe?g|png|webp)$/i', (string) wp_parse_url( $url, PHP_URL_PATH ) ) ) {
					return $url;
				}
			}
			break;
		}
		return '';
	}
}
