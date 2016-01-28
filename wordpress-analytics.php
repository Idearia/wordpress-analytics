<?php

  /**
   * Plugin Name: Wordpress Analytics
   * Plugin URI: https://github.com/coccoinomane/wordpress_analytics
   * Description: Let Google Analytics communicate with Wordpress and track user activity beyond pageviews
   * Version: 0.1
   * Author: Guido W. Pettinari
   * Author URI: http://www.guidowalterpettinari.eu
   * License: GPL3
   */

  /* Build settings page */
  include_once (plugin_dir_path(__FILE__) . "settings.php");

  /* Load content grouping function */
  if (get_option ('wpan_enable_content_grouping'))
    include_once (plugin_dir_path(__FILE__) . "content_grouping.php");

  /* Load scroll tracking function */
  if (get_option ('wpan_enable_scroll_tracking'))
    include_once (plugin_dir_path(__FILE__) . "scroll_tracking.php");

  /* Load Google Analytics tracking function */
  include_once (plugin_dir_path(__FILE__) . "tracking_code.php");

  /* Insert Google Analytics tracking code */
  add_action ('wp_head', 'wordpress_analytics_tracking_code');

  /* Insert debug tools */
  include_once (plugin_dir_path(__FILE__) . "debug.php");

?>