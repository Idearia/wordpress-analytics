<?php

  /**
   * PHP wrapper for the call tracking Javascript, suitable for use in
   * WordPress.
   *
   * Created by Guido W. Pettinari on 02.03.2016.
   * Part of WordPress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */

  function wpan_call_tracking () {

    /* Extract the call tracking options from the database */
    $options = wpan_get_options ();
    $ga_tracker = isset ( $options ['tracker_name'] ) ? $options ['tracker_name'] : '';
    $phone_regex_include_pattern = isset ( $options['phone_regex_include_pattern'] ) ? $options['phone_regex_include_pattern'] : '';
    $phone_regex_exclude_pattern = isset ( $options['phone_regex_exclude_pattern'] ) ? $options['phone_regex_exclude_pattern'] : '';
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
          . "gaTracker='$ga_tracker' "
          . "regexIncludePattern='$phone_regex_include_pattern' "
          . "regexExcludePattern='$phone_regex_exclude_pattern' "
          . "detectPhoneNumbers='$detect_phone_numbers' "
          . "debug='$debug' "
          . "defer='defer'"
          . "> "
          . "</script>\n";

  }

?>