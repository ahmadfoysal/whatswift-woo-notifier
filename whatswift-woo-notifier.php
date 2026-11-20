<?php
/**
 * Plugin Name: SwiftNotify – Instant Order Alerts for WooCommerce
 * Plugin URI: https://whatswift.com/
 * Description: Sends WhatsApp order alerts to WooCommerce customers and store staff through the SwiftNotify (WhatSwift) API, with duplicate protection, a delivery log and automatic retries.
 * Version: 2.0.0
 * Author: Ahmad Foysal
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: whatswift-woo-notifier
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 8.0
 * WC tested up to: 11.3
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;

define( 'SNFW_VERSION', '2.0.0' );
define( 'SNFW_DB_VERSION', '2' );
define( 'SNFW_FILE', __FILE__ );
define( 'SNFW_DIR', plugin_dir_path( __FILE__ ) );
define( 'SNFW_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'SNFW_TUTORIAL_URL' ) ) {
	define( 'SNFW_TUTORIAL_URL', 'https://www.youtube-nocookie.com/embed/KBCkDqoO5og?rel=0&modestbranding=1&iv_load_policy=3' );
}

require_once SNFW_DIR . 'includes/class-snfw-settings.php';
require_once SNFW_DIR . 'includes/class-snfw-install.php';
require_once SNFW_DIR . 'includes/class-snfw-phone.php';
require_once SNFW_DIR . 'includes/class-snfw-placeholders.php';
require_once SNFW_DIR . 'includes/class-snfw-logger.php';
require_once SNFW_DIR . 'includes/class-snfw-api.php';
require_once SNFW_DIR . 'includes/class-snfw-dispatcher.php';
require_once SNFW_DIR . 'includes/class-snfw-optin.php';
require_once SNFW_DIR . 'includes/class-snfw-admin.php';
require_once SNFW_DIR . 'includes/class-snfw-order-box.php';

register_activation_hook( __FILE__, array( 'SNFW_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SNFW_Install', 'deactivate' ) );

// Declare compatibility with WooCommerce order storage (HPOS) and block checkout.
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		SNFW_Install::maybe_upgrade();

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					if ( current_user_can( 'activate_plugins' ) ) {
						echo '<div class="notice notice-warning"><p>' . esc_html__( 'SwiftNotify needs WooCommerce to be installed and active.', 'whatswift-woo-notifier' ) . '</p></div>';
					}
				}
			);
			return;
		}

		SNFW_Dispatcher::init();
		SNFW_Optin::init();

		if ( is_admin() ) {
			SNFW_Admin::init();
			SNFW_Order_Box::init();
		}
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	static function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=swift-notifier-settings' ) ) . '">' . esc_html__( 'Settings', 'whatswift-woo-notifier' ) . '</a>' );
		return $links;
	}
);
