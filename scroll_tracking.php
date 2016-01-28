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

  function wordpress_analytics_scroll_tracking () {

    /* Script path & url */
    $script_path = plugin_dir_path(__FILE__) . 'scroll_tracking.js';
    $script = plugin_dir_url(__FILE__) . 'scroll_tracking.js';
      
    /* Add the timestamp as a query string to the script, in order to reload
    automatically the script when it is changed rather than using the cached
    version (see http://stackoverflow.com/a/14536240/2972183) */
    $script_versioned = $script . '?ver=' . filemtime($script_path);

    /* Load the imagesLoaded javascript library to ensure that scroll
    tracking works properly with image-rich pages.
    TODO: we should use wp_enqueue() here. */
    echo "<script src='https://npmcdn.com/imagesloaded@4.1/imagesloaded.pkgd.js'></script>\n";

    /* Load the script; in the future we could use wp_enqueue instead
    TODO: we should use wp_enqueue() here. */
    echo "<script type='text/javascript' src='$script_versioned'></script>\n";

  }

?>
