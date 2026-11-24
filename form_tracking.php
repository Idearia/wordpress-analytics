<?php

/**
 * Track form submissions in Google Analytics 4 from the server, using
 * the Measurement Protocol.
 *
 * The event is sent by the web server with wp_remote_post(), not by the
 * browser of the visitor: ad blockers cannot stop it. No external library
 * is needed.
 *
 * For now the feature works for forms made with the Gravity Forms plugin.
 *
 * Created by Guido W. Pettinari on 04.08.2016.
 * Part of WordPress Analytics:
 * https://github.com/coccoinomane/wordpress_analytics
 */

/** Name of the event sent to Google Analytics 4 when a form is submitted */
define( 'WPAN_GA4_FORM_EVENT', 'generate_lead' );

/** Name of the event sent with the "Send test event" link in the settings */
define( 'WPAN_GA4_TEST_EVENT', 'wpan_test_event' );

// The Gravity Forms actions are fired only if Gravity Forms is active
add_action( 'gform_after_submission', 'wpan_send_form_submitted', 10, 2 );
add_action( 'gform_paypal_fulfillment', 'wpan_send_payment_done', 10, 4 );


/**
 * Return the Measurement Protocol API secret of the Google Analytics 4
 * property, or an empty string if there is none.
 *
 * The constant WPAN_GA4_API_SECRET (ex. in wp-config.php) takes precedence
 * over the value set in the plugin options.
 *
 * @param array|null $options Plugin options; leave empty to read them from the database.
 * @return string
 */
function wpan_get_api_secret( $options = null ) {

	if ( defined( 'WPAN_GA4_API_SECRET' ) && WPAN_GA4_API_SECRET ) {
		return trim( (string) WPAN_GA4_API_SECRET );
	}

	if ( null === $options ) {
		$options = wpan_get_options();
	}

	return isset( $options['api_secret'] ) ? trim( (string) $options['api_secret'] ) : '';

}


/**
 * Extract the Google Analytics client ID from the value of the _ga cookie
 * (ex. GA1.1.1234567890.1700000000 => 1234567890.1700000000).
 *
 * @param mixed $cookie Value of the _ga cookie.
 * @return string The client ID, or an empty string if the cookie is not valid.
 */
function wpan_ga4_client_id_from_cookie( $cookie ) {

	if ( is_string( $cookie ) && preg_match( '/^GA\d+\.\d+\.(\d+\.\d+)$/', $cookie, $matches ) ) {
		return $matches[1];
	}

	return '';

}


/**
 * Extract the Google Analytics 4 session ID from the value of the
 * _ga_<container ID> cookie. Both formats in use are supported:
 *
 * - GS1.1.1700000000.5.1.1700000100.0.0.0   => 1700000000
 * - GS2.1.s1700000000$o5$g1$t1700000100$j60 => 1700000000
 *
 * @param mixed $cookie Value of the _ga_<container ID> cookie.
 * @return string The session ID, or an empty string if the cookie is not valid.
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
 * Tell who the visitor is, according to the Google Analytics cookies sent
 * by the browser, so that the event is attributed to the same user, session
 * and traffic source as the pageviews.
 *
 * Without cookies (ex. the visitor refused them) the event is sent anyway,
 * with a random client ID: the form is counted, but its traffic source is
 * not known.
 *
 * @param string $measurement_id GA4 measurement ID (G-XXXXXXXXXX).
 * @param array  $cookies        Cookies sent by the browser, usually $_COOKIE.
 * @return array With the 'client_id' and 'session_id' elements; the latter
 * might be empty.
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
 * Send an event to Google Analytics 4 from the server, using the
 * Measurement Protocol.
 *
 * By default the request does not wait for the answer of Google, so that
 * the visitor is not slowed down. Please note that Google does not tell
 * whether the measurement ID and the API secret are right: an event sent
 * with a wrong API secret is silently discarded.
 *
 * @param array  $event    Event, with the 'name' and 'params' elements.
 * @param array  $visitor  Visitor, as returned by wpan_ga4_visitor().
 * @param bool   $blocking Whether to wait for the answer of Google.
 * @param bool   $validate If true, the event is sent to the validation
 * server of Google, which checks its format and does not record it.
 * @return array|WP_Error|false The answer of wp_remote_post(), or false if
 * the measurement ID or the API secret are missing.
 */
