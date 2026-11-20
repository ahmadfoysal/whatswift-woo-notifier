<?php
/**
 * Template placeholders.
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;

/**
 * Replaces {placeholders} in a template with order data.
 */
class SNFW_Placeholders {

	/**
	 * Placeholder => description, for the editor.
	 *
	 * @return array
	 */
	public static function catalog() {
		return array(
			'{name}'             => __( 'Customer first name', 'whatswift-woo-notifier' ),
			'{full_name}'        => __( 'Customer full name', 'whatswift-woo-notifier' ),
			'{order_number}'     => __( 'Order number', 'whatswift-woo-notifier' ),
			'{order_total}'      => __( 'Order total with currency', 'whatswift-woo-notifier' ),
			'{status}'           => __( 'Order status', 'whatswift-woo-notifier' ),
			'{product_names}'    => __( 'Product names, comma separated', 'whatswift-woo-notifier' ),
			'{products}'         => __( 'Product list, one per line with quantity', 'whatswift-woo-notifier' ),
			'{product_prices}'   => __( 'Line totals', 'whatswift-woo-notifier' ),
			'{product_ids}'      => __( 'Product IDs', 'whatswift-woo-notifier' ),
			'{payment_method}'   => __( 'Payment method', 'whatswift-woo-notifier' ),
			'{shipping_method}'  => __( 'Shipping method', 'whatswift-woo-notifier' ),
			'{billing_address}'  => __( 'Billing address', 'whatswift-woo-notifier' ),
			'{shipping_address}' => __( 'Shipping address', 'whatswift-woo-notifier' ),
			'{phone}'            => __( 'Customer phone', 'whatswift-woo-notifier' ),
			'{email}'            => __( 'Customer email', 'whatswift-woo-notifier' ),
			'{customer_note}'    => __( 'Note the customer left at checkout', 'whatswift-woo-notifier' ),
			'{order_date}'       => __( 'Order date', 'whatswift-woo-notifier' ),
			'{order_url}'        => __( 'Link for the customer to view the order', 'whatswift-woo-notifier' ),
			'{payment_url}'      => __( 'Link to pay for the order', 'whatswift-woo-notifier' ),
			'{admin_order_url}'  => __( 'Link to the order in the dashboard (for staff)', 'whatswift-woo-notifier' ),
			'{site_name}'        => __( 'Store name', 'whatswift-woo-notifier' ),
			'{site_url}'         => __( 'Store address', 'whatswift-woo-notifier' ),
		);
	}

	/**
	 * Sample values for the live preview.
	 *
	 * @return array
	 */
	public static function sample() {
		$price = static function ( $amount ) {
			return self::plain_price( $amount, get_woocommerce_currency() );
		};
		return array(
			'{name}'             => 'Rahim',
			'{first_name}'       => 'Rahim',
			'{last_name}'        => 'Uddin',
			'{full_name}'        => 'Rahim Uddin',
			'{order_id}'         => '1042',
			'{order_number}'     => '1042',
			'{order_total}'      => $price( 2450 ),
			'{status}'           => __( 'Processing', 'whatswift-woo-notifier' ),
			'{product_names}'    => 'Cotton Panjabi, Leather Wallet',
			'{products}'         => "• Cotton Panjabi × 1\n• Leather Wallet × 2",
			'{product_prices}'   => $price( 1650 ) . ', ' . $price( 800 ),
			'{product_ids}'      => '87, 112',
			'{payment_method}'   => __( 'Cash on delivery', 'whatswift-woo-notifier' ),
			'{shipping_method}'  => __( 'Home delivery', 'whatswift-woo-notifier' ),
			'{billing_address}'  => 'House 12, Road 5, Dhanmondi, Dhaka 1205',
			'{shipping_address}' => 'House 12, Road 5, Dhanmondi, Dhaka 1205',
			'{phone}'            => '8801712345678',
			'{email}'            => 'rahim@example.com',
			'{customer_note}'    => __( 'Please call before delivery.', 'whatswift-woo-notifier' ),
			'{order_date}'       => date_i18n( get_option( 'date_format' ) ),
			'{order_url}'        => home_url( '/my-account/view-order/1042/' ),
			'{payment_url}'      => home_url( '/checkout/order-pay/1042/' ),
			'{admin_order_url}'  => admin_url( 'admin.php?page=wc-orders&action=edit&id=1042' ),
			'{site_name}'        => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'{site_url}'         => home_url( '/' ),
		);
	}

