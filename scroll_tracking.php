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

  $script_name = 'scroll_tracking.js';

  /* Script location on the file system */
  $script_path = ANALYTICS_DIR . DIR_SEP . $script_name;

  /* Script location on the web */
  $script_uri = ANALYTICS_URI . '/' . $script_name;

  /* Add the timestamp as a query string to the script, in order to reload
  automatically the script when it is changed rather than using the cached
  version (see http://stackoverflow.com/a/14536240/2972183) */
  $script_uri_versioned = $script_uri . '?ver=' . filemtime($script_path);

  /* Load the imagesLoaded javascript library to ensure that scroll 
  tracking works properly with image-rich pages */
  echo "<script src='https://npmcdn.com/imagesloaded@4.1/imagesloaded.pkgd.js'></script>\n";

  /* Load the script; in the future we could use wp_enqueue instead */
  echo "<script type='text/javascript' src='$script_uri_versioned'></script>\n";

?>
