<?php
/**
 * Optional "send me WhatsApp updates" checkbox at checkout.
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;

/**
 * Checkout consent, for classic and block checkout.
 */
class SNFW_Optin {

	const META        = '_snfw_optin';
	const BLOCK_FIELD = 'snfw/whatsapp-updates';

	/**
	 * Whether the block checkout checkbox was registered.
	 *
	 * @var bool
	 */
	private static $block_field_registered = false;

	/**
	 * Hooks.
	 */
	public static function init() {
		if ( ! SNFW_Settings::get( 'optin_enabled' ) ) {
			return;
		}
		add_action( 'woocommerce_review_order_before_submit', array( __CLASS__, 'render_classic' ) );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'save_classic' ), 10, 2 );
		add_action( 'woocommerce_init', array( __CLASS__, 'register_block_field' ) );
	}

	/**
	 * Classic checkout checkbox.
	 */
	public static function render_classic() {
		woocommerce_form_field(
			'snfw_optin',
			array(
				'type'  => 'checkbox',
				'class' => array( 'form-row-wide', 'snfw-optin' ),
				'label' => esc_html( SNFW_Settings::get( 'optin_label' ) ),
			),
			SNFW_Settings::get( 'optin_checked' ) ? 1 : 0
		);
	}

	/**
	 * Store the classic checkout choice on the order.
	 *
	 * @param WC_Order $order Order.
	 * @param array    $data  Posted data.
	 */
	public static function save_classic( $order, $data ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verified the checkout nonce.
		$order->update_meta_data( self::META, empty( $_POST['snfw_optin'] ) ? 'no' : 'yes' );
	}

	/**
	 * Block checkout checkbox (WooCommerce 8.9+).
	 */
	public static function register_block_field() {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}
		try {
			woocommerce_register_additional_checkout_field(
				array(
					'id'       => self::BLOCK_FIELD,
					'label'    => SNFW_Settings::get( 'optin_label' ),
					'location' => 'order',
					'type'     => 'checkbox',
				)
			);
			self::$block_field_registered = true;
		} catch ( Exception $e ) {
			unset( $e ); // Field could not be registered; block checkout orders are then treated as opted in.
		}
	}

	/**
	 * May the customer of this order be messaged?
	 *
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	public static function allows( $order ) {
		if ( ! SNFW_Settings::get( 'optin_enabled' ) ) {
			return true;
		}

		$via = $order->get_created_via();
		if ( 'checkout' === $via ) {
			return 'yes' === $order->get_meta( self::META );
		}
		if ( 'store-api' === $via && self::$block_field_registered ) {
			$value = $order->get_meta( '_wc_other/' . self::BLOCK_FIELD );
			return ! empty( $value ) && 'no' !== $value;
		}

		// Orders made by staff or other channels never saw the checkbox.
		return true;
	}
}