	/**
	 * Render a template for an order.
	 *
	 * @param string   $template Template text.
	 * @param WC_Order $order    Order.
	 * @param string   $status   Status slug to show for {status}; defaults to the order's current status.
	 * @return string
	 */
	public static function render( $template, $order, $status = '' ) {
		$currency = $order->get_currency();
		$names    = array();
		$prices   = array();
		$ids      = array();
		$lines    = array();

		foreach ( $order->get_items() as $item ) {
			$names[]  = $item->get_name();
			$prices[] = self::plain_price( $item->get_total(), $currency );
			$ids[]    = $item->get_product_id();
			$lines[]  = '• ' . $item->get_name() . ' × ' . $item->get_quantity();
		}

		$status = $status ? $status : $order->get_status();
		$date   = $order->get_date_created();

		$values = array(
			'{name}'             => $order->get_billing_first_name(),
			'{first_name}'       => $order->get_billing_first_name(),
			'{last_name}'        => $order->get_billing_last_name(),
			'{full_name}'        => trim( $order->get_formatted_billing_full_name() ),
			'{order_id}'         => $order->get_order_number(),
			'{order_number}'     => $order->get_order_number(),
			'{order_total}'      => self::plain_price( $order->get_total(), $currency ),
			'{status}'           => wc_get_order_status_name( $status ),
			'{product_names}'    => implode( ', ', $names ),
			'{products}'         => implode( "\n", $lines ),
			'{product_prices}'   => implode( ', ', $prices ),
			'{product_ids}'      => implode( ', ', $ids ),
			'{payment_method}'   => $order->get_payment_method_title(),
			'{shipping_method}'  => $order->get_shipping_method(),
			'{billing_address}'  => self::plain_address( $order->get_formatted_billing_address() ),
			'{shipping_address}' => self::plain_address( $order->get_formatted_shipping_address() ? $order->get_formatted_shipping_address() : $order->get_formatted_billing_address() ),
			'{phone}'            => $order->get_billing_phone(),
			'{email}'            => $order->get_billing_email(),
			'{customer_note}'    => $order->get_customer_note(),
			'{order_date}'       => $date ? wc_format_datetime( $date ) : '',
			'{order_url}'        => $order->get_view_order_url(),
			'{payment_url}'      => $order->get_checkout_payment_url(),
			'{admin_order_url}'  => $order->get_edit_order_url(),
			'{site_name}'        => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'{site_url}'         => home_url( '/' ),
		);

		$values  = apply_filters( 'snfw_placeholder_values', $values, $order, $status );
		$message = strtr( (string) $template, array_map( 'strval', $values ) );
		$message = wp_specialchars_decode( $message, ENT_QUOTES );
		$message = trim( preg_replace( "/\n{3,}/", "\n\n", str_replace( "\r", '', $message ) ) );

		return (string) apply_filters( 'snfw_message', $message, $template, $order, $status );
	}

	/**
	 * Price as plain text in the given currency, e.g. "৳ 1,200.00".
	 *
	 * @param float  $amount   Amount.
	 * @param string $currency Currency code.
	 * @return string
	 */
	public static function plain_price( $amount, $currency ) {
		$price = wp_strip_all_tags( wc_price( (float) $amount, array( 'currency' => $currency ) ) );
		$price = html_entity_decode( $price, ENT_QUOTES, 'UTF-8' );
		return trim( str_replace( "\xC2\xA0", ' ', $price ) );
	}

	/**
	 * Formatted address (HTML with <br>) as one plain line.
	 *
	 * @param string $address Address HTML.
	 * @return string
	 */
	private static function plain_address( $address ) {
		$address = preg_replace( '/<br\s*\/?>/i', ', ', (string) $address );
		return trim( html_entity_decode( wp_strip_all_tags( $address ), ENT_QUOTES, 'UTF-8' ) );
	}
}
