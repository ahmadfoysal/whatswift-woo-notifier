<?php
/**
 * Settings storage, defaults and sanitising.
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;

/**
 * All plugin settings live in one option: snfw_settings.
 */
class SNFW_Settings {

	const OPTION = 'snfw_settings';

	/**
	 * Request-level cache.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Order statuses as slug => label, without the "wc-" prefix.
	 *
	 * @return array
	 */
	public static function statuses() {
		$out = array();
		if ( function_exists( 'wc_get_order_statuses' ) ) {
			foreach ( wc_get_order_statuses() as $key => $label ) {
				$out[ 'wc-' === substr( $key, 0, 3 ) ? substr( $key, 3 ) : $key ] = $label;
			}
		}
		unset( $out['checkout-draft'] );
		return $out;
	}

	/**
	 * Built-in starter templates.
	 *
	 * @param string $key Template key.
	 * @return string
	 */
	public static function default_template( $key ) {
		$templates = array(
			'placed'        => __( "Hello {name} 👋\n\nThank you for your order at *{site_name}*!\n\n🧾 Order: #{order_number}\n🛍️ Items: {product_names}\n💰 Total: {order_total}\n💳 Payment: {payment_method}\n\nWe will keep you updated here.", 'whatswift-woo-notifier' ),
			'pending'       => __( "Hello {name}, your order #{order_number} is waiting for payment ({order_total}).\n\nPay here: {payment_url}", 'whatswift-woo-notifier' ),
			'processing'    => __( "Hello {name}, good news! ✅\n\nYour order #{order_number} is confirmed and is now being prepared.", 'whatswift-woo-notifier' ),
			'on-hold'       => __( 'Hello {name}, your order #{order_number} is on hold. We will message you as soon as it moves forward.', 'whatswift-woo-notifier' ),
			'completed'     => __( "Hello {name} 🎉\n\nYour order #{order_number} is complete. Thank you for shopping with *{site_name}*!", 'whatswift-woo-notifier' ),
			'cancelled'     => __( 'Hello {name}, your order #{order_number} has been cancelled. If this is a mistake, just reply to this message.', 'whatswift-woo-notifier' ),
			'refunded'      => __( 'Hello {name}, your order #{order_number} has been refunded ({order_total}).', 'whatswift-woo-notifier' ),
			'failed'        => __( "Hello {name}, the payment for your order #{order_number} did not go through.\n\nTry again here: {payment_url}", 'whatswift-woo-notifier' ),
			'generic'       => __( 'Hello {name}, your order #{order_number} is now *{status}*.', 'whatswift-woo-notifier' ),
			'seller_placed' => __( "🔔 *New order #{order_number}*\n\n👤 {full_name} ({phone})\n{products}\n💰 {order_total} · {payment_method}\n📍 {billing_address}\n\n{admin_order_url}", 'whatswift-woo-notifier' ),
			'seller_status' => __( 'ℹ️ Order #{order_number} is now *{status}* · {full_name} · {order_total}', 'whatswift-woo-notifier' ),
		);
		return isset( $templates[ $key ] ) ? $templates[ $key ] : $templates['generic'];
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'app_key'           => '',
			'auth_key'          => '',
			'country_code'      => self::store_calling_code(),
			'customer_placed'   => array(
				'enabled'      => true,
				'template'     => self::default_template( 'placed' ),
				'attach_image' => false,
			),
			'customer_statuses' => array(),
			'seller_numbers'    => '',
			'seller_placed'     => array(
				'enabled'  => true,
				'template' => self::default_template( 'seller_placed' ),
			),
			'seller_status'     => array(
				'enabled'  => false,
				'template' => self::default_template( 'seller_status' ),
				'statuses' => array( 'cancelled', 'failed' ),
			),
			'skip_first_status' => true,
			'once_per_status'   => true,
			'order_notes'       => true,
			'optin_enabled'     => false,
			'optin_label'       => __( 'Send me order updates on WhatsApp', 'whatswift-woo-notifier' ),
			'optin_checked'     => true,
			'retention_days'    => 30,
			'delete_on_remove'  => false,
		);
	}

	/**
	 * Calling code of the store's base country, digits only.
	 *
	 * @return string
	 */
	public static function store_calling_code() {
		if ( function_exists( 'WC' ) && WC()->countries ) {
			$code = WC()->countries->get_country_calling_code( WC()->countries->get_base_country() );
			$code = is_array( $code ) ? reset( $code ) : $code;
			$code = preg_replace( '/\D/', '', (string) $code );
			if ( $code ) {
				return $code;
			}
		}
		return '880';
	}

	/**
	 * All settings merged over defaults.
	 *
	 * @return array
	 */
	public static function all() {
		if ( ! has_action( 'update_option_' . self::OPTION, array( __CLASS__, 'flush_cache' ) ) ) {
			add_action( 'update_option_' . self::OPTION, array( __CLASS__, 'flush_cache' ) );
			add_action( 'add_option_' . self::OPTION, array( __CLASS__, 'flush_cache' ) );
			add_action( 'delete_option_' . self::OPTION, array( __CLASS__, 'flush_cache' ) );
		}
		if ( null === self::$cache ) {
			$saved    = get_option( self::OPTION, array() );
			$saved    = is_array( $saved ) ? $saved : array();
			$defaults = self::defaults();
			$merged   = array_merge( $defaults, $saved );
			foreach ( array( 'customer_placed', 'seller_placed', 'seller_status' ) as $group ) {
				$merged[ $group ] = array_merge( $defaults[ $group ], is_array( $merged[ $group ] ) ? $merged[ $group ] : array() );
			}
			self::$cache = $merged;
		}
		return self::$cache;
	}

	/**
	 * Forget the cached settings (the option changed).
	 */
	public static function flush_cache() {
		self::$cache = null;
	}

	/**
	 * One setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Customer message config for one order status.
	 *
	 * @param string $slug Status slug without "wc-".
	 * @return array enabled, template.
	 */
	public static function status_config( $slug ) {
		$statuses = self::get( 'customer_statuses' );
		if ( isset( $statuses[ $slug ] ) && is_array( $statuses[ $slug ] ) ) {
			return array_merge(
				array(
					'enabled'  => false,
					'template' => '',
				),
				$statuses[ $slug ]
			);
		}
		// Not saved yet: suggest a template; only "completed" starts switched on.
		return array(
			'enabled'  => 'completed' === $slug,
			'template' => self::default_template( $slug ),
		);
	}

	/**
	 * Seller numbers as a clean list.
	 *
	 * @return string[]
	 */
	public static function seller_numbers() {
		$numbers = preg_split( '/[\s,;]+/', (string) self::get( 'seller_numbers' ), -1, PREG_SPLIT_NO_EMPTY );
		$out     = array();
		foreach ( $numbers as $number ) {
			$number = SNFW_Phone::normalize( $number );
			if ( $number ) {
				$out[] = $number;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Whether API keys are present.
	 *
	 * @return bool
	 */
	public static function has_keys() {
		return '' !== (string) self::get( 'app_key' ) && '' !== (string) self::get( 'auth_key' );
	}

	/**
	 * Merge new values into the stored settings.
	 *
	 * @param array $values Already sanitised values.
	 */
	public static function update( array $values ) {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		update_option( self::OPTION, array_merge( $saved, $values ) );
		self::$cache = null;
	}

	/**
	 * Sanitise one settings section coming from the admin form.
	 *
	 * @param string $section Section name.
	 * @param array  $input   Raw (unslashed) input.
	 * @return array Values ready for update().
	 */
	public static function sanitize_section( $section, array $input ) {
		$out  = array();
		$text = static function ( $key ) use ( $input ) {
			return isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';
		};
		$area = static function ( $value ) {
			return sanitize_textarea_field( (string) $value );
		};

		switch ( $section ) {
			case 'connection':
				$out['app_key']      = $text( 'app_key' );
				$out['auth_key']     = $text( 'auth_key' );
				$out['country_code'] = preg_replace( '/\D/', '', $text( 'country_code' ) );
				if ( '' === $out['country_code'] ) {
					$out['country_code'] = self::store_calling_code();
				}
				break;

			case 'customer':
				$placed                 = isset( $input['customer_placed'] ) && is_array( $input['customer_placed'] ) ? $input['customer_placed'] : array();
				$out['customer_placed'] = array(
					'enabled'      => ! empty( $placed['enabled'] ),
					'template'     => $area( isset( $placed['template'] ) ? $placed['template'] : '' ),
					'attach_image' => ! empty( $placed['attach_image'] ),
				);
				$out['customer_statuses'] = array();
				$posted                   = isset( $input['customer_statuses'] ) && is_array( $input['customer_statuses'] ) ? $input['customer_statuses'] : array();
				foreach ( array_keys( self::statuses() ) as $slug ) {
					$row                               = isset( $posted[ $slug ] ) && is_array( $posted[ $slug ] ) ? $posted[ $slug ] : array();
					$out['customer_statuses'][ $slug ] = array(
						'enabled'  => ! empty( $row['enabled'] ),
						'template' => $area( isset( $row['template'] ) ? $row['template'] : '' ),
					);
				}
				break;

			case 'seller':
				$out['seller_numbers'] = $area( isset( $input['seller_numbers'] ) ? $input['seller_numbers'] : '' );
				$placed                = isset( $input['seller_placed'] ) && is_array( $input['seller_placed'] ) ? $input['seller_placed'] : array();
				$status                = isset( $input['seller_status'] ) && is_array( $input['seller_status'] ) ? $input['seller_status'] : array();
				$out['seller_placed']  = array(
					'enabled'  => ! empty( $placed['enabled'] ),
					'template' => $area( isset( $placed['template'] ) ? $placed['template'] : '' ),
				);
				$chosen                = isset( $status['statuses'] ) && is_array( $status['statuses'] ) ? array_map( 'sanitize_key', $status['statuses'] ) : array();
				$out['seller_status']  = array(
					'enabled'  => ! empty( $status['enabled'] ),
					'template' => $area( isset( $status['template'] ) ? $status['template'] : '' ),
					'statuses' => array_values( array_intersect( $chosen, array_keys( self::statuses() ) ) ),
				);
				break;

			case 'options':
				foreach ( array( 'skip_first_status', 'once_per_status', 'order_notes', 'optin_enabled', 'optin_checked', 'delete_on_remove' ) as $flag ) {
					$out[ $flag ] = ! empty( $input[ $flag ] );
				}
				$out['optin_label'] = $text( 'optin_label' );
				if ( '' === $out['optin_label'] ) {
					$defaults           = self::defaults();
					$out['optin_label'] = $defaults['optin_label'];
				}
				$out['retention_days'] = max( 1, min( 365, absint( isset( $input['retention_days'] ) ? $input['retention_days'] : 30 ) ) );
				break;
		}

		return $out;
	}
}