function wpan_ga4_send_event( $event, $visitor, $blocking = false, $validate = false ) {

	$measurement_id = wpan_get_measurement_id();
	$api_secret     = wpan_get_api_secret();

	if ( ! $measurement_id || ! $api_secret ) {
		wpan_debug( 'Cannot send event to Google Analytics 4: measurement ID or API secret missing' );
		return false;
	}

	// The session ID attributes the event to the session of the visitor; the
	// engagement time is needed for the user to show up in the reports
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

	// Allow the user to change the endpoint, ex. to collect data in the EU
	// with https://region1.google-analytics.com
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
 * Tell GA the form has been sumbitted
 *
 * @param array $entry
 * @param array $form
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

	// Remember who the visitor is: the payment confirmation, if any, will
	// arrive later and without the cookies of the visitor
	$visitor = wpan_ga4_visitor( wpan_get_measurement_id( $options ), $_COOKIE );
	if ( function_exists( 'gform_update_meta' ) && isset( $entry['id'] ) ) {
		gform_update_meta( $entry['id'], 'wpan_ga4_visitor', $visitor );
	}

	$is_payment_form  = isset( $entry['payment_status'] ) && $entry['payment_status']; // payment forms start with 'processing' as payment status
	$wait_for_payment = isset( $options['wait_for_form_payment'] ) && $options['wait_for_form_payment'];

	// If the form is a payment form, and the user chose to wait for
	// payment confirmation, do not send the event yet
	if ( $is_payment_form && $wait_for_payment ) {
		wpan_debug( 'Will wait for payment confirmation before sending form submission event' );
		return;
	}

	// Send the form submission event to GA
	try {
		wpan_send_tracking_event( $entry, $form, $visitor );
	} catch ( \Throwable $e ) {
		$msg = 'Errore in WordPress Analytics Form Tracking: ' . $e->getMessage();
		wpan_notify_email( $msg );
		error_log( $msg ); // phpcs:ignore
	}
}


/**
 * Tell GA that the payment (if any) has gone through.
 *
 * The event will include the transaction ID and the transaction amount.
 *
 * @param array  $entry
 * @param array  $feed
 * @param string $transaction_id
 * @param float  $amount
 */
function wpan_send_payment_done( $entry, $feed, $transaction_id, $amount ) {

	$options          = wpan_get_options();
	$wait_for_payment = isset( $options['wait_for_form_payment'] ) && $options['wait_for_form_payment'];

	// If the user chose not to wait for payment confirmation, the
	// event was already sent => do nothing
	if ( empty( $options['form_tracking'] ) || ! $wait_for_payment ) {
		return;
	}

	// Send the form submission event to GA, adding the transaction
	// ID & amount to the event
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
		$msg = 'Errore in WordPress Analytics Form Tracking: ' . $e->getMessage();
		wpan_notify_email( $msg );
		error_log( $msg ); // phpcs:ignore
	}
}


/**
 * Build the event to send to Google Analytics 4 for a form submission.
 *
 * @param array $entry        Gravity Forms entry.
 * @param array $form         Gravity Forms form.
 * @param array $extra_params Parameters to add to the event (ex. payment data).
 * @return array Event, with the 'name' and 'params' elements.
 */
function wpan_ga4_build_form_event( $entry, $form, $extra_params = [] ) {

	$source_url = isset( $entry['source_url'] ) ? (string) $entry['source_url'] : '';
	$post_id    = isset( $entry['post_id'] ) && $entry['post_id'] ? (int) $entry['post_id'] : ( $source_url ? url_to_postid( $source_url ) : 0 );

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
 * Send an event to GA with details about the submission
 *
 * @param array $entry        Gravity Forms entry.
 * @param array $form         Gravity Forms form.
 * @param array $visitor      Visitor, as returned by wpan_ga4_visitor().
 * @param array $extra_params Parameters to add to the event (ex. payment data).
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

	/* Allow the user to filter the event (an array with the 'name' and 'params'
	elements); if false, the event will not be sent */
	$event = apply_filters( 'wpan_filter_ga4_form_event', $event, $entry, $form );

	$response = null;

	if ( $event ) {

		/* In debug mode, ask Google first whether the event is well formed */
		if ( $debug ) {
			$validation = wpan_ga4_send_event( $event, $visitor, true, true );
			wpan_debug( 'Answer of the Google Analytics 4 validation server:' );
			wpan_debug( is_array( $validation ) ? wp_remote_retrieve_body( $validation ) : $validation );
		}

		/* Send event to GA severs */
		$response = wpan_ga4_send_event( $event, $visitor, $debug );

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( $response->get_error_message() );
		}
	}

	/* Allow the user to send more events for the same visitor, with
	wpan_ga4_send_event() */
	do_action( 'wpan_action_after_ga4_form_event', $event, $visitor, $entry, $form );

	/* Debug */
	if ( $debug ) {
		wpan_debug( 'Sent the following event to Google Analytics 4:' );
		wpan_debug( $event ? $event : 'Event empty because of filter' );
	}

}


