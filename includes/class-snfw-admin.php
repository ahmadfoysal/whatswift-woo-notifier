<?php
/**
 * Admin screens.
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu, assets, form handlers and view helpers.
 */
class SNFW_Admin {

	const SLUG = 'swift-notifier-settings';
	const CAP  = 'manage_woocommerce';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_snfw_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_snfw_send', array( __CLASS__, 'handle_send' ) );
		add_action( 'admin_post_snfw_resend', array( __CLASS__, 'handle_resend' ) );
		add_action( 'admin_post_snfw_clear_log', array( __CLASS__, 'handle_clear_log' ) );
		add_action( 'admin_init', array( __CLASS__, 'privacy_text' ) );
	}

	/**
	 * Tabs: slug => label.
	 *
	 * @return array
	 */
	public static function tabs() {
		return array(
			'dashboard' => __( 'Dashboard', 'whatswift-woo-notifier' ),
			'customer'  => __( 'Customer messages', 'whatswift-woo-notifier' ),
			'seller'    => __( 'Staff alerts', 'whatswift-woo-notifier' ),
			'send'      => __( 'Send a message', 'whatswift-woo-notifier' ),
			'log'       => __( 'Message log', 'whatswift-woo-notifier' ),
			'settings'  => __( 'Settings', 'whatswift-woo-notifier' ),
			'help'      => __( 'Help', 'whatswift-woo-notifier' ),
		);
	}

	/**
	 * URL of a tab.
	 *
	 * @param string $tab  Tab slug.
	 * @param array  $args Extra query args.
	 * @return string
	 */
	public static function url( $tab = 'dashboard', array $args = array() ) {
		return add_query_arg(
			array_merge(
				array(
					'page' => self::SLUG,
					'tab'  => $tab,
				),
				$args
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Current tab.
	 *
	 * @return string
	 */
	public static function current_tab() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard';
		return array_key_exists( $tab, self::tabs() ) ? $tab : 'dashboard';
	}

	/**
	 * Menu entry.
	 */
	public static function menu() {
		add_menu_page(
			__( 'SwiftNotify', 'whatswift-woo-notifier' ),
			__( 'SwiftNotify', 'whatswift-woo-notifier' ),
			self::CAP,
			self::SLUG,
			array( __CLASS__, 'render' ),
			'dashicons-whatsapp',
			56
		);
	}

	/**
	 * Styles and scripts for our page only.
	 *
	 * @param string $hook Page hook.
	 */
	public static function assets( $hook ) {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'snfw-admin', SNFW_URL . 'assets/css/admin.css', array(), SNFW_VERSION );
		wp_enqueue_script( 'snfw-admin', SNFW_URL . 'assets/js/admin.js', array(), SNFW_VERSION, true );
		wp_localize_script(
			'snfw-admin',
			'snfwAdmin',
			array(
				'sample' => SNFW_Placeholders::sample(),
				'i18n'   => array(
					'unsaved'      => __( 'You have unsaved changes.', 'whatswift-woo-notifier' ),
					'emptyPreview' => __( 'Your message preview appears here.', 'whatswift-woo-notifier' ),
					'confirmReset' => __( 'Replace the current text with the starter template?', 'whatswift-woo-notifier' ),
					'show'         => __( 'Show', 'whatswift-woo-notifier' ),
					'hide'         => __( 'Hide', 'whatswift-woo-notifier' ),
					'chars'        => __( 'characters', 'whatswift-woo-notifier' ),
				),
			)
		);
	}

	/**
	 * Page shell.
	 */
	public static function render() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$tab = self::current_tab();
		?>
		<div class="wrap snfw-wrap">
			<h1 class="screen-reader-text"><?php esc_html_e( 'SwiftNotify', 'whatswift-woo-notifier' ); ?></h1>
			<header class="snfw-header">
				<div class="snfw-brand">
					<span class="snfw-logo dashicons dashicons-whatsapp" aria-hidden="true"></span>
					<div>
						<strong><?php esc_html_e( 'SwiftNotify', 'whatswift-woo-notifier' ); ?></strong>
						<span><?php esc_html_e( 'WhatsApp order alerts for WooCommerce', 'whatswift-woo-notifier' ); ?></span>
					</div>
				</div>
				<div class="snfw-header-meta">
					<?php if ( SNFW_Settings::has_keys() ) : ?>
						<span class="snfw-pill snfw-pill--ok"><?php esc_html_e( 'API keys added', 'whatswift-woo-notifier' ); ?></span>
					<?php else : ?>
						<a class="snfw-pill snfw-pill--warn" href="<?php echo esc_url( self::url( 'settings' ) ); ?>"><?php esc_html_e( 'Not connected — add API keys', 'whatswift-woo-notifier' ); ?></a>
					<?php endif; ?>
					<span class="snfw-version">v<?php echo esc_html( SNFW_VERSION ); ?></span>
				</div>
			</header>

			<nav class="snfw-tabs" aria-label="<?php esc_attr_e( 'SwiftNotify sections', 'whatswift-woo-notifier' ); ?>">
				<?php foreach ( self::tabs() as $slug => $label ) : ?>
					<a href="<?php echo esc_url( self::url( $slug ) ); ?>" class="snfw-tab<?php echo $slug === $tab ? ' is-active' : ''; ?>"<?php echo $slug === $tab ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<?php self::render_notice(); ?>

			<main class="snfw-main">
				<?php require SNFW_DIR . 'views/' . $tab . '.php'; ?>
			</main>
		</div>
		<?php
	}

	/**
	 * Store a one-time notice for the current user.
	 *
	 * @param string $type success|error|info.
	 * @param string $text Message.
	 */
	public static function notice( $type, $text ) {
		set_transient(
			'snfw_notice_' . get_current_user_id(),
			array(
				'type' => $type,
				'text' => $text,
			),
			MINUTE_IN_SECONDS
		);
	}

	/**
	 * Print and clear the stored notice.
	 */
	private static function render_notice() {
		$key    = 'snfw_notice_' . get_current_user_id();
		$notice = get_transient( $key );
		if ( ! is_array( $notice ) ) {
			return;
		}
		delete_transient( $key );
		printf(
			'<div class="snfw-notice snfw-notice--%1$s" role="status">%2$s</div>',
			esc_attr( $notice['type'] ),
			esc_html( $notice['text'] )
		);
	}

	/**
	 * Stop users who may not manage the store.
	 */
	private static function require_cap() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'whatswift-woo-notifier' ), 403 );
		}
	}

	/**
	 * Redirect back to a tab and stop.
	 *
	 * @param string $tab  Tab.
	 * @param array  $args Extra args.
	 */
	private static function back( $tab, array $args = array() ) {
		wp_safe_redirect( self::url( $tab, $args ) );
		exit;
	}

	/**
	 * Save settings.
	 */
	public static function handle_save() {
		check_admin_referer( 'snfw_save' );
		self::require_cap();

		$tab      = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'settings';
		$sections = isset( $_POST['sections'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['sections'] ) ) : array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised per field in SNFW_Settings::sanitize_section().
		$input  = isset( $_POST['snfw'] ) && is_array( $_POST['snfw'] ) ? wp_unslash( $_POST['snfw'] ) : array();
		$values = array();

		foreach ( $sections as $section ) {
			$values = array_merge( $values, SNFW_Settings::sanitize_section( $section, $input ) );
		}
		if ( $values ) {
			SNFW_Settings::update( $values );
			self::notice( 'success', __( 'Saved.', 'whatswift-woo-notifier' ) );
		}

		self::back( array_key_exists( $tab, self::tabs() ) ? $tab : 'settings' );
	}

	/**
	 * Send a manual or test message and report the real result.
	 */
	public static function handle_send() {
		check_admin_referer( 'snfw_send' );
		self::require_cap();

		$tab     = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'send';
		$phone   = isset( $_POST['snfw_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['snfw_phone'] ) ) : '';
		$message = isset( $_POST['snfw_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['snfw_message'] ) ) : '';
		$file    = isset( $_POST['snfw_file'] ) ? esc_url_raw( wp_unslash( $_POST['snfw_file'] ) ) : '';
		$is_test = ! empty( $_POST['snfw_test'] );

		if ( $is_test && '' === $message ) {
			/* translators: %s: store name */
			$message = sprintf( __( '✅ SwiftNotify is connected to %s. Order alerts will be sent from this WhatsApp number.', 'whatswift-woo-notifier' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
		}

		$result = SNFW_Dispatcher::send_now(
			$phone,
			$message,
			array(
				'file'  => $file,
				'event' => $is_test ? 'test' : 'manual',
			)
		);

		if ( $result['success'] ) {
			update_option( 'snfw_test_ok', time(), false );
			/* translators: %s: phone number */
			self::notice( 'success', sprintf( __( 'Message sent to %s.', 'whatswift-woo-notifier' ), SNFW_Phone::normalize( $phone ) ) );
		} elseif ( ! empty( $result['pending'] ) ) {
			/* translators: %s: reason */
			self::notice( 'info', sprintf( __( 'Not delivered yet — it will be retried automatically. %s', 'whatswift-woo-notifier' ), $result['detail'] ) );
		} else {
			/* translators: %s: reason */
			self::notice( 'error', sprintf( __( 'Message not sent. %s', 'whatswift-woo-notifier' ), $result['detail'] ) );
		}

		self::back( array_key_exists( $tab, self::tabs() ) ? $tab : 'send' );
	}

	/**
	 * Resend a logged message.
	 */
	public static function handle_resend() {
		check_admin_referer( 'snfw_resend' );
		self::require_cap();

		$row = SNFW_Logger::get( isset( $_GET['log'] ) ? absint( $_GET['log'] ) : 0 );
		if ( ! $row ) {
			self::notice( 'error', __( 'That log entry no longer exists.', 'whatswift-woo-notifier' ) );
			self::back( 'log' );
		}

		$result = SNFW_Dispatcher::send_now(
			'+' . ltrim( $row->phone, '+' ),
			$row->message,
			array(
				'file'      => $row->file,
				'event'     => 'resend',
				'recipient' => $row->recipient,
				'order_id'  => (int) $row->order_id,
			)
		);

		if ( $result['success'] ) {
			self::notice( 'success', __( 'Message resent.', 'whatswift-woo-notifier' ) );
		} else {
			/* translators: %s: reason */
			self::notice( 'error', sprintf( __( 'Resend failed. %s', 'whatswift-woo-notifier' ), $result['detail'] ) );
		}
		self::back( 'log' );
	}

	/**
	 * Empty the log.
	 */
	public static function handle_clear_log() {
		check_admin_referer( 'snfw_clear_log' );
		self::require_cap();
		SNFW_Logger::clear();
		self::notice( 'success', __( 'Message log cleared.', 'whatswift-woo-notifier' ) );
		self::back( 'log' );
	}

	/**
	 * Suggested privacy policy text.
	 */
	public static function privacy_text() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content(
				'SwiftNotify',
				wp_kses_post( '<p>' . __( 'When you place an order, we send your phone number and the text of the order notification to SwiftNotify (whatswift.com) so that it can be delivered to you on WhatsApp. A copy of each notification is kept in our store records for a limited time.', 'whatswift-woo-notifier' ) . '</p>' )
			);
		}
	}

	/* ---------- View helpers ---------- */

	/**
	 * Status badge.
	 *
	 * @param string $status Log status.
	 * @return string HTML.
	 */
	public static function badge( $status ) {
		$labels = array(
			'sent'    => __( 'Sent', 'whatswift-woo-notifier' ),
			'failed'  => __( 'Failed', 'whatswift-woo-notifier' ),
			'skipped' => __( 'Not sent', 'whatswift-woo-notifier' ),
			'queued'  => __( 'Waiting', 'whatswift-woo-notifier' ),
			'retry'   => __( 'Retrying', 'whatswift-woo-notifier' ),
			'sending' => __( 'Sending', 'whatswift-woo-notifier' ),
		);
		$label  = isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
		return '<span class="snfw-badge snfw-badge--' . esc_attr( $status ) . '">' . esc_html( $label ) . '</span>';
	}

	/**
	 * Local time of a UTC log timestamp.
	 *
	 * @param string $utc MySQL datetime in UTC.
	 * @return string
	 */
	public static function local_time( $utc ) {
		$time = strtotime( $utc . ' UTC' );
		return $time ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $time ) : '';
	}

	/**
	 * A switch (styled checkbox).
	 *
	 * @param string $name    Field name.
	 * @param bool   $checked State.
	 * @param string $label   Accessible label.
	 * @param bool   $visible Show the label next to the switch.
	 */
	public static function toggle( $name, $checked, $label, $visible = false ) {
		?>
		<label class="snfw-switch">
			<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $checked ); ?> />
			<span class="snfw-switch-track" aria-hidden="true"></span>
			<span class="<?php echo $visible ? 'snfw-switch-label' : 'screen-reader-text'; ?>"><?php echo esc_html( $label ); ?></span>
		</label>
		<?php
	}

	/**
	 * One message template editor card.
	 *
	 * @param array $args title, when, name (field prefix), enabled, template, default, open, extra (callable).
	 */
	public static function template_card( array $args ) {
		$id = 'snfw-t-' . md5( $args['name'] );
		?>
		<section class="snfw-card snfw-template<?php echo ! empty( $args['open'] ) ? ' is-open' : ''; ?><?php echo $args['enabled'] ? '' : ' is-off'; ?>">
			<header class="snfw-template-head">
				<?php
				/* translators: %s: message name */
				self::toggle( $args['name'] . '[enabled]', $args['enabled'], sprintf( __( 'Send "%s" message', 'whatswift-woo-notifier' ), $args['title'] ) );
				?>
				<button type="button" class="snfw-template-toggle" aria-expanded="<?php echo ! empty( $args['open'] ) ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $id ); ?>">
					<span class="snfw-template-title"><?php echo esc_html( $args['title'] ); ?></span>
					<span class="snfw-template-when"><?php echo esc_html( $args['when'] ); ?></span>
					<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
				</button>
			</header>
			<div class="snfw-template-body" id="<?php echo esc_attr( $id ); ?>">
				<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>-text"><?php echo esc_html( $args['title'] ); ?></label>
				<textarea class="snfw-textarea" id="<?php echo esc_attr( $id ); ?>-text" name="<?php echo esc_attr( $args['name'] ); ?>[template]" rows="7" data-snfw-template data-default="<?php echo esc_attr( $args['default'] ); ?>"><?php echo esc_textarea( $args['template'] ); ?></textarea>
				<div class="snfw-template-tools">
					<button type="button" class="button-link" data-snfw-reset><?php esc_html_e( 'Use starter template', 'whatswift-woo-notifier' ); ?></button>
					<span class="snfw-count" data-snfw-count></span>
				</div>
				<?php
				if ( ! empty( $args['extra'] ) && is_callable( $args['extra'] ) ) {
					call_user_func( $args['extra'] );
				}
				?>
			</div>
		</section>
		<?php
	}

	/**
	 * Sidebar with live preview and placeholder chips.
	 */
	public static function editor_sidebar() {
		?>
		<aside class="snfw-side">
			<div class="snfw-phone" aria-label="<?php esc_attr_e( 'Message preview', 'whatswift-woo-notifier' ); ?>">
				<div class="snfw-phone-bar">
					<span class="snfw-phone-avatar dashicons dashicons-store" aria-hidden="true"></span>
					<span><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
				</div>
				<div class="snfw-phone-chat">
					<div class="snfw-bubble" data-snfw-preview></div>
				</div>
			</div>
			<p class="snfw-hint"><?php esc_html_e( 'Preview with sample order data. Use *bold*, _italic_ and ~strikethrough~ like in WhatsApp.', 'whatswift-woo-notifier' ); ?></p>

			<div class="snfw-card snfw-chips-card">
				<h3><?php esc_html_e( 'Insert order details', 'whatswift-woo-notifier' ); ?></h3>
				<p class="snfw-hint"><?php esc_html_e( 'Click to add at the cursor.', 'whatswift-woo-notifier' ); ?></p>
				<div class="snfw-chips">
					<?php foreach ( SNFW_Placeholders::catalog() as $tag => $description ) : ?>
						<button type="button" class="snfw-chip" data-snfw-chip="<?php echo esc_attr( $tag ); ?>" title="<?php echo esc_attr( $description ); ?>"><?php echo esc_html( $tag ); ?></button>
					<?php endforeach; ?>
				</div>
			</div>
		</aside>
		<?php
	}
}
