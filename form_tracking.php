<?php

/**
 * Track forms submissions in Google Analytics using the Measurement
 * Protocol.
 *
 * Created by Guido W. Pettinari on 04.08.2016.
 * Part of WordPress Analytics:
 * https://github.com/coccoinomane/wordpress_analytics
 */

// Add Gravity Forms filter
if ( class_exists( 'GFCommon' ) ) {

	/* Load the server-side library to send hits to Google Analytics */
	if ( ! defined( 'WPAN_GAMP_LOADED' ) ) {
		if ( wpan_load_measurement_protocol_client() ) {
			add_action( 'gform_after_submission', 'wpan_send_form_submitted', 10, 4 );
			add_action( 'gform_post_payment_status', 'wpan_send_payment_done', 10, 8 );
		}
	}

	/**
	 * Tell GA the form has been sumbitted
	 *
	 * @param Array $entry
	 * @param Array $form
	 */
	function wpan_send_form_submitted( $entry, $form ) {
		try {
			wpan_send_tracking_event( $entry, $form );
		} catch ( \Throwable $e ) {
			$msg = 'Errore in WordPress Analytics Form Tracking: ' . $e->getMessage();
			wpan_notify_email( $msg );
			error_log( $msg );
		}
	}

	/**
	 * Tell GA that the payment has gone through (if any)
	 *
	 * @param Array  $feed
	 * @param Array  $entry
	 * @param String $status
	 * @param String $transaction_id
	 */
	function wpan_send_payment_done( $feed, $entry, $status, $transaction_id ) {
		if ( $status === 'Completed' || $status === 'Paid' ) {
			try {
				$form         = GFAPI::get_form( $entry['form_id'] );
				$event_action = 'form-payment:' . $form['title'];
				wpan_send_tracking_event( $entry, $form, $event_action );
			} catch ( \Throwable $e ) {
				$msg = 'Errore in WordPress Analytics Form Tracking: ' . $e->getMessage();
				wpan_notify_email( $msg );
				error_log( $msg );
			}
		}
	}

	/**
	 * Send an event to GA with details about the submission
	 *
	 * @param Array  $entry
	 * @param Array  $form
	 * @param String $event_action Value to pass to GA for the event action
	 * field; leave empty to use form:<Form title>.
	 * @param String $event_label Value to pass to GA for the event label
	 * field; leave empty to use the path of the page with the form.
	 */
	function wpan_send_tracking_event( $entry, $form, $event_action = null, $event_label = null ) {

		global $post;

		$msg = 'About to send form tracking event to Google Analytics...';
		wpan_debug( $msg );
		GFCommon::log_debug( $msg );

		/* Extract the plugin options from the database */
		$options      = wpan_get_options();
		$tracking_uid = isset( $options ['tracking_uid'] ) ? $options ['tracking_uid'] : '';
		$debug        = isset( $options['debug'] ) ? $options['debug'] : '';

		// I have taken the following four lines of code from
		// https://github.com/theiconic/php-ga-measurement-protocol
		// Thank you!
		$document_path     = wp_parse_url( $entry['source_url'], PHP_URL_PATH );
		$document_location = 'http' . ( isset( $_SERVER['HTTPS'] ) ? 's' : '' ) . '://' . $_SERVER['HTTP_HOST'] . '/' . $_SERVER['REQUEST_URI']; // phpcs:ignore
		$document_title    = isset( $post ) && get_the_title( $post ) ? get_the_title( $post ) : 'no title';

		/* Setup the class */
		$ga_options = [
			'client_create_random_id' => true, // create random client id if the class can't fetch the current cliend id
			'client_fallback_id'      => 555, // fallback client id when cid was not found and random client id is off
			'client_id'               => null, // override client id
			'user_id'                 => null, // determine current user id
			'adapter'                 => [ // adapter options
				'async' => true, // requests to google are async - don't wait for google server response
				'ssl'   => false, // use ssl connection to google server
			],
		];

		/* Allow the user to filter the GA options */
		$event = apply_filters( 'wpan_filter_form_tracking_options', $ga_options, $entry, $form );

		/* Connect to tracker */
		$gatracking = new \Racecore\GATracking\GATracking( $tracking_uid, $ga_options );

		/* Build GA event */
		$event = $gatracking->createTracking( 'Event' );
		$event->setAsNonInteractionHit( false );
		$event->setEventCategory( 'Contact' );
		$event->setEventAction( $event_action ?? 'form:' . $form['title'] );
		$event->setEventLabel( $event_label ?? $document_path );
		$event->setDocumentPath( $document_path );
		$event->setDocumentLocation( $document_location );
		$event->setDocumentTitle( $document_title );

		/* Allow the user to filter the event; if false, the event will not be sent */
		$event = apply_filters( 'wpan_filter_form_tracking_event', $event, $gatracking, $entry, $form );

		/* Send event to GA severs */
		if ( $event ) {
			$response = $gatracking->sendTracking( $event );
		}

		/* Allow the user to send more events with the same tracker */
		do_action( 'wpan_action_post_send_form_tracking_event', $gatracking, $entry, $form );

		/* Debug */
		if ( $debug ) {
			wpan_debug( 'Sent the following event to Google Analytics:' );
			wpan_debug( $event ? $event : 'Event empty because of filter' );
			wpan_debug( 'Received the following respons from Google Analytics (ASYNC, so it might be empty): ' );
			wpan_debug( $response );
			// wpan_debug( "This is the entry of the form in Gravity Forms:" );
			// wpan_debug( $entry );
			// wpan_debug( "This is the form in Gravity Forms:" );
			// wpan_debug( $form );
		}

	}
}


