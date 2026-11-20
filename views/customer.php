<?php
/**
 * Customer messages tab.
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;

$snfw_placed = SNFW_Settings::get( 'customer_placed' );
$snfw_when   = array(
	'pending'    => __( 'Order is waiting for payment', 'whatswift-woo-notifier' ),
	'processing' => __( 'Payment received / order confirmed', 'whatswift-woo-notifier' ),
	'on-hold'    => __( 'Order is put on hold', 'whatswift-woo-notifier' ),
	'completed'  => __( 'Order is marked complete', 'whatswift-woo-notifier' ),
	'cancelled'  => __( 'Order is cancelled', 'whatswift-woo-notifier' ),
	'refunded'   => __( 'Order is refunded', 'whatswift-woo-notifier' ),
	'failed'     => __( 'Payment failed', 'whatswift-woo-notifier' ),
);
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-snfw-dirty>
	<input type="hidden" name="action" value="snfw_save" />
	<input type="hidden" name="tab" value="customer" />
	<input type="hidden" name="sections[]" value="customer" />
	<?php wp_nonce_field( 'snfw_save' ); ?>

	<div class="snfw-editor">
		<div class="snfw-editor-main">
			<p class="snfw-lead"><?php esc_html_e( 'Switch on the messages you want customers to get, and write them in your own words.', 'whatswift-woo-notifier' ); ?></p>

			<?php
			SNFW_Admin::template_card(
				array(
					'title'    => __( 'Order placed', 'whatswift-woo-notifier' ),
					'when'     => __( 'Right after a customer places an order', 'whatswift-woo-notifier' ),
					'name'     => 'snfw[customer_placed]',
					'enabled'  => ! empty( $snfw_placed['enabled'] ),
					'template' => $snfw_placed['template'],
					'default'  => SNFW_Settings::default_template( 'placed' ),
					'open'     => true,
					'extra'    => static function () use ( $snfw_placed ) {
						echo '<div class="snfw-option-row">';
						SNFW_Admin::toggle( 'snfw[customer_placed][attach_image]', ! empty( $snfw_placed['attach_image'] ), __( 'Attach the photo of the first product', 'whatswift-woo-notifier' ), true );
						echo '</div>';
					},
				)
			);

			foreach ( SNFW_Settings::statuses() as $snfw_slug => $snfw_label ) {
				$snfw_cfg = SNFW_Settings::status_config( $snfw_slug );
				SNFW_Admin::template_card(
					array(
						'title'    => $snfw_label,
						/* translators: %s: order status name */
						'when'     => isset( $snfw_when[ $snfw_slug ] ) ? $snfw_when[ $snfw_slug ] : sprintf( __( 'Order status changes to %s', 'whatswift-woo-notifier' ), $snfw_label ),
						'name'     => 'snfw[customer_statuses][' . $snfw_slug . ']',
						'enabled'  => ! empty( $snfw_cfg['enabled'] ),
						'template' => $snfw_cfg['template'],
						'default'  => SNFW_Settings::default_template( $snfw_slug ),
					)
				);
			}
			?>
		</div>
		<?php SNFW_Admin::editor_sidebar(); ?>
	</div>

	<div class="snfw-savebar">
		<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save messages', 'whatswift-woo-notifier' ); ?></button>
	</div>
</form>
