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

  /* Slug of the settings menu */
  $menu_slug = 'wordpress-analytics-settings';
  
  /* Name of the array where we shall store all the options */
  $option_name   = 'wpan:option_array';

  /* Group to which the setting begins; we shall use only one
  group, since all settings are stored in a single array */
  $option_group = 'wpan:option_group';

  /* Default values of all settings */
  $default_values = [
    'tracking_uid' => '',
    'scroll_tracking'  => '0',
    'content_grouping'   => '0',
    'group_index_wordpress' => '1',
    'group_index_woocommerce' => '2',
    'group_index_blog' => '3',
    'pixel_threshold' => '300',
    'time_threshold' => '60'
  ];

  /** Add the settings menu page */
  add_action('admin_menu', 'wpan_add_options_page');

  function wpan_add_options_page() {
    
    global $menu_slug;
    
    $page_title = 'Wordpress Analytics Settings';
    $menu_title = 'Wordpress Analytics';
    $capability = 'administrator';
    $function = 'wpan_render_options_page';
    $icon_url = 'dashicons-admin-generic';
    $position = 99;

    add_menu_page ($page_title,
                   $menu_title,
                   $capability,
                   $menu_slug,
                   $function,
                   $icon_url,
                   $position);
  }



  /** Render the settings menu page */
  function wpan_render_options_page() {

    global $menu_slug;
    global $option_group;

    /* Include the jQuery to enable contextual popup help */

    /* Intercept the calls to add_settings_error() in wpan_validate_options(),
    and complain if the options are wrong. */
    settings_errors ();

    ?>
    <div class="wrap">
        <h2><?php print $GLOBALS['title']; ?></h2>
        <form action="options.php" method="POST">
            <?php
            settings_fields( $option_group );
            do_settings_sections( $menu_slug ); 
            submit_button(); 
            ?>
        </form>
    </div>
    <?php

  }


  /** Register the settings in the database */
  add_action('admin_init', 'wpan_register_settings');

  function wpan_register_settings() {

    global $menu_slug;
    global $option_name;
    global $option_group;
    global $default_values;

    /* Fetch existing options */
    $option_values = get_option( $option_name );

    /* Parse option values into predefined keys, throw the rest away */
    $data = shortcode_atts( $default_values, $option_values );

    register_setting(
      $option_group,                // group, used for settings_fields()
      $option_name,                 // option name, used as key in database
      'wpan_validate_options'       // validation callback
    );

    /* "General settings" section */
    $section = 'general_settings';
    add_settings_section(
      $section . '_section',
      'General settings',
      'wpan_render_' . $section . '_section',
      $menu_slug
    );

    $name = 'tracking_uid';
    $title = 'Google Analytics tracking ID';
    $desc = 'Google Analytics tracking ID';
    add_settings_field(
      $name,
      $title,
      'wpan_render_' . $name,
      $menu_slug,
      $section . '_section',
      [
        'options_name' => $option_name,
        'options_vals' => $data,
        'name'         => $name,
        'description'  => $desc,
        'section'      => esc_attr( $section ),
        'value'        => esc_attr( $data[ $name ] ),
      ]
    );

    $name = 'content_grouping';
    $title = 'Enable content grouping?';
    $desc = 'Enable content grouping?';
    add_settings_field(
      $name,
      $title,
      'wpan_render_' . $name,
      $menu_slug,
      $section . '_section',
      [
        'options_name' => $option_name,
        'options_vals' => $data,
        'name'         => $name,
        'description'  => $desc,
        'section'      => esc_attr( $section ),
        'value'        => esc_attr( $data[ $name ] ),
      ]
    );

    $name = 'scroll_tracking';
    $title = 'Enable scroll tracking?';
    $desc = 'Enable scroll tracking?';
    add_settings_field(
      $name,
      $title,
      'wpan_render_' . $name,
      $menu_slug,
      $section . '_section',
      [
        'options_name' => $option_name,
        'options_vals' => $data,
        'name'         => $name,
        'description'  => $desc,
        'section'      => esc_attr( $section ),
        'value'        => esc_attr( $data[ $name ] ),
      ]
    );

    /* "Content grouping" section */
    $section = 'content_grouping';
    add_settings_section(
      $section . '_section',
      'Content grouping',
      'wpan_render_' . $section . '_section',
      $menu_slug
    );

    $name = 'group_index_wordpress';
    $title = 'Group index for Wordpress category';
    $desc = 'Send the Wordpress category to this group index in Google Analytics';
    add_settings_field(
      $name,
      $title,
      'wpan_render_' . $name,
      $menu_slug,
      $section . '_section',
      [
        'options_name' => $option_name,
        'options_vals' => $data,
        'name'         => $name,
        'description'  => $desc,
        'section'      => esc_attr( $section ),
        'value'        => esc_attr( $data[ $name ] ),
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
      'wpan_render_' . $name,
      $menu_slug,
      $section . '_section',
      [
        'options_name' => $option_name,
        'options_vals' => $data,
        'name'         => $name,
        'description'  => $desc,
        'section'      => esc_attr( $section ),
        'value'        => esc_attr( $data[ $name ] ),
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
      'wpan_render_' . $name,
      $menu_slug,
      $section . '_section',
      [
        'options_name' => $option_name,
        'options_vals' => $data,
        'name'         => $name,
        'description'  => $desc,
        'section'      => esc_attr( $section ),
        'value'        => esc_attr( $data[ $name ] ),
        'min'          => 1,
        'max'          => 100,
      ]
    );

    /* "Scroll tracking" section */
    $section = 'scroll_tracking';
    add_settings_section(
      $section . '_section',
      'Scroll tracking',
      'wpan_render_' . $section . '_section',
      $menu_slug
    );

    $name = 'pixel_threshold';
    $title = 'Pixels threshold for engagement';
    $desc = 'Pixels the user needs to scroll before we consider him/her engaged';
    add_settings_field(
      $name,
      $title,
      'wpan_render_' . $name,
      $menu_slug,
      $section . '_section',
      [
        'options_name' => $option_name,
        'options_vals' => $data,
        'name'         => $name,
        'description'  => $desc,
        'section'      => esc_attr( $section ),
        'value'        => esc_attr( $data[ $name ] ),
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
      'wpan_render_' . $name,
      $menu_slug,
      $section . '_section',
      [
        'options_name' => $option_name,
        'options_vals' => $data,  
        'name'         => $name,
        'description'  => $desc,
        'section'      => esc_attr( $section ),
        'value'        => esc_attr( $data[ $name ] ),
        'min'          => 1,
        'max'          => 10000,
      ]
    );

  } // end of wpan_register_settings()


  /** Validate the array containing the options; taken from
  http://wordpress.stackexchange.com/a/100137/86662 */

  function wpan_validate_options ( $options ) {

    global $option_name;
    global $option_group;
    global $default_values;

    /* Revert to the defaul values if the data is corrupted */
    if ( ! is_array( $options ) ) {

      add_settings_error(
        $option_group,
        'settings-data-corrupted',
        "Couldn't read options! Corrupt database? Field: ". $options . ".");

      return $default_values;

    }

    /* Initialise output array */
    $out = array ();

    /* Validate settings one by one */
    foreach ( $default_values as $key => $default_value ) {

      $option_value = $options[ $key ];

      /* If the user left the field empty, adopt the default value */
      if ( empty ( $option_value ) ) {
        $out[ $key ] = $default_value;
        continue;
      }

      $error_type = '';

      switch ($key) {

        case 'tracking_uid':
          break;

        case 'group_index_wordpress':
          if ( $option_value < 0 ) {
            $error_type = 'negative-group-index-wordpress';
            $error_message = 'Wordpress group index must be positive';
          }
          break;

        case 'group_index_woocommerce':
          if ( $option_value < 0 ) {
            $error_type = 'negative-group-index-woocommerce';
            $error_message = 'Woocommerce group index must be positive';
          }
          break;

        case 'group_index_blog':
          if ( $option_value < 0 ) {
            $error_type = 'negative-group-index-blog';
            $error_message = 'Blog group index must be positive';
          }
          break;

        case 'pixel_threshold':
          if ( $option_value < 0 ) {
            $error_type = 'negative-pixel-threshold';
            $error_message = 'The pixel threshold must be positive';
          }
          break;

        case 'time_threshold':
          if ( $option_value < 0 ) {
            $error_type = 'negative-time-threshold';
            $error_message = 'The time threshold must be positive';
          }
          break;

        default:
          $out[ $key ] = $option_value;
          break;

      } // switch
      
      if ( empty ( $error_type ) ) {

        $out[ $key ] = $option_value;

      }

      else {

        add_settings_error(
          $option_name,
          esc_attr ( $error_type ),
          $error_message . '.');

      }
      
    } // foreach

    return $out;

  }

  /** Generic rendering functions */

  function wpan_render_text_input ( $args ) {

    /* Print description */
    echo '<p>' . $args['description'] . '</p>';

    /* Create a text input */
    printf(
        '<input type="text" name="%1$s[%2$s]" id="%2$s" value="%3$s">',
        $args['options_name'],
        $args['name'],
        $args['value']
    );
  }

  function wpan_render_number_input ( $args ) {

    /* Print description
    TODO: should use a question mark popup */
    // echo '<p>' . $args['description'] . '</p>';

    /* Create a text input */
    printf(
        '<input type="number" min="%1$s" max="%2$s" name="%3$s[%4$s]" id="%4$s" value="%5$s" class="%6$s">',
        $args['min'],
        $args['max'],
        $args['options_name'],
        $args['name'],
        $args['value'],
        $args['desc']
    );
  }

  function wpan_render_checkbox_input ( $args ) {

    /* Print description
    TODO: should use a question mark popup */
    // echo '<p>' . $args['description'] . '</p>';

    /* Create a checkbox input */
    $checked = checked('1', $args['options_vals'][$args['name']], false);
    printf(
        '<input type="checkbox" name="%1$s[%2$s]" id="%2$s" value="1" %3$s>',
        $args['options_name'],
        $args['name'],
        $checked
    );
  }



  /** Render the "General settings" section */
  function wpan_render_general_settings_section () {

    echo "<p>General settings</p>";

  }

  function wpan_render_tracking_uid ( $args ) {

    wpan_render_text_input ($args);

  }

  function wpan_render_content_grouping ( $args ) {

    wpan_render_checkbox_input ($args);

  }

  function wpan_render_scroll_tracking ( $args ) {

    wpan_render_checkbox_input ($args);

  }


  /** Render the "Content grouping" section */
  function wpan_render_content_grouping_section () {

    echo "<p>Settings for the content grouping functionality</p>";

  }

  function wpan_render_group_index_wordpress ( $args ) {

    wpan_render_number_input ( $args );

  }

  function wpan_render_group_index_woocommerce ( $args ) {

    wpan_render_number_input ( $args );

  }

  function wpan_render_group_index_blog ( $args ) {

    wpan_render_number_input ( $args );

  }

  /** Render the "Scroll tracking" section */
  function wpan_render_scroll_tracking_section () {

    echo "<p>Settings for the scroll tracking functionality</p>";

  }

  function wpan_render_pixel_threshold ( $args ) {

    wpan_render_number_input ( $args );

  }

  function wpan_render_time_threshold ( $args ) {

    wpan_render_number_input ( $args );

  }