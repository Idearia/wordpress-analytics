<?php

  /**
   * Send category information from WordPress to Google Analytics 4 (GA4).
   *
   * GA4 has one predefined content group per page (the 'content_group'
   * parameter); the function returns the parameters to add to the 'config'
   * command of the Google tag, so that they are sent with the pageview:
   *
   * - content_group: for a post, the top-level WordPress category of the post
   *   that comes first in alphabetical order; for a WooCommerce product, the
   *   fixed value 'Prodotti'.
   * - product_category: for a WooCommerce product, its top-level product
   *   category ('Undefined Product' if it has none).
   * - content_type: for a post, the value of the custom field
   *   'analytics_content_type', if any.
   *
   * To see product_category and content_type in the GA4 reports, register
   * them as event-scoped custom dimensions in the GA4 admin.
   *
   * Created by Guido W. Pettinari on 08.01.2015.
   * Part of WordPress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   *
   * @return array Parameters for the 'config' command; empty if the page
   * has no category.
   */

  function wpan_content_grouping() {

    $params = [];


    // ====================================================================================
    // =                                    Product page                                  =
    // ====================================================================================

    if ( function_exists( 'is_product' ) && is_product() ) {

      $params['content_group'] = 'Prodotti';
      $params['product_category'] = 'Undefined Product';

      /* Extract the terms in the product category attached this post, and select from
      them the top-level term that is first in alphabetical order */
      $terms = get_the_terms( get_the_ID(), 'product_cat' );
      if ( $terms && ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
          if ( $term->parent == 0 && ! empty( $term->name ) ) {
            $params['product_category'] = $term->name;
            break;
          }
        }
      }

      return $params;

    }


    // ====================================================================================
    // =                                    Content page                                  =
    // ====================================================================================

    /* Extract the categories of this post, and select from them the top-level
    category that is first in alphabetical order. Note that in WordPress it is
    possible for posts not to have top-level categories; in that case nothing
    is sent and the page will be catalogued as (not set) in GA. */
    $categories = get_the_category();
    if ( $categories && ! is_wp_error( $categories ) ) {
      foreach ( $categories as $cat ) {
        if ( $cat->category_parent == 0 && ! empty( $cat->cat_name ) ) {
          $params['content_group'] = $cat->cat_name;
          break;
        }
      }
    }

    if ( $params ) {

      /* Extract content type from custom field */
      $content_type = get_post_meta( get_the_ID(), 'analytics_content_type', true );

      if ( $content_type )
        $params['content_type'] = $content_type;

    }

    return $params;

  } // wpan_content_grouping


  /* If Advanced Custom Fields is installed, load all JSON files
  in the acf subdirectory */
  if ( class_exists( 'acf' ) && isset ( $options['enable_json_folder'] ) && $options['enable_json_folder'] ) {

    add_filter( 'acf/settings/load_json', 'my_acf_json_load_point' );

    function my_acf_json_load_point( $paths ) {

      $paths[] = WPAN_PLUGIN_DIR . "acf";

      return $paths;
      
    }

  } // if acf

?>
