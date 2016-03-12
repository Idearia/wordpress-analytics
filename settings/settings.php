<?php

  /**
   * Library to build a plugin settings page using Wordpress
   * Settings API, with tabs support.
   *
   * I've never tested the library with a theme, but it should
   * work with minor modifications, if any.
   * 
   * Thanks to the following people  who inspired this library:
   * - Stack Exchange user toscho:
   *   http://wordpress.stackexchange.com/a/100137/86662
   * - The great tutorial series by Tom McFarlin:
   *   http://code.tutsplus.com/series/the-complete-guide-to
   *   -the-wordpress-settings-api--cms-624
   *
   * Created by Guido W. Pettinari on 28.01.2016.
   * Part of Wordpress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */  


  // ==================================================================================
  // =                              Access the options                                =
  // ==================================================================================

  /**
   * Return all options for the plugin.
   *
   * The options are retrieved from the database. If the database does not contain
   * them yet, the returned array will be empty.
   */

  function wpan_get_options () {
    
    global $wpan_menu_structure;
    
    $options = [];
    
    foreach ( $wpan_menu_structure as $section ) {

      $section_options = get_option ( $section['db_key'] );

      if ($section_options)
        $options = array_merge ( $options, $section_options );

    }
    
    return $options;
    
  }



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
              $is_hidden = $tab['visible'] == true ? '' : "hidden='hidden'";
              echo "<a href='?page=$wpan_menu_slug&tab=" . $tab['id'] . "' class='nav-tab $is_active' $is_hidden>" . $tab['display'] . "</a>\n";
            }
          ?>
          <script> jQuery("a.nav-tab[hidden='hidden']").hide() </script>
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
        wpan_section_title === '%name%' ? $section['display'] : wpan_section_title,
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


  
  // ==================================================================================
  // =                                Display functions                               =
  // ==================================================================================

  /** Generic displaying functions */

  function wpan_display_text_input ( $args ) {

    // /* Print description */
    // echo '<p>' . $args['desc'] . '</p>';

    /* Create a text input */
    printf(
        '<input type="text" name="%1$s[%2$s]" id="%2$s" value="%3$s" maxlength="%4$s" size="%5$s">',
        esc_attr ($args['db_key']),
        esc_attr ($args['name']),
        esc_attr ($args['value']),
        esc_attr ($args['maxlength']),
        esc_attr ($args['size'])
    );
  }

  function wpan_display_number_input ( $args ) {

    /* Print description
    TODO: should use a question mark popup */
    // echo '<p>' . $args['desc'] . '</p>';

    /* Create a text input */
    printf(
        '<input type="number" name="%1$s[%2$s]" id="%2$s" value="%3$s" min="%4$s" max="%5$s">',
        esc_attr ($args['db_key']),
        esc_attr ($args['name']),
        esc_attr ($args['value']),
        esc_attr ($args['min']),
        esc_attr ($args['max'])
    );
  }

  function wpan_display_checkbox_input ( $args ) {

    /* Print description
    TODO: should use a question mark popup */
    // echo '<p>' . $args['desc'] . '</p>';

    /* Create a checkbox input */
    $checked = checked('1', $args['value'], false);
    printf(
        '<input type="checkbox" name="%1$s[%2$s]" id="%2$s" value="1" %3$s>',
        esc_attr ($args['db_key']),
        esc_attr ($args['name']),
        esc_attr ($checked)
    );
  }


  
  // ==================================================================================
  // =                            Include section pages                               =
  // ==================================================================================

  foreach ($wpan_menu_structure as $section) {

    require_once ( WPAN_PLUGIN_DIR . 'settings/' . $section['id'] . '.php' );
    
  }
