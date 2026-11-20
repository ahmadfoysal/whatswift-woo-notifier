<?php
/**
 * Dashboard tab.
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;

$snfw_today   = SNFW_Logger::counts_since( ( new DateTime( 'today', wp_timezone() ) )->getTimestamp() );
$snfw_week    = SNFW_Logger::counts_since( time() - WEEK_IN_SECONDS );
$snfw_last    = SNFW_Logger::last_attempt();
$snfw_has_key = SNFW_Settings::has_keys();
list( $snfw_recent ) = SNFW_Logger::query( array( 'per_page' => 6 ) );

$snfw_placed   = SNFW_Settings::get( 'customer_placed' );
$snfw_any_on   = ! empty( $snfw_placed['enabled'] );
foreach ( array_keys( SNFW_Settings::statuses() ) as $snfw_slug ) {
	$snfw_cfg    = SNFW_Settings::status_config( $snfw_slug );
	$snfw_any_on = $snfw_any_on || ! empty( $snfw_cfg['enabled'] );
}
$snfw_steps = array(
	array( $snfw_has_key, __( 'Add your App Key and Auth Key', 'whatswift-woo-notifier' ), SNFW_Admin::url( 'settings' ) ),
	array( (bool) get_option( 'snfw_test_ok' ), __( 'Send yourself a test message', 'whatswift-woo-notifier' ), '#snfw-test' ),
	array( $snfw_any_on, __( 'Choose which customer messages to send', 'whatswift-woo-notifier' ), SNFW_Admin::url( 'customer' ) ),
	array( (bool) SNFW_Settings::seller_numbers(), __( 'Add a staff number for new-order alerts', 'whatswift-woo-notifier' ), SNFW_Admin::url( 'seller' ) ),
);
$snfw_done  = count( array_filter( wp_list_pluck( $snfw_steps, 0 ) ) );
?>
<div class="snfw-grid snfw-grid--stats">
	<div class="snfw-card snfw-stat">
		<span class="snfw-stat-label"><?php esc_html_e( 'Sent today', 'whatswift-woo-notifier' ); ?></span>
		<strong class="snfw-stat-value"><?php echo esc_html( number_format_i18n( $snfw_today['sent'] ) ); ?></strong>
	</div>
	<div class="snfw-card snfw-stat">
		<span class="snfw-stat-label"><?php esc_html_e( 'Sent in the last 7 days', 'whatswift-woo-notifier' ); ?></span>
		<strong class="snfw-stat-value"><?php echo esc_html( number_format_i18n( $snfw_week['sent'] ) ); ?></strong>
	</div>
	<div class="snfw-card snfw-stat<?php echo $snfw_week['failed'] ? ' snfw-stat--bad' : ''; ?>">
		<span class="snfw-stat-label"><?php esc_html_e( 'Failed in the last 7 days', 'whatswift-woo-notifier' ); ?></span>
		<strong class="snfw-stat-value"><?php echo esc_html( number_format_i18n( $snfw_week['failed'] ) ); ?></strong>
		<?php if ( $snfw_week['failed'] ) : ?>
			<a href="<?php echo esc_url( SNFW_Admin::url( 'log', array( 'status' => 'failed' ) ) ); ?>"><?php esc_html_e( 'See what failed', 'whatswift-woo-notifier' ); ?></a>
		<?php endif; ?>
	</div>
	<div class="snfw-card snfw-stat">
		<span class="snfw-stat-label"><?php esc_html_e( 'Duplicates and held back (7 days)', 'whatswift-woo-notifier' ); ?></span>
		<strong class="snfw-stat-value"><?php echo esc_html( number_format_i18n( $snfw_week['skipped'] ) ); ?></strong>
	</div>
</div>

<div class="snfw-grid snfw-grid--two">
	<div>
		<?php if ( $snfw_done < count( $snfw_steps ) ) : ?>
		<section class="snfw-card">
			<h2><?php esc_html_e( 'Get set up', 'whatswift-woo-notifier' ); ?> <span class="snfw-muted"><?php echo esc_html( $snfw_done . ' / ' . count( $snfw_steps ) ); ?></span></h2>
			<ol class="snfw-steps">
				<?php foreach ( $snfw_steps as $snfw_step ) : ?>
					<li class="<?php echo $snfw_step[0] ? 'is-done' : ''; ?>">
						<span class="dashicons <?php echo $snfw_step[0] ? 'dashicons-yes-alt' : 'dashicons-marker'; ?>" aria-hidden="true"></span>
						<?php if ( $snfw_step[0] ) : ?>
							<span><?php echo esc_html( $snfw_step[1] ); ?></span>
						<?php else : ?>
							<a href="<?php echo esc_url( $snfw_step[2] ); ?>"><?php echo esc_html( $snfw_step[1] ); ?></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</section>
		<?php endif; ?>

		<section class="snfw-card">
			<h2><?php esc_html_e( 'Recent messages', 'whatswift-woo-notifier' ); ?></h2>
			<?php if ( ! $snfw_recent ) : ?>
				<p class="snfw-empty"><?php esc_html_e( 'No messages yet. They will show up here as orders come in.', 'whatswift-woo-notifier' ); ?></p>
			<?php else : ?>
				<ul class="snfw-recent">
					<?php foreach ( $snfw_recent as $snfw_row ) : ?>
						<li>
							<?php echo SNFW_Admin::badge( $snfw_row->status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in badge(). ?>
							<span class="snfw-recent-main">
								<strong><?php echo esc_html( SNFW_Dispatcher::event_label( $snfw_row->event ) ); ?></strong>
								<?php if ( $snfw_row->order_id ) : ?>
									· #<?php echo esc_html( $snfw_row->order_id ); ?>
								<?php endif; ?>
								· <?php echo esc_html( $snfw_row->phone ); ?>
							</span>
							<time><?php echo esc_html( SNFW_Admin::local_time( $snfw_row->created_at ) ); ?></time>
						</li>
					<?php endforeach; ?>
				</ul>
				<p><a href="<?php echo esc_url( SNFW_Admin::url( 'log' ) ); ?>"><?php esc_html_e( 'Open the full log', 'whatswift-woo-notifier' ); ?> →</a></p>
			<?php endif; ?>
		</section>
	</div>

	<div>
		<section class="snfw-card">
			<h2><?php esc_html_e( 'Connection', 'whatswift-woo-notifier' ); ?></h2>
			<?php if ( ! $snfw_has_key ) : ?>
				<p class="snfw-state snfw-state--warn"><span class="dashicons dashicons-warning" aria-hidden="true"></span> <?php esc_html_e( 'No API keys yet. Nothing can be sent.', 'whatswift-woo-notifier' ); ?></p>
				<p><a class="button button-primary" href="<?php echo esc_url( SNFW_Admin::url( 'settings' ) ); ?>"><?php esc_html_e( 'Add API keys', 'whatswift-woo-notifier' ); ?></a></p>
			<?php elseif ( ! $snfw_last ) : ?>
				<p class="snfw-state"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span> <?php esc_html_e( 'Keys are saved. Send a test message to check they work.', 'whatswift-woo-notifier' ); ?></p>
			<?php elseif ( 'sent' === $snfw_last->status ) : ?>
				<p class="snfw-state snfw-state--ok"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
					<?php
					/* translators: %s: date and time */
					echo esc_html( sprintf( __( 'Working. Last message delivered %s.', 'whatswift-woo-notifier' ), SNFW_Admin::local_time( $snfw_last->updated_at ) ) );
					?>
				</p>
			<?php else : ?>
				<p class="snfw-state snfw-state--bad"><span class="dashicons dashicons-dismiss" aria-hidden="true"></span>
					<?php
					/* translators: %s: date and time */
					echo esc_html( sprintf( __( 'The last message failed (%s).', 'whatswift-woo-notifier' ), SNFW_Admin::local_time( $snfw_last->updated_at ) ) );
					?>
				</p>
				<p class="snfw-muted"><?php echo esc_html( $snfw_last->response ); ?></p>
			<?php endif; ?>
		</section>

		<section class="snfw-card" id="snfw-test">
			<h2><?php esc_html_e( 'Send a test message', 'whatswift-woo-notifier' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snfw_send" />
				<input type="hidden" name="tab" value="dashboard" />
				<input type="hidden" name="snfw_test" value="1" />
				<?php wp_nonce_field( 'snfw_send' ); ?>
				<label class="snfw-label" for="snfw-test-phone"><?php esc_html_e( 'Your WhatsApp number', 'whatswift-woo-notifier' ); ?></label>
				<div class="snfw-inline">
					<input type="tel" id="snfw-test-phone" name="snfw_phone" class="snfw-input" placeholder="8801XXXXXXXXX" required <?php disabled( ! $snfw_has_key ); ?> />
					<button type="submit" class="button button-primary" <?php disabled( ! $snfw_has_key ); ?>><?php esc_html_e( 'Send test', 'whatswift-woo-notifier' ); ?></button>
				</div>
				<p class="snfw-hint"><?php esc_html_e( 'You will see the real answer from SwiftNotify, not just "sent".', 'whatswift-woo-notifier' ); ?></p>
			</form>
		</section>
	</div>
</div>
