<?php

  /**
   * Register the fields contained in the the "Advanced settings" section.
   */

  function wpan_register_advanced_settings_fields( $section, $displayed_values ) {

    /* Show the Network Mode setting only for multisite installations and
    only in the main site */
    if ( is_multisite() && wpan_is_main_blog() ) {

      // wpan_register_setting_field( $section, 'network_mode' );

      $name = 'network_mode';
      $title = 'Network Mode';
      $desc = 'Manage plugin options only at the network level, preventing site admins to change them. ';
      $desc .= 'IMPORTANT: Until I find a way to use the Settings API to set options in the network admin (ex. ';
      $desc .= 'using http://wordpress.stackexchange.com/a/72503/86662), the plugin options will be set and read ';
      $desc .= 'from the main blog admin page rather than the network admin page.';
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

    $name = 'create_tracker';
    $title = 'Create tracker?';
    $desc = "By default WordPress analytics will create a GA tracker named 'ga' that will be used to send ";
    $desc .= "both events and pageviews to Google Analytics. If you uncheck this box, the plugin will ";
    $desc .= "only send the events, without creating neither the tracker nor the pageview. If you do so, ";
    $desc .= "make sure that a tracker is created (either manually or by another plugin) before WordPress ";
    $desc .= "Analytics is loaded, and that a pageview is sent after that.";
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

    $name = 'tracker_name';
    $title = 'Name of the GA tracker to be used';
    $desc = "By default we use 'ga' for the tracker name; change it to integrate with other ";
    $desc .= "Analytics plugins. For example, to use the plugin 'WooCommerce Google Analytics Pro ";
    $desc .= "together with WordPress Analytics, set __gaTracker in this field, and uncheck the ";
    $desc .= "'Create Tracker' option.";
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
        'maxlength'    => '50',
        'label_for'    => $name,
      ]
    );

    $name = 'enhanced_link_attribution';
    $title = 'Enhanced Link Attribution';
    $desc = 'Enable <a href="https://support.google.com/analytics/answer/2558867">Enhanced Link Attribution</a>. ';
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

    $name = 'cross_domain_support';
    $title = 'Cross Domain support';
    $desc = 'Enable support for cross-domain tracking as described in <a href="https://developers.google.com/analytics/devguides/collection/analyticsjs/linker#bi-directional_cross-domain_tracking">';
    $desc .= "in Google's documentation</a>? This feature just loads the linker plugin; if you also need ";
    $desc .= "to call the 'autoLink' function, use the 'Custom Code' feature in the Advanced Settings.";
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


  /**
   * Build the "Advanced settings" section.
   **/

  function wpan_display_advanced_settings_section () {

  }


  /**
   * Display the single fields in the "Advanced settings" section.
   */   

  function wpan_display_create_tracker ( $args ) {

    wpan_display_checkbox_input ( $args );
  
  }

  function wpan_display_tracker_name ( $args ) {

    wpan_display_text_input ( $args );

  }

  function wpan_display_debug ( $args ) {

    wpan_display_checkbox_input ( $args );

  }

  function wpan_display_enhanced_link_attribution ( $args ) {

    wpan_display_checkbox_input ( $args );

  }

  function wpan_display_cross_domain_support ( $args ) {

    wpan_display_checkbox_input ( $args );

    ?>

    <script>

    /* DISABLED */
    // /* Script to make sure that enhanced_link_attribution is turned on 
    // whenever the cross_domain_support is on */
    //
    // var vb_checkbox = jQuery('input#cross_domain_support');
    // var la_checkbox = jQuery('input#enhanced_link_attribution');
    //
    // vb_checkbox.click (function () {
    //
    //   if (vb_checkbox.is(':checked')) {
    //
    //     la_checkbox.prop('checked', true);
    //     la_checkbox.prop('readonly', true);
    //
    //   }
    //   else {
    //
    //     la_checkbox.prop('readonly', false);
    //
    //   }
    //
    // });
    //
    // la_checkbox.click (function() {
    //
    //   if (vb_checkbox.is(':checked')) {
    //     alert ('Enhanced Link Attribution cannot be disabled when Vertical Booking support is turned on');
    //     return false;
    //   }
    //
    // });

    </script>
    
    <?php

  }

  function wpan_display_network_mode ( $args ) {

    wpan_display_checkbox_input ( $args );

  }

?>