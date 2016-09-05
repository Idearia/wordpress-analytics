<?php

  /**
   * Plugin Name: Wordpress Analytics
   * Plugin URI: https://github.com/coccoinomane/wordpress_analytics
   * Description: Let Google Analytics communicate with Wordpress and track user activity beyond pageviews
   * Version: alpha_v7
   * Author: Guido W. Pettinari
   * Author URI: http://www.guidowalterpettinari.eu
   * License: GPL3
   */

  /* Define plugin version */
  define( "WPAN_VERSION", "alpha_v7" );
  define( "WPAN_URL", "https://github.com/coccoinomane/wordpress_analytics" );

  /* Define plugin directory & URL */
  define( "WPAN_PLUGIN_DIR", plugin_dir_path(__FILE__) );
  define( "WPAN_PLUGIN_URL", plugin_dir_url(__FILE__) );

  /* Include utility functions */
  require_once ( WPAN_PLUGIN_DIR . 'functions.php' );

  /* Build settings page */
  require_once ( WPAN_PLUGIN_DIR . 'settings/settings.php' );

  /* Extract plugin options from the database */
  $options = wpan_get_options ();

  /* Stop unless we have a valid tracking ID */
  if ( isset ( $options ['tracking_uid'] ) && $options ['tracking_uid'] ) {


    // ========================
    // = Client-side tracking =
    // ========================

    /* Load content grouping function */
    if ( isset ( $options ['content_grouping'] ) && $options ['content_grouping'] )
      require_once ( WPAN_PLUGIN_DIR . 'content_grouping.php' );

    /* Load scroll tracking function */
    if ( isset ( $options ['scroll_tracking'] ) && $options ['scroll_tracking'] )
      require_once ( WPAN_PLUGIN_DIR . 'scroll_tracking.php' );

    /* Load call tracking function */
    if ( isset ( $options ['call_tracking'] ) && $options ['call_tracking'] )
      require_once ( WPAN_PLUGIN_DIR . 'call_tracking.php' );

    /* Load the function to write the actual complete GA tracking code */
    require_once ( WPAN_PLUGIN_DIR . 'tracking_code.php' );

    /* Insert the tracking code in the header */
    add_action ('wp_head', 'wpan_tracking_code', PHP_INT_MAX);


    // ========================
    // = Server-side tracking =
    // ========================

    /* Load Gravity Forms tracking */
    if ( isset ( $options ['form_tracking'] ) && $options ['form_tracking'] )
      require_once ( WPAN_PLUGIN_DIR . 'form_tracking.php' );


    // ===============
    // = Debug tools =
    // ===============

    /* Insert debug tools */
    if ( isset ( $options ['debug'] ) && $options ['debug'] )
      require_once ( WPAN_PLUGIN_DIR . 'debug.php' );

  }

?>