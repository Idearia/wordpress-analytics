<?php

  /**
   * PHP wrapper for the email tracking Javascript, suitable for use in
   * Wordpress.
   *
   * Created by Guido W. Pettinari on 09.11.2016.
   * Part of Wordpress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */

  function wpan_email_tracking () {

    /* Extract the email tracking options from the database */
    $options = wpan_get_options ();
    $debug = isset ( $options['debug'] ) ? $options['debug'] : '';

    /* Script path & url */
    $script_path = WPAN_PLUGIN_DIR . 'js/email_tracking.js';
    $script_url = WPAN_PLUGIN_URL . 'js/email_tracking.js';
    $script_versioned = $script_url . '?ver=' . filemtime($script_path);

    /* Load the script */
    echo "<script src='$script_versioned' "
          . "debug='$debug' "
          . "defer='defer'"
          . "> "
          . "</script>\n";

  }

?>