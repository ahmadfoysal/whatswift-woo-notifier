<?php
/**
 * Message log tab.
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only filters.
$snfw_status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
$snfw_search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$snfw_page   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
// phpcs:enable

$snfw_per_page = 25;
list( $snfw_rows, $snfw_total ) = SNFW_Logger::query(
	array(
		'status'   => $snfw_status,
		'search'   => $snfw_search,
		'page'     => $snfw_page,
		'per_page' => $snfw_per_page,
	)
);
$snfw_pages   = (int) ceil( $snfw_total / $snfw_per_page );
$snfw_filters = array(
	''        => __( 'All', 'whatswift-woo-notifier' ),
	'sent'    => __( 'Sent', 'whatswift-woo-notifier' ),
	'failed'  => __( 'Failed', 'whatswift-woo-notifier' ),
	'skipped' => __( 'Not sent', 'whatswift-woo-notifier' ),
	'retry'   => __( 'Retrying', 'whatswift-woo-notifier' ),
);
?>
<section class="snfw-card snfw-card--flush">
	<div class="snfw-log-bar">
		<div class="snfw-filter" role="group" aria-label="<?php esc_attr_e( 'Filter by result', 'whatswift-woo-notifier' ); ?>">
			<?php foreach ( $snfw_filters as $snfw_key => $snfw_label ) : ?>
				<a class="<?php echo $snfw_key === $snfw_status ? 'is-active' : ''; ?>" href="<?php echo esc_url( SNFW_Admin::url( 'log', array_filter( array( 'status' => $snfw_key, 's' => $snfw_search ) ) ) ); ?>"><?php echo esc_html( $snfw_label ); ?></a>
			<?php endforeach; ?>
		</div>
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="snfw-inline">
			<input type="hidden" name="page" value="<?php echo esc_attr( SNFW_Admin::SLUG ); ?>" />
			<input type="hidden" name="tab" value="log" />
			<?php if ( $snfw_status ) : ?>
				<input type="hidden" name="status" value="<?php echo esc_attr( $snfw_status ); ?>" />
			<?php endif; ?>
			<label class="screen-reader-text" for="snfw-log-search"><?php esc_html_e( 'Search the log', 'whatswift-woo-notifier' ); ?></label>
			<input type="search" id="snfw-log-search" name="s" class="snfw-input" value="<?php echo esc_attr( $snfw_search ); ?>" placeholder="<?php esc_attr_e( 'Order number, phone or text', 'whatswift-woo-notifier' ); ?>" />
			<button type="submit" class="button"><?php esc_html_e( 'Search', 'whatswift-woo-notifier' ); ?></button>
		</form>
	</div>

	<?php if ( ! $snfw_rows ) : ?>
		<p class="snfw-empty"><?php echo esc_html( $snfw_status || $snfw_search ? __( 'Nothing matches this filter.', 'whatswift-woo-notifier' ) : __( 'No messages yet. Every message SwiftNotify sends, retries or holds back will be listed here.', 'whatswift-woo-notifier' ) ); ?></p>
	<?php else : ?>
		<div class="snfw-table-wrap">
			<table class="snfw-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Result', 'whatswift-woo-notifier' ); ?></th>
						<th><?php esc_html_e( 'When', 'whatswift-woo-notifier' ); ?></th>
						<th><?php esc_html_e( 'Order', 'whatswift-woo-notifier' ); ?></th>
						<th><?php esc_html_e( 'To', 'whatswift-woo-notifier' ); ?></th>
						<th><?php esc_html_e( 'Message', 'whatswift-woo-notifier' ); ?></th>
						<th><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'whatswift-woo-notifier' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $snfw_rows as $snfw_row ) : ?>
						<?php $snfw_order = $snfw_row->order_id ? wc_get_order( (int) $snfw_row->order_id ) : false; ?>
						<tr>
							<td><?php echo SNFW_Admin::badge( $snfw_row->status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
							<td class="snfw-nowrap"><?php echo esc_html( SNFW_Admin::local_time( $snfw_row->created_at ) ); ?></td>
							<td class="snfw-nowrap">
								<?php if ( $snfw_order ) : ?>
									<a href="<?php echo esc_url( $snfw_order->get_edit_order_url() ); ?>">#<?php echo esc_html( $snfw_order->get_order_number() ); ?></a>
								<?php elseif ( $snfw_row->order_id ) : ?>
									#<?php echo esc_html( $snfw_row->order_id ); ?>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
							<td class="snfw-nowrap">
								<?php echo esc_html( $snfw_row->phone ? $snfw_row->phone : '—' ); ?>
								<small><?php echo esc_html( 'seller' === $snfw_row->recipient ? __( 'Staff', 'whatswift-woo-notifier' ) : ( 'customer' === $snfw_row->recipient ? __( 'Customer', 'whatswift-woo-notifier' ) : __( 'Manual', 'whatswift-woo-notifier' ) ) ); ?></small>
							</td>
							<td class="snfw-log-message">
								<details>
									<summary><strong><?php echo esc_html( SNFW_Dispatcher::event_label( $snfw_row->event ) ); ?></strong> — <?php echo esc_html( wp_html_excerpt( $snfw_row->message, 70, '…' ) ); ?></summary>
									<pre><?php echo esc_html( $snfw_row->message ); ?></pre>
									<?php if ( $snfw_row->file ) : ?>
										<p><?php esc_html_e( 'File:', 'whatswift-woo-notifier' ); ?> <a href="<?php echo esc_url( $snfw_row->file ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $snfw_row->file ); ?></a></p>
									<?php endif; ?>
								</details>
								<?php if ( $snfw_row->response ) : ?>
									<p class="snfw-log-reason"><?php echo esc_html( $snfw_row->response ); ?><?php echo (int) $snfw_row->attempts > 1 ? ' ' . esc_html( sprintf( /* translators: %d: number of attempts */ __( '(%d attempts)', 'whatswift-woo-notifier' ), (int) $snfw_row->attempts ) ) : ''; ?></p>
								<?php endif; ?>
							</td>
							<td class="snfw-nowrap">
								<?php if ( in_array( $snfw_row->status, array( 'sent', 'failed', 'skipped' ), true ) && $snfw_row->phone ) : ?>
									<a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=snfw_resend&log=' . (int) $snfw_row->id ), 'snfw_resend' ) ); ?>" data-snfw-confirm="<?php esc_attr_e( 'Send this message again now?', 'whatswift-woo-notifier' ); ?>"><?php echo esc_html( 'sent' === $snfw_row->status ? __( 'Send again', 'whatswift-woo-notifier' ) : __( 'Send now', 'whatswift-woo-notifier' ) ); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div class="snfw-log-foot">
			<span class="snfw-muted">
				<?php
				/* translators: %s: number of messages */
				echo esc_html( sprintf( _n( '%s message', '%s messages', $snfw_total, 'whatswift-woo-notifier' ), number_format_i18n( $snfw_total ) ) );
				?>
			</span>
			<?php
			if ( $snfw_pages > 1 ) {
				echo '<span class="snfw-pages">';
				echo wp_kses_post(
					paginate_links(
						array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'current'   => $snfw_page,
							'total'     => $snfw_pages,
							'prev_text' => '‹',
							'next_text' => '›',
						)
					)
				);
				echo '</span>';
			}
			?>
			<a class="snfw-danger-link" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=snfw_clear_log' ), 'snfw_clear_log' ) ); ?>" data-snfw-confirm="<?php esc_attr_e( 'Delete the whole message log? Duplicate protection for past orders is reset too.', 'whatswift-woo-notifier' ); ?>"><?php esc_html_e( 'Clear log', 'whatswift-woo-notifier' ); ?></a>
		</div>
	<?php endif; ?>
</section>
