<?php

  /**
   * PHP wrapper for the form-tracking Javascript, suitable for use in
   * Wordpress.
   *
   * Created by Guido W. Pettinari on 04.08.2016.
   * Part of Wordpress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */

  function wordpress_analytics_form_tracking () {

    /* Extract the form-tracking options from the database */
    $options = wpan_get_options ();
    $debug = isset ( $options['debug'] ) ? $options['debug'] : '';

    echo ("FORM TITLE = $form_title\n");
    die();


    /* Track form upon confirmation */
    add_action("gform_after_submission", "gf_ga_tracking", 10, 2);

    function gf_ga_tracking($entry, $form) { 

      /* Script path & url */
      $script_path = WPAN_PLUGIN_DIR . 'js/form_tracking.js';
      $script_url = WPAN_PLUGIN_URL . 'js/form_tracking.js';
      $script_versioned = $script_url . '?ver=' . filemtime($script_path);

      /* Extract form title */
      $form_title = $form["title"];
      
      echo ("FORM TITLE = $form_title\n");
      die();

      /* Load the script */
      echo "<script src='$script_versioned' "
            . "formTitle='$form_title'"
            . "debug='$debug' "
            . "defer='defer'"
            . "> "
            . "</script>\n";
    }
  }

?>