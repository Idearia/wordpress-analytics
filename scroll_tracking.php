<?php

  /**
   * PHP wrapper of a slightly modified version of the scroll tracking
   * javascript by Justin Cutroni, suitable for use in Wordpress.
   *
   * The javascript will be loaded from the URI specified in the 
   * named constant ANALYTICS_SCRIPT_URI.
   * 
   * The javascript is named scroll_tracking.js; its latest version
   * can be found at https://gist.github.com/a1c715e2a448da2dfd69.
   * 
   * Created by Guido W. Pettinari on 23.12.2015.
   * Last version: https://gist.github.com/409f9b97cb3e2803ad47
   */

  /* Load the script; in the future we could use wp_enqueue instead */
  echo "<script type='text/javascript' src='" . ANALYTICS_SCRIPT_URI . "'></script>";

?>
