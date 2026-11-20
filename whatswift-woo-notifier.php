<?php
/*
Plugin Name: SwiftNotify – Instant Order Alerts for WooCommerce
Plugin URI: https://whatswift.com/
Description: Sends instant order alerts to WooCommerce customers and store admin using the SwiftNotify API when an order is placed or updated.
Version: 1.8
Author: Ahmad Foysal
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 5.0
Requires PHP: 7.4
Requires Plugins: woocommerce
*/

if (!defined('ABSPATH')) exit;

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

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_swift-notifier-settings') return;
    wp_enqueue_style('snfw-admin-style', plugin_dir_url(__FILE__) . 'assets/css/snfw-admin.css');
    wp_enqueue_script('snfw-admin-script', plugin_dir_url(__FILE__) . 'assets/js/snfw-admin.js', ['jquery'], null, true);
});

add_action('admin_init', function () {
    $fields = [
        'snfw_api_key',
        'snfw_auth_key',
        'snfw_seller_number',
        'snfw_template_seller_alert',
        'snfw_template_order_placed'
    ];

    foreach ($fields as $field) {
        register_setting('snfw-settings-group', $field, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ]);
    }

    foreach (wc_get_order_statuses() as $status_key => $status_label) {
        $key = str_replace('wc-', '', $status_key);
        register_setting('snfw-settings-group', 'snfw_template_' . $key, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field'
        ]);
    }

    if (
        isset($_POST['snfw_send_custom_message']) &&
        check_admin_referer('snfw_send_custom_message_action', 'snfw_send_custom_message_nonce') &&
        current_user_can('manage_options')
    ) {
        $number = sanitize_text_field($_POST['snfw_custom_number']);
        $message = sanitize_textarea_field($_POST['snfw_custom_message']);
        $api_key = get_option('snfw_api_key');
        $auth_key = get_option('snfw_auth_key');

        if ($number && $message && $api_key && $auth_key) {
            snfw_send_whatsapp($number, $message, $api_key, $auth_key);
            add_action('admin_notices', function () {
                echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Message sent successfully!</strong></p></div>';
            });
        } else {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible"><p><strong>❌ Failed to send message. Check number, message, and API keys.</strong></p></div>';
            });
        }
    }
});

add_action('admin_notices', function () {
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
        echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Settings saved successfully!</strong></p></div>';
    }
});

