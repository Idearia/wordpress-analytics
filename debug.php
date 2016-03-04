<?php

  /**
   * File with debug functions for the Wordpress Analytics
   * plugin.
   *
   * Created by Guido W. Pettinari on 29.01.2016.
   * Part of Wordpress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */

  add_action ('wp_footer', 'wordpress_analytics_debug', 100);

  /**
   * Execute a debug function at the very bottom of all posts
   * and pages.
   */

  function wordpress_analytics_debug () {

    echo "<div hidden>\n";
    echo "\n\nWPAN DEBUG INFORMATION\n\n";

    /* Print all options in the database */
    // $options = wpan_get_options ();
    // echo '<pre>' , print_r ( $options ), '</pre>';
    
    /* Print taxonomy names */
    // $taxonomies = get_taxonomies();
    // foreach ( $taxonomies as $taxonomy ) {
    //     echo '<p>' . $taxonomy . '</p>';
    // }

    /* Print all of the taxonomy information */
    // echo '<pre>' , print_r ( get_taxonomies( array(), 'objects' ) ), '</pre>';

    /* Print only part of taxonomy information */
    // $args = array (
    //     'object_type' => ['post'],
    //     'public' => true,
    //     'show_ui' => '1'
    // );
    // $taxonomies = get_taxonomies( $args, 'objects' );
    //
    // foreach ( $taxonomies as $taxonomy ) {
    //     echo $taxonomy->labels->singular_name . PHP_EOL;
    // }

    echo "\n\nEND OF WPAN DEBUG INFORMATION\n\n";
    echo "</div>\n";

  }

?>
