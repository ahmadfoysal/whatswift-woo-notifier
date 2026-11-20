# SwiftNotify – Instant Order Alerts for WooCommerce

Source repository for the WordPress.org plugin
[`whatswift-woo-notifier`](https://wordpress.org/plugins/whatswift-woo-notifier/).

## Layout

| Path | What it holds |
|---|---|
| `whatswift-woo-notifier.php` | Bootstrap: constants, HPOS / block-checkout declaration, loading |
| `includes/class-snfw-settings.php` | Settings (one option, `snfw_settings`), defaults, sanitising |
| `includes/class-snfw-install.php` | Log table, migration from 1.x, daily clean-up |
| `includes/class-snfw-dispatcher.php` | Order hooks, duplicate rules, queue, retries |
| `includes/class-snfw-logger.php` | Log table access (`{prefix}snfw_log`) |
| `includes/class-snfw-api.php` | HTTP client for `whatswift.com/api/create-message` |
| `includes/class-snfw-placeholders.php` | `{placeholder}` rendering |
| `includes/class-snfw-phone.php` | Phone number normalising |
| `includes/class-snfw-optin.php` | Optional checkout consent checkbox |
| `includes/class-snfw-admin.php`, `views/` | Admin screens |
| `includes/class-snfw-order-box.php` | WhatsApp box on the order screen |
| `.wordpress-org/` | WordPress.org banners, icon, screenshots (SVN `/assets`) |

## How a message travels

1. An order hook fires (`woocommerce_checkout_order_processed`,
   `woocommerce_store_api_checkout_order_processed`, `woocommerce_order_status_changed`).
2. The dispatcher renders the template and writes a `queued` row to the log. A unique
   `dedupe_key` (`o{order}:placed:c`, `o{order}:st:{status}:c`, …) makes a second insert
   for the same order and event impossible.
3. On `shutdown` the response is handed to the visitor, then queued rows are sent.
   An Action Scheduler job is the safety net and handles retries (1 min, 5 min).
4. A row is claimed with an atomic `UPDATE … WHERE status IN ('queued','retry')`,
   so it is never sent twice.

## Developer hooks

`snfw_should_send`, `snfw_message`, `snfw_placeholder_values`, `snfw_normalized_phone`,
`snfw_api_endpoint`, `snfw_send_before_response_ends`.

## Release

1. Bump `Version:` and `SNFW_VERSION` in the main file and `Stable tag:` in `readme.txt`; add a changelog entry.
2. Merge or push to `main`. The `Deploy to WordPress.org` workflow uploads `trunk/`, `tags/<version>/` and the listing assets from `.wordpress-org/` when that version is not on WordPress.org yet, leaving out the paths in `.distignore`. Publishing a GitHub release, or running the workflow from the Actions tab, does the same.
3. The workflow needs the repository secrets `SVN_USERNAME` and `SVN_PASSWORD`.
