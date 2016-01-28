<?php

  /**
   * Build the settings page for the Wordpress Analytics plugin.
   *
   * Created by Guido W. Pettinari on 28.01.2016.
   * Part of Wordpress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */  

  /** Register the settings in the database */
  add_action('admin_init', 'wordpress_analytics_register_settings');

  function wordpress_analytics_register_settings() {
    register_setting( 'wpan_settings_group', 'wpan_tracking_uid' );
    register_setting( 'wpan_settings_group', 'wpan_enable_scroll_tracking' );
    register_setting( 'wpan_settings_group', 'wpan_enable_content_grouping' );
  }



  /** Add the menu page */
  add_action('admin_menu', 'wordpress_analytics_add_menu');

  function wordpress_analytics_add_menu() {
    add_menu_page ('Wordpress Analytics Settings',
                   'Wordpress Analytics',
                   'administrator',
                   'wordpress-analytics-settings',
                   'wordpress_analytics_build_settings_page',
                   'dashicons-admin-generic');
  }

  /** Build the menu page */
  function wordpress_analytics_build_settings_page() {

    $tracking_uid = esc_attr(get_option('wpan_tracking_uid'));
    $st_checked = checked('1', get_option('wpan_enable_scroll_tracking'));
    $cg_checked = checked('1', get_option('wpan_enable_content_grouping'));

  ?>

<div class="wrap">
<h2>Wordpress Analytics Options</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'wpan_settings_group' ); ?>
    <?php do_settings_sections( 'wpan_settings_group' ); ?>

    <table class="form-table">
        <tr valign="top">
        <th scope="row">Google Analytics tracking ID</th>
        <td><input type="text" name="wpan_tracking_uid" value="<?php echo $tracking_uid; ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Enable scroll tracking?</th>
        <td><input type="checkbox" name="wpan_enable_scroll_tracking" value="1" <?php echo $st_checked; ?>/></td>
        </tr>

        <tr valign="top">
        <th scope="row">Enable content grouping?</th>
        <td><input type="checkbox" name="wpan_enable_content_grouping" value="1" <?php echo $cg_checked; ?>/></td>
        </tr>
    </table>

    <?php submit_button(); ?>

</form>
</div>

<?php
  
  }

?>