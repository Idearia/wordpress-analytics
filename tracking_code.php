<?php

  /**
   * Insert Google Analytics (GA) tracking code in Javascript
   * in the .
   *
   * Anything echoed by this function will be written in the 
   * head of all pages.
   *
   * Created by Guido W. Pettinari on 23.12.2015.
   * Part of WordPress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */

  function wpan_tracking_code () {
    
    /* Extract the tracking UID from the database */
    $options = wpan_get_options ();
    $tracking_uid = isset ( $options ['tracking_uid'] ) ? $options ['tracking_uid'] : '';
    $create_tracker = isset ( $options ['create_tracker'] ) ? $options ['create_tracker'] : '';
    $send_page_view = isset ( $options ['send_page_view'] ) ? $options ['send_page_view'] : '';
    $ga_tracker = isset ( $options ['tracker_name'] ) ? $options ['tracker_name'] : '';
    $content_grouping = is_single() && isset ( $options ['content_grouping'] ) && $options ['content_grouping'];
    $scroll_tracking = is_single() && isset ( $options ['scroll_tracking'] ) && $options ['scroll_tracking'];
    $call_tracking = isset ( $options ['call_tracking'] ) && $options ['call_tracking'];
    $email_tracking = isset ( $options ['email_tracking'] ) && $options ['email_tracking'];
    $custom_code = isset ( $options ['custom_code'] ) && $options ['custom_code'];
    $enhanced_link_attribution = isset ( $options['enhanced_link_attribution'] ) && $options['enhanced_link_attribution'];
    $cross_domain_support = isset ( $options['cross_domain_support'] ) && $options['cross_domain_support'];

    echo PHP_EOL . PHP_EOL . "<!-- BEGIN: Tracking code inserted by WordPress Analytics " . WPAN_VERSION . " - " . WPAN_URL . "-->" . PHP_EOL;

    /* Execute the script only if the tracking ID exists */
    if (!$tracking_uid) {
      echo  "<!-- Error: empty tracking uid -->";
    }
    /* Execute the script only if the tracker name exists */
    else if (!$ga_tracker) {
      echo  "<!-- Error: empty tracker name -->";
    }
    else {

      /* Load the analytics.js library & create the tracker */
      if ($create_tracker) {
?>

<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js',<?php echo("'$ga_tracker'"); ?>);
  /* Pass the tracking UID of the GA property associated to this website */
  <?php
    if ( ! $cross_domain_support ) {
      echo "$ga_tracker('create', '" . $tracking_uid . "', 'auto');\n";
    }
    else {
      /* Alas, the allowLinker field can be passed only within a create command
      (https://developers.google.com/analytics/devguides/collection/analyticsjs/
      field-reference#allowLinker) */
      echo "$ga_tracker('create', '" . $tracking_uid . "', 'auto', {'allowLinker': true});\n";
    }
  ?>
</script>

<?php
 
      }

      /* Scroll tracking script to track reading behaviour */
      if ( $scroll_tracking ) {
        wpan_scroll_tracking();
      }

      /* Call tracking script to track clicks on phone number links */
      if ( $call_tracking ) {
        wpan_call_tracking();
      }

      /* Email tracking script to track clicks on email links */
      if ( $email_tracking ) {
        wpan_email_tracking();
      }

      /* Content grouping script to categorise the website content in GA */
      if ( $content_grouping ) {
        wpan_content_grouping();
      }

      /* Enable Cross Domain support */
      if ( $cross_domain_support ) {
        echo "<script> $ga_tracker('require', 'linker'); </script>\n";
        echo "<script> $ga_tracker('require', 'displayfeatures'); </script>\n"; 
      }

      /* Enable Enhanced Link attribution */
      if ( $enhanced_link_attribution ) {
        echo "<script> $ga_tracker('require', 'linkid'); </script>\n";
      }

      /* Execute code specified by the user */
      if ( $custom_code ) {
        echo wpan_php_eval( $options ['custom_code'] );
      }

      /* Send a pageview hit to the GA servers, and transmit any information
      that was set above in the tracker. This line should be at the end
      of the script. */
      if ($send_page_view) {
        echo "<script> $ga_tracker('send', 'pageview'); </script>\n";
      }

    } // if $tracking_uid
    
    echo "<!-- END: Tracking code inserted by WordPress Analytics " . WPAN_VERSION . " - " . WPAN_URL . "-->" . PHP_EOL . PHP_EOL;
    
  } // end of function
  
?>