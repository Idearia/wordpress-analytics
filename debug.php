<?php

  /**
   * File with debug functions for the Wordpress Analytics
   * plugin.
   *
   * Created by Guido W. Pettinari on 29.01.2016.
   * Part of Wordpress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */

  add_action ('wp_footer', 'wordpress_analytics_debug', 100);

  /**
   * Execute a debug function at the very bottom of all posts
   * and pages.
   */

  function wordpress_analytics_debug () {

    /* Print all options in the database */
    // $options = wpan_get_options ();
    // echo '<pre>' , print_r ( $options ), '</pre>';

  }

?>