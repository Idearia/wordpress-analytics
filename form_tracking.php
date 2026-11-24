<?php

/**
 * Track Gravity Forms submissions in Google Analytics 4 from the server, with
 * the Measurement Protocol: ad blockers cannot stop it.
 *
 * Part of WordPress Analytics:
 * https://github.com/coccoinomane/wordpress_analytics
 */

/** Name of the event sent to Google Analytics 4 when a form is submitted */
define( 'WPAN_GA4_FORM_EVENT', 'generate_lead' );

// The Gravity Forms actions are fired only if Gravity Forms is active
add_action( 'gform_after_submission', 'wpan_send_form_submitted', 10, 2 );
add_action( 'gform_paypal_fulfillment', 'wpan_send_payment_done', 10, 4 );


/**
 * API secret of the GA4 property; the WPAN_GA4_API_SECRET constant takes precedence over the plugin option.
 */
function wpan_get_api_secret( $options = null ) {
	if ( defined( 'WPAN_GA4_API_SECRET' ) && WPAN_GA4_API_SECRET ) {
		return trim( (string) WPAN_GA4_API_SECRET );
	}

	if ( null === $options ) {
		$options = wpan_get_options();
	}

	return trim( (string) wpan_option( $options, 'api_secret' ) );
}


/**
 * Client ID from the _ga cookie (GA1.1.123.456 => 123.456), or an empty string.
 */
function wpan_ga4_client_id_from_cookie( $cookie ) {
	if ( is_string( $cookie ) && preg_match( '/^GA\d+\.\d+\.(\d+\.\d+)$/', $cookie, $matches ) ) {
		return $matches[1];
	}

	return '';
}


/**
 * Session ID from the _ga_<ID> cookie, in the GS1 or GS2 format, or an empty string.
 */
function wpan_ga4_session_id_from_cookie( $cookie ) {
	if ( ! is_string( $cookie ) ) {
		return '';
	}

	if ( preg_match( '/^GS1\.\d+\.(\d+)\./', $cookie, $matches ) ) {
		return $matches[1];
	}

	if ( preg_match( '/^GS2\.\d+\.(?:[^$]*\$)*?s(\d+)(?:\$|$)/', $cookie, $matches ) ) {
		return $matches[1];
	}

	return '';
}


/**
 * Client and session ID of the visitor, read from the Google Analytics cookies.
 * Without cookies the client ID is random: the form is counted, with no traffic source.
 */
function wpan_ga4_visitor( $measurement_id, $cookies ) {
	$client_id = wpan_ga4_client_id_from_cookie( isset( $cookies['_ga'] ) ? $cookies['_ga'] : null );
	if ( ! $client_id ) {
		$client_id = mt_rand( 100000000, 2147483647 ) . '.' . time();
	}

	$session_cookie = '_ga_' . substr( $measurement_id, 2 );
	$session_id     = wpan_ga4_session_id_from_cookie( isset( $cookies[ $session_cookie ] ) ? $cookies[ $session_cookie ] : null );

	return [
		'client_id'  => $client_id,
		'session_id' => $session_id,
	];
}


/**
 * Send an event (array with 'name' and 'params') to GA4 with the Measurement Protocol.
 * Google does not tell if the ID or the secret are wrong: it discards the event silently.
 * With $validate the event goes to the validation server, which does not record it.
 */
function wpan_ga4_send_event( $event, $visitor, $blocking = false, $validate = false ) {
	$measurement_id = wpan_get_measurement_id();
	$api_secret     = wpan_get_api_secret();

	if ( ! $measurement_id || ! $api_secret ) {
		wpan_debug( 'Cannot send event to Google Analytics 4: measurement ID or API secret missing' );
		return false;
	}

	// Needed to attribute the event to the session and to show the user in reports
	if ( ! empty( $visitor['session_id'] ) ) {
		$event['params']['session_id'] = (string) $visitor['session_id'];
	}
	if ( ! isset( $event['params']['engagement_time_msec'] ) ) {
		$event['params']['engagement_time_msec'] = 1;
	}

	$payload = [
		'client_id' => (string) $visitor['client_id'],
		'events'    => [ $event ],
	];

	// Ex. https://region1.google-analytics.com to collect data in the EU
	$endpoint = apply_filters( 'wpan_filter_ga4_endpoint', 'https://www.google-analytics.com' );
	$url      = rtrim( $endpoint, '/' ) . ( $validate ? '/debug' : '' ) . '/mp/collect'
		. '?measurement_id=' . rawurlencode( $measurement_id )
		. '&api_secret=' . rawurlencode( $api_secret );

	return wp_remote_post(
		$url,
		[
			'blocking' => (bool) $blocking,
			'timeout'  => $blocking ? 5 : 1,
			'headers'  => [ 'Content-Type' => 'application/json' ],
			'body'     => wp_json_encode( $payload ),
		]
	);
}


