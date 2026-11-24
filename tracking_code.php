<?php
/**
 * Insert in the <head> of all pages the Google tag for Google Analytics 4
 * (if WordPress Analytics is in charge of loading it), the tracking scripts
 * and the custom code specified by the user.
 *
 * Anything echoed by this function will be written in the head of all pages.
 *
 * Custom code and call/email tracking scripts are printed regardless of
 * Google Analytics being configured: websites rely on them for things that
 * have nothing to do with GA (ex. Facebook Pixel, phone number links).
 *
 * Created by Guido W. Pettinari on 23.12.2015.
 * Part of WordPress Analytics:
 * https://github.com/coccoinomane/wordpress_analytics
 */
function wpan_tracking_code() {

	// Extract the plugin options from the database
	$options          = wpan_get_options();
	$measurement_id   = wpan_get_measurement_id( $options );
	$load_mode        = wpan_get_load_mode( $options );
	$content_grouping = is_single() && isset( $options ['content_grouping'] ) && $options ['content_grouping'];
	$call_tracking    = isset( $options ['call_tracking'] ) && $options ['call_tracking'];
	$email_tracking   = isset( $options ['email_tracking'] ) && $options ['email_tracking'];
	$custom_code      = isset( $options ['custom_code'] ) && $options ['custom_code'];
	$load_gtag        = 'gtag' === $load_mode && $measurement_id;

	echo PHP_EOL . PHP_EOL . '<!-- BEGIN: Tracking code inserted by WordPress Analytics ' . WPAN_VERSION . ' - ' . WPAN_URL . '-->' . PHP_EOL; // phpcs:ignore

	// Load the Google tag (gtag.js). The 'config' command, which sends the
	// pageview, is issued at the end, after the custom code.
	if ( $load_gtag ) {
		?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $measurement_id ); ?>"></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());
</script>
		<?php
	} elseif ( 'gtag' === $load_mode ) {
		echo '<!-- Google tag not loaded: empty measurement ID -->' . PHP_EOL;
	} elseif ( 'gtm' === $load_mode ) {
		echo '<!-- Google tag not loaded: Google Analytics 4 is loaded by Google Tag Manager or by another plugin -->' . PHP_EOL;
	}

	// Call tracking script to track clicks on phone numbers, and to turn
	// phone numbers into links
	if ( $call_tracking ) {
		wpan_call_tracking();
	}

	// Email tracking script to track clicks on email links
	if ( $email_tracking ) {
		wpan_email_tracking();
	}

	// Execute code specified by the user
	if ( $custom_code ) {
		// Custom code written for Universal Analytics might still call the old
		// tracker (ex. ga('set', ...)), which does not exist anymore: define it
		// as an empty function, lest a Javascript error stops the rest of the
		// user's script (ex. a Facebook Pixel in the same script tag)
		$legacy_tracker = isset( $options ['tracker_name'] ) && $options ['tracker_name'] ? $options ['tracker_name'] : 'ga';
		if ( preg_match( '/^[A-Za-z_$][A-Za-z0-9_$]*$/', $legacy_tracker ) && false !== strpos( $options ['custom_code'], $legacy_tracker . '(' ) ) {
			echo "<script> window['$legacy_tracker'] = window['$legacy_tracker'] || function(){}; </script>\n"; // phpcs:ignore
		}
		echo wpan_php_eval( $options ['custom_code'] ); // phpcs:ignore
	}

	// Configure the Google Analytics 4 property; this also sends the pageview,
	// together with the content group of the page (if any). This should be at
	// the end of the script.
	if ( $load_gtag ) {
		$config = $content_grouping ? wpan_content_grouping() : [];
		echo '<script> gtag(\'config\', ' . wp_json_encode( $measurement_id ) . ( $config ? ', ' . wp_json_encode( $config ) : '' ) . "); </script>\n"; // phpcs:ignore
	}

	echo '<!-- END: Tracking code inserted by WordPress Analytics ' . WPAN_VERSION . ' - ' . WPAN_URL . '-->' . PHP_EOL . PHP_EOL; // phpcs:ignore

}
