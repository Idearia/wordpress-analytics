<?php

  function wpan_register_advanced_settings_fields( $section, $displayed_values ) {

    $name = 'enhanced_link_attribution';
    $title = 'Enhanced Link Attribution';
    $desc = 'Enable <a href="https://support.google.com/analytics/answer/2558867">Enhanced Link Attribution</a>.';
    $desc .= 'Remember to enable the option in the property settings inside Google Analytics';
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

    $name = 'debug';
    $title = 'Debug mode';
    $desc = 'Print useful information to console';
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


  /** display the "Advanced settings" section */
  function wpan_display_advanced_settings_section () {

  }

  function wpan_display_enhanced_link_attribution ( $args ) {

    wpan_display_checkbox_input ( $args );

  }

  function wpan_display_debug ( $args ) {

    wpan_display_checkbox_input ( $args );

  }

?>