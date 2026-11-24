<?php

/**
 * Plugin Name: WordPress Analytics
 * Plugin URI: https://github.com/coccoinomane/wordpress_analytics
 * Description: Let Google Analytics communicate with WordPress and track user activity beyond pageviews
 * Version: 1.7.1
 * Author: Guido W. Pettinari
 * Author URI: http://www.guidowalterpettinari.eu
 * License: GPL3
 */

// If this file is called directly, abort (security measure)
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin version
define( 'WPAN_VERSION', 'v1.5' );
define( 'WPAN_URL', 'https://github.com/coccoinomane/wordpress_analytics' );

// Define plugin directory & URL
define( 'WPAN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPAN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Define plugin's config file: a settings.yaml left in the settings folder
// (legacy format, possibly customised for this website) takes precedence
// over the bundled settings.json
define( 'WPAN_DEFAULT_CONFIG_FILE', WPAN_PLUGIN_DIR . 'settings/settings.json' );
define( 'WPAN_LEGACY_CONFIG_FILE', WPAN_PLUGIN_DIR . 'settings/settings.yaml' );
define( 'WPAN_CONFIG_FILE', is_readable( WPAN_LEGACY_CONFIG_FILE ) ? WPAN_LEGACY_CONFIG_FILE : WPAN_DEFAULT_CONFIG_FILE );

// Include utility functions
require_once WPAN_PLUGIN_DIR . 'functions.php';

// Include the settings library
require_once WPAN_PLUGIN_DIR . 'settings/settings_library.php';

// Build settings page
if ( is_admin() ) {
	require_once WPAN_PLUGIN_DIR . 'settings/settings.php';
	// Warn the administrators if the website's settings.yaml cannot be read
	add_action( 'admin_notices', 'wpan_config_fallback_notice' );
	add_action( 'network_admin_notices', 'wpan_config_fallback_notice' );
	// Warn the administrators if Google Analytics 4 is not configured properly
	add_action( 'admin_notices', 'wpan_ga4_admin_notices' );
}

// Extract plugin options from the database */
$options = wpan_get_options();

// Debug: print to debug.log the options we just fetched */
// wpan_debug( "Fetched the following plugin's options:", true );
// wpan_debug( $options, true );

// ========================
// = Client-side tracking =
// ========================

// Load content grouping function
if ( wpan_option( $options, 'content_grouping' ) ) {
	require_once WPAN_PLUGIN_DIR . 'content_grouping.php';
}

// Load the function to write the Google tag, the tracking scripts and the
// custom code
require_once WPAN_PLUGIN_DIR . 'tracking_code.php';

// Insert the code in the header. This does not depend on Google Analytics
// being configured: custom code and phone number links must always work.
add_action( 'wp_head', 'wpan_tracking_code', PHP_INT_MAX );


// ========================
// = Server-side tracking =
// ========================

// Load Gravity Forms tracking, sent to Google Analytics 4 with the
// measurement protocol. The events are sent only if form tracking is enabled.
require_once WPAN_PLUGIN_DIR . 'form_tracking.php';

if ( is_admin() ) {
	add_action( 'admin_notices', 'wpan_form_tracking_admin_notices' );
}
