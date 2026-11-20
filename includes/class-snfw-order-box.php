<?php
/**
 * WhatsApp box on the order edit screen.
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shows the messages of one order and lets staff message the customer.
 */
class SNFW_Order_Box {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_ajax_snfw_order_send', array( __CLASS__, 'ajax_send' ) );
	}

	/**
	 * Order screen IDs (new order storage and classic posts).
	 *
	 * @return string[]
	 */
	private static function screens() {
		$screens = array( 'shop_order' );
		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screens[] = wc_get_page_screen_id( 'shop-order' );
		}
		return array_unique( $screens );
	}

	/**
	 * Register the box.
	 */
	public static function register() {
		foreach ( self::screens() as $screen ) {
			add_meta_box( 'snfw-order-box', __( 'WhatsApp messages', 'whatswift-woo-notifier' ), array( __CLASS__, 'render' ), $screen, 'side', 'default' );
		}
	}

	/**
	 * Assets on order screens.
	 */
	public static function assets() {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, self::screens(), true ) ) {
			return;
		}
		wp_enqueue_style( 'snfw-admin', SNFW_URL . 'assets/css/admin.css', array(), SNFW_VERSION );
		wp_enqueue_script( 'snfw-order', SNFW_URL . 'assets/js/order.js', array(), SNFW_VERSION, true );
		wp_localize_script(
			'snfw-order',
			'snfwOrder',
			array(
				'ajax'    => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'snfw_order_send' ),
				'sending' => __( 'Sending…', 'whatswift-woo-notifier' ),
				'error'   => __( 'Could not send. Please try again.', 'whatswift-woo-notifier' ),
			)
		);
	}

	/**
	 * Box content.
	 *
	 * @param WP_Post|WC_Order $post_or_order Screen object.
	 */
	public static function render( $post_or_order ) {
		$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
		if ( ! $order ) {
			return;
		}

		list( $rows ) = SNFW_Logger::query(
			array(
				'order_id' => $order->get_id(),
				'per_page' => 8,
			)
		);
		?>
		<div class="snfw-orderbox" data-order="<?php echo esc_attr( $order->get_id() ); ?>">
			<ul class="snfw-orderbox-list" data-snfw-list>
				<?php if ( ! $rows ) : ?>
					<li class="snfw-muted" data-snfw-empty><?php esc_html_e( 'No WhatsApp messages for this order yet.', 'whatswift-woo-notifier' ); ?></li>
				<?php endif; ?>
				<?php
				foreach ( $rows as $row ) {
					echo self::row_html( $row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in row_html().
				}
				?>
			</ul>
			<p><a href="<?php echo esc_url( SNFW_Admin::url( 'log', array( 's' => $order->get_id() ) ) ); ?>"><?php esc_html_e( 'Full history', 'whatswift-woo-notifier' ); ?></a></p>

			<?php if ( ! SNFW_Settings::has_keys() ) : ?>
				<p class="snfw-muted"><?php esc_html_e( 'Add your API keys to send messages.', 'whatswift-woo-notifier' ); ?></p>
			<?php elseif ( ! $order->get_billing_phone() ) : ?>
				<p class="snfw-muted"><?php esc_html_e( 'This order has no phone number.', 'whatswift-woo-notifier' ); ?></p>
			<?php else : ?>
				<label class="screen-reader-text" for="snfw-order-message"><?php esc_html_e( 'Message to the customer', 'whatswift-woo-notifier' ); ?></label>
				<textarea id="snfw-order-message" rows="3" data-snfw-message placeholder="<?php esc_attr_e( 'Write a message to the customer…', 'whatswift-woo-notifier' ); ?>"></textarea>
				<button type="button" class="button" data-snfw-send><?php esc_html_e( 'Send on WhatsApp', 'whatswift-woo-notifier' ); ?></button>
				<p class="snfw-orderbox-result" data-snfw-result role="status"></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * One history line.
	 *
	 * @param object $row Log row.
	 * @return string
	 */
	private static function row_html( $row ) {
		return sprintf(
			'<li title="%4$s">%1$s <span>%2$s</span> <time>%3$s</time></li>',
			SNFW_Admin::badge( $row->status ),
			esc_html( SNFW_Dispatcher::event_label( $row->event ) . ( 'seller' === $row->recipient ? ' · ' . __( 'staff', 'whatswift-woo-notifier' ) : '' ) ),
			esc_html( SNFW_Admin::local_time( $row->created_at ) ),
			esc_attr( $row->response )
		);
	}

	/**
	 * Send a message to the order's customer.
	 */
	public static function ajax_send() {
		check_ajax_referer( 'snfw_order_send', 'nonce' );

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$message  = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$order    = $order_id ? wc_get_order( $order_id ) : false;

		if ( ! $order || ! current_user_can( 'edit_shop_order', $order_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'whatswift-woo-notifier' ) ), 403 );
		}

		$result = SNFW_Dispatcher::send_now(
			$order->get_billing_phone(),
			SNFW_Placeholders::render( $message, $order ),
			array(
				'order_id'  => $order_id,
				'event'     => 'manual',
				'recipient' => 'customer',
			)
		);

		$row  = $result['log_id'] ? SNFW_Logger::get( $result['log_id'] ) : null;
		$data = array(
			'message' => $result['success'] ? __( 'Sent.', 'whatswift-woo-notifier' ) : $result['detail'],
			'row'     => $row ? self::row_html( $row ) : '',
		);

		if ( $result['success'] ) {
			wp_send_json_success( $data );
		}
		wp_send_json_error( $data );
	}
}
