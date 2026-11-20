# SwiftNotify – Instant Order Alerts for WooCommerce

Source repository for the WordPress.org plugin
[`whatswift-woo-notifier`](https://wordpress.org/plugins/whatswift-woo-notifier/).

## Layout
- `whatswift-woo-notifier.php`, `readme.txt`, `assets/`: the plugin as shipped.
- `.wordpress-org/`: WordPress.org listing banners, icon and screenshots (SVN `/assets`).

## History
Releases 1.5–1.9 were imported from the WordPress.org SVN `tags/` directory;
each release is a commit with a matching git tag.

## Release
1. Bump `Version:` in the plugin header and `Stable tag:` in `readme.txt`, add a changelog entry.
2. Tag the release (`git tag -a X.Y`) and push.
3. Deploy to WordPress.org SVN (`trunk/` + `tags/X.Y/`), excluding the paths in `.distignore`.
