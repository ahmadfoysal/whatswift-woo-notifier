<?php
/*
Plugin Name: SwiftNotify – WhatsApp Alerts for WooCommerce
Plugin URI: https://whatswift.com/
Description: Sends WhatsApp messages to WooCommerce customers and seller using the Whatswift API when an order is placed or updated.
Version: 1.5
Author: Ahmad Foysal
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires Plugins: woocommerce
*/


if (! defined('ABSPATH')) exit; // Exit if accessed directly

function snfw_debug_log($message)
{
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[SwiftNotifier] ' . $message);
    }
}

add_action('admin_menu', function () {
    add_menu_page(
        'Swift Notifier Settings',
        'Swift Notifier',
        'manage_options',
        'swift-notifier-settings',
        'snfw_settings_page',
        'dashicons-admin-generic',
        100
    );
});

function snfw_settings_page()
{
?>
    <div class="wrap">
        <h2>Swift Notifier API & Message Settings</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('snfw-settings-group');
            do_settings_sections('snfw-settings-group');
            ?>
            <table class="form-table">
                <tr>
                    <th>App Key</th>
                    <td><input type="text" name="snfw_api_key" value="<?php echo esc_attr(get_option('snfw_api_key')); ?>" /></td>
                </tr>
                <tr>
                    <th>Auth Key</th>
                    <td><input type="text" name="snfw_sender" value="<?php echo esc_attr(get_option('snfw_sender')); ?>" /></td>
                </tr>
                <tr>
                    <th>Seller Alert Number</th>
                    <td><input type="text" name="snfw_seller_number" value="<?php echo esc_attr(get_option('snfw_seller_number')); ?>" /></td>
                </tr>
                <tr>
                    <th>Seller Alert Message</th>
                    <td><textarea name="snfw_template_seller_alert" rows="2" cols="50"><?php echo esc_textarea(get_option('snfw_template_seller_alert', 'New order #{order_id} received with {product_names} totaling {order_total}')); ?></textarea></td>
                </tr>
                <tr>
                    <th>New Order Message</th>
                    <td><textarea name="snfw_template_new_order" rows="2" cols="50"><?php echo esc_textarea(get_option('snfw_template_new_order', 'Hello {name}, your order #{order_id} for {product_names} has been received.')); ?></textarea></td>
                </tr>
                <tr>
                    <th>Completed Order Message</th>
                    <td><textarea name="snfw_template_completed" rows="2" cols="50"><?php echo esc_textarea(get_option('snfw_template_completed')); ?></textarea></td>
                </tr>
                <tr>
                    <th>Cancelled Order Message</th>
                    <td><textarea name="snfw_template_cancelled" rows="2" cols="50"><?php echo esc_textarea(get_option('snfw_template_cancelled')); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <div style="background:#fff;border:1px solid #ccd0d4;padding:20px;margin-top:30px;">
            <h3>Available Placeholders for Messages</h3>
            <ul>
                <li><code>{name}</code> – Customer's name</li>
                <li><code>{order_id}</code> – Order ID</li>
                <li><code>{product_names}</code> – Product names</li>
                <li><code>{product_ids}</code> – Product IDs</li>
                <li><code>{product_prices}</code> – Product prices</li>
                <li><code>{order_total}</code> – Total order amount (with shipping)</li>
                <li><code>{shipping_method}</code> – Shipping method</li>
                <li><code>{payment_method}</code> – Payment method</li>
                <li><code>{billing_address}</code> – Billing address</li>
                <li><code>{status}</code> – Order status</li>
            </ul>
        </div>
    </div>
<?php
}

