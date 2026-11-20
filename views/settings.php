<?php
/**
 * Settings tab.
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;

$snfw = SNFW_Settings::all();
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="snfw-narrow" data-snfw-dirty>
	<input type="hidden" name="action" value="snfw_save" />
	<input type="hidden" name="tab" value="settings" />
	<input type="hidden" name="sections[]" value="connection" />
	<input type="hidden" name="sections[]" value="options" />
	<?php wp_nonce_field( 'snfw_save' ); ?>

	<section class="snfw-card">
		<h2><?php esc_html_e( 'SwiftNotify connection', 'whatswift-woo-notifier' ); ?></h2>
		<p class="snfw-hint">
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: link to whatswift.com */
					__( 'Find both keys in your %s account, under your connected WhatsApp app.', 'whatswift-woo-notifier' ),
					'<a href="https://whatswift.com/" target="_blank" rel="noopener noreferrer">whatswift.com</a>'
				),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
						'rel'    => array(),
					),
				)
			);
			?>
		</p>

		<label class="snfw-label" for="snfw-app-key"><?php esc_html_e( 'App Key', 'whatswift-woo-notifier' ); ?></label>
		<input type="text" id="snfw-app-key" name="snfw[app_key]" class="snfw-input" value="<?php echo esc_attr( $snfw['app_key'] ); ?>" autocomplete="off" spellcheck="false" />

		<label class="snfw-label" for="snfw-auth-key"><?php esc_html_e( 'Auth Key', 'whatswift-woo-notifier' ); ?></label>
		<div class="snfw-inline">
			<input type="password" id="snfw-auth-key" name="snfw[auth_key]" class="snfw-input" value="<?php echo esc_attr( $snfw['auth_key'] ); ?>" autocomplete="new-password" spellcheck="false" />
			<button type="button" class="button" data-snfw-reveal="snfw-auth-key"><?php esc_html_e( 'Show', 'whatswift-woo-notifier' ); ?></button>
		</div>

		<label class="snfw-label" for="snfw-cc"><?php esc_html_e( 'Default country code', 'whatswift-woo-notifier' ); ?></label>
		<div class="snfw-inline snfw-inline--start">
			<span class="snfw-prefix">+</span>
			<input type="text" id="snfw-cc" name="snfw[country_code]" class="snfw-input snfw-input--short" value="<?php echo esc_attr( $snfw['country_code'] ); ?>" inputmode="numeric" />
		</div>
		<p class="snfw-hint"><?php esc_html_e( 'Added to numbers typed without a country code (for example 017… becomes 88017…). The billing country of the order is used first when it is known.', 'whatswift-woo-notifier' ); ?></p>
	</section>

	<section class="snfw-card">
		<h2><?php esc_html_e( 'Duplicate protection', 'whatswift-woo-notifier' ); ?></h2>
		<div class="snfw-option-row">
			<?php SNFW_Admin::toggle( 'snfw[once_per_status]', $snfw['once_per_status'], __( 'Send each status message only once per order', 'whatswift-woo-notifier' ), true ); ?>
			<p class="snfw-hint"><?php esc_html_e( 'If an order goes back to a status it already had, the customer is not messaged again.', 'whatswift-woo-notifier' ); ?></p>
		</div>
		<div class="snfw-option-row">
			<?php SNFW_Admin::toggle( 'snfw[skip_first_status]', $snfw['skip_first_status'], __( 'One message for a new order, not two', 'whatswift-woo-notifier' ), true ); ?>
			<p class="snfw-hint"><?php esc_html_e( 'A new order usually becomes Pending, On hold or Processing in the same moment. That status message is held back when the "Order placed" message has just been sent.', 'whatswift-woo-notifier' ); ?></p>
		</div>
		<p class="snfw-hint"><?php esc_html_e( 'Always on: the "Order placed" message is sent once per order, and the same text is never sent to the same number twice within 10 minutes.', 'whatswift-woo-notifier' ); ?></p>
	</section>

	<section class="snfw-card">
		<h2><?php esc_html_e( 'Customer consent', 'whatswift-woo-notifier' ); ?></h2>
		<div class="snfw-option-row">
			<?php SNFW_Admin::toggle( 'snfw[optin_enabled]', $snfw['optin_enabled'], __( 'Ask customers at checkout before messaging them', 'whatswift-woo-notifier' ), true ); ?>
			<p class="snfw-hint"><?php esc_html_e( 'Adds a checkbox to the checkout. Customers who leave it unticked get no WhatsApp messages.', 'whatswift-woo-notifier' ); ?></p>
		</div>
		<label class="snfw-label" for="snfw-optin-label"><?php esc_html_e( 'Checkbox text', 'whatswift-woo-notifier' ); ?></label>
		<input type="text" id="snfw-optin-label" name="snfw[optin_label]" class="snfw-input" value="<?php echo esc_attr( $snfw['optin_label'] ); ?>" />
		<div class="snfw-option-row">
			<?php SNFW_Admin::toggle( 'snfw[optin_checked]', $snfw['optin_checked'], __( 'Ticked by default (classic checkout)', 'whatswift-woo-notifier' ), true ); ?>
		</div>
	</section>

	<section class="snfw-card">
		<h2><?php esc_html_e( 'Records', 'whatswift-woo-notifier' ); ?></h2>
		<div class="snfw-option-row">
			<?php SNFW_Admin::toggle( 'snfw[order_notes]', $snfw['order_notes'], __( 'Add a note to the order when a message is sent or fails', 'whatswift-woo-notifier' ), true ); ?>
		</div>
		<label class="snfw-label" for="snfw-retention"><?php esc_html_e( 'Keep the message log for', 'whatswift-woo-notifier' ); ?></label>
		<div class="snfw-inline snfw-inline--start">
			<input type="number" id="snfw-retention" name="snfw[retention_days]" class="snfw-input snfw-input--short" min="1" max="365" value="<?php echo esc_attr( $snfw['retention_days'] ); ?>" />
			<span><?php esc_html_e( 'days', 'whatswift-woo-notifier' ); ?></span>
		</div>
		<div class="snfw-option-row">
			<?php SNFW_Admin::toggle( 'snfw[delete_on_remove]', $snfw['delete_on_remove'], __( 'Delete all SwiftNotify settings and logs when the plugin is deleted', 'whatswift-woo-notifier' ), true ); ?>
		</div>
	</section>

	<div class="snfw-savebar">
		<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save settings', 'whatswift-woo-notifier' ); ?></button>
	</div>
</form>
