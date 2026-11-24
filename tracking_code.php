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
	$content_grouping = is_single() && wpan_option( $options, 'content_grouping' );
	$debug            = wpan_option( $options, 'debug' );
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

	// Function used by the tracking scripts below to send their events: with
	// gtag() if it is there, otherwise, if Google Analytics 4 is loaded by
	// Google Tag Manager, as a dataLayer event that the container can pick up
	if ( wpan_option( $options, 'scroll_tracking' ) || wpan_option( $options, 'call_tracking' ) || wpan_option( $options, 'email_tracking' ) ) {
		?>
<script>
	window.wpanSendEvent = function (name, params) {
		params = params || {};
		params.page_path = window.location.pathname;
		if (typeof window.gtag === 'function')
			window.gtag('event', name, params);
<?php if ( 'gtm' === $load_mode ) : ?>
		else {
			params.event = name;
			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push(params);
		}
<?php endif; ?>
	};
</script>
		<?php
	}

	// Scroll tracking script to track reading behaviour
	if ( is_single() && wpan_option( $options, 'scroll_tracking' ) ) {
		wpan_tracking_script(
			'scroll_tracking',
			[
				'timeThreshold'  => wpan_option( $options, 'time_threshold' ),
				'pixelThreshold' => wpan_option( $options, 'pixel_threshold' ),
				'debug'          => $debug,
			]
		);
	}

	// Call tracking script to track clicks on phone numbers, and to turn
	// phone numbers into links
	if ( wpan_option( $options, 'call_tracking' ) ) {
		// Prevent mobile browsers from detecting phone numbers
		if ( wpan_option( $options, 'detect_phone_numbers' ) ) {
			echo "<meta name='format-detection' content='telephone=no'>\n";
		}
		wpan_tracking_script(
			'call_tracking',
			[
				'regexIncludePattern' => wpan_option( $options, 'phone_regex_include_pattern' ),
				'regexExcludePattern' => wpan_option( $options, 'phone_regex_exclude_pattern' ),
				'detectPhoneNumbers'  => wpan_option( $options, 'detect_phone_numbers' ),
				'debug'               => $debug,
			]
		);
	}

	// Email tracking script to track clicks on email links
	if ( wpan_option( $options, 'email_tracking' ) ) {
		wpan_tracking_script( 'email_tracking', [ 'debug' => $debug ] );
	}

	// Execute code specified by the user
	if ( wpan_option( $options, 'custom_code' ) ) {
		// Custom code written for Universal Analytics might still call the old
		// tracker (ex. ga('set', ...)), which does not exist anymore: define it
		// as an empty function, lest a Javascript error stops the rest of the
		// user's script (ex. a Facebook Pixel in the same script tag)
		$legacy_tracker = wpan_option( $options, 'tracker_name' ) ? $options['tracker_name'] : 'ga';
		if ( preg_match( '/^[A-Za-z_$][A-Za-z0-9_$]*$/', $legacy_tracker ) && preg_match( '/' . preg_quote( $legacy_tracker, '/' ) . '\s*\(/', $options ['custom_code'] ) ) {
			echo "<script> window['$legacy_tracker'] = window['$legacy_tracker'] || function(){}; </script>\n"; // phpcs:ignore
		}
		echo wpan_php_eval( $options ['custom_code'] ); // phpcs:ignore
	}

	// Configure the Google Analytics 4 property; this also sends the pageview
	// (unless the user turned it off), together with the content group of the
	// page (if any). This should be at the end of the script.
	if ( $load_gtag ) {
		$config = $content_grouping ? wpan_content_grouping() : [];
		if ( ! wpan_option( $options, 'send_page_view' ) ) {
			$config['send_page_view'] = false;
		}
		echo '<script> gtag(\'config\', ' . wp_json_encode( $measurement_id ) . ( $config ? ', ' . wp_json_encode( $config ) : '' ) . "); </script>\n"; // phpcs:ignore
	}

	echo '<!-- END: Tracking code inserted by WordPress Analytics ' . WPAN_VERSION . ' - ' . WPAN_URL . '-->' . PHP_EOL . PHP_EOL; // phpcs:ignore

}


/**
 * Print the tag of one of the tracking scripts in the js folder.
 *
 * The modification time of the file is added to the URL, so that browsers
 * reload the script when it changes rather than using the cached version.
 *
 * @param string $name       Name of the script, without extension.
 * @param array  $attributes Settings for the script, passed as attributes
 *                           of its tag.
 */
function wpan_tracking_script( $name, $attributes ) {

	$src = WPAN_PLUGIN_URL . "js/$name.js?ver=" . filemtime( WPAN_PLUGIN_DIR . "js/$name.js" );

	echo "<script src='$src' "; // phpcs:ignore
	foreach ( $attributes as $attribute => $value ) {
		echo "$attribute='$value' "; // phpcs:ignore
	}
	echo "defer='defer'> </script>\n";

}
