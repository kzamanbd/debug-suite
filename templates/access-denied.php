<?php
/**
 * Simple access denied template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load our custom header template
include DEBUG_SUITE_PLUGIN_DIR . 'templates/header-debug-suite.php';
?>

<div class="flex items-center justify-center min-h-screen text-center bg-gray-50">
	<div class="max-w-lg p-12 mx-5 bg-white rounded-2xl shadow-xl">
		<div class="text-center mb-8">
			<img src="<?php echo esc_url( DEBUG_SUITE_PLUGIN_URL . 'assets/images/brand-logo.svg' ); ?>" 
				 alt="Debug Suite" 
				 class="h-12 mx-auto mb-5">
		</div>
		
		<h1 class="text-3xl font-semibold text-gray-900 mb-4">
			<?php esc_html_e( 'Access Denied', 'debug-suite' ); ?>
		</h1>
		
		<p class="text-gray-600 text-base leading-relaxed mb-8">
			<?php esc_html_e( 'You need administrator access to view Debug Suite. Please log in with an administrator account to continue.', 'debug-suite' ); ?>
		</p>
		
		<div class="flex gap-3 justify-center flex-wrap">
			<a href="<?php echo esc_url( wp_login_url( home_url( 'debug-suite' ) ) ); ?>" 
			   class="inline-flex items-center px-6 py-3 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-600 transition-colors">
				<?php esc_html_e( 'Login as Administrator', 'debug-suite' ); ?>
			</a>
			
			<a href="<?php echo esc_url( home_url() ); ?>" 
			   class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
				<?php esc_html_e( 'Go Home', 'debug-suite' ); ?>
			</a>
		</div>
		
		<div class="mt-8 pt-6 border-t border-gray-200 text-gray-500 text-sm">
			<?php esc_html_e( 'Debug Suite requires administrative privileges for security purposes.', 'debug-suite' ); ?>
		</div>
	</div>
</div>

<?php
// Load our custom footer template
include DEBUG_SUITE_PLUGIN_DIR . 'templates/footer-debug-suite.php';
?>
