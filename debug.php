<?php

  /**
   * Execute a debug function at the very bottom of all posts
   * and pages.
   *
   * Created by Guido W. Pettinari on 29.01.2016.
   * Part of Wordpress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */

  add_action ('wp_footer', 'wordpress_analytics_debug', 100);

  function wordpress_analytics_debug () {

    /* Print basic package information */
    // echo "tracking_uid = " . get_option ('wpan_tracking_uid') . "<br>";
    // echo "enable_scroll_tracking = " . get_option ('wpan_enable_scroll_tracking') . "<br>";
    // echo "enable_content_grouping = " . get_option ('wpan_enable_content_grouping') . "<br>";

    /* Check the top-level category name of the current post */
    // $categories = get_the_category();
    // foreach ($categories as $cat) {
    //   if ($cat->category_parent == 0) {
    //       $category_name = $cat->cat_name;
    //       break;
    //   }
    // }
    // echo "category_name = $category_name <br>";

    /* Print content type */
    // echo "ID = " . $post->ID . "<br>";
    // echo "analytics_content_type = " . get_post_meta($post->ID, 'analytics_content_type', true) . "<br>";
    
  }

?>