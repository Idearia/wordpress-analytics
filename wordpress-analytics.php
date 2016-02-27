<?php

  /**
   * Plugin Name: Wordpress Analytics
   * Plugin URI: https://github.com/coccoinomane/wordpress_analytics
   * Description: Let Google Analytics communicate with Wordpress and track user activity beyond pageviews
   * Version: 0.1
   * Author: Guido W. Pettinari
   * Author URI: http://www.guidowalterpettinari.eu
   * License: GPL3
   */

  /* Include utility functions */
  require_once (plugin_dir_path(__FILE__) . "functions.php");

  /* Build settings page */
  require_once (plugin_dir_path(__FILE__) . "settings.php");

  /* Extract plugin options from the database */
  $options = wpan_get_options ();

  /* Stop unless we have a valid tracking ID */
  if ( isset ( $options ['tracking_uid'] ) && $options ['tracking_uid'] ) {

    /* Load content grouping function */
    if ( isset ( $options ['content_grouping'] ) && $options ['content_grouping'] )
      require_once (plugin_dir_path(__FILE__) . "content_grouping.php");

    /* Load scroll tracking function */
    if ( isset ( $options ['scroll_tracking'] ) && $options ['scroll_tracking'] )
      require_once (plugin_dir_path(__FILE__) . "scroll_tracking.php");

    /* Write the actual Google Analytics tracking code */
    require_once (plugin_dir_path(__FILE__) . "tracking_code.php");

    /* Insert the tracking code in the header */
    add_action ('wp_head', 'wordpress_analytics_tracking_code');

    /* Insert debug tools */
    if ( isset ( $options ['debug'] ) && $options ['debug'] )
      require_once (plugin_dir_path(__FILE__) . "debug.php");
    
  }

?>