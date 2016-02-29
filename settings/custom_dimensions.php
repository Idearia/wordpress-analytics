<?php

  function wpan_register_custom_dimensions_fields( $section, $displayed_values ) {

    $name = 'custom_dimensions_repeater';
    $title = 'Choose information to pass from Wordpress to Google Analytics';
    $desc = 'Make sure to match the index you choose here with the one in Google Analytics admin panel';
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
      ]
    );

  }


  /** display the "Content grouping" section */
  function wpan_display_custom_dimensions_section () {

    echo "<p>Settings for the Custom Dimensions functionality</p>";

  }

  function wpan_display_custom_dimensions_repeater ( $args ) {
    
    // $value = $args['value'];
    //
    // echo "<pre>" . print_r ( $value ) . "</pre>";
    // echo (count ( $value ) % 3) . PHP_EOL;

    /* Why isn't this printing anything even if the condition is met? */
    // if ( count ( $value ) % 3 !== 0 ) {
    //
    //   add_settings_error(
    //     $args['db_key'],
    //     "wrong-custom-dimensions-number",
    //     "BUG! Field '" . $args['name'] . "' must have a number of elements divisible by 3");
    //
    // }

  }

?>