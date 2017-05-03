<?php

  /**
   * Library to build a plugin settings page using WordPress
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
   * Part of WordPress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */


  // ==================================================================================
  // =                              Access the options                                =
  // ==================================================================================

  /**
   * Return all plugin options stored in the database.
   *
   * The options are retrieved from the database. If the database does not
   * contain them yet, the returned array will be empty.
   *
   */
  function wpan_get_options () {

    /* Load the plugin's structure if it's not available */
    if ( ! array_key_exists( 'wpan_menu_structure', $GLOBALS ) ) {
      wpan_load_menu_structure( WPAN_CONFIG_FILE );
    }

    /* Make the plugin's structure available in the current scope */
    global $wpan_menu_structure;

    $options = [];

    if ( $wpan_menu_structure ) {

      foreach ( $wpan_menu_structure['menus'] as $menu ) {

        /* The plugin's options are divided in sections. Each section corresponds to
        a serialized entry in the database */
        foreach ( $menu['sections'] as $section ) {

          /* In network mode, settings are read from the main blog's database */
          if ( is_multisite() && wpan_is_network_mode() ) {
            $section_options = get_blog_option ( wpan_get_main_blog_id(), $section['db_key'] );
          }

          /* In normal mode, the settings are read from each blog's database */
          else {
            $section_options = get_option ( $section['db_key'] );
          }

          if ($section_options)
            $options = array_merge ( $options, $section_options );

        }

      }
      
    }

    return $options;

  }


  // ==================================================================================
  // =                            Load the menu structure                             =
  // ==================================================================================

  /**
   * Load the plugin's menu structure from the config file to the global array
   * $wpan_menu_structure.
   *
   * Parse the config file's content into a global array. This array will be used
   * by other functions in the library to build the settings pages in the backend
   * (=> using the Settings API) and as a blueprint to retrieve the plugin's options
   * in the database (=> using the wpan_get_options() function).
   *
   * IMPORTANT: You cannot rely on wpan_get_options() to access the plugin options
   * in the scope of this function; use get_option() instead. The reason is that
   * wpan_get_options() relies  on this function to have already run. Similarly,
   * you cannot use wpan_debug() because it relies on wpan_get_options(); use
   * wpan_debug_wp() instead.
   */
  function wpan_load_menu_structure ( $filepath ) {

    /* If the structure does not exist, load if from the config file */
    if ( ! array_key_exists( 'wpan_menu_structure', $GLOBALS ) ) {

      // ==================================================================================
      // =                                Load from file                                  =
      // ==================================================================================

      /* Load the config file's content into memory */
      $settings_file_content = file_get_contents( $filepath );
      if ( false === $settings_file_content ) {
        wpan_debug_wp( 'Could not load config file for WordPress Analytics; file: ' . $filepath );
        $GLOBALS['wpan_menu_structure'] = [];
        return;
      }

      /* Parse the config file's content into a global array. This array will be used
      to build the settings pages and as a blueprint to retrieve the plugin's options
      from the database. */

      $extension = pathinfo( $filepath, PATHINFO_EXTENSION );

      /* If config file is JSON, use the buil-in PHP function json_encode() to
      parse the config file */
      if ( 'json' == $extension ) {
        $GLOBALS['wpan_menu_structure'] = json_decode( $settings_file_content, true );
      }
      /* If config file is YAML load the external Yaml library and use it to parse
      the config file */
      else if ( 'yaml' == $extension || 'yml' == $extension ) {
        wpan_load_yaml_library();
        if ( defined( "WPAN_YAML_LOADED" ) ) {
          $GLOBALS['wpan_menu_structure'] = Symfony\Component\Yaml\Yaml::parse( $settings_file_content );
        }
        else {
          wpan_debug_wp( "Yaml library not loaded, cannot open config file " . $filepath );
          return;
        }
      }
      /* If config file is not recognized... */
      else {
        wpan_debug_wp( "Extension $extension not recognized, cannot open config file " . $filepath);
        return;
      }


      // ==================================================================================
      // =                               Sanitize structure                               =
      // ==================================================================================
      
      /* Some options in the config file are optional; here we set them to their default value */
      
      global $wpan_menu_structure;
      
      foreach ( $wpan_menu_structure['menus'] as &$menu ) {

        if ( ! isset( $menu['type'] ) )
          $menu['type'] = 'tabbed';

        if ( ! isset( $menu['icon'] ) )
          $menu['icon'] = 'dashicons-admin-generic';

        foreach ( $menu['sections'] as &$section ) {

          if ( ! isset( $section['db_key'] ) )
            $section['db_key'] = 'wpan:' . $section['id'];

          if ( ! isset( $section['name'] ) )
            $section['name'] = 'wpan_' . $section['id'] . '_section';

          if ( ! isset( $section['page'] ) )
            $section['page'] = 'wpan_' . $section['id'] . '_page';

          if ( ! isset( $section['group'] ) )
            $section['group'] = 'wpan_' . $section['id'] . '_option_group';

          if ( ! isset( $section['visible'] ) )
            $section['visible'] = true;

          if ( ! isset( $section['display_section_title'] ) )
            $section['display_section_title'] = true;

        }
      }

      /* Debug: print to debug.log the plugin structure */
      // wpan_debug( $GLOBALS['wpan_menu_structure'], true );

    }
    
  }


  // ==================================================================================
  // =                               Register settings                                =
  // ==================================================================================

  /**
   * Build the internal structure of the settings menus, using $wpan_menu_structure
   * as a blueprint.
   *
   * Should be called as a callback for the admin_init action.
   *
   * This function does three things: first, the settings are registered so that
   * they can be modified in the database; second, the settings sections are defined
   * and lastly the sections are populated with settings fields.
   *
   * This function allows for the display functions (ex. wpan_display_tabbed_page
   * and wpan_display_general_settings_section) to correctly display the settings
   * pages.
   */
  function wpan_initialise_settings() {

    global $wpan_menu_structure;

    if ( ! isset( $wpan_menu_structure['menus'] ) )
      return;

    foreach ( $wpan_menu_structure['menus'] as $menu ) {

      if ( ! isset( $menu['sections'] ) )
        return;

      foreach ( $menu['sections'] as $section ) {

        if ( ! isset( $section['fields'] ) )
          return;

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

        /* Build an array with the default values of the options (a.k.a. fields) for
        this section */
        $defaults = [];
        foreach ( $section['fields'] as $key => $field ) {
          $defaults[ $field['id'] ] = $field[ 'default' ];
        }

        /* If the database key correponding to the current option does not exist in
        the database, create it and fill it with default values. Not doing so will
        force the user to submit all settings section singularly before being able
        to use the plugin. */
        if( false == get_option( $section['db_key'] ) ) {
          add_option( $section['db_key'], $defaults );
        }

        /* Values to display in the input form. We take the option values stored in the
        database. If an option is not present in the database, we assume it is empty.
        We do this because of how the Settings API works: the database entries associated
        to input forms with emtpy fields are removed from the database rather than left
        there with an empty value.  */
        $displayed_values = get_option( $section['db_key'] );
        foreach ( $defaults as $key => $value ) {
          if ( ! isset( $displayed_values[ $key ] ) ) {
            $displayed_values[ $key ] = '';
          }
        }

        /* Debug option values and displayed values */
        // if ( $section['id'] == 'advanced_settings' ) {
        //   wpan_debug ("FIELDS = " . print_r( $section['fields'], true ) );
        //   wpan_debug ("DB = " . print_r( get_option( $section['db_key'] ), true) );
        //   wpan_debug ("DISPLAYED VALUES = " . print_r( $displayed_values, true ) );
        // }

        /* Add the current to the menu page */
        wpan_add_section( $section, $menu, $displayed_values );

        /* Add to the current section all fields that belong to it. In the Settings
        API language, a field is a single option. Adding a field means telling
        WordPress how to build the HTML for that field in the settings page, and
        where to store/retrieve the value of the field in the database. */
        foreach ( $section['fields'] as $field ) {
          wpan_add_field( $field, $section, $displayed_values );
        }

      } // foreach ($section)

    } // foreach ($menu)

  } // wpan_initialise_settings


  /**
   * Add a section to a given menu page using the add_settings_section
   * function from the Settings API.
   *
   * Takes as arguments:
   *   $section: The section array
   *   $menu: The menu array
   *   $displayed_values: The current values of all fields, including those
   *     from other sections
   */
  function wpan_add_section( $section, $menu, $displayed_values  ) {

    /* Allow the user to skip the section */
    $display_section = apply_filters( 'wpan_display_section', true, $section['id'] );
    if ( ! $display_section ) {
      return;
    }

    /* By default, a section is a description plus a list of option fields.
    We allow the user to add more stuff by means of an action. Note that
    options are rendered separately. */
    $display_function = function () use ( $section, $menu ) {

      if ( isset( $section['desc'] ) ) {
        echo $section['desc'];
      }

      /* The action must be named as wpan_display_<section_name>_section, ex.
      wpan_display_general_settings_section(). The custom function does not need to
      display the setting fields, which are rendered independently using
      wpan_display_xxx_input() functions. */
      do_action( 'wpan_display_' . $section['id'] . '_section', $section, $menu );

    };

    /* If the user has defined a custom template, we use it instead. The
    custom function must be in the format wpan_display_<section_name>_section, ex.
    wpan_display_general_settings_section. The custom function does not need to
    display the setting fields, which are rendered independently using 
    wpan_display_xxx_input() functions. */
    // if ( function_exists( 'wpan_display_' . $section['id'] . '_section' ) ) {
    //   $display_function = 'wpan_display_' . $section['id'] . '_section';
    // }

    /* Should we show the section's title? */
    $show_section_title = isset( $section['display_section_title'] ) && $section['display_section_title'];

    /* Draw the HTML in the appropriate menu page */
    add_settings_section(
      $section['name'],
      $show_section_title ? $section['display'] : '',
      $display_function,
      $section['page']
    );

  }


  /**
   * Add a field to a given section using the add_settings_field
   * function from the Settings API.
   *
   * Takes as arguments:
   *   $section: The field array
   *   $section: The section array
   *   $displayed_values: The current values of all fields, including those
   *     from other sections.
   */
  function wpan_add_field( $field, $section, $displayed_values  ) {

    /* Allow the user to skip the field */
    $display_field = apply_filters( 'wpan_display_field', true, $field['id'] );
    if ( ! $display_field ) {
      return;
    }

    /* By default, we display the field in the settings page use the standard
    templates in the settings library */
    $display_function = 'wpan_display_' . $field['type'] . '_input';

    /* If the user has defined a custom template, we use it instead. The
    custom function must be in the format wpan_display_<field_name>_field, ex.
    wpan_display_call_tracking_field. It takes only one argument, an array with the 
    following keys: 
      - db_key: database key where the value of the input field will be stored.
          The database key is serialized, so the value must be saved as db_key[value].
      - name: id of the field
      - desc: long description of the option controlled by the field
      - value: value of the input field
      - type: type of input field, ex. text, textarea, checkbox...
      - attributes: HTML attributes for the input field; no need to escape them
      - section: the section array containing the field
      - option_vals: the current values of all fields, including those in other sections
    */
    if ( function_exists( 'wpan_display_' . $field['id'] . '_field' ) ) {
      $display_function = 'wpan_display_' . $field['id'] . '_field';
    }
  
    /* Prepare the HTML attributes */
    $attributes = isset ( $field['attributes'] ) ? array_map( 'esc_attr', $field['attributes'] ) : [];

    /* Draw the HTML in the appropriate section */
    add_settings_field(
      $field['id'],
      $field['title'],
      $display_function,
      $section['page'],
      $section['name'],
      [
        /* Variables specific to the current field */
        'db_key'       => $section['db_key'],
        'name'         => $field['id'],
        'desc'         => $field['desc'],
        'value'        => $displayed_values[ $field['id'] ],
        'type'         => $field['type'], 
        'attributes'   => $attributes,
        /* Section-level variable */ 
        'section'      => $section,
        /* Global-level variable */ 
        'options_vals' => $displayed_values,
      ]
    );

  }


  // ==================================================================================
  // =                                 Add menu pages                                 =
  // ==================================================================================

  /**
   * Add the plugin's menu pages to the WordPress backend.
   *
   * This function should be called as a callback for the admin_menu action.
   */
  function wpan_add_menu_pages() {

    global $wpan_menu_structure;

    foreach ( $wpan_menu_structure['menus'] as $menu ) {

      /* By default, we display the menu page using the standard
      template in the settings library. As of now, we only have one,
      the tabbed template */
      $display_function = 'wpan_display_' . $menu['type'] . '_menu_page';

      /* If the user has defined a custom template, we use it instead. The
      custom function must be in the format wpan_display_<menu_name>_menu_page,
      ex. wpan_display_general_settings_section. */
      if ( function_exists( 'wpan_display_' . $menu['slug'] . '_menu_page' ) ) {
        $display_function = 'wpan_display_' . $menu['slug'] . '_menu_page';
      }

      /* If the menu page belongs to an existing menu, then add it to that menu */
      if ( $menu['parent_slug'] ) {

        add_submenu_page (
          $menu[ 'parent_slug' ],
          $menu[ 'display' ],
          $menu[ 'display_menu' ],
          $menu[ 'capability' ],
          $menu[ 'slug' ],
          $display_function
        );

      }

      /* Otherwise, add it to the top level */
      else {

        add_menu_page (
          $menu[ 'display' ],
          $menu[ 'display_menu' ],
          $menu[ 'capability' ],
          $menu[ 'slug' ],
          $display_function,
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
          $display_function
        );

      }
            
    } // foreach($menu)
  } // wpan_add_menu_pages


  // ==================================================================================
  // =                              Menu page templates                               =
  // ==================================================================================

  /**
   * Display a tabbed menu page where each tab corresponds to a section.
   *
   * Takes as only argument a $menu element of a $wpan_menu_structure.
   *
   * When called as a callback for add_menu_page(), the menu page to draw
   * the menu in is inferred based on the current screen ID.
   */

  function wpan_display_tabbed_menu_page( $menu='' ) {

    /* Get the slug of the active menu. This is a workaround we need to use when this
    function is called as the callback function for add_menu_page(), because the
    Settings API in this case does not allow arguments for the callback function. */
    if ( '' == $menu ) {
      $screen = get_current_screen();
      $menu_id = preg_replace( '/.*_page_/', '', $screen->id );
      global $wpan_menu_structure;
      $menu = $wpan_menu_structure['menus'][ $menu_id ];
    }

    /* Each section shall have its own tab */
    $tabs = $menu['sections'];
    
    /* TODO: Include the jQuery to enable contextual popup help */

    /* Intercept the calls to add_settings_error() in wpan_sanitize_options(),
    and complain if the options are wrong. Otherwise show a nice "Settings
    saved" notice. TODO: It seems this call is unnecessary when dealing with
    a submenu; actually, including it will lead to duplicate notices! */
    // settings_errors ();

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

        <?php /* Don't show the navigator if there's only one tab */ ?>
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


  /**
   * Display a menu page as a list of sections.
   *
   * Takes as only argument a $menu element of a $wpan_menu_structure.
   */

  function wpan_display_listed_menu_page( $menu='' ) {

    /* Get the current active menu */
    if ( '' == $menu ) {
      global $wpan_current_menu;
      $menu = $wpan_current_menu;
    }

    /* Each section shall have its own tab */
    $sections = $menu['sections'];

    /* Intercept the calls to add_settings_error() in wpan_sanitize_options(),
    and complain if the options are wrong. Otherwise show a nice "Settings
    saved" notice */
    settings_errors ();

    ?>

    <div class="wrap">

      <h2><?php print $GLOBALS['title']; ?></h2>

      <form action="options.php" method="POST">

        <?php

          /* Prints out all the settings sections that belong to the active tab */
          foreach ($sections as $section) {
            if ( $section['visible'] ) {
              settings_fields( $section['group'] );
              do_settings_sections( $section['page'] );
            }
          }

          submit_button();

        ?>

      </form>
    </div>

    <?php

  }



  // ==================================================================================
  // =                               Section templates                                =
  // ==================================================================================

  /**
   * Load the syntax highlighting library for the "Custom code" section.
   **/

  add_action( 'wpan_display_custom_code_section', 'wpan_load_syntax_highlighting', 10, 2 );

  function wpan_load_syntax_highlighting ( $section, $menu ) {

    if ( ! defined( 'WPAN_SYNTAX_HIGHLIGHTING_LOADED' ) ) {

      define( "WPAN_CODEMIRROR_DIR", WPAN_PLUGIN_DIR . 'vendor/codemirror/' );
      define( "WPAN_CODEMIRROR_URL", WPAN_PLUGIN_URL . 'vendor/codemirror/' );

      if ( ! file_exists( WPAN_CODEMIRROR_DIR ) ) {

        wpan_debug( "Could not find syntax highlighting library; folder " . WPAN_CODEMIRROR_DIR . " could not be found." );

        return false;
      
      }
      
      /* Load from the CodeMirror library all the scripts needed to
      have PHP syntax highlighting */
      ?>

<link rel="stylesheet" href=<?php echo WPAN_CODEMIRROR_URL.'lib/codemirror.css'; ?>>
<script src=<?php echo WPAN_CODEMIRROR_URL.'lib/codemirror.js'; ?>></script>
<script src=<?php echo WPAN_CODEMIRROR_URL.'addon/edit/matchbrackets.js'; ?>></script>
<script src=<?php echo WPAN_CODEMIRROR_URL.'mode/htmlmixed/htmlmixed.js'; ?>></script>
<script src=<?php echo WPAN_CODEMIRROR_URL.'mode/xml/xml.js'; ?>></script>
<script src=<?php echo WPAN_CODEMIRROR_URL.'mode/javascript/javascript.js'; ?>></script>
<script src=<?php echo WPAN_CODEMIRROR_URL.'mode/css/css.js'; ?>></script>
<script src=<?php echo WPAN_CODEMIRROR_URL.'mode/clike/clike.js'; ?>></script>
<script src=<?php echo WPAN_CODEMIRROR_URL.'mode/php/php.js'; ?>></script>

      <?php
     
      define( "WPAN_SYNTAX_HIGHLIGHTING_LOADED", true );
      
      wpan_debug( "Syntax highlighting library loaded." );
 
    }

    return true;

  }



  // ==================================================================================
  // =                             Input field templates                              =
  // ==================================================================================

  /**
   * Display a single-line input field (<input type="text">).
   */
  function wpan_display_text_input ( $args ) {

    /* Print description */
    echo '<p>' . $args['desc'] . '</p>';

    /* Create a text input */
    printf(
        '<input type="text" name="%1$s[%2$s]" id="%2$s" value="%3$s" maxlength="%4$s" size="%5$s">',
        esc_attr( $args['db_key'] ),
        esc_attr( $args['name'] ),
        esc_attr( $args['value'] ),
        esc_attr( $args['attributes']['maxlength'] ),
        isset( $args['attributes']['size'] ) ? esc_attr( $args['attributes']['size'] ) : ''
    );
  }


  /**
   * Display a number input field (<input type="number">).
   */
  function wpan_display_number_input ( $args ) {

    /* Print description */
    echo '<p>' . $args['desc'] . '</p>';

    /* Create a text input */
    printf(
        '<input type="number" name="%1$s[%2$s]" id="%2$s" value="%3$s" min="%4$s" max="%5$s">',
        esc_attr( $args['db_key'] ),
        esc_attr( $args['name'] ),
        esc_attr( $args['value'] ),
        esc_attr( $args['attributes']['min'] ),
        esc_attr( $args['attributes']['max'] )
    );
  }


  /**
   * Display a checkbox input field (<input type="checkbox">).
   */
  function wpan_display_checkbox_input ( $args ) {

    /* Print description */
    echo '<p>' . $args['desc'] . '</p>';

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

    /* Print description */
    echo '<p>' . $args['desc'] . '</p>';

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

    /* Print description */
    echo '<p>' . $args['desc'] . '</p>';

    /* Show a simple textarea input */
    printf(
        '<textarea name="%1$s[%2$s]" id="%2$s" rows="%4$s" cols="%5$s" placeholder="%6$s">%3$s</textarea>',
        esc_attr( $args['db_key'] ),
        esc_attr( $args['name'] ),
        htmlspecialchars( $args['value'] ), /* Escape HTML special characters (',",&,<,>) */
        esc_attr( $args['attributes']['rows'] ),
        esc_attr( $args['attributes']['cols'] ),
        esc_attr( $args['attributes']['placeholder'] )
    );

    if ( WPAN_SYNTAX_HIGHLIGHTING_LOADED ) {

    ?>

<script>
var myCodeMirror = CodeMirror.fromTextArea(document.getElementById('custom_code'), {
    mode:  "php",
    smartIndent: true,
    lineNumbers: true,
});
// myCodeMirror.setSize(500, 300);
</script>

    <?php

    }

  }

