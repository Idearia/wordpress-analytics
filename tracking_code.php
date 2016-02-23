<?php

  /**
   * Insert Google Analytics (GA) code for Wordpress websites,
   * including scroll tracking and content grouping.
   *
   * Created by Guido W. Pettinari on 23.12.2015.
   * Part of Wordpress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */

  function wordpress_analytics_tracking_code () {
    
    /* Extract the tracking UID from the database */
    $options = get_option ("wpan:option_array");
    $tracking_uid = $options ['tracking_uid'];

    /* Execute the script only if the tracking ID exists */
    if ($tracking_uid) {

?>

<script>
  /* Generic tracking code for Google Universal Analytics */
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
  <?php echo "ga('create', '" . $tracking_uid . "', 'auto');\n" ?>
</script>

<?php

      /* Is this page an ecommerce product? */
      $is_product = function_exists('is_product') && is_product();

      /* Scroll tracking script to track reading behaviour. It applies
      only to blog entries */
      if (is_single() && $options ['scroll_tracking'])
        wordpress_analytics_scroll_tracking();

      /* Content grouping script to categorise the website content in GA. It
      applies to all post content, ie. both blog entries and product pages. */
      if (is_single() && $options ['content_grouping'])
        wordpress_analytics_content_grouping();

?>

<script>
  /* Send a pageview hit to the GA servers, and transmit any information
  that was set above in the tracker. This line should be at the end
  of the script. */
  ga('send', 'pageview');
</script>

<?php
  
    } // if $tracking_uid
    
  } // end of function
  
?>