/**
 * Gravity Forms callback: a form has been submitted.
 */
function wpan_send_form_submitted( $entry, $form ) {
	$options = wpan_get_options();

	if ( empty( $options['form_tracking'] ) ) {
		return;
	}

	if ( ! wpan_get_measurement_id( $options ) || ! wpan_get_api_secret( $options ) ) {
		wpan_debug( 'Form submission not sent to Google Analytics 4: measurement ID or API secret missing' );
		return;
	}

	// The payment confirmation, if any, will arrive later and without cookies
	$visitor = wpan_ga4_visitor( wpan_get_measurement_id( $options ), $_COOKIE );
	if ( function_exists( 'gform_update_meta' ) && isset( $entry['id'] ) ) {
		gform_update_meta( $entry['id'], 'wpan_ga4_visitor', $visitor );
	}

	$is_payment_form  = isset( $entry['payment_status'] ) && $entry['payment_status'];
	$wait_for_payment = wpan_option( $options, 'wait_for_form_payment' );

	if ( $is_payment_form && $wait_for_payment ) {
		wpan_debug( 'Will wait for payment confirmation before sending form submission event' );
		return;
	}

	try {
		wpan_send_tracking_event( $entry, $form, $visitor );
	} catch ( \Throwable $e ) {
		wpan_report_form_tracking_error( $e );
	}
}


/**
 * Gravity Forms callback: the payment has gone through; adds transaction ID and amount.
 */
function wpan_send_payment_done( $entry, $feed, $transaction_id, $amount ) {
	$options          = wpan_get_options();
	$wait_for_payment = wpan_option( $options, 'wait_for_form_payment' );

	// Without waiting for payment the event was already sent
	if ( empty( $options['form_tracking'] ) || ! $wait_for_payment ) {
		return;
	}

	try {
		$form    = class_exists( 'GFAPI' ) ? GFAPI::get_form( $entry['form_id'] ) : [];
		$visitor = function_exists( 'gform_get_meta' ) ? gform_get_meta( $entry['id'], 'wpan_ga4_visitor' ) : null;
		if ( ! is_array( $visitor ) || empty( $visitor['client_id'] ) ) {
			$visitor = wpan_ga4_visitor( wpan_get_measurement_id( $options ), [] );
		}
		$payment = [ 'transaction_id' => (string) $transaction_id ];
		if ( is_numeric( $amount ) ) {
			$payment['value']    = (float) $amount;
			$payment['currency'] = ! empty( $entry['currency'] ) ? $entry['currency'] : 'EUR';
		}
		wpan_send_tracking_event( $entry, is_array( $form ) ? $form : [], $visitor, $payment );
	} catch ( \Throwable $e ) {
		wpan_report_form_tracking_error( $e );
	}
}


/**
 * Report an error without interrupting the submission of the form.
 */
function wpan_report_form_tracking_error( $e ) {
	$msg = 'Errore in WordPress Analytics Form Tracking: ' . $e->getMessage();
	wpan_notify_email( $msg );
	error_log( $msg ); // phpcs:ignore
}


/**
 * Build the GA4 event for a form submission.
 */