add_action('admin_init', function () {
    register_setting('snfw-settings-group', 'snfw_api_key', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('snfw-settings-group', 'snfw_sender', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('snfw-settings-group', 'snfw_seller_number', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('snfw-settings-group', 'snfw_template_seller_alert', ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('snfw-settings-group', 'snfw_template_new_order', ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('snfw-settings-group', 'snfw_template_completed', ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('snfw-settings-group', 'snfw_template_cancelled', ['sanitize_callback' => 'sanitize_textarea_field']);
});

add_action('woocommerce_thankyou', 'snfw_send_order_placed_message', 10, 1);
add_action('woocommerce_order_status_changed', 'snfw_send_order_status_changed_message', 10, 4);

function snfw_send_order_placed_message($order_id)
{
    $order = wc_get_order($order_id);
    if (!$order) return;

    $number = $order->get_billing_phone();
    $name = $order->get_billing_first_name();
    $api_key = get_option('snfw_api_key');
    $auth_key = get_option('snfw_sender');
    $template = get_option('snfw_template_new_order');

    $message = snfw_prepare_message($template, $order, $name, $order_id);
    if ($number) snfw_send_whatsapp($number, $message, $api_key, $auth_key);

    $seller_number = get_option('snfw_seller_number');
    $seller_template = get_option('snfw_template_seller_alert');
    if ($seller_number && $seller_template) {
        $seller_msg = snfw_prepare_message($seller_template, $order, $name, $order_id);
        snfw_send_whatsapp($seller_number, $seller_msg, $api_key, $auth_key);
    }
}

function snfw_send_order_status_changed_message($order_id, $old_status, $new_status, $order)
{
    if (!$order) return;

    $number = $order->get_billing_phone();
    $name = $order->get_billing_first_name();
    $api_key = get_option('snfw_api_key');
    $auth_key = get_option('snfw_sender');
    $template_key = "snfw_template_{$new_status}";
    $template = get_option($template_key);
    if (!$template) return;

    $message = snfw_prepare_message($template, $order, $name, $order_id, $new_status);
    if ($number) snfw_send_whatsapp($number, $message, $api_key, $auth_key);
}

function snfw_prepare_message($template, $order, $name, $order_id, $status = '')
{
    $product_names = $product_prices = $product_ids = [];

    foreach ($order->get_items() as $item) {
        $product_names[] = $item->get_name();
        $product_prices[] = number_format($item->get_total(), 2) . ' ৳';
        $product_ids[] = $item->get_product_id();
    }

    $replacements = [
        '{name}' => $name,
        '{order_id}' => $order_id,
        '{product_names}' => implode(', ', $product_names),
        '{product_prices}' => implode(', ', $product_prices),
        '{product_ids}' => implode(', ', $product_ids),
        '{order_total}' => number_format($order->get_total(), 2) . ' ৳',
        '{shipping_method}' => $order->get_shipping_method(),
        '{payment_method}' => $order->get_payment_method_title(),
        '{billing_address}' => trim(preg_replace('/\s+/', ' ', $order->get_formatted_billing_address())),
        '{status}' => $status
    ];

    return strtr($template, $replacements);
}

function snfw_send_whatsapp($number, $message, $appkey, $authkey)
{
    $number = snfw_format_phone_number($number);
    $url = 'https://whatswift.com/api/create-message';

    snfw_debug_log("Sending to: $number | Message: $message");

    $response = wp_remote_post($url, [
        'method'  => 'POST',
        'body'    => [
            'appkey'  => $appkey,
            'authkey' => $authkey,
            'to'      => $number,
            'message' => $message,
            'sandbox' => 'false'
        ],
        'timeout' => 30
    ]);

    if (is_wp_error($response)) {
        snfw_debug_log('cURL Error: ' . $response->get_error_message());
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $decoded = json_decode($body, true);

    if (!empty($decoded['status']) && $decoded['status'] === 'success') {
        snfw_debug_log('Success: Message sent.');
    } else {
        snfw_debug_log('API Error: ' . ($decoded['message'] ?? 'Unknown response') . ' | Raw: ' . $body);
    }
}

function snfw_format_phone_number($number)
{
    // Remove all non-digit characters except +
    $number = preg_replace('/[^0-9+]/', '', $number);

    // Replace leading 00 with nothing (e.g. 00880 → 880)
    if (str_starts_with($number, '00')) {
        $number = substr($number, 2);
    }

    // Remove leading + if any
    if (str_starts_with($number, '+')) {
        $number = substr($number, 1);
    }

    // If Bangladeshi local number (e.g. 01XXXXXXXXX)
    if (preg_match('/^01[0-9]{9}$/', $number)) {
        return '880' . substr($number, 1);
    }

    // If already in BD format (8801XXXXXXXXX)
    if (preg_match('/^8801[0-9]{9}$/', $number)) {
        return $number;
    }

    // For other countries: remove + or 00, just keep digits
    if (preg_match('/^[0-9]{10,15}$/', $number)) {
        return $number;
    }

    // Invalid format
    snfw_debug_log("Invalid number: " . $number);
    return '';
}
