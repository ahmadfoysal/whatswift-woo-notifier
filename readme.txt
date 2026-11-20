=== SwiftNotify – Instant Order Alerts for WooCommerce ===
Contributors: ahmadfoysal
Tags: woocommerce, whatsapp, order notification, order alerts, notifications
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WhatsApp order alerts for WooCommerce customers and store staff, with duplicate protection, a delivery log and automatic retries.

== Description ==

**SwiftNotify** sends WhatsApp messages through the SwiftNotify (WhatSwift) API when an order is placed or its status changes.

= What it does =

* **Customer messages** – an "Order placed" message plus one message for every order status you switch on (processing, completed, cancelled, refunded, custom statuses…).
* **Staff alerts** – new-order alerts to one or more staff numbers, and optional alerts for the statuses you choose.
* **Duplicate protection** – each order gets each message once. Reloading the thank-you page, a payment gateway calling back twice or an order returning to an old status sends nothing new. A new order produces one message, not two.
* **Message log** – every message with its result: sent, failed (with the reason returned by SwiftNotify), retrying or held back. Search by order number or phone and send again in one click.
* **Automatic retries** – if SwiftNotify cannot be reached, the message is retried in the background.
* **No slower checkout** – the customer sees the order confirmation first; messages go out right after.
* **Template editor** – live WhatsApp-style preview, click-to-insert order details, starter templates, bold/italic formatting.
* **Order screen box** – see the WhatsApp history of an order and message the customer from the order page.
* **Send a message** – send a one-off message (with an optional image or document link) to any number.
* **International numbers** – local numbers get the country code of the order's billing country, or your default.
* **Optional checkout consent** – a "Send me order updates on WhatsApp" checkbox for classic and block checkout.
* Works with WooCommerce High-Performance Order Storage (HPOS) and the block checkout.

= Order details you can use in messages =

`{name}`, `{full_name}`, `{order_number}`, `{order_total}`, `{status}`, `{product_names}`, `{products}`, `{product_prices}`, `{product_ids}`, `{payment_method}`, `{shipping_method}`, `{billing_address}`, `{shipping_address}`, `{phone}`, `{email}`, `{customer_note}`, `{order_date}`, `{order_url}`, `{payment_url}`, `{admin_order_url}`, `{site_name}`, `{site_url}`

== Installation ==

1. Install the plugin from the WordPress plugin screen, or upload the `whatswift-woo-notifier` folder to `/wp-content/plugins/`.
2. Activate it. WooCommerce must be active.
3. Open **SwiftNotify → Settings** and enter your App Key and Auth Key from whatswift.com.
4. On the Dashboard, send yourself a test message.
5. Choose and edit your messages under **Customer messages** and **Staff alerts**.

== Frequently Asked Questions ==

= Where do I get the API keys? =
Sign up at [whatswift.com](https://whatswift.com), connect your WhatsApp number and copy the App Key and Auth Key.

= A customer says they did not get a message. What do I check? =
Open **SwiftNotify → Message log** and search for the order number. The log shows whether the message was sent, failed (and why) or was held back, and lets you send it again.

= How are duplicate messages prevented? =
Each order gets the "Order placed" message once and each status message once. The same text is also never sent to the same number twice within 10 minutes. Messages that were held back are listed in the log with the reason.

= I am updating from 1.x. Do I need to set everything up again? =
No. Your keys, staff number and message texts are carried over automatically. Status messages that had text stay switched on.

= Does it work with the block checkout and HPOS? =
Yes, both are supported.

= Is my data removed when I delete the plugin? =
Only if you switch that on under Settings → Records.

== Screenshots ==

1. Dashboard with delivery numbers, connection state and test message
2. Message editor with live WhatsApp preview
3. Message log with results, reasons and resend
4. Settings: connection, duplicate protection, consent

== External Services ==

This plugin connects to the SwiftNotify API (whatswift.com) to deliver WhatsApp messages. It is required for the plugin to work.

* What is sent: the recipient's phone number, the message text, an optional file link, and your App Key and Auth Key.
* When: when an order is placed, when an order status changes and a message for it is switched on, and when you send a test or manual message.
* Provider: SwiftNotify – [Terms of Service](https://whatswift.com/page/terms-and-conditions), [Privacy Policy](https://whatswift.com/page/privacy-policy-for-whatswift)

The Help tab embeds a tutorial video from YouTube (youtube-nocookie.com). It loads only when a store administrator opens that tab. [YouTube Terms](https://www.youtube.com/t/terms), [Google Privacy Policy](https://policies.google.com/privacy)

== Changelog ==

= 2.0.0 =
Complete rewrite.

* New: duplicate protection – one "Order placed" message and one message per status for each order.
* New: message log with results, reasons, search and resend.
* New: automatic retries when SwiftNotify cannot be reached; real API results instead of always "sent".
* New: messages are sent after the page is delivered, so checkout is not slowed down.
* New: dashboard, redesigned settings, template editor with live preview and click-to-insert order details.
* New: per-status on/off switches and starter templates.
* New: several staff numbers, staff alerts for chosen statuses.
* New: WhatsApp box on the order screen, order notes for sent and failed messages.
* New: optional product photo on the "Order placed" message; file link on manual messages.
* New: default country code and billing-country detection for phone numbers.
* New: optional checkout consent checkbox.
* New: more order details for templates (order date, links, addresses, customer note and more).
* New: HPOS and block checkout support; orders created by staff now trigger messages too.
* Fixed: prices now use the store currency instead of a fixed ৳ sign.
* Fixed: no messages to invalid phone numbers; no errors when WooCommerce is inactive.
* Changed: requires WordPress 6.2, WooCommerce 8.0 and PHP 7.4.
* Settings from 1.x are migrated automatically.

= 1.9 =
* Added: Tutorial tab with embedded YouTube (privacy-friendly youtube-nocookie)
* UI: Settings page help section (WhatsApp contact)

= 1.8 =
* Fixed: Security improvements (nonce + capability checks)
* Fixed: Output escaping and sanitization
* Fixed: JavaScript and CSS loading using wp_enqueue
* Added: External service documentation in readme

= 1.6 =
* Added dynamic message templates for all WooCommerce order statuses
* Added placeholder reference guide
* Introduced "Custom Message" feature

= 1.5 =
* Initial public release

== Upgrade Notice ==

= 2.0.0 =
Major update: duplicate protection, message log, retries and a new interface. Your existing settings are kept.