function wpan_ga4_build_form_event( $entry, $form, $extra_params = [] ) {
	$source_url = isset( $entry['source_url'] ) ? (string) $entry['source_url'] : '';
	// Page of the form; $entry['post_id'] is instead the post created by the form
	$post_id = ! empty( $entry['source_id'] ) ? (int) $entry['source_id'] : ( $source_url ? url_to_postid( $source_url ) : 0 );

	$params = [
		'form_id'    => isset( $form['id'] ) ? (string) $form['id'] : ( isset( $entry['form_id'] ) ? (string) $entry['form_id'] : '' ),
		'form_title' => isset( $form['title'] ) ? (string) $form['title'] : '',
	];

	if ( $source_url ) {
		$params['page_location'] = $source_url;
		$params['page_path']     = (string) wp_parse_url( $source_url, PHP_URL_PATH );
	}

	if ( $post_id && get_the_title( $post_id ) ) {
		$params['page_title'] = get_the_title( $post_id );
	}

	return [
		'name'   => WPAN_GA4_FORM_EVENT,
		'params' => array_merge( $params, $extra_params ),
	];
}


/**
 * Build, filter and send the event of a form submission.
 */
function wpan_send_tracking_event( $entry, $form, $visitor, $extra_params = [] ) {
	$msg = 'About to send form tracking event to Google Analytics 4...';
	wpan_debug( $msg );
	if ( class_exists( 'GFCommon' ) ) {
		GFCommon::log_debug( $msg );
	}

	$options = wpan_get_options();
	$debug   = ! empty( $options['debug'] );

	$event = wpan_ga4_build_form_event( $entry, $form, $extra_params );

	// A filter returning false stops the event
	$event = apply_filters( 'wpan_filter_ga4_form_event', $event, $entry, $form );

	$response = null;

	if ( $event ) {
		// In debug mode, ask Google first whether the event is well formed
		if ( $debug ) {
			$validation = wpan_ga4_send_event( $event, $visitor, true, true );
			wpan_debug( 'Answer of the Google Analytics 4 validation server:' );
			wpan_debug( is_array( $validation ) ? wp_remote_retrieve_body( $validation ) : $validation );
		}

		$response = wpan_ga4_send_event( $event, $visitor, $debug );

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( $response->get_error_message() );
		}
	}

	do_action( 'wpan_action_after_ga4_form_event', $event, $visitor, $entry, $form );

	if ( $debug ) {
		wpan_debug( 'Sent the following event to Google Analytics 4:' );
		wpan_debug( $event ? $event : 'Event empty because of filter' );
	}
}


/**
 * Admin notices: form tracking enabled but not configured, or legacy hooks in use.
 */
function wpan_form_tracking_admin_notices() {
	if ( ! wpan_can_show_admin_notices() ) {
		return;
	}

	$options      = wpan_get_options();
	$settings_url = admin_url( 'admin.php?page=wordpress_analytics' );

	if ( ! empty( $options['form_tracking'] ) && ( ! wpan_get_measurement_id( $options ) || ! wpan_get_api_secret( $options ) ) ) {
		printf(
			'<div class="notice notice-warning"><p><strong>WordPress Analytics:</strong> form tracking is enabled, but form submissions are <strong>not</strong> being sent to Google Analytics 4: '
			. 'it needs both the measurement ID and the Measurement Protocol API secret. <a href="%s">Enter them in the settings</a>, or turn form tracking off.</p></div>',
			esc_url( $settings_url )
		);
	}

	// Hooks of the Universal Analytics version, not called anymore
	$legacy_hooks = [];
	foreach ( [ 'wpan_filter_form_tracking_options', 'wpan_filter_form_tracking_event', 'wpan_action_post_send_form_tracking_event' ] as $hook ) {
		if ( has_filter( $hook ) ) {
			$legacy_hooks[] = $hook;
		}
	}
	if ( $legacy_hooks ) {
		printf(
			'<div class="notice notice-warning"><p><strong>WordPress Analytics:</strong> this website customises form tracking with hooks that are not called anymore: <code>%s</code>. '
			. 'Rewrite them with <code>wpan_filter_ga4_form_event</code> and <code>wpan_action_after_ga4_form_event</code> (see the README of the plugin).</p></div>',
			esc_html( implode( ', ', $legacy_hooks ) )
		);
	}
}
