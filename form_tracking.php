<?php

  /**
   * Track forms submissions in Google Analytics using the Measurement
   * Protocol.
   *
   * Created by Guido W. Pettinari on 04.08.2016.
   * Part of WordPress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */

  /* Add Gravity Forms filter */
  if ( class_exists( 'GFCommon' ) ) {

    /* Load the server-side library to send hits to Google Analytics */
    if ( ! defined( 'WPAN_GAMP_LOADED' ) ) {

      if ( wpan_load_measurement_protocol_client () ) {

        /* Send data to GA only after form validation & submission */
        add_action( 'gform_after_submission', 'wpan_send_form_tracking_event', 10, 4 );
        
      }

    }

    function wpan_send_form_tracking_event( $entry, $form ) {

  		global $post;

      $msg = "About to send form tracking event to Google Analytics...";
      wpan_debug( $msg );
      GFCommon::log_debug( $msg );

      /* Extract the plugin options from the database */
      $options = wpan_get_options ();
      $tracking_uid = isset ( $options ['tracking_uid'] ) ? $options ['tracking_uid'] : '';
      $debug = isset ( $options['debug'] ) ? $options['debug'] : '';
      $form_title = $form['title'];

      /* I have taken the following four lines of code from
      https://github.com/theiconic/php-ga-measurement-protocol;
      thank you! */
      $document_path = parse_url( $entry['source_url'], PHP_URL_PATH );
  		$document_location = 'http' . ( isset( $_SERVER['HTTPS'] ) ? 's' : '' ) . '://' . $_SERVER['HTTP_HOST'] . '/' . $_SERVER['REQUEST_URI'];
  		$document_title = isset( $post ) && get_the_title( $post ) ? get_the_title( $post ) : 'no title';

      /* Setup the class */
      $ga_options = [
        'client_create_random_id' => true, // create random client id if the class can't fetch the current cliend id
        'client_fallback_id' => 555, // fallback client id when cid was not found and random client id is off
        'client_id' => null, // override client id
        'user_id' => null, // determine current user id
        'adapter' => [ // adapter options
          'async' => true, // requests to google are async - don't wait for google server response
          'ssl' => false // use ssl connection to google server
        ]
      ];

      /* Connect to tracker */
      $gatracking = new \Racecore\GATracking\GATracking( $tracking_uid, $ga_options );

      /* Build GA event */
      $event = $gatracking->createTracking( 'Event' );
      $event->setAsNonInteractionHit( false );
      $event->setEventCategory( 'Contact' );
      $event->setEventAction( 'form:' . $form_title );
      $event->setEventLabel( $document_path );

  		$event->setDocumentPath( $document_path );
  		$event->setDocumentLocation( $document_location );
  		$event->setDocumentTitle( $document_title );


      /* Send event to GA severs */
      $response = $gatracking->sendTracking( $event );

      /* Debug */
      if ( $debug ) {
        wpan_debug( "Sent the following event to Google Analytics:" );
        wpan_debug( $event );
        wpan_debug( "Received the following respons from Google Analytics (ASYNC, so it might be empty): ");
        wpan_debug( $response );
        // wpan_debug( "This is the form that triggered the event:" );
        // wpan_debug( $form );
        // wpan_debug( "This is the entry of the form in Gravity Forms:" );
        // wpan_debug( $entry );
      }

    }
    
  }

?>