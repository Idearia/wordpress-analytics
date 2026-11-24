<?php

/**
 * Collection of useful variables and utility functions for the
 * WordPress Analytics plugin.
 *
 * Created by Guido W. Pettinari on 27.02.2016.
 * Part of WordPress Analytics:
 * https://github.com/coccoinomane/wordpress_analytics
 */
/**
 * Maximum number of characters allowed for a regex input via a
 * text input field
 */
define( 'WPAN_MAX_REGEX_LENGTH', 200 );

/**
 * Wrapper to eval, using output buffering.
 *
 * This function is based on php_eval in the Drupal API. Thank you very
 * much to the Drupal developers! Here is the original documentation
 * from https://api.drupal.org/api/drupal/modules!php!php.module/function
 * /php_eval/7:
 *
 * Evaluates a string of PHP code.
 *
 * This is a wrapper around PHP's eval(). It uses output buffering to
 * capture both returned and printed text. Unlike eval(), we require
 * code to be surrounded by <?php ?> tags; in other words, we evaluate
 * the code as if it were a stand-alone PHP file.
 *
 * Using this wrapper also ensures that the PHP code which is evaluated
 * can not overwrite any variables in the calling code, unlike a regular
 * eval() call.
 *
 * @param String $code The code to be executed.
 */
function wpan_php_eval( $code ) {

	if ( empty( $code ) ) {
		return '';
	}

	ob_start();
	print eval( '?>' . $code ); // phpcs:ignore
	$output = ob_get_contents();
	ob_end_clean();

	return $output;
}


/**
 * Load the PHP client in order to send hits to Google Analytics
 * via the measurement protocol.
 *
 * In order to send hits to Google Analytics from the server, we employ Google's
 * measurement protocol (https://developers.google.com/analytics/devguides/
 * collection/protocol/v1/devguide). Rather than dealing with the raw library,
 * we use a lightweight PHP client from Racecore (https://github.com/ins0/google-
 * measurement-php-client) which we will refer to as ga-mp.
 */
function wpan_load_measurement_protocol_client() {

	if ( ! defined( 'WPAN_GAMP_LOADED' ) ) {

		define( 'WPAN_GAMP_DIR', WPAN_PLUGIN_DIR . 'vendor/ins0/google-measurement-php-client/src/' );
		define( 'WPAN_GAMP_URL', WPAN_PLUGIN_URL . 'vendor/ins0/google-measurement-php-client/src/' );
		$autoload_file = WPAN_GAMP_DIR . 'Racecore/GATracking/Autoloader.php';

		if ( file_exists( $autoload_file ) ) {

			try {

				require_once $autoload_file;
				Racecore\GATracking\Autoloader::register( WPAN_GAMP_DIR );
				define( 'WPAN_GAMP_LOADED', true );
				wpan_debug( 'Measurement protocol client loaded.' );
				return true;

			} catch ( Exception $e ) {

				wpan_debug( 'Could not load measurement protocol client; error message:' );
				wpan_debug( $e->getMessage() );

			}
		} else {

			wpan_debug( "Could not load measurement protocol client; file $autoload_file could not be found." );

		}
	}

}


/**
 * Debug function: write to WordPress level debug log (usually in
 * wp-content/debug.log) and to debug.log in the plugin's directory.
 *
 * The plugin's own debug.log file will be written if the plugin's
 * debug option is active, or if the 'force' argument is set to true.
 *
 * WordPress' debug log will be written if WP_DEBUG and WP_DEBUG_LOG are
 * both set to true (usually in wp-config.php) and if either the plugin's
 * debug option is active or the 'force' argument is set to true.
 *
 * @param String $log
 * @param Bool   $force If true, write the debug message regardless of whether
 * the debug option is set.
 */
function wpan_debug( $log, $force = false ) {

	/* Location of plugin's debug file */
	$plugin_debug_file = WPAN_PLUGIN_DIR . 'debug.log';

	// If the $force argument is true, print the debug message regardless
	// of the value of the global debug flag
	if ( $force ) {
		$write = true;
	} else {
		/* Use the plugin's debug flag to determine whether to write or not */
		$options = wpan_get_options();
		$write   = isset( $options['debug'] ) ? $options['debug'] : false;
	}

	if ( $write && defined( 'WPAN_PLUGIN_DIR' ) ) {

		/* Prepend file & line information to each line */
		$pre = preg_replace( '/.*?public_html/', '', __FILE__ ) . ':' . __LINE__ . ': ';

		/* Line to be logged */
		if ( is_array( $log ) || is_object( $log ) ) {
			$line = $pre . print_r( $log, true );
		} else {
			$line = $pre . $log;
		}

		/* Write the line to the plugin's debug log */
		file_put_contents( $plugin_debug_file, $line . PHP_EOL, FILE_APPEND );

		/* Write the line to WordPress' debug log */
		error_log( $line );

	}

}


/**
 * Write to WordPress debug.log file; courtesy of Elegant Themes.
 *
 * @param String $log
 */
function wpan_debug_wp( $log ) {

	if ( is_array( $log ) || is_object( $log ) ) {
		error_log( print_r( $log, true ) );
	} else {
		error_log( $log );
	}

}

