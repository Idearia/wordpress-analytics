<?php

/**
 * Plugin Name: WordPress Analytics
 * Plugin URI: https://github.com/coccoinomane/wordpress_analytics
 * Description: Let Google Analytics communicate with WordPress and track user activity beyond pageviews
 * Version: alpha_v8
 * Author: Guido W. Pettinari
 * Author URI: http://www.guidowalterpettinari.eu
 * License: GPL3
 */

// If this file is called directly, abort (security measure)
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin version
define( 'WPAN_VERSION', 'alpha_v9' );
define( 'WPAN_URL', 'https://github.com/coccoinomane/wordpress_analytics' );

// Define plugin directory & URL
define( 'WPAN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPAN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Define plugin's config file
define( 'WPAN_CONFIG_FILE', WPAN_PLUGIN_DIR . '/settings/settings.yaml' );

// Autoload Composer packages
require __DIR__ . '/vendor/autoload.php';

// Include utility functions
require_once WPAN_PLUGIN_DIR . 'functions.php';

// Include the settings library
require_once WPAN_PLUGIN_DIR . 'settings/settings_library.php';

// Build settings page
if ( is_admin() ) {
	require_once WPAN_PLUGIN_DIR . 'settings/settings.php';
}

// Extract plugin options from the database */
$options = wpan_get_options();

// Debug: print to debug.log the options we just fetched */
// wpan_debug( "Fetched the following plugin's options:", true );
// wpan_debug( $options, true );

// Stop unless we have a valid tracking ID
if ( isset( $options ['tracking_uid'] ) && $options ['tracking_uid'] ) {

	// ========================
	// = Client-side tracking =
	// ========================

	// Load content grouping function
	if ( isset( $options ['content_grouping'] ) && $options ['content_grouping'] ) {
		require_once WPAN_PLUGIN_DIR . 'content_grouping.php';
	}

	// Load scroll tracking function
	if ( isset( $options ['scroll_tracking'] ) && $options ['scroll_tracking'] ) {
		require_once WPAN_PLUGIN_DIR . 'scroll_tracking.php';
	}

	// Load call tracking function
	if ( isset( $options ['call_tracking'] ) && $options ['call_tracking'] ) {
		require_once WPAN_PLUGIN_DIR . 'call_tracking.php';
	}

	// Load email tracking function
	if ( isset( $options ['email_tracking'] ) && $options ['email_tracking'] ) {
		require_once WPAN_PLUGIN_DIR . 'email_tracking.php';
	}

	// Load the function to write the actual complete GA tracking code
	require_once WPAN_PLUGIN_DIR . 'tracking_code.php';

	// Insert the tracking code in the header
	add_action( 'wp_head', 'wpan_tracking_code', PHP_INT_MAX );


	// ========================
	// = Server-side tracking =
	// ========================

	// Load Gravity Forms tracking
	if ( isset( $options ['form_tracking'] ) && $options ['form_tracking'] ) {
		require_once WPAN_PLUGIN_DIR . 'form_tracking.php';
	}
}
