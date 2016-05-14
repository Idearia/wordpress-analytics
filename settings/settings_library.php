<?php

  /**
   * Library to build a plugin settings page using Wordpress
   * Settings API, with tabs support.
   *
   * I've never tested the library with a theme, but it should
   * work with minor modifications, if any.
   *
   * Refer to https://codex.wordpress.org/Administration_Menus
   * for a list of slugs for the default admin menu, ex.
   * -> For Tools: add_submenu_page('tools.php',...)
   * -> For Settings: add_submenu_page('options-general.php',...)
   *
   * I wish to thank to the following people who inspired this
   * library:
   * - The great tutorial series by Tom McFarlin:
   *   http://code.tutsplus.com/series/the-complete-guide-to
   *   -the-wordpress-settings-api--cms-624
   * - Stack Exchange user toscho:
   *   http://wordpress.stackexchange.com/a/100137/86662
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

    foreach ( $wpan_menu_structure['menus'] as $menu ) {

      foreach ( $menu['sections'] as $section ) {

        $section_options = get_option ( $section['db_key'] );

        if ($section_options)
          $options = array_merge ( $options, $section_options );

      }

    }

    return $options;

  }



  // ==================================================================================
  // =                                 Add menu pages                                 =
  // ==================================================================================

  /**
   * Add the menu pages to the admin menu.
   */
  add_action('admin_menu', 'wpan_add_menu_pages');

  function wpan_add_menu_pages() {

    global $wpan_menu_structure;

    foreach ( $wpan_menu_structure['menus'] as $menu ) {

      /* If the menu page belongs to an existing menu, then add it to that menu */

      if ( $menu['parent_slug'] ) {

        add_submenu_page (
          $menu[ 'parent_slug' ],
          $menu[ 'display' ],
          $menu[ 'display_menu' ],
          $menu[ 'capability' ],
          $menu[ 'slug' ],
          $menu[ 'func_display' ]
        );

      }

      /* Otherwise, add it to the top level */

      else {

        add_menu_page (
          $menu[ 'display' ],
          $menu[ 'display_menu' ],
          $menu[ 'capability' ],
          $menu[ 'slug' ],
          $menu[ 'func_display' ],
          $menu[ 'icon' ],
          $menu[ 'position' ]
        );

        /* The add_menu_page() creates a menu item in the sidebar and a submenu page
        with the same name. We rename the submenu page to whatever is specified in
        $menu[ 'display_submenu' ] by calling add_submenu_page(), with the parent_slug
        argument equal to the menu slug argument */

        add_submenu_page (
          $menu[ 'slug' ], /* parent slug */
          $menu[ 'display' ],
          $menu[ 'display_submenu' ],
          $menu[ 'capability' ],
          $menu[ 'slug' ],
          $menu[ 'func_display' ]
        );

      }
    }
  } // wpan_add_menu_pages


  // ==================================================================================
  // =                              Menu page templates                               =
  // ==================================================================================

  /**
   * Display a tabbed menu page where each tab corresponds to a section.
   *
   * Takes as only argument a $menu element of a $wpan_menu_structure.
   */

  function wpan_display_tabbed_menu_page ( $menu ) {

    /* Each section shall have its own tab */
    $tabs = $menu['sections'];

    /* TODO: Include the jQuery to enable contextual popup help */

    /* Intercept the calls to add_settings_error() in wpan_sanitize_options(),
    and complain if the options are wrong. Otherwise show a nice "Settings
    saved" notice. TODO: It seems this call is unnecessary when dealing with
    a submenu; actually, including it will lead to duplicate notices! */
    settings_errors ();

    /* By default, we set the first tab to be the active one */
    reset( $tabs );
    $first_key = key ( $tabs );
    $active_tab = $tabs[ $first_key ]['id'];

    /* Check which tab is active; we define the 'tab' query parameter ourselves below */
    if( isset( $_GET[ 'tab' ] ) )
      $active_tab = $_GET[ 'tab' ];

    ?>

    <div class="wrap">

        <h2><?php print $GLOBALS['title']; ?></h2>

        <?php if ( count ($tabs ) > 1 ): ?>

        <h2 class='nav-tab-wrapper'>
          <?php
            foreach ($tabs as $tab) {
              $is_active = $tab['id'] == $active_tab ? ' nav-tab-active' : '';
              $is_hidden = $tab['visible'] == true ? '' : ' hidden="hidden"';
              echo "<a href='?page=" . $menu['slug'] . "&tab=" . $tab['id'] .
                "' class='nav-tab$is_active'$is_hidden>" . $tab['display'] . "</a>\n";
            }
          ?>
          <script> jQuery("a.nav-tab[hidden='hidden']").hide(); </script>
        </h2>

        <?php endif ?>

        <form action="options.php" method="POST">

          <?php

            /* Prints out all the settings sections that belong to the active tab */
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
   * This function allows for the display functions (ex. wpan_display_tabbed_page
   * and wpan_display_general_settings_section) to correctly display the settings
   * pages.
   */
  function wpan_initialise_settings() {

    global $wpan_menu_structure;

    foreach ( $wpan_menu_structure['menus'] as $menu ) {

      foreach ( $menu['sections'] as $section ) {

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
        $show_section_title = isset( $section['display_section_title'] ) && $section['display_section_title'];

        add_settings_section(
          $section['name'],
          $show_section_title ? $section['display'] : '',
          $section['func_display'],
          $section['page']
        );


        /* Register the fields in the section by calling the 'func_register_'
        function for the considered section.

        In the Settings API language, a field is a single option. Registering
        a field means telling Wordpress how to build the HTML for that field
        in the settings page, and where to store/retrieve the value of the
        field in the database. This is what the func_register_ functions do. */

        require_once ( WPAN_PLUGIN_DIR . 'settings/' . $section['id'] . '.php' );

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

    } // foreach ($menu)

  } // wpan_initialise_settings



  // ==================================================================================
  // =                                Display functions                               =
  // ==================================================================================


  /**
   * Display a single-line input field (<input type="text">).
   */
  function wpan_display_text_input ( $args ) {

    // /* Print description */
    // echo '<p>' . $args['desc'] . '</p>';

    /* Create a text input */
    printf(
        '<input type="text" name="%1$s[%2$s]" id="%2$s" value="%3$s" maxlength="%4$s" size="%5$s">',
        esc_attr( $args['db_key'] ),
        esc_attr( $args['name'] ),
        esc_attr( $args['value'] ),
        esc_attr( $args['maxlength'] ),
        isset( $args['size'] ) ? esc_attr( $args['size'] ) : ''
    );
  }


  /**
   * Display a number input field (<input type="number">).
   */
  function wpan_display_number_input ( $args ) {

    /* Print description
    TODO: should use a question mark popup */
    // echo '<p>' . $args['desc'] . '</p>';

    /* Create a text input */
    printf(
        '<input type="number" name="%1$s[%2$s]" id="%2$s" value="%3$s" min="%4$s" max="%5$s">',
        esc_attr( $args['db_key'] ),
        esc_attr( $args['name'] ),
        esc_attr( $args['value'] ),
        esc_attr( $args['min'] ),
        esc_attr( $args['max'] )
    );
  }


  /**
   * Display a checkbox input field (<input type="checkbox">).
   */
  function wpan_display_checkbox_input ( $args ) {

    /* Print description
    TODO: should use a question mark popup */
    // echo '<p>' . $args['desc'] . '</p>';

    /* Create a checkbox input */
    $checked = checked('1', $args['value'], false);
    printf(
        '<input type="checkbox" name="%1$s[%2$s]" id="%2$s" value="1" %3$s>',
        esc_attr( $args['db_key'] ),
        esc_attr( $args['name'] ),
        esc_attr( $checked )
    );
  }


  /**
   * Display a multi-line input field (<textarea>).
   */
  function wpan_display_textarea_input ( $args ) {

    /* Print description
    TODO: should use a question mark popup */
    // echo '<p>' . $args['desc'] . '</p>';

    /* Create a textarea input */
    printf(
        '<textarea name="%1$s[%2$s]" id="%2$s" rows="%4$s" cols="%5$s" placeholder="%6$s">%3$s</textarea>',
        esc_attr( $args['db_key'] ),
        esc_attr( $args['name'] ),
        esc_textarea( $args['value'] ),
        esc_attr( $args['rows'] ),
        esc_attr( $args['cols'] ),
        esc_attr( $args['placeholder'] )
    );
  }



  /**
   * Display a multi-line input field meant to insert PHP & HTML code.
   *
   * If available, we use the great CodeMirror library to enable syntax
   * highlighting. Otherwise, we use the standard <textarea> input
   * element.
   *
   * CodeMirror can be found at https://codemirror.net.
   */
  function wpan_display_code_input ( $args ) {

    /* Print description
    TODO: should use a question mark popup */
    // echo '<p>' . $args['desc'] . '</p>';

    /* Show a simple textarea input */
    printf(
        '<textarea id=custom_code name="%1$s[%2$s]" id="%2$s" rows="%4$s" cols="%5$s" placeholder="%6$s">%3$s</textarea>',
        esc_attr( $args['db_key'] ),
        esc_attr( $args['name'] ),
        htmlspecialchars( $args['value'] ), /* Escape HTML special characters (',",&,<,>) */
        esc_attr( $args['rows'] ),
        esc_attr( $args['cols'] ),
        esc_attr( $args['placeholder'] )
    );

    if ( WPAN_DO_SYNTAX_HIGHLIGHTING ) {

    ?>

  <script>
  var myCodeMirror = CodeMirror.fromTextArea(document.getElementById('custom_code'), {
      mode:  "php",
      smartIndent: true,
      lineNumbers: true,
  });
  </script>

    <?php

    }

  }