/**
 * Notify the debug email of something that happened on the website
 *
 * @param String $message
 */
function wpan_notify_email( $message ) {

	// Get destination email address
	$options     = wpan_get_options();
	$debug_email = isset( $options['debug_email'] ) ? $options['debug_email'] : false;

	// Validate email address
	if ( ! $debug_email || ! filter_var( $debug_email, FILTER_VALIDATE_EMAIL ) ) {
		return false;
	}

	// Email subject
	$subject = 'Error in WordPress Analytics on ' . get_site_url();

	// Send email
	$sent = wp_mail( $debug_email, $subject, $message );

	return $sent;
}

/**
 * Return the blog ID of the main blog of the network (usually 1)
 */
function wpan_get_main_blog_id() {

	global $current_site;

	return $current_site->blog_id;

}


/**
 * Return true if the current blog is the main blog, false otherwise
 */
function wpan_is_main_blog() {

	return wpan_get_main_blog_id() === get_current_blog_id();

}


/**
 * Return true if WordPress analytics is running in network mode,
 * which means that the plugin options can only be modified from
 * the network's admin interface.
 */
function wpan_is_network_mode() {

	// This is the way it should be when we will learn how to use the Settings
	// API in the network admin (see long comment in settings.php)
	// $advanced_options = get_site_option ( 'wpan:advanced_settings' );
	// In the mean while we just check in the main blog options
	$advanced_options = get_blog_option( wpan_get_main_blog_id(), 'wpan:advanced_settings' );

	return is_multisite() && isset( $advanced_options['network_mode'] ) && $advanced_options['network_mode'];

}


/**
 * Return true if the given string looks like a Google Analytics 4
 * measurement ID (G-XXXXXXXXXX).
 *
 * @param mixed $value
 * @return bool
 */
function wpan_is_measurement_id( $value ) {

	return is_string( $value ) && 1 === preg_match( '/^G-[A-Z0-9]+$/', $value );

}


/**
 * Return the Google Analytics 4 measurement ID set in the plugin options,
 * or an empty string if it is missing or not valid.
 *
 * @param array|null $options Plugin options; leave empty to read them from the database.
 * @return string
 */
function wpan_get_measurement_id( $options = null ) {

	if ( null === $options ) {
		$options = wpan_get_options();
	}

	$measurement_id = isset( $options['measurement_id'] ) ? strtoupper( trim( (string) $options['measurement_id'] ) ) : '';

	return wpan_is_measurement_id( $measurement_id ) ? $measurement_id : '';

}


/**
 * Tell who is in charge of loading Google Analytics 4 on the website:
 *
 * - 'gtag': WordPress Analytics prints the Google tag (gtag.js);
 * - 'gtm':  Google Tag Manager or another plugin loads GA4, so WordPress
 *           Analytics must not print the Google tag (pageviews would be
 *           counted twice);
 * - 'none': nobody; only the features not related to GA are active.
 *
 * @param array|null $options Plugin options; leave empty to read them from the database.
 * @return string
 */
function wpan_get_load_mode( $options = null ) {

	if ( null === $options ) {
		$options = wpan_get_options();
	}

	$load_mode = isset( $options['load_mode'] ) ? $options['load_mode'] : '';

	return in_array( $load_mode, [ 'gtag', 'gtm', 'none' ], true ) ? $load_mode : 'gtag';

}


/**
 * Warn the administrators when Google Analytics 4 is not configured yet, or
 * when it risks being loaded twice.
 *
 * Should be called as a callback for the admin_notices action.
 */
function wpan_ga4_admin_notices() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// In network mode the settings can be changed only from the main blog
	if ( is_multisite() && wpan_is_network_mode() && ! wpan_is_main_blog() ) {
		return;
	}

	$options        = wpan_get_options();
	$load_mode      = wpan_get_load_mode( $options );
	$measurement_id = wpan_get_measurement_id( $options );
	$settings_url   = admin_url( 'admin.php?page=wordpress_analytics' );

	if ( 'none' !== $load_mode && ! $measurement_id ) {
		printf(
			'<div class="notice notice-warning"><p><strong>WordPress Analytics:</strong> the Google Analytics 4 measurement ID is missing, so no data is sent to Google Analytics. '
			. 'Custom code and phone number links keep working. <a href="%s">Enter the measurement ID (G-XXXXXXXXXX)</a>, '
			. 'or choose "Nobody" as who loads Google Analytics 4 to dismiss this notice.</p></div>',
			esc_url( $settings_url )
		);
	}

	// Google Tag Manager for WordPress (GTM4WP) usually loads GA4 by itself
	if ( 'gtag' === $load_mode && $measurement_id && defined( 'GTM4WP_VERSION' ) ) {
		printf(
			'<div class="notice notice-warning"><p><strong>WordPress Analytics:</strong> Google Tag Manager for WordPress is active on this website. '
			. 'If your GTM container already loads Google Analytics 4, pageviews are being counted twice: '
			. '<a href="%s">set "Who loads Google Analytics 4?" to "Google Tag Manager or another plugin"</a>.</p></div>',
			esc_url( $settings_url )
		);
	}

}