function snfw_settings_page()
{
    $order_statuses = wc_get_order_statuses();
?>
    <div class="wrap">
        <h2 style="color:#25D366">SwiftNotify Settings</h2>
        <ul class="custom-tab-nav">
            <li><a href="#api-settings" class="nav-tab nav-tab-active" onclick="showTab(event, 'api-settings')">API Settings</a></li>
            <li><a href="#customer-notifications" class="nav-tab" onclick="showTab(event, 'customer-notifications')">Customer Notifications</a></li>
            <li><a href="#seller-notifications" class="nav-tab" onclick="showTab(event, 'seller-notifications')">Seller Notifications</a></li>
            <li><a href="#custom-message" class="nav-tab" onclick="showTab(event, 'custom-message')">✉️ Custom Message</a></li>
            <li><a href="#bulk-messaging" class="nav-tab" onclick="showTab(event, 'bulk-messaging')">📢 Bulk Messaging</a></li>
        </ul>
        <form method="post" action="options.php">
            <?php
            settings_fields('snfw-settings-group');
            do_settings_sections('snfw-settings-group');
            ?>
            <div id="api-settings" class="tab-content" style="display:flex;flex-wrap:wrap;gap:20px">
                <div class="card">
                    <div class="card-header">🔧 App Key</div>
                    <input type="text" name="snfw_api_key" value="<?php echo esc_attr(get_option('snfw_api_key')); ?>" class="styled-input" />
                </div>
                <div class="card">
                    <div class="card-header">🔐 Auth Key</div>
                    <input type="text" name="snfw_auth_key" value="<?php echo esc_attr(get_option('snfw_auth_key')); ?>" class="styled-input" />
                </div>
            </div>

            <div id="customer-notifications" class="tab-content" style="display:none;flex-wrap:wrap;gap:20px">
                <div style="width: 100%; background: #e6f7ed; padding: 10px 15px; border-left: 4px solid #25D366; border-radius: 5px; margin-bottom: 20px; font-size: 14px;">
                    <strong>Available placeholders:</strong> <br>
                    <code>{name}</code>, <code>{order_id}</code>, <code>{product_names}</code>, <code>{product_prices}</code>, <code>{product_ids}</code>, <code>{order_total}</code>, <code>{shipping_method}</code>, <code>{payment_method}</code>, <code>{billing_address}</code>, <code>{status}</code>
                </div>
                <div class="card">
                    <div class="card-header">📦 Order Placed Message</div>
                    <textarea name="snfw_template_order_placed" class="styled-textarea"><?php echo esc_textarea(get_option('snfw_template_order_placed')); ?></textarea>
                </div>
                <?php
                foreach ($order_statuses as $key => $label) {
                    $key = str_replace('wc-', '', $key);
                    $option_key = 'snfw_template_' . $key;
                ?>
                    <div class="card">
                        <div class="card-header">💬 <?php echo esc_html($label); ?> Message</div>
                        <textarea name="<?php echo esc_attr($option_key); ?>" class="styled-textarea"><?php echo esc_textarea(get_option($option_key)); ?></textarea>
                    </div>
                <?php
                }
                ?>
            </div>

            <div id="seller-notifications" class="tab-content" style="display:none;flex-wrap:wrap;gap:20px">
                <div class="card">
                    <div class="card-header">📱 Seller Number</div>
                    <input type="text" name="snfw_seller_number" value="<?php echo esc_attr(get_option('snfw_seller_number')); ?>" class="styled-input" />
                </div>
                <div class="card">
                    <div class="card-header">📢 Seller Notification Message</div>
                    <textarea name="snfw_template_seller_alert" class="styled-textarea"><?php echo esc_textarea(get_option('snfw_template_seller_alert')); ?></textarea>
                </div>
            </div>

            <div id="bulk-messaging" class="tab-content" style="display:none">
                <div class="card">
                    <p>Coming Soon</p>
                </div>
            </div>

            <div class="submit-row" id="snfw-settings-submit">
                <button type="submit" class="button-primary full-width-button">📀 Save Settings</button>
            </div>
        </form>


        <div id="custom-message" class="tab-content" style="display:none">
            <div class="card">
                <form method="post" action="">
                    <?php wp_nonce_field('snfw_send_custom_message_action', 'snfw_send_custom_message_nonce'); ?>
                    <input type="text" name="snfw_custom_number" class="styled-input" placeholder="Enter WhatsApp Number (e.g. 8801XXXXXXXXX)" required>
                    <textarea name="snfw_custom_message" class="styled-textarea" placeholder="Enter your message..." rows="4" required></textarea>
                    <button type="submit" name="snfw_send_custom_message" class="button-primary full-width-button" style="margin-top: 15px;">📨 Send Message</button>
                </form>
            </div>
        </div>

    </div>
<?php
}

add_action('woocommerce_thankyou', 'snfw_send_order_placed_message', 10, 1);
add_action('woocommerce_order_status_changed', 'snfw_send_order_status_changed_message', 10, 4);

function snfw_send_order_placed_message($order_id)
{
    $order = wc_get_order($order_id);
    if (!$order) return;

    $number = $order->get_billing_phone();
    $name = $order->get_billing_first_name();
    $api_key = get_option('snfw_api_key');
    $auth_key = get_option('snfw_auth_key');
    $template = get_option('snfw_template_order_placed');

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
    $auth_key = get_option('snfw_auth_key');
    $template_key = 'snfw_template_' . $new_status;
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

    return strtr($template, [
        '{name}' => $name,
        '{order_id}' => $order_id,
        '{product_names}' => implode(', ', $product_names),
        '{product_prices}' => implode(', ', $product_prices),
        '{product_ids}' => implode(', ', $product_ids),
        '{order_total}' => number_format($order->get_total(), 2) . ' ৳',
        '{shipping_method}' => $order->get_shipping_method(),
        '{payment_method}' => $order->get_payment_method_title(),
        '{billing_address}' => wp_strip_all_tags($order->get_formatted_billing_address()),
        '{status}' => $status
    ]);
}

function snfw_send_whatsapp($number, $message, $appkey, $authkey)
{
    $number = snfw_format_phone_number($number);
    $url = 'https://whatswift.com/api/create-message';

    wp_remote_post($url, [
        'headers' => [
            'Accept' => 'application/json'
        ],
        'body' => [
            'appkey' => $appkey,
            'authkey' => $authkey,
            'to' => $number,
            'message' => $message,
            'sandbox' => 'false'
        ],
        'timeout' => 30
    ]);
}

function snfw_format_phone_number($number)
{
    $number = preg_replace('/[^0-9+]/', '', $number);
    if (str_starts_with($number, '00')) $number = substr($number, 2);
    if (str_starts_with($number, '+')) $number = substr($number, 1);
    if (preg_match('/^01[0-9]{9}$/', $number)) return '880' . substr($number, 1);
    if (preg_match('/^8801[0-9]{9}$/', $number)) return $number;
    if (preg_match('/^[0-9]{10,15}$/', $number)) return $number;
    return '';
}
