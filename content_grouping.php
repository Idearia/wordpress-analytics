<?php

  /**
   * Send category information from Wordpress to Google Analytics (GA).
   *
   * The Wordpress category is passed using the Content Grouping feature of
   * GA, to the contentGroup1 group.
   *
   * If the function is_product() is defined, the script assumes the website
   * has an ecommerce, and the product category will be transmitted from the
   * 'product_cat' taxonomy to the contentGroup2 group.
   * 
   * This script should be placed just before the pageview is transmitted
   * to GA, ie. before the ga ('send', 'pageview') in the standard tracking
   * code, lest the grouping data is not transmitted to GA. You can either
   * copy & paste the script there, or include it as a file with the PHP 
   * function include() or the Wordpress function get_template_part().
   *
   * As an alternative, you could place this script wherever you want and
   * transmit the data to GA using a non-interaction event. If you do so,
   * you'll see the content grouping data in the Event -> Pages report
   * rather than in the Content -> All Pages one.
   * 
   * This script was improved thanks to the following resources; thanks
   * to the authors!
   * - https://www.highposition.com/blog/how-to-send-author-content-groups-
   *   wordpress-google-analytics/
   * - http://stackoverflow.com/a/34714363/2972183
   *
   * Created by Guido W. Pettinari on 08.01.2015.
   * Part of Wordpress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */

  function wordpress_analytics_content_grouping() {

    /* Extract the content grouping options from the database. If these are
    not properly formatted, nothing will be sent to GA. */
    $options = wpan_get_options ();
    $wordpress_group = isset ( $options['group_index_wordpress'] ) ? $options['group_index_wordpress'] : '';
    $woocommerce_group = isset ( $options['group_index_woocommerce'] ) ? $options['group_index_woocommerce'] : '';
    $blog_group = isset ( $options['group_index_blog'] ) ? $options['group_index_blog'] : '';

    /* Extract the categories of this post, and select from them the
    top-level category that is first in alphabetical order. Note that
    in Wordpress it is possible for posts not to have top-level
    categories; in that case, the category name will be empty and
    the post will be catalogued as (not set) in GA. */
    $categories = get_the_category();
    foreach ($categories as $cat) {
      if ($cat->category_parent == 0) {
          $category_name = $cat->cat_name;
          break;
      }
    }


    // ====================================================================================
    // =                                    Product page                                  =
    // ====================================================================================

    if (function_exists('is_product') && is_product()) {

      echo "<script> ga('set', 'contentGroup" . $wordpress_group . "', '" . "Prodotti" . "'); </script>\n";

      /* Extract the terms in the product category attached this post, and select from
      them the top-level term that is first in alphabetical order. Note that in Wordpress
      it is possible for posts not to have top-level terms; in that case, the category
      name will be empty and the post will be catalogued as (not set) in GA. */
      $terms = get_the_terms (get_the_ID(), 'product_cat');
      foreach ($terms as $term) {
        if ($term->parent == 0) {
            $term_name = $term->name;
            break;
        }
      }


      if ( $terms && !is_wp_error($terms) && !empty($term_name))
        echo "<script> ga('set', 'contentGroup" . $woocommerce_group . "', '" . $term_name . "'); </script>\n";
      else
        echo "<script> ga('set', 'contentGroup" . $woocommerce_group . "', '" . "Undefined Product" . "'); </script>\n";
  
    }


    // ====================================================================================
    // =                                    Content page                                  =
    // ====================================================================================

    /* Default behaviour: send to Analytics the post category from Wordpress, and
    the information stored in the custom field 'analytics_content_type' */
    else if ($categories && !is_wp_error($categories) && !empty($category_name)) {

      echo "<script> ga('set', 'contentGroup" . $wordpress_group . "', '" . $category_name . "'); </script>\n";

      /* Extract content type from custom field */
      $content_type = get_post_meta(get_the_ID(), 'analytics_content_type', true);

      if ($content_type)
        echo "<script> ga('set', 'contentGroup" . $blog_group . "', '" . $content_type . "'); </script>\n";

    }
  }
  
?>
