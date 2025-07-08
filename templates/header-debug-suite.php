<!DOCTYPE html>
<html <?php language_attributes(); ?> class="root-debug-suite-frontend">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html__( 'Debug Suite', 'debug-suite' ); ?></title>
	<link rel="icon" href="<?php echo esc_url( DEBUG_SUITE_PLUGIN_URL . 'assets/images/brand-logo.png' ); ?>">
	
	<?php wp_head(); ?>
</head>
<body class="debug-suite-frontend">
