<?php

  /**
   * Insert Google Analytics (GA) code for Wordpress websites,
   * including scroll tracking and content grouping.
   *
   * Anything echoed by this function will be written in the 
   * head of all pages.
   *
   * Created by Guido W. Pettinari on 23.12.2015.
   * Part of Wordpress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */

  function wordpress_analytics_tracking_code () {
    
    /* Extract the tracking UID from the database */
    $options = wpan_get_options ();
    $tracking_uid = isset ( $options ['tracking_uid'] ) ? $options ['tracking_uid'] : '';

    /* Execute the script only if the tracking ID exists */
    if ($tracking_uid) {

?>

<script>
  /* Generic tracking code for Google Universal Analytics */
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
  /* Pass the tracking UID of the GA property associated to this website */
  <?php echo "ga('create', '" . $tracking_uid . "', 'auto');\n";?>
</script>

<?php
 
    /* Is this page an ecommerce product? */
    $is_product = function_exists('is_product') && is_product();

    /* Scroll tracking script to track reading behaviour */
    if ( is_single() && isset ( $options ['scroll_tracking'] ) && $options ['scroll_tracking'] ) {
      wordpress_analytics_scroll_tracking();
    }

    /* Call tracking script to track clicks on phone number links */
    if ( isset ( $options ['call_tracking'] ) && $options ['call_tracking'] ) {
      wordpress_analytics_call_tracking();
    }

    /* Content grouping script to categorise the website content in GA */
    if ( is_single() && isset ( $options ['content_grouping'] ) && $options ['content_grouping'] ) {
      wordpress_analytics_content_grouping();
    }

    /* Enable Vertical Booking support */
    if ( isset ( $options['vertical_booking_support'] ) && $options['vertical_booking_support'] ) {
      echo "<script> ga('require', 'linker'); </script>\n";
      echo "<script> ga('linker:autoLink', [/\\.(com|net)$/], true, true); </script>\n";
      echo "<script> ga('require', 'displayfeatures'); </script>\n"; 
    }

    /* Enable Enhanced Link attribution */
    if ( isset ( $options['enhanced_link_attribution'] ) && $options['enhanced_link_attribution'] ) {
      echo "<script> ga('require', 'linkid'); </script>\n";
    }

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