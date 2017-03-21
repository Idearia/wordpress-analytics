/**
 * Javascript for use with Universal Google Analytics (GA) that tracks
 * clicks on emails within a webpage.
 *
 * To be tracked, the emails must be in the following markup:
 *
 *   <a href="mailto:info@apple.com">Whatever text there is.</a>
 *
 * Created by Guido W. Pettinari on 09.11.2016.
 * Part of Wordpress Analytics:
 * https://github.com/coccoinomane/wordpress_analytics
 */

jQuery(document).ready(function($) {


  // ==========================================================================
  // =                              Initialisation                            =
  // ==========================================================================

  /* Get the current script, using a selector that matches any src attributes
  that end with the filename of this file */
  var this_js_script = $('script[src*=email_tracking\\.js]');

  /* Parse the Google Analytics tracker name */
  var gaTrackerName = this_js_script.attr('gaTracker');
  if (gaTrackerName === undefined) {
    console.warn(" -> The 'gaTracker' argument was not found, using 'ga'");
    gaTrackerName = 'ga'
  }
  
  /* Check whether the tracker exists */
  var gaTracker = window[gaTrackerName];
  if (typeof gaTracker !== 'function') {
    console.warn(" -> The function '" + gaTrackerName + "' does not exist in the global scope");
  }

  /* Debug flag, set to true to log useful messages */
  var debugMode = parseInt (this_js_script.attr('debug'));
  if (debugMode === undefined)
    debugMode = false;

  /* Get some information about the current page */
  var pageTitle = document.title;
  var pagePath = window.location.pathname;


  // ===========================================================================
  // =                             Track email clicks                          =
  // ===========================================================================

  /* We shall add an event listener for all clicks on emails. Notice
  that the clicks do not necessarily convert to a sent email. */
  var emailSelector = $("a[href^='mailto:']");

  /* Number of clicks so far */
  var numberOfClicks = 0;

  emailSelector.click(function () {

    numberOfClicks++;

    var emailAddress = $(this).attr('href');

    if (debugMode)
      console.log(' -> Clicked on ' + emailAddress);

    /* Send the event, attaching the email address. Do so only
    if the user hasn't already clicked on the address before. */
    if (numberOfClicks == 1) {
      gaTracker('send', 'event', 'Contact', emailAddress, pagePath);
      if (debugMode)
        console.log(' -> Sent click event for ' + emailAddress);
    }
    else {
      if (debugMode)
        console.log(' -> Ignored click event #' + numberOfClicks + ' for ' + emailAddress);
    }

  });


  /**
   * Function to automatically escape special characters in regex patterns;
   * thanks to bobince on http://stackoverflow.com/a/3561711/2972183
   */

  RegExp.escape = function(s) {
    return s.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
  };


}); // $(document).ready

