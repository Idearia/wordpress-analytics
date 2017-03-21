<?php

  /**
   * PHP wrapper of a slightly modified version of the scroll tracking
   * javascript by Justin Cutroni, suitable for use in Wordpress.
   *
   * The javascript needs to be called scroll_tracking.js and to reside
   * in the URI specified in the named constant ANALYTICS_URI.
   * 
   * Created by Guido W. Pettinari on 23.12.2015.
   * Part of Wordpress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */

  function wpan_scroll_tracking () {

    /* Extract the scroll tracking options from the database */
    $options = wpan_get_options ();
    $ga_tracker = isset ( $options ['tracker_name'] ) ? $options ['tracker_name'] : '';
    $pixel_threshold = isset ( $options['pixel_threshold'] ) ? $options['pixel_threshold'] : '';
    $time_threshold = isset ( $options['time_threshold'] ) ? $options['time_threshold'] : '';
    $debug = isset ( $options['debug'] ) ? $options['debug'] : '';

    /* Script path & url */
    $script_path = WPAN_PLUGIN_DIR . 'js/scroll_tracking.js';
    $script_url = WPAN_PLUGIN_URL . 'js/scroll_tracking.js';
      
    /* Add the timestamp as a query string to the script, in order to reload
    automatically the script when it is changed rather than using the cached
    version (see http://stackoverflow.com/a/14536240/2972183) */
    $script_versioned = $script_url . '?ver=' . filemtime($script_path);

    /* Load the imagesLoaded javascript library to ensure that scroll
    tracking works properly with image-rich pages.
    TODO: we should use wp_enqueue() here. */
    echo "<script src='https://npmcdn.com/imagesloaded@4.1/imagesloaded.pkgd.js' defer='defer'></script>\n";

    /* Load the script.
    TODO: we should use wp_enqueue() here. */
    echo "<script src='$script_versioned' "
          . "gaTracker='$ga_tracker' "
          . "timeThreshold='$time_threshold' "
          . "pixelThreshold='$pixel_threshold' "
          . "debug='$debug' "
          . "defer='defer'"
          . "> "
          . "</script>\n";

  }

?>
