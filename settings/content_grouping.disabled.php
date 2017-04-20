<?php

  /**
   * Register the fields contained in the the "Content grouping" section.
   */

  function wpan_register_content_grouping_fields( $section, $displayed_values ) {

    $name = 'group_index_wordpress';
    $title = 'Group index for WordPress category';
    $desc = 'Send the WordPress category to this group index in Google Analytics';
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
        'max'          => 100,
      ]
    );

    $name = 'group_index_woocommerce';
    $title = 'Group index for Woocommerce product category';
    $desc = 'Send the Woocommerce product category to this group index in Google Analytics';
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
        'max'          => 100,
      ]
    );

    $name = 'group_index_blog';
    $title = 'Group index for blog post category';
    $desc = 'Send the blog post category to this group index in Google Analytics';
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
        'max'          => 100,
      ]
    );
    
  }


  /**
   * Build the "Content grouping" section.
   **/

  function wpan_display_content_grouping_section () {

    echo "<p>Settings for the content grouping functionality</p>";

  }


  /**
   * Display the single fields in the "Content grouping" section.
   */   

  function wpan_display_group_index_wordpress ( $args ) {

    wpan_display_number_input ( $args );

  }

  function wpan_display_group_index_woocommerce ( $args ) {

    wpan_display_number_input ( $args );

  }

  function wpan_display_group_index_blog ( $args ) {

    wpan_display_number_input ( $args );

  }

?>