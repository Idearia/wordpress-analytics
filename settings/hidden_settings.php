<?php

  /**
   * Register the fields contained in the the "Hidden settings" section.
   */

  function wpan_register_hidden_settings_fields( $section, $displayed_values ) {

    $name = 'enable_json_folder';
    $title = 'Look for JSON meta boxes';
    $link = '<a href="http://www.advancedcustomfields.com/resources/local-json/">ACF JSON Format</a>';
    $desc = "Include the meta boxes found in '" . WPAN_PLUGIN_DIR .  "acf'; these must be in the  " . $link;
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


  /**
   * Build the "Hidden settings" section.
   **/

  function wpan_display_hidden_settings_section () {

    echo "<p>Really advanced settings that should be left untouched</p>";

  }


  /**
   * Display the single fields in the "Custom code" section.
   */

  function wpan_display_enable_json_folder ( $args ) {

    wpan_display_checkbox_input ( $args );

  }

?>