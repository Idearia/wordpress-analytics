<?php

  function wpan_register_call_tracking_fields( $section, $displayed_values ) {

    $name = 'phone_regex_include_pattern';
    $title = "Phone pattern (include)";
    $desc = "Consider only phone numbers that match this pattern; leave blank to catch all clicks to 'tel:' links.";
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

    $name = 'phone_regex_exclude_pattern';
    $title = "Phone pattern (exclude)";
    $desc = "Do not consider phone numbers that match this regex pattern; leave blank to catch all clicks to 'tel:' links.";
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
    $desc = "If set, automatically convert text like `TEL: 123456` into a phone number link. Useful if you can't input HTML in pages, ex. if you use Visual Composer contact-info element.";
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

  function wpan_display_phone_regex_include_pattern ( $args ) {

    wpan_display_text_input ( $args );

  }

  function wpan_display_phone_regex_exclude_pattern ( $args ) {

    wpan_display_text_input ( $args );

  }

  function wpan_display_detect_phone_numbers ( $args ) {

    wpan_display_checkbox_input ( $args );

  }
  
?>