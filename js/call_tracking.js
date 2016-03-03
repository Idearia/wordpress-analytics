/**
 * Javascript for use with Universal Google Analytics (GA) that tracks
 * clicks on phone numbers within a webpage.
 *
 * To be tracked, the phone numbers must be in the following markup:
 *
 *   <a href="tel:+1-303-499-7111">+1 (303) 499-7111</a>
 *
 * See https://developers.google.com/web/fundamentals/native-hardware
 * /click-to-call/?hl=en for further details.
 *
 * This script optionally applies the correct 'tel:' markup to phone
 * numbers if they haven't it already. To to so, pass the attribute
 * detectPhoneNumbers='1' in the HTML tag calling this script.
 * 
 * Created by Guido W. Pettinari on 02.03.2016.
 * Part of Wordpress Analytics:
 * https://github.com/coccoinomane/wordpress_analytics
 */

jQuery(document).ready(function($) {


  // ==========================================================================
  // =                              Initialisation                            =
  // ==========================================================================

  /* Get the current script, using a selector that matches any src attributes
  that end with the filename of this file */
  var this_js_script = $('script[src*=call_tracking\\.js]');

  /* Debug flag, set to true to log useful messages */
  var debugMode = parseInt (this_js_script.attr('debug'));
  if (debugMode === undefined)
    debugMode = false;

  /* Should we automatically add the 'tel:' markup to phone numbers in the page? */
  var detectPhoneNumbers = this_js_script.attr('detectPhoneNumbers');
  if (detectPhoneNumbers === undefined)
    detectPhoneNumbers = false;

  /* Regex pattern used to validate & find phone numbers in the webpage */
  var regexPattern = this_js_script.attr('regexPattern');
  if (regexPattern === undefined)
    regexPattern = '';

  /* Get some information about the current page */
  var pageTitle = document.title;

  /* Delimiters that can appear in phone numbers */
  var delimiters = [' ', '.', '-', ','];



  // ===========================================================================
  // =                             Track phone clicks                          =
  // ===========================================================================

  /* We shall add an event listener for all clicks on phone numbers. Notice
  that the clicks do not necessarily convert to a phone call, as the mobile
  usually asks for confirmation before calling */
  var telSelector = $("a[href^='tel:']");

  telSelector.click(function () {

    var phoneNumber = $(this).attr('href');

    if (debugMode)
      console.log(' -> Click on ' + phoneNumber);
    
    /* If the user specified a pattern, then consider only phone numbers that
    match that pattern */
    /* TODO: fix this */
    // if (regexPattern && phoneNumber.search('/' + regexPattern + '/g') < 0) {
    //   if (debugMode)
    //     console.log(' -> User provided pattern (' + regexPattern + ') not matched, ignoring click');
    //   return false;
    // }
      
    /* Send to GA an event, using the phone number as the event action.
    In order to avoid spurious events due to inconsistent naming conventions,
    we strip all delimiters from the phone number before sending the event */
    var stripPattern = '[' + RegExp.escape (delimiters.join('')) + ']';
    var stripRegex = new RegExp(stripPattern, 'g');
    var strippedPhoneNumber = phoneNumber.replace(stripRegex,'');

    // ga('send', 'event', 'Calling', strippedPhoneNumber, pageTitle);

    if (debugMode)
      console.log(' -> Sent click event for ' + phoneNumber + ' (-> ' + strippedPhoneNumber + ')');

  });


  // ==========================================================================
  // =                            Find phone numbers                          =
  // ==========================================================================

  if (detectPhoneNumbers) {

    /* TODO: Implement automatic detection of phone numbers */
    
  }


  /**
   * Function to automatically escape special characters in regex patterns;
   * thanks to bobince on http://stackoverflow.com/a/3561711/2972183
   */

  RegExp.escape = function(s) {
    return s.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
  };


}); // $(document).imagesLoaded

