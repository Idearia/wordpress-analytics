<?php

/**
 * Register the fields contained in the the "Scroll tracking" section.
 */

  function wpan_register_scroll_tracking_fields( $section, $displayed_values ) {

    $name = 'pixel_threshold';
    $title = 'Pixels threshold for engagement';
    $desc = 'Pixels the user needs to scroll before we consider him/her engaged.';
    add_settings_field(
      $name,
      $title,
      'wpan_display_' . $name,
      $section['page'],
      $section['name'],
      [
        'db_key'       => $section['db_key'],
        'options_vals' => $displayed_values,
        'name'         => $name,
        'desc'         => $desc,
        'section'      => $section,
        'value'        => $displayed_values[ $name ],
        'min'          => 1,
        'max'          => 10000,
      ]
    );

    $name = 'time_threshold';
    $title = 'Time required to read content';
    $desc = 'Time in seconds the user needs to spend on the content before we consider it read';
    add_settings_field(
      $name,
      $title,
      'wpan_display_' . $name,
      $section['page'],
      $section['name'],
      [
        'db_key'       => $section['db_key'],
        'options_vals' => $displayed_values,  
        'name'         => $name,
        'desc'         => $desc,
        'section'      => $section,
        'value'        => $displayed_values[ $name ],
        'min'          => 1,
        'max'          => 10000,
      ]
    );
    
  }


  /**
   * Build the "Scroll tracking" section.
   **/

  function wpan_display_scroll_tracking_section () {

    echo "<p>Settings for the scroll tracking functionality.</p>";
    $desc = "Parameters set here will be ignored if you have Javascript caching enabled.";
    echo "<p>$desc</p>";

  }


  /**
   * Display the single fields in the "Scroll tracking" section.
   */
   
  function wpan_display_pixel_threshold ( $args ) {

    wpan_display_number_input ( $args );

  }

  function wpan_display_time_threshold ( $args ) {

    wpan_display_number_input ( $args );

  }
  
?>