/**
 * Send a test event to Google Analytics 4 and go back to the settings page.
 *
 * Callback for the admin_post_wpan_ga4_test_event action, triggered by the
 * "Send test event" link in the settings page.
 */
function wpan_ga4_handle_test_event() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You are not allowed to do this.' );
	}
	check_admin_referer( 'wpan_ga4_test_event' );

	$measurement_id = wpan_get_measurement_id();

	if ( ! $measurement_id || ! wpan_get_api_secret() ) {
		$result = [ 'error', 'the test event was not sent: enter both the measurement ID and the API secret, save, then try again.' ];
	} else {
		$event    = [
			'name'   => WPAN_GA4_TEST_EVENT,
			'params' => [ 'debug_mode' => 1 ],
		];
		$response = wpan_ga4_send_event( $event, wpan_ga4_visitor( $measurement_id, $_COOKIE ), true );
		$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
		if ( is_wp_error( $response ) ) {
			$result = [ 'error', 'the test event could not be sent: ' . $response->get_error_message() ];
		} elseif ( $code < 200 || $code >= 300 ) {
			$result = [ 'error', 'the test event was refused by Google (HTTP ' . $code . ').' ];
		} else {
			$result = [
				'success',
				'test event sent. Open Google Analytics 4, Reports > Realtime (or Admin > DebugView): within a minute you should see the event "' . WPAN_GA4_TEST_EVENT . '". '
				. 'If it does not show up, the measurement ID or the API secret are wrong: Google accepts the request anyway and does not report the mistake.',
			];
		}
	}

	set_transient( 'wpan_ga4_test_event_result_' . get_current_user_id(), $result, 60 );
	wp_safe_redirect( admin_url( 'admin.php?page=wordpress_analytics' ) );
	exit;

}


/**
 * Show the outcome of the test event, and warn the administrators when form
 * tracking cannot work.
 *
 * Should be called as a callback for the admin_notices action.
 */
function wpan_form_tracking_admin_notices() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Outcome of the "Send test event" link
	$transient = 'wpan_ga4_test_event_result_' . get_current_user_id();
	$result    = get_transient( $transient );
	if ( is_array( $result ) ) {
		delete_transient( $transient );
		printf(
			'<div class="notice notice-%s is-dismissible"><p><strong>WordPress Analytics:</strong> %s</p></div>',
			esc_attr( $result[0] ),
			esc_html( $result[1] )
		);
	}

	// In network mode the settings can be changed only from the main blog
	if ( is_multisite() && wpan_is_network_mode() && ! wpan_is_main_blog() ) {
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

	// Hooks of the Universal Analytics version: they received objects of a
	// library that is not there anymore, so they are not called anymore
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


/**
 * Display the API secret field in the settings page, with the link to send
 * a test event.
 *
 * @param array $args Arguments of the field, see wpan_add_field().
 */
function wpan_custom_display_api_secret_field( $args ) {

	if ( defined( 'WPAN_GA4_API_SECRET' ) && WPAN_GA4_API_SECRET ) {
		echo '<p><em>The API secret is set by the <code>WPAN_GA4_API_SECRET</code> constant (ex. in wp-config.php), which takes precedence over this field.</em></p>';
	}

	wpan_display_text_field( $args );

	printf(
		' <a class="button" href="%s">Send test event</a><p class="description">Save the settings before sending the test event.</p>',
		esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wpan_ga4_test_event' ), 'wpan_ga4_test_event' ) )
	);

}
