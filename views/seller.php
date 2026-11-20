<?php
/**
 * Staff alerts tab.
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;

$snfw_placed = SNFW_Settings::get( 'seller_placed' );
$snfw_status = SNFW_Settings::get( 'seller_status' );
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-snfw-dirty>
	<input type="hidden" name="action" value="snfw_save" />
	<input type="hidden" name="tab" value="seller" />
	<input type="hidden" name="sections[]" value="seller" />
	<?php wp_nonce_field( 'snfw_save' ); ?>

	<div class="snfw-editor">
		<div class="snfw-editor-main">
			<section class="snfw-card">
				<h2><?php esc_html_e( 'Who gets the alerts', 'whatswift-woo-notifier' ); ?></h2>
				<label class="snfw-label" for="snfw-seller-numbers"><?php esc_html_e( 'Staff WhatsApp numbers', 'whatswift-woo-notifier' ); ?></label>
				<textarea id="snfw-seller-numbers" class="snfw-textarea snfw-textarea--small" name="snfw[seller_numbers]" rows="3" placeholder="8801XXXXXXXXX"><?php echo esc_textarea( SNFW_Settings::get( 'seller_numbers' ) ); ?></textarea>
				<p class="snfw-hint"><?php esc_html_e( 'One number per line, with country code. Every number gets every alert below.', 'whatswift-woo-notifier' ); ?></p>
			</section>

			<?php
			SNFW_Admin::template_card(
				array(
					'title'    => __( 'New order alert', 'whatswift-woo-notifier' ),
					'when'     => __( 'When any new order comes in', 'whatswift-woo-notifier' ),
					'name'     => 'snfw[seller_placed]',
					'enabled'  => ! empty( $snfw_placed['enabled'] ),
					'template' => $snfw_placed['template'],
					'default'  => SNFW_Settings::default_template( 'seller_placed' ),
					'open'     => true,
				)
			);

			SNFW_Admin::template_card(
				array(
					'title'    => __( 'Status change alert', 'whatswift-woo-notifier' ),
					'when'     => __( 'When an order moves to one of the statuses you tick', 'whatswift-woo-notifier' ),
					'name'     => 'snfw[seller_status]',
					'enabled'  => ! empty( $snfw_status['enabled'] ),
					'template' => $snfw_status['template'],
					'default'  => SNFW_Settings::default_template( 'seller_status' ),
					'open'     => true,
					'extra'    => static function () use ( $snfw_status ) {
						echo '<fieldset class="snfw-checks"><legend>' . esc_html__( 'Alert staff for these statuses', 'whatswift-woo-notifier' ) . '</legend>';
						foreach ( SNFW_Settings::statuses() as $slug => $label ) {
							printf(
								'<label><input type="checkbox" name="snfw[seller_status][statuses][]" value="%1$s" %2$s /> %3$s</label>',
								esc_attr( $slug ),
								checked( in_array( $slug, (array) $snfw_status['statuses'], true ), true, false ),
								esc_html( $label )
							);
						}
						echo '</fieldset>';
					},
				)
			);
			?>
		</div>
		<?php SNFW_Admin::editor_sidebar(); ?>
	</div>

	<div class="snfw-savebar">
		<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save alerts', 'whatswift-woo-notifier' ); ?></button>
	</div>
</form>
