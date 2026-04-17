<?php
/**
 * Admin notice for database upgrade.
 *
 * @var string $current_version The current database version.
 * @var string $latest_version  The latest plugin version.
 *
 * @package DebugSuite\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="notice notice-warning" id="debug-suite-upgrader-notice">
	<p>
		<strong><?php esc_html_e( 'Debug Suite Data Update Required', 'debug-suite' ); ?></strong>
	</p>
	<p>
		<?php
		printf(
			/* translators: %1$s: current database version, %2$s: latest version */
			esc_html__( 'We need to update your data to version %2$s. Please click the button below to complete the update process.', 'debug-suite' ),
			'<code>' . esc_html( $current_version ) . '</code>',
			'<code>' . esc_html( $latest_version ) . '</code>'
		);
		?>
	</p>
	<p>
		<button type="button" class="button button-primary" id="debug-suite-do-upgrade-btn">
			<?php esc_html_e( 'Run the Updater', 'debug-suite' ); ?>
		</button>
		<span class="spinner" id="debug-suite-upgrade-spinner" style="display: none; float: none; margin-left: 10px;"></span>
	</p>
	<div id="debug-suite-upgrade-message" style="margin-top: 10px; display: none;"></div>
</div>

<script>
	(function ($) {
		$(function() {
			const button = $('#debug-suite-do-upgrade-btn');
			const spinner = $('#debug-suite-upgrade-spinner');
			const message = $('#debug-suite-upgrade-message');

			if (!button.length) return;

			const showMessage = (msg, isError = false) => {
				message.removeClass('notice-success notice-error')
					.addClass(`notice notice-${isError ? 'error' : 'success'} inline`)
					.text(msg)
					.show();
			};

			button.on('click', function(e) {
				e.preventDefault();
				
				button.prop('disabled', true);
				spinner.css('display', 'inline-block');
				message.hide().empty();

				$.post(
					<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
					{
						action: 'debug_suite_do_upgrade',
						nonce: <?php echo wp_json_encode( wp_create_nonce( 'debug_suite_do_upgrade' ) ); ?>
					}
				).done((response) => {
					if (response?.success) {
						showMessage(response?.data?.message || <?php echo wp_json_encode( __( 'Update completed successfully.', 'debug-suite' ) ); ?>);
						setTimeout(() => window.location.reload(), 1500);
					} else {
						showMessage(response?.data?.message || <?php echo wp_json_encode( __( 'Upgrade failed.', 'debug-suite' ) ); ?>, true);
						button.prop('disabled', false);
					}
				}).fail((jqXHR, errorThrown) => {
					showMessage(jqXHR.responseJSON?.data?.message || errorThrown || <?php echo wp_json_encode( __( 'Failed to complete update.', 'debug-suite' ) ); ?>, true);
					button.prop('disabled', false);
				}).always(() => {
					spinner.hide();
				});
			});
		});
	})(jQuery);
</script>
