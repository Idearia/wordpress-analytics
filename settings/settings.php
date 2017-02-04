<?php

  /**
   * Build the settings page for the Wordpress Analytics plugin,
   * using the settings library in settings.php.
   *
   * Created by Guido W. Pettinari on 28.01.2016.
   * Part of Wordpress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */  


  /* Include the settings library */
  require_once ( WPAN_PLUGIN_DIR . 'settings/settings_library.php' );


  // ==================================================================================
  // =                              Settings structure                                =
  // ==================================================================================

  /**
   * Structure of the settings page; add menu pages, sections and fields
   * here.
   *
   * The structure is stored in a hierarchical array where each entry is a menu
   * item in the left sidebar of the Wordpress admin section. Each menu item
   * comes with its page, which is further subdivided in sections, each with
   * its tab. The sections contain the fields, that is, the options that the
   * user will set.
   * 
   * You can add entries to this array to include new menu pages, sections
   * and option fields.
   * 
   * For each menu page that you add to the $wpan_menu_structure array,
   * you also need to write a wpan_display_<menu_id>_menu_page() function
   * to display the menu page.
   *   
   * For each section that you add to the $wpan_menu_structure array,
   * you also need to write two functions in a new file:
   *   1) wpan_register_<section_name>() to populate the section with settings,
   *   2) wpan_display_<section_name>() to render the section in HTML.
   * 
   *   Similarly, for each field that you add to $wpan_menu_structure,
   * remember to:
   *   1) add the field to the function wpan_register_<section_name>(),
   *   2) implement the function wpan_display_<field_name>() to render the field,
   *   3) sanitize the user input for the field in wpan_sanitize_options(), unless
   *      it is a checkbox.
   * 
   * A top-level menu in our structure looks just like a submenu with these extra
   * fields:
   * 
   *   'display_submenu' => Title shown in the menu sidebar
   *   'icon' => Icon to use for the 'dashicons-admin-generic',
   *   'position' => 99,          
   * 
   */

  $wpan_menu_structure = [
      'menus' => [
          /* Top level menu */
          'wordpress_analytics' => [
              'parent_slug' => '',
              'slug' => 'wordpress_analytics',
              'display' => 'Wordpress Analytics Settings',
              'display_menu' => 'Wordpress Analytics',
              'func_display' => 'wpan_display_general_menu_page',
              'capability' => 'administrator',
              'sections' => [
                  'general_settings' => [
                      'id' => 'general_settings',
                      'name' => 'general_settings_section',
                      'display' => "General settings",
                      'page' => 'wpan_general_settings_page',
                      'group' => 'wpan_general_settings_option_group',
                      'db_key' => 'wpan:general_settings',
                      'visible' => true,
                      'func_register' => 'wpan_register_general_settings_fields',
                      'func_display' => 'wpan_display_general_settings_section',
                      'display_section_title' => false,
                      'fields' => [
                          'tracking_uid' => '',
                          'scroll_tracking' => '0',
                          'content_grouping' => '0',
                          'call_tracking' => '0',
                          'form_tracking' => '0',
                          'email_tracking' => '0',
                      ],
                  ],
                  'content_grouping' => [
                      'id' => 'content_grouping',
                      'name' => 'content_grouping_section',
                      'display' => 'Content grouping',
                      'page' => 'wpan_content_grouping_page',
                      'group' => 'wpan_content_grouping_option_group',
                      'db_key' => 'wpan:content_grouping',
                      'visible' => true,
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
                      'visible' => true,
                      'db_key' => 'wpan:scroll_tracking',
                      'func_register' => 'wpan_register_scroll_tracking_fields',
                      'func_display' => 'wpan_display_scroll_tracking_section',
                      'fields' => [
                          'pixel_threshold' => '300',
                          'time_threshold' => '60',
                      ],
                  ],
                  'call_tracking' => [
                      'id' => 'call_tracking',
                      'name' => 'call_tracking_section',
                      'display' => 'Call tracking',
                      'page' => 'wpan_call_tracking_page',
                      'group' => 'wpan_call_tracking_option_group',
                      'visible' => true,
                      'db_key' => 'wpan:call_tracking',
                      'func_register' => 'wpan_register_call_tracking_fields',
                      'func_display' => 'wpan_display_call_tracking_section',
                      'fields' => [
                          'phone_regex_include_pattern' => '',
                          'phone_regex_exclude_pattern' => '',
                          'detect_phone_numbers' => '1',
                      ],
                  ],
                  // 'form_tracking' => [
                  //     'id' => 'form_tracking',
                  //     'name' => 'form_tracking_section',
                  //     'display' => 'Form tracking',
                  //     'page' => 'wpan_form_tracking_page',
                  //     'group' => 'wpan_form_tracking_option_group',
                  //     'visible' => true,
                  //     'db_key' => 'wpan:form_tracking',
                  //     'func_register' => 'wpan_register_form_tracking_fields',
                  //     'func_display' => 'wpan_display_form_tracking_section',
                  //     'fields' => [
                  //         'form_include_id' => '',
                  //         'form_exclude_id' => '',
                  //     ],
                  // ],
                  'hidden_settings' => [
                      'id' => 'hidden_settings',
                      'name' => 'hidden_settings_section',
                      'display' => 'Hidden settings',
                      'page' => 'wpan_hidden_settings_page',
                      'group' => 'wpan_hidden_settings_option_group',
                      'visible' => false,
                      'db_key' => 'wpan:hidden_settings',
                      'func_register' => 'wpan_register_hidden_settings_fields',
                      'func_display' => 'wpan_display_hidden_settings_section',
                      'fields' => [
                          'enable_json_folder' => '0',
                      ],
                  ],
              ],
              /* Specific to top-level menu */
              'display_submenu' => 'General',
              'icon' => 'dashicons-admin-generic',
              'position' => 99,          
          ],
          /* Sub menus */
          'advanced_settings' => [
              'parent_slug' => 'wordpress_analytics',
              'slug' => 'advanced_settings_menu',
              'display' => 'Advanced Settings',
              'display_menu' => 'Advanced',
              'func_display' => 'wpan_display_advanced_menu_page',
              'capability' => 'administrator',
              'sections' => [
                  'advanced_settings' => [
                      'id' => 'advanced_settings',
                      'name' => 'advanced_settings_section',
                      'display' => 'Advanced settings',
                      'page' => 'wpan_advanced_settings_page',
                      'group' => 'wpan_advanced_settings_option_group',
                      'visible' => true,
                      'db_key' => 'wpan:advanced_settings',
                      'func_register' => 'wpan_register_advanced_settings_fields',
                      'func_display' => 'wpan_display_advanced_settings_section',
                      'display_section_title' => false,
                      'fields' => [
                          'cross_domain_support' => '0',
                          'enhanced_link_attribution' => '0',
                          'network_mode' => '0',
                          'debug' => '0',
                      ],
                  ],
                  'custom_code' => [
                      'id' => 'custom_code',
                      'name' => 'custom_code_section',
                      'display' => 'Custom code',
                      'page' => 'wpan_custom_code_page',
                      'group' => 'wpan_custom_code_option_group',
                      'visible' => true,
                      'db_key' => 'wpan:custom_code',
                      'func_register' => 'wpan_register_custom_code_fields',
                      'func_display' => 'wpan_display_custom_code_section',
                      'fields' => [
                          'custom_code' => '',
                      ],
                  ],
              ],
          ],
      ],
  ];



  // ==================================================================================
  // =                               Register settings                                =
  // ==================================================================================

  add_action('admin_init', 'wpan_initialise_settings');



  // ==================================================================================
  // =                                 Add menu pages                                 =
  // ==================================================================================  

  /* Extract the options from the database. Must be after the declaration
  of $wpan_menu_structure. */
  $options = wpan_get_options ();
  
  /* In network mode the plugin's settings are controlled only by the network
  super admninstrator. This means that the settings menu is visible only at
  the network level, and not at the site level */
  if ( is_multisite() && wpan_is_network_mode() ) {
    
    /* As for now (03-02-2017) I haven't found a simple way to set the network 
    settings (wp_sitemeta) via the Settings API. The problem is that the Settings API
    uses options.php as a callback for the input form, but options.php is not available
    on the network admin pages. As a result, although the settings menu correctly
    shows in the network admin menu, if you change a plugin setting in the network
    admin menu, you end up with an Internal Server Error. */ 
    /* Therefore, for now, in network mode we show the settings menu only on the
    main site. Every change made to the plugin settings on the main blog will
    affect the plugin behaviour on all sites. */
    /* TODO: Try to implement the solution is shown here:
    http://wordpress.stackexchange.com/a/72503/86662. See also the solution
    proposed by @convissor on https://core.trac.wordpress.org/ticket/15691
    (i.e. copy from http://wordpress.org/extend/plugins/login-security-solution/) */
    
    /* This line should be commented until we solve the problem described above */
    // add_action('network_admin_menu', 'wpan_add_menu_pages'); 

    if ( wpan_is_main_blog() ) {

      add_action('admin_menu', 'wpan_add_menu_pages'); 

    }
    
  }

  /* In normal mode, every site can set its own settings */
  else {
    
    add_action('admin_menu', 'wpan_add_menu_pages');
    
  }



  // ==================================================================================
  // =                                Display menus                                   =
  // ==================================================================================

  /**
   * Display the page for the Wordpress Analytics general menu
   */

  function wpan_display_general_menu_page () {
    
    global $wpan_menu_structure;
    
    wpan_display_tabbed_menu_page ( $wpan_menu_structure['menus']['wordpress_analytics'] );
    
  }


  /**
   * Display the page for the Wordpress Analytics advanced menu
   */

  function wpan_display_advanced_menu_page () {
    
    global $wpan_menu_structure;
    
    wpan_display_tabbed_menu_page ( $wpan_menu_structure['menus']['advanced_settings'] );
    
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

      $error_code = '';

      if ( isset ( $input[ $key ] ) ) {
        
        /* If the user input something in the field, check if makes sense */
        if ( ! empty ( $input[ $key ] ) ) {

          switch ($key) {

            case 'tracking_uid':
              $tracking_uid_regex = '/(UA|YT|MO)-\d+-\d+/i';
              if ( ! preg_match( $tracking_uid_regex, $value ) ) {
                $error_code = 'wrong-tracking-uid';
                $error_message = 'The tracking ID should be of the form UA-XXXXXXXX-Y';
                $error_type = 'error';
              }
              break;

            case 'group_index_wordpress':
              if ( $value < 0 ) {
                $error_code = 'negative-group-index-wordpress';
                $error_message = 'Wordpress group index must be positive';
                $error_type = 'error';
              }
              break;

            case 'group_index_woocommerce':
              if ( $value < 0 ) {
                $error_code = 'negative-group-index-woocommerce';
                $error_message = 'Woocommerce group index must be positive';
                $error_type = 'error';
              }
              break;

            case 'group_index_blog':
              if ( $value < 0 ) {
                $error_code = 'negative-group-index-blog';
                $error_message = 'Blog group index must be positive';
                $error_type = 'error';
              }
              break;

            case 'pixel_threshold':
              if ( $value < 0 ) {
                $error_code = 'negative-pixel-threshold';
                $error_message = 'The pixel threshold must be positive';
                $error_type = 'error';
              }
              break;

            case 'time_threshold':
              if ( $value < 0 ) {
                $error_code = 'negative-time-threshold';
                $error_message = 'The time threshold must be positive';
                $error_type = 'error';
              }
              break;

            case 'phone_exclude_regex_pattern':

            case 'phone_include_regex_pattern':
              if ( $value && preg_match("/$value/", null) === false ) {
                /* If the regex is not legit, find out what the error message is */
                $regex_error_msg = '';
                foreach ( get_defined_constants(true)['pcre'] as $k => $v ) {
                  if ( strstr ($k, "ERROR") && $v == preg_last_error() ) {
                    $regex_error_msg = $k;
                  }
                }
                $error_code = 'phone-regex-not-valid';
                $error_message = "Error in '$key'";
                /* It might happen that preg_last_error() returns an OK status code even if
                preg_match() has failed; see https://akrabat.com/preg_last_error-returns-no-
                error-on-preg_match-failure/ for why this happens */
                if ($regex_error_msg)
                  $error_message . ': ' . $regex_error_msg;
                $error_type = 'error';
              }
              elseif ( strlen ( $value ) > WPAN_MAX_REGEX_LENGTH ) {
                $error_code = 'phone-regex-too-long';
                $error_message = "Regex ($key) must be shorter than" . WPAN_MAX_REGEX_LENGTH . " characters for security reasons";
                $error_type = 'error';
              }
              break;

            case 'cross_domain_support':
              /* Vertical booking support requires enhanced link attribution (DISABLED) */
              // $output['enhanced_link_attribution'] = true;
              // $error_code = 'set-enhanced-link-attribution';
              // $error_message = 'Enhanced link attribution was turned on to allow Vertical Booking support';
              // $error_type = 'updated';
              break;

            case 'custom_code':
              /* TODO: add syntax & malware checks */
              break;

          } // switch

        } // if not empty


        /* Return an error message if the input didn't respect our standards. Note
        that if $error_type == 'updated', the message will be a (green) notice rather
        than a (red) error message.  */
        if ( ! empty ( $error_code ) ) {

          add_settings_error(
            $key,
            esc_attr ( $error_code ),
            $error_message . '.',
            $error_type);

        }
        
        /* If the error was not severe, store the input value in the output
        array */
        if ( empty ( $error_code ) || $error_type !== 'error' ) {

          $output[ $key ] = $value;

        }

        /* Strip all HTML and PHP tags and properly handle quoted strings.
        Thanks to Tom McFarlin: http://goo.gl/i0jL7t */
        $dont_strip = [
          'phone_regex_include_pattern',
          'phone_regex_exclude_pattern',
          'custom_code',
        ];
        if ( isset ( $output[$key] ) && ! in_array ( $key, $dont_strip ) )
          $output[$key] = strip_tags( stripslashes( $output[$key] ) );        
        
      } // if isset (input[$key])

    } // foreach

    return $output;
    
  } // wpan_sanitize_options
