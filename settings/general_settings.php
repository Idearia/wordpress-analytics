<?php

  function wpan_register_general_settings_fields( $section, $displayed_values ) {

    $name = 'tracking_uid';
    $title = 'Google Analytics tracking ID';
    $desc = 'Google Analytics tracking ID';
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
        'maxlength'    => '16',
        'label_for'    => $name,
      ]
    );

    $name = 'content_grouping';
    $title = 'Enable content grouping?';
    $link = '<a href="https://support.google.com/analytics/answer/2853423">Content Grouping</a>';
    $desc = 'Group pageviews in Google Analytics using the ' . $link . ' feature';
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

    $name = 'scroll_tracking';
    $title = 'Enable scroll tracking?';
    $desc = 'Send events to Google Analytics based on the users\' scrolling activity on each page';
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

    $name = 'call_tracking';
    $title = 'Enable call tracking?';
    $desc = 'Send an event to Google Analytics every time a user clicks on a phone number';
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

  /** display the "General settings" section */
  function wpan_display_general_settings_section () {

    echo "<p>General settings</p>";

  }

  function wpan_display_tracking_uid ( $args ) {

    wpan_display_text_input ( $args );

  }

  function wpan_display_content_grouping ( $args ) {

    wpan_display_checkbox_input ( $args );

  }

  function wpan_display_scroll_tracking ( $args ) {

    wpan_display_checkbox_input ( $args );

  }

  function wpan_display_call_tracking ( $args ) {

    wpan_display_checkbox_input ( $args );

  }

