<?php

  function wpan_register_call_tracking_fields( $section, $displayed_values ) {

    $name = 'phone_regex_pattern';
    $title = "Phone numbers' pattern";
    $desc = "Phone numbers matching this regex pattern will be sent to Google Analytics; leave blank to catch all clicks to 'tel:' links.";
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
        'size'         => 30,
        'maxlength'    => WPAN_MAX_REGEX_LENGTH,
        'label_for'    => $name,
      ]
    );

    $name = 'detect_phone_numbers';
    $title = 'Detect phone numbers automatically';
    $desc = "If set, we shall treat all numbers matching the regex pattern above as clickable phone numbers, regardless of whether they are 'tel:' links";
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
        'label_for'    => $name,
      ]
    );
    
  }


  /** display the "Scroll tracking" section */
  function wpan_display_call_tracking_section () {

    echo "<p>Settings for the call tracking functionality.</p>";
    $desc = "Parameters set here will be ignored if you have Javascript caching enabled.";
    echo "<p>$desc</p>";

  }

  function wpan_display_phone_regex_pattern ( $args ) {

    wpan_display_text_input ( $args );

  }

  function wpan_display_detect_phone_numbers ( $args ) {

    wpan_display_checkbox_input ( $args );

  }
  
?>