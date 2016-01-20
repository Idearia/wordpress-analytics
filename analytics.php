<?php

  /**
   * Google Universal Analytics (GA) code for Wordpress websites.
   *
   * This PHP script assumes that the tracking ID of the website
   * is defined in the named constant ANALYTICS_TRACKING_UID.
   * 
   * Created by Guido W. Pettinari.
   * Last version: https://gist.github.com/d5d01b3fde68ec56a18a
   */

  /* Shortcut to the directory separator */
  define (DIR_SEP, DIRECTORY_SEPARATOR);
  
  /* Working directory on the filesystem */
  define (ANALYTICS_DIR, dirname(__FILE__));

  /* Working directory on the web, needed to call javascripts */
  define (ANALYTICS_URI, dirname(get_stylesheet_uri()) . DIR_SEP . "analytics");

  /* Execute the script only if the tracking ID exists */
  if (defined('ANALYTICS_TRACKING_UID')) {

?>

<script>
  /* Generic tracking code for Google Universal Analytics */
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
  <?php echo "ga('create', '" . ANALYTICS_TRACKING_UID ."', 'auto');" ?>
</script>

<?php

    /* Is this page an ecommerce product? */
    $is_product = function_exists('is_product') && is_product();

    /* Scroll tracking script to track reading behaviour. It applies
    only to blog entries */
    // if (is_single() && !$is_product)
    if (is_single())
      get_template_part('analytics/scroll_tracking');

    /* Content grouping script to categorise the website content in GA. It
    applies to all post content, ie. both blog entries and product pages. */
    if (is_single())
      get_template_part('analytics/content_grouping');

?>

<script>
  /* Send a pageview hit to the GA servers, and transmit any information
  that was set above in the tracker. This line should be at the end
  of the script. */
  ga('send', 'pageview');
</script>

<?php
  
  } // if ANALYTICS_TRACKING_UID is defined
  
?>