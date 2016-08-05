/**
 * Javascript for use with Universal Google Analytics (GA) that tracks
 * form submissions within a webpage.
 *
 * Created by Guido W. Pettinari on 05.08.2016.
 * Part of Wordpress Analytics:
 * https://github.com/coccoinomane/wordpress_analytics
 */

jQuery(document).ready(function($) {

  // ==========================================================================
  // =                              Initialisation                            =
  // ==========================================================================

  /* Get the current script, using a selector that matches any src attributes
  that end with the filename of this file */
  var this_js_script = $('script[src*=form_tracking\\.js]');

  /* Debug flag, set to true to log useful messages */
  var debugMode = parseInt (this_js_script.attr('debug'));
  if (debugMode === undefined)
    debugMode = false;

  /* Extract the title of them form */
  var formTitle = this_js_script.attr('formTitle');
  if (formTitle === undefined)
    formTitle = "Form title unavailable";

  /* Get some information about the current page */
  var pageTitle = document.title;

  if (debugMode)
    console.log ("Inside form-tracking script for '" + formTitle "'");

  /* Send Google Analytics an event with the form title as action */
  ga('send', 'event', 'FormSubmission', formTitle, pageTitle);
  if (debugMode)
    console.log(' -> Sent form submission event for "' + formTitle + '"');

}); // $(document).ready

