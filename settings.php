<?php

  /**
   * Build the settings page for the Wordpress Analytics plugin.
   * 
   * We follow the gret approach of Stack Exchange user toscho from
   * http://wordpress.stackexchange.com/a/100137/86662.
   *
   * Created by Guido W. Pettinari on 28.01.2016.
   * Part of Wordpress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */  


  // ==================================================================================
  // =                              Settings' settings                                =
  // ==================================================================================

  /* Slug of the settings page */
  $wpan_menu_slug = 'wordpress-analytics-settings';
  
  /* Sections in our settings page */
  $wpan_menu_structure = [
      'general_settings' => [
          'id' => 'general_settings',
          'name' => 'general_settings_section',
          'display' => "General settings",
          'page' => 'wpan_general_settings_page',
          'group' => 'wpan_general_settings_option_group',
          'db_key' => 'wpan:general_settings',
          'func_register' => 'wpan_register_general_settings_fields',
          'func_display' => 'wpan_display_general_settings_section',
          'fields' => [
              'tracking_uid' => '',
              'scroll_tracking'  => '0',
              'content_grouping'   => '0',
          ],
      ],
      'content_grouping' => [
          'id' => 'content_grouping',
          'name' => 'content_grouping_section',
          'display' => 'Content grouping',
          'page' => 'wpan_content_grouping_page',
          'group' => 'wpan_content_grouping_option_group',
          'db_key' => 'wpan:content_grouping',
          'func_register' => 'wpan_register_content_grouping_fields',
          'func_display' => 'wpan_display_content_grouping_section',
          'fields' => [
              'group_index_wordpress' => '1',
              'group_index_woocommerce' => '2',
              'group_index_blog' => '3',
          ],
      ],
      'scroll_tracking' => [
          'id' => 'scroll_tracking',
          'name' => 'scroll_tracking_section',
          'display' => 'Scroll tracking',
          'page' => 'wpan_scroll_tracking_page',
          'group' => 'wpan_scroll_tracking_option_group',
          'db_key' => 'wpan:scroll_tracking',
          'func_register' => 'wpan_register_scroll_tracking_fields',
          'func_display' => 'wpan_display_scroll_tracking_section',
          'fields' => [
              'pixel_threshold' => '300',
              'time_threshold' => '60',
          ],
      ],
      'advanced_settings' => [
          'id' => 'advanced_settings',
          'name' => 'advanced_settings_section',
          'display' => 'Advanced settings',
          'page' => 'wpan_advanced_settings_page',
          'group' => 'wpan_advanced_settings_option_group',
          'db_key' => 'wpan:advanced_settings',
          'func_register' => 'wpan_register_advanced_settings_fields',
          'func_display' => 'wpan_display_advanced_settings_section',
          'fields' => [
              'enhanced_link_attribution' => '0',
              'debug' => '0',
          ],
      ],
  ];

  /* What should we display as the title of a settings section? Leave blank
  to use the section's name */
  define (wpan_section_title, '<br>');


  // ==================================================================================
  // =                              Build settings page                               =
  // ==================================================================================

  /** Add the settings menu page */
  add_action('admin_menu', 'wpan_add_options_page');

  function wpan_add_options_page() {
    
    global $wpan_menu_slug;
    
    $page_title = 'Wordpress Analytics Settings';
    $menu_title = 'Wordpress Analytics';
    $capability = 'administrator';
    $function = 'wpan_display_options_page';
    $icon_url = 'dashicons-admin-generic';
    $position = 99;

    add_menu_page ($page_title,
                   $menu_title,
                   $capability,
                   $wpan_menu_slug,
                   $function,
                   $icon_url,
                   $position);
  }


  /** Build the settings menu page */
  function wpan_display_options_page() {

    global $wpan_menu_slug;
    global $wpan_menu_structure;

    /* Each section shall have its own tab */
    $tabs = $wpan_menu_structure;

    /* TODO: Include the jQuery to enable contextual popup help */

    /* Intercept the calls to add_settings_error() in wpan_sanitize_options(),
    and complain if the options are wrong. */
    settings_errors ();

    /* By default, we make the general settings tab active */
    $active_tab = $tabs['general_settings']['id'];

    /* Check which tab is active; we define the 'tab' query parameter ourselves below */
    if( isset( $_GET[ 'tab' ] ) )
      $active_tab = $_GET[ 'tab' ];
    
    ?>

    <div class="wrap">

        <h2><?php print $GLOBALS['title']; ?></h2>

        <h2 class='nav-tab-wrapper'>
          <?php
            foreach ($tabs as $tab) {
              $is_active = $tab['id'] == $active_tab ? 'nav-tab-active' : '';
              echo "<a href='?page=$wpan_menu_slug&tab=" . $tab['id'] . "' class='nav-tab $is_active'>" . $tab['display'] . "</a>\n";
            }
          ?>
        </h2>

        <form action="options.php" method="POST">

          <?php
          
            /* Prints out all settings sections added to the active tab */
            foreach ($tabs as $tab) {
              if ($active_tab == $tab['id']) {
                settings_fields( $tab['group'] );
                do_settings_sections( $tab['page'] );
                break;
              }
            }

            submit_button();
            
          ?>

        </form>
    </div>

    <?php

  }


  // ==================================================================================
  // =                               Register settings                                =
  // ==================================================================================

  add_action('admin_init', 'wpan_initialise_settings');

  /**
   * Build the internal structure of the settings menus, using $wpan_menu_structure
   * as a blueprint.
   *
   * This is a two-step process: first, the settings are registered so that they
   * can be modified in the database; second, the settings sections are defined
   * and populated with settings fields.
   *
   * This function allows for the display functions (ex. wpan_display_options_page
   * and wpan_display_general_settings_section) to correctly display the settings
   * pages. 
   */
  function wpan_initialise_settings() {

    global $wpan_menu_structure;

    foreach ($wpan_menu_structure as $section) {

      /* Register settings for the current section. This does two things:
      1) Add the database key to a whitelist of options that we are allowed to modify
      within our settings page via <form action="options.php">.
      2) Call the sanitize function given in the third argument every time the user
      submits the form. IMPORTANT: the sanitize function is called twice if the option
      is not yet registed in the database. */
      register_setting(
        $section['group'],            // group, used for settings_fields()
        $section['db_key'],           // option name, used as key in database
        'wpan_sanitize_options'       // sanitizion callback, called after each settings save
      );


      /* If the database key correponding to the current option does not exist in
      the database, create it and fill it with default values. Not doing so will
      force the user to submit all settings section singularly before being able
      to use the plugin. */
      if( false == get_option( $section['db_key'] ) ) {
        add_option( $section['db_key'], $section['fields'] );
      }


      /* Values displayed in the input form. If an option is not present in the database,
      then we display its default value. Should never happen because of the add_option
      above. */
      $displayed_values = shortcode_atts( $section['fields'], get_option( $section['db_key'] ) );


      /* Add the section */
      add_settings_section(
        $section['name'],
        wpan_section_title === '' ? $section['display'] : wpan_section_title,
        $section['func_display'],
        $section['page']
      );


      /* Add the fields to the section by calling the 'func_register' function
      for the considered section*/
      if ( ! is_callable ( $section['func_register'] ) ) {
        add_settings_error(
          $section['db_key'],
          "function-not-callable",
          "BUG! Function '" . $section['func_register'] . "' is not callable!");
      }      
      else { 
        call_user_func ($section['func_register'], $section, $displayed_values);
      }
      
    } // foreach ($section)
    
  } // wpan_initialise_settings


  // ----------------------------------------------------------------------------------
  // -                                 General settings                               -
  // ----------------------------------------------------------------------------------

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
        'value'        => esc_attr( $displayed_values[ $name ] ),
      ]
    );

    $name = 'content_grouping';
    $title = 'Enable content grouping?';
    $desc = 'Enable content grouping?';
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
        'value'        => esc_attr( $displayed_values[ $name ] ),
      ]
    );

    $name = 'scroll_tracking';
    $title = 'Enable scroll tracking?';
    $desc = 'Enable scroll tracking?';
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
        'value'        => esc_attr( $displayed_values[ $name ] ),
      ]
    );

  }


  // ----------------------------------------------------------------------------------
  // -                                 Content grouping                               -
  // ----------------------------------------------------------------------------------

  function wpan_register_content_grouping_fields( $section, $displayed_values ) {

    $name = 'group_index_wordpress';
    $title = 'Group index for Wordpress category';
    $desc = 'Send the Wordpress category to this group index in Google Analytics';
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
        'value'        => esc_attr( $displayed_values[ $name ] ),
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
        'value'        => esc_attr( $displayed_values[ $name ] ),
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
        'value'        => esc_attr( $displayed_values[ $name ] ),
        'min'          => 1,
        'max'          => 100,
      ]
    );
    
  }


  // ----------------------------------------------------------------------------------
  // -                                 Scroll tracking                                -
  // ----------------------------------------------------------------------------------

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
        'value'        => esc_attr( $displayed_values[ $name ] ),
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
        'value'        => esc_attr( $displayed_values[ $name ] ),
        'min'          => 1,
        'max'          => 10000,
      ]
    );
    
  }


  // ----------------------------------------------------------------------------------
  // -                                Advanced settings                               -
  // ----------------------------------------------------------------------------------

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
        'value'        => esc_attr( $displayed_values[ $name ] )
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
        'value'        => esc_attr( $displayed_values[ $name ] )
      ]
    );

  }



  // ==================================================================================
  // =                               Sanitize options                                 =
  // ==================================================================================

  /**
   * Sanitize the user's input.
   *
   * Check that the values filled by the user in the options form
   * make sense, making sure no harmful code is injected.
   *
   * The input is an associative array containing the key-value
   * pairs corresponding to the input forms filled by the user.
   * For fields left empty, $input[$key] is not set.
   *
   * Inspired by http://wordpress.stackexchange.com/a/100137/86662.
   */

  function wpan_sanitize_options ( $input ) {

    /* Initialise output array */
    $output = array ();

    /* Sanitize settings one by one */
    foreach ( $input as $key => $value ) {

      $error_type = '';

      if ( isset ( $input[ $key ] ) ) {
        
        /* If the user input something in the field, check if makes sense */
        if ( ! empty ( $input[ $key ] ) ) {

          switch ($key) {

            case 'tracking_uid':
              if ( strlen ( trim ( $value ) ) < 13 ) {
                $error_type = 'tracking-uid-too-short';
                $error_message = 'The tracking ID should be of the form UA-XXXXXXX-Y';
              }
              break;

            case 'group_index_wordpress':
              if ( $value < 0 ) {
                $error_type = 'negative-group-index-wordpress';
                $error_message = 'Wordpress group index must be positive';
              }
              break;

            case 'group_index_woocommerce':
              if ( $value < 0 ) {
                $error_type = 'negative-group-index-woocommerce';
                $error_message = 'Woocommerce group index must be positive';
              }
              break;

            case 'group_index_blog':
              if ( $value < 0 ) {
                $error_type = 'negative-group-index-blog';
                $error_message = 'Blog group index must be positive';
              }
              break;

            case 'pixel_threshold':
              if ( $value < 0 ) {
                $error_type = 'negative-pixel-threshold';
                $error_message = 'The pixel threshold must be positive';
              }
              break;

            case 'time_threshold':
              if ( $value < 0 ) {
                $error_type = 'negative-time-threshold';
                $error_message = 'The time threshold must be positive';
              }
              break;

          } // switch

        } // if not empty


        /* Return an error message if the input given for this key didn't
        respect our standards */

        if ( empty ( $error_type ) ) {

          $output[ $key ] = $value;

        }

        /* Return an error message if the input didn't respect our standards */
        else {

          add_settings_error(
            $key,
            esc_attr ( $error_type ),
            $error_message . '.');

        }
        
        /* Strip all HTML and PHP tags and properly handle quoted strings.
        Thanks to Tom McFarlin: http://goo.gl/i0jL7t */
        if ( isset ( $output[$key] ) )
          $output[$key] = strip_tags( stripslashes( $output[$key] ) );        
        
      } // if isset (input[$key])

    } // foreach

    return $output;
    
  }



  // ==================================================================================
  // =                                Display functions                               =
  // ==================================================================================

  /** Generic displaying functions */

  function wpan_display_text_input ( $args ) {

    // /* Print description */
    // echo '<p>' . $args['desc'] . '</p>';

    /* Create a text input */
    printf(
        '<input type="text" name="%1$s[%2$s]" id="%2$s" value="%3$s">',
        $args['db_key'],
        $args['name'],
        $args['value']
    );
  }

  function wpan_display_number_input ( $args ) {

    /* Print description
    TODO: should use a question mark popup */
    // echo '<p>' . $args['desc'] . '</p>';

    /* Create a text input */
    printf(
        '<input type="number" min="%1$s" max="%2$s" name="%3$s[%4$s]" id="%4$s" value="%5$s" class="%6$s">',
        $args['min'],
        $args['max'],
        $args['db_key'],
        $args['name'],
        $args['value'],
        $args['desc']
    );
  }

  function wpan_display_checkbox_input ( $args ) {

    /* Print description
    TODO: should use a question mark popup */
    // echo '<p>' . $args['desc'] . '</p>';

    /* Create a checkbox input */
    $checked = checked('1', $args['options_vals'][$args['name']], false);
    printf(
        '<input type="checkbox" name="%1$s[%2$s]" id="%2$s" value="1" %3$s>',
        $args['db_key'],
        $args['name'],
        $checked
    );
  }



  /** display the "General settings" section */
  function wpan_display_general_settings_section () {

    echo "<p>General settings</p>";

  }

  function wpan_display_tracking_uid ( $args ) {

    wpan_display_text_input ($args);

  }

  function wpan_display_content_grouping ( $args ) {

    wpan_display_checkbox_input ($args);

  }

  function wpan_display_scroll_tracking ( $args ) {

    wpan_display_checkbox_input ($args);

  }


  /** display the "Content grouping" section */
  function wpan_display_content_grouping_section () {

    echo "<p>Settings for the content grouping functionality</p>";

  }

  function wpan_display_group_index_wordpress ( $args ) {

    wpan_display_number_input ( $args );

  }

  function wpan_display_group_index_woocommerce ( $args ) {

    wpan_display_number_input ( $args );

  }

  function wpan_display_group_index_blog ( $args ) {

    wpan_display_number_input ( $args );

  }

  /** display the "Scroll tracking" section */
  function wpan_display_scroll_tracking_section () {

    echo "<p>Settings for the scroll tracking functionality.</p>";
    $desc = "Parameters set here will be ignored if you have Javascript caching enabled.";
    echo "<p>$desc</p>";

  }

  function wpan_display_pixel_threshold ( $args ) {

    wpan_display_number_input ( $args );

  }

  function wpan_display_time_threshold ( $args ) {

    wpan_display_number_input ( $args );

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

