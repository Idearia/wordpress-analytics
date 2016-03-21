<?php

  /**
   * Register the fields contained in the the "Custom code" section.
   */

  function wpan_register_custom_code_fields( $section, $displayed_values ) {

    $name = 'custom_code';
    $title = 'Custom code';
    $desc = 'Any code you write here will be included in all pages of your website just before the pageview is sent.';
    $desc .= 'Remember to include the PHP opening & closing tags.';
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
        'rows'         => 15,
        'cols'         => 120,
        'placeholder'  => 'Insert code here; leave blank for no effect',
        'label_for'    => $name,
      ]
    );

  }


  /**
   * Build the "Custom code" section.
   **/

  function wpan_display_custom_code_section () {

  }


  /**
   * Display the single fields in the "Custom code" section.
   */
   
  function wpan_display_custom_code ( $args ) {

    wpan_display_code_input ( $args );

  }

?>