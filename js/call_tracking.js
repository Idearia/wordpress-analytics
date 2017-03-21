/**
 * Javascript for use with Universal Google Analytics (GA) that tracks
 * clicks on phone numbers within a webpage.
 *
 * To be tracked, the phone numbers must be in the following markup:
 *
 *   <a href="tel:+1-303-499-7111">Whatever text there is.</a>
 *
 * See https://developers.google.com/web/fundamentals/native-hardware
 * /click-to-call/?hl=en for further details.
 *
 * Optionally, the script will also spot text in the page in the form
 * TEL: +39 06 123456 or TEL: +39-06-123456 and convert it into
 *
 *   <a href="tel:+3906123456">06123456</a>.
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

  /* Should we automatically add the 'tel:' markup to phone numbers in the page? */
  var detectPhoneNumbers = this_js_script.attr('detectPhoneNumbers');
  if (detectPhoneNumbers === undefined)
    detectPhoneNumbers = true;

  /* Regex pattern (include) used to validate & find phone numbers in the webpage */
  var regexIncludePattern = this_js_script.attr('regexIncludePattern');
  if (regexIncludePattern === undefined)
    regexIncludePattern = '';

  /* Regex pattern (exclude) used to validate & find phone numbers in the webpage */
  var regexExcludePattern = this_js_script.attr('regexExcludePattern');
  if (regexExcludePattern === undefined)
    regexExcludePattern = '';

  /* Get some information about the current page */
  var pageTitle = document.title;
  var pagePath = window.location.pathname;

  /* Delimiters that can appear in phone numbers */
  var delimiters = [' ', '.', '-', ','];



  // ==========================================================================
  // =                            Find phone numbers                          =
  // ==========================================================================

  /* Look for phone numbers in the page and enclose them in a 'tel:' link */

  if (detectPhoneNumbers) {

    /* Regex to match phone numbers */
    var telPattern = 'TEL: (?:(\\+\\d*)[ -]?)?(?:(\\d*)[ -]?)?(?:(\\d*)[ -]?)?';
    var telRegex = new RegExp(telPattern, 'g');

    /* List of places where to look for telephone numbers. Keep in mind that
    all matched elements containing a phone number will be rewritten, thus
    potentially interfering with other scripts. Therefore, I suggest you
    try to be specific in your choice, ex. look for the specific div.class
    rather than for $(document.body). */
    var telContainersStrings = [
      '[class^="contact"]',
    ];
    var telContainers = jQuery(telContainersStrings.join(',')).filter(function() {
      return telRegex.test(jQuery(this).text());
    });

    /* Replace the HTML in the selected elements using the telephone regex */
    if (telContainers.length > 0) {
      var newHTML = telContainers.html().replace(telRegex, "<a href='tel:$1$2$3'>$2 $3</a>");
      telContainers.html(newHTML);
    }

  }



  // ===========================================================================
  // =                             Track phone clicks                          =
  // ===========================================================================

  /* We shall add an event listener for all clicks on phone numbers. Notice
  that the clicks do not necessarily convert to a phone call, as the mobile
  usually asks for confirmation before calling */
  var telSelector = $("a[href^='tel:']");

  /* Number of clicks so far */
  var numberOfClicks = 0;

  telSelector.click(function () {

    numberOfClicks++;

    var phoneNumber = $(this).attr('href');

    if (debugMode)
      console.log(' -> Clicked on ' + phoneNumber);

    /* Consider only phone numbers that match the given include pattern */
    if (regexIncludePattern) {
      var includeRegex = new RegExp(regexIncludePattern, 'g');
      if (phoneNumber.search(includeRegex) < 0) {
        if (debugMode)
          console.log(' -> User provided inclusion pattern (' + regexIncludePattern + ') not matched, ignoring click');
        return false;
      }
    }

    /* Do not consider phone numbers that match the given exclude pattern */
    if (regexExcludePattern) {
      var excludeRegex = new RegExp(regexExcludePattern, 'g');
      if (phoneNumber.search(excludeRegex) >= 0) {
        if (debugMode)
          console.log(' -> User provided exclusion pattern (' + regexExcludePattern + ') matched, ignoring click');
        return false;
      }
    }

    /* Send to GA an event, using the phone number as the event action.
    In order to avoid spurious events due to inconsistent naming conventions,
    we strip all delimiters from the phone number before sending the event */
    var delimitersPattern = '[' + RegExp.escape (delimiters.join('')) + ']';
    var delimitersRegex = new RegExp(delimitersPattern, 'g');

    /* For the same reason, we also strip any 00 or + symbol from the beginning
    of the phone number */
    var prefixPattern = '(tel:)(00|\\+)';
    var prefixRegex = new RegExp(prefixPattern, 'g');
    var strippedPhoneNumber = phoneNumber.replace(delimitersRegex,'').replace(prefixRegex,'$1');

    /* Send the event, attaching phone number & page information. Do so only
    if the user hasn't already clicked on the phone number before. */
    if (numberOfClicks == 1) {
      gaTracker('send', 'event', 'Contact', strippedPhoneNumber, pagePath);
      if (debugMode)
        console.log(' -> Sent click event for ' + phoneNumber + ' (-> ' + strippedPhoneNumber + ')');
    }
    else {
      if (debugMode)
        console.log(' -> Ignored click event #' + numberOfClicks + ' for ' + phoneNumber);
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

