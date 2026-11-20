=== SwiftNotify – WhatsApp Alerts for WooCommerce ===
Contributors: ahmadfoysal
Tags: woocommerce, whatsapp, order notification, message, api, sms
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send WhatsApp messages to your WooCommerce customers and to yourself when orders are placed or updated — powered by the Whatswift API.

== Description ==

**SwiftNotify** is a WhatsApp automation plugin for WooCommerce that enables real-time order alerts for customers and store owners.

It automatically sends a WhatsApp message when:
- A customer places an order
- An order status is updated (e.g., completed, cancelled)

**Key Features:**
- 🔔 WhatsApp alerts for customers on order creation
- 🔄 Status-based alerts (completed, cancelled, etc.)
- 🧾 Separate seller alert message with different template
- ✏️ Customizable message templates with smart placeholders
- ⚙️ Easy setup: just add your App Key and Auth Key from [Whatswift](https://whatswift.com)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/swift-notifier-for-woocommerce`, or install directly via the WordPress plugin dashboard.
2. Activate the plugin via the **Plugins** menu in WordPress.
3. Go to **Swift Notifier Settings** from the admin sidebar.
4. Enter your Whatswift API credentials (`App Key` and `Auth Key`).
5. Customize your WhatsApp message templates using available placeholders.

== Frequently Asked Questions ==

= Where do I get the Whatswift API credentials? =
Sign up at [https://whatswift.com](https://whatswift.com) and generate your App Key and Auth Key.

= Can I customize the WhatsApp messages? =
Yes! You can use smart placeholders such as:
- `{name}` – Customer's name  
- `{order_id}` – Order ID  
- `{product_names}` – Product names  
- `{product_ids}` – Product IDs  
- `{product_prices}` – Product prices  
- `{order_total}` – Total order amount (with shipping)  
- `{shipping_method}` – Shipping method  
- `{payment_method}` – Payment method  
- `{billing_address}` – Billing address  
- `{status}` – Current order status

= Does this plugin work with any WooCommerce store? =
Yes, it works with any store that has WooCommerce installed and active.

== Screenshots ==

1. Admin Settings Page – SwiftNotify Configuration
2. WhatsApp Message Template Example with placeholders

== Changelog ==

= 1.5 =
* Initial public release
* Customer and seller WhatsApp alert support
* Placeholder system added (e.g., product IDs, prices, shipping method, etc.)
* Logging enabled for message debugging

== Upgrade Notice ==

= 1.5 =
Recommended upgrade for dynamic placeholders and better messaging control.
