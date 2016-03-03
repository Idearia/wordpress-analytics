<?php

  /**
   * PHP wrapper for the call tracking Javascript, suitable for use in
   * Wordpress.
   *
   * Created by Guido W. Pettinari on 02.03.2016.
   * Part of Wordpress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */

  function wordpress_analytics_call_tracking () {

    /* Extract the call tracking options from the database */
    $options = wpan_get_options ();
    $phone_regex_pattern = isset ( $options['phone_regex_pattern'] ) ? $options['phone_regex_pattern'] : '';
    $detect_phone_numbers = isset ( $options['detect_phone_numbers'] ) ? $options['detect_phone_numbers'] : '';
    $debug = isset ( $options['debug'] ) ? $options['debug'] : '';

    /* Script path & url */
    $script_path = WPAN_PLUGIN_DIR . 'js/call_tracking.js';
    $script_url = WPAN_PLUGIN_URL . 'js/call_tracking.js';
    $script_versioned = $script_url . '?ver=' . filemtime($script_path);

    /* Prevent mobile browsers from detecting phone numbers */
    if ( $detect_phone_numbers ) {
      echo "<meta name='format-detection' content='telephone=no'>\n";
    }

    /* Load the script */
    echo "<script src='$script_versioned' "
          . "regexPattern='$phone_regex_pattern' "
          . "detectPhoneNumbers='$detect_phone_numbers' "
          . "debug='$debug' "
          . "defer='defer'"
          . "> "
          . "</script>\n";

  }

?>
