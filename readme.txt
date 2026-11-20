=== SwiftNotify – Instant Order Alerts for WooCommerce ===
Contributors: ahmadfoysal
Tags: woocommerce, order notification, message, api, sms, order alert
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send instant messages to your WooCommerce customers and store admin when orders are placed or updated — powered by the SwiftNotify API.

== Description ==

🎥 Watch demo: https://youtu.be/JZZl-mwcAcE?si=xJ5f8WC6hhPifZpI

**SwiftNotify** is an order alert plugin for WooCommerce that enables real-time messaging for customers and store owners.

It automatically sends an alert when:
- A customer places an order
- An order status is updated (e.g., completed, cancelled)

**Key Features:**
* 🔔 Real-time customer alerts on new order creation  
* 🔄 Status-based notifications (e.g. completed, cancelled, refunded, etc.)  
* 🧾 Separate admin alert message with a custom template  
* ✏️ Customizable message templates using smart placeholders:
  `{name}`, `{order_id}`, `{product_names}`, `{order_total}`, etc.  
* 🧍‍♂️ "Custom Message" tab – manually send alerts to any number  
* 📢 "Bulk Messaging" tab (UI included, feature coming soon)  
* 🎨 Tabbed admin settings page with clean and modern UI  
* ⚙️ Easy setup with SwiftNotify App Key and Auth Key  

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/swift-notifier-for-woocommerce`, or install directly via the WordPress plugin dashboard.
2. Activate the plugin via the **Plugins** menu in WordPress.
3. Go to **Swift Notifier Settings** from the admin sidebar.
4. Enter your SwiftNotify API credentials (`App Key` and `Auth Key`).
5. Customize your message templates using available placeholders.

== Frequently Asked Questions ==

= Where do I get the API credentials? =
Sign up at [https://whatswift.com](https://whatswift.com) and generate your App Key and Auth Key.

= Can I customize the messages? =
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
2. Message Template Example with placeholders

== Changelog ==

= 1.6 =
* Added dynamic message templates for all WooCommerce order statuses  
* Added placeholder reference guide for easier message personalization  
* Introduced “Custom Message” feature to manually send alerts to any number  
* Added “Bulk Messaging” tab (UI only – feature coming soon)  
* Enhanced UI design and admin panel layout  

= 1.5 =
* Initial public release  
* Customer and admin alert support  
* Placeholder system added (e.g., product IDs, prices, shipping method, etc.)  
* Logging enabled for message debugging  

== Upgrade Notice ==

= 1.6 =
Recommended upgrade with major improvements, personalization options, and enhanced UI.
