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
    $content_grouping = is_single() && isset ( $options ['content_grouping'] ) && $options ['content_grouping'];
    $scroll_tracking = is_single() && isset ( $options ['scroll_tracking'] ) && $options ['scroll_tracking'];
    $call_tracking = isset ( $options ['call_tracking'] ) && $options ['call_tracking'];
    $custom_code = isset ( $options ['custom_code'] ) && $options ['custom_code'];
    $enhanced_link_attribution = isset ( $options['enhanced_link_attribution'] ) && $options['enhanced_link_attribution'];
    $vertical_booking_support = isset ( $options['vertical_booking_support'] ) && $options['vertical_booking_support'];

    /* Execute the script only if the tracking ID exists */
    if ($tracking_uid) {

      echo PHP_EOL . PHP_EOL . "<-- BEGIN: Tracking code inserted by Wordpress Analytics " . WPAN_VERSION . " - " . WPAN_URL . "-->" . PHP_EOL;

?>

<script>
  /* Generic tracking code for Google Universal Analytics */
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
  /* Pass the tracking UID of the GA property associated to this website */
  <?php
    if ( ! $vertical_booking_support ) {
      echo "ga('create', '" . $tracking_uid . "', 'auto');\n";
    }
    else {
      echo "ga('create', '" . $tracking_uid . "', 'auto', {'allowLinker': true});\n";
    }
  ?>
</script>

<?php
 
    /* Scroll tracking script to track reading behaviour */
    if ( $scroll_tracking ) {
      wordpress_analytics_scroll_tracking();
    }

    /* Call tracking script to track clicks on phone number links */
    if ( $call_tracking ) {
      wordpress_analytics_call_tracking();
    }

    /* Content grouping script to categorise the website content in GA */
    if ( $content_grouping ) {
      wordpress_analytics_content_grouping();
    }

    /* Enable Vertical Booking support */
    if ( $vertical_booking_support ) {
      echo "<script> ga('require', 'linker'); </script>\n";
      echo "<script> ga('linker:autoLink', ['verticalbooking.com'], true, true); </script>\n";
      echo "<script> ga('require', 'displayfeatures'); </script>\n"; 
    }

    /* Enable Enhanced Link attribution */
    if ( $enhanced_link_attribution ) {
      echo "<script> ga('require', 'linkid'); </script>\n";
    }

    /* Execute code specified by the user */
    if ( $custom_code ) {
      echo php_eval( $options ['custom_code'] );
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
    
    echo PHP_EOL . "<-- END: Tracking code inserted by Wordpress Analytics " . WPAN_VERSION . " - " . WPAN_URL . "-->" . PHP_EOL . PHP_EOL;
    
  } // end of function
  
?>