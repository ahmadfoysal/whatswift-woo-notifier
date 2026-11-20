<?php
/**
 * Send a message tab.
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;

$snfw_has_key = SNFW_Settings::has_keys();
?>
<div class="snfw-editor">
	<div class="snfw-editor-main">
		<section class="snfw-card">
			<h2><?php esc_html_e( 'Send a WhatsApp message', 'whatswift-woo-notifier' ); ?></h2>
			<?php if ( ! $snfw_has_key ) : ?>
				<p class="snfw-state snfw-state--warn"><span class="dashicons dashicons-warning" aria-hidden="true"></span> <?php esc_html_e( 'Add your API keys in Settings before sending.', 'whatswift-woo-notifier' ); ?></p>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snfw_send" />
				<input type="hidden" name="tab" value="send" />
				<?php wp_nonce_field( 'snfw_send' ); ?>

				<label class="snfw-label" for="snfw-phone"><?php esc_html_e( 'WhatsApp number', 'whatswift-woo-notifier' ); ?></label>
				<input type="tel" id="snfw-phone" name="snfw_phone" class="snfw-input" placeholder="8801XXXXXXXXX" required />
				<p class="snfw-hint"><?php esc_html_e( 'Full number with country code.', 'whatswift-woo-notifier' ); ?></p>

				<label class="snfw-label" for="snfw-message"><?php esc_html_e( 'Message', 'whatswift-woo-notifier' ); ?></label>
				<textarea id="snfw-message" name="snfw_message" class="snfw-textarea" rows="7" required data-snfw-template data-snfw-plain></textarea>
				<div class="snfw-template-tools"><span></span><span class="snfw-count" data-snfw-count></span></div>

				<label class="snfw-label" for="snfw-file"><?php esc_html_e( 'File link (optional)', 'whatswift-woo-notifier' ); ?></label>
				<input type="url" id="snfw-file" name="snfw_file" class="snfw-input" placeholder="https://" />
				<p class="snfw-hint"><?php esc_html_e( 'A public link to an image or document: jpg, png, webp, pdf, docx, xlsx, csv or txt.', 'whatswift-woo-notifier' ); ?></p>

				<p><button type="submit" class="button button-primary button-hero" <?php disabled( ! $snfw_has_key ); ?>><?php esc_html_e( 'Send message', 'whatswift-woo-notifier' ); ?></button></p>
			</form>
		</section>
	</div>
	<aside class="snfw-side">
		<div class="snfw-phone">
			<div class="snfw-phone-bar">
				<span class="snfw-phone-avatar dashicons dashicons-store" aria-hidden="true"></span>
				<span><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			</div>
			<div class="snfw-phone-chat"><div class="snfw-bubble" data-snfw-preview></div></div>
		</div>
	</aside>
</div>
