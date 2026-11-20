<?php
/**
 * Help tab.
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="snfw-grid snfw-grid--two">
	<div>
		<section class="snfw-card">
			<h2><?php esc_html_e( 'Video tutorial', 'whatswift-woo-notifier' ); ?></h2>
			<div class="snfw-video">
				<iframe src="<?php echo esc_url( SNFW_TUTORIAL_URL ); ?>" title="<?php esc_attr_e( 'SwiftNotify tutorial', 'whatswift-woo-notifier' ); ?>" loading="lazy" allow="encrypted-media; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
			</div>
		</section>

		<section class="snfw-card">
			<h2><?php esc_html_e( 'Common questions', 'whatswift-woo-notifier' ); ?></h2>
			<details class="snfw-faq">
				<summary><?php esc_html_e( 'A customer says they got no message. What do I check?', 'whatswift-woo-notifier' ); ?></summary>
				<p><?php esc_html_e( 'Open the Message log and search for the order number. Each entry shows whether the message was sent, failed (with the reason from SwiftNotify) or was held back, and you can send it again from there.', 'whatswift-woo-notifier' ); ?></p>
			</details>
			<details class="snfw-faq">
				<summary><?php esc_html_e( 'How are duplicate messages prevented?', 'whatswift-woo-notifier' ); ?></summary>
				<p><?php esc_html_e( 'Each order gets the "Order placed" message once, and each status message once. Reloading the thank-you page or a payment gateway calling back twice sends nothing new. The same text is also never sent to the same number twice within 10 minutes.', 'whatswift-woo-notifier' ); ?></p>
			</details>
			<details class="snfw-faq">
				<summary><?php esc_html_e( 'What happens when SwiftNotify is unreachable?', 'whatswift-woo-notifier' ); ?></summary>
				<p><?php esc_html_e( 'The message is retried automatically after 1 minute and again after 5 minutes. If it still fails, it is marked Failed in the log and a note is added to the order.', 'whatswift-woo-notifier' ); ?></p>
			</details>
			<details class="snfw-faq">
				<summary><?php esc_html_e( 'Does sending slow down my checkout?', 'whatswift-woo-notifier' ); ?></summary>
				<p><?php esc_html_e( 'No. The customer sees the order confirmation first; messages are sent right after, in the background.', 'whatswift-woo-notifier' ); ?></p>
			</details>
		</section>
	</div>

	<div>
		<section class="snfw-card">
			<h2><?php esc_html_e( 'Need a hand?', 'whatswift-woo-notifier' ); ?></h2>
			<p><span class="dashicons dashicons-whatsapp" aria-hidden="true"></span> <a href="https://wa.me/8801303705753" target="_blank" rel="noopener noreferrer">+880 1303 705 753</a></p>
			<p><span class="dashicons dashicons-email" aria-hidden="true"></span> <a href="mailto:support@whatswift.com">support@whatswift.com</a></p>
		</section>

		<section class="snfw-card">
			<h2><?php esc_html_e( 'Order details you can insert', 'whatswift-woo-notifier' ); ?></h2>
			<dl class="snfw-reference">
				<?php foreach ( SNFW_Placeholders::catalog() as $snfw_tag => $snfw_description ) : ?>
					<dt><code><?php echo esc_html( $snfw_tag ); ?></code></dt>
					<dd><?php echo esc_html( $snfw_description ); ?></dd>
				<?php endforeach; ?>
			</dl>
		</section>
	</div>
</div>
