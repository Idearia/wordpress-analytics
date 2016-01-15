/**
 * Javascript for use with Universal Google Analytics (GA) that tracks the
 * scrolling actions performed by the user of a website.
 *
 * This script is a version of the fantastic Advanced Content Tracking script
 * by Justin Cutroni, slightly modified by Guido Pettinari to include extra
 * documentation and adjust some parameters (see doc below). The original script
 * can be found at http://cutroni.com/blog/2014/02/12/advanced-content-tracking-
 * with-universal-analytics/, while the latest version of this script can be
 * found at https://gist.github.com/a1c715e2a448da2dfd69.
 *
 * This script defines the following GA events:
 *
 * - ArticleLoaded: the page has been loaded in the user browser.
 * - StartReading: the user scrolled down at least 150 pixels; the
 *     exact number of pixels can be changed via the readerLocation
 *     variable.
 * - ContentBottom: the user reached the end of the content section
 *     of the page; in the HTML of the page, the content section should
 *     should be delimeted by the entry-content tags (see h-entry
 *     microformat).
 * - PageBottom: the user reached the end of the page.
 * - ContentRead: the user spent more than 60 seconds on the page since
 *     s/he started scrolling AND s/he reached the end of the content
 *     section.
 *
 * .. and the following GA dimensions and metrics:
 *
 * - Reading behaviour (dimension1): can be either 'Scroller' if the
 *     user spent less than 60s on the page before reaching the end of
 *     the content section, or 'Reader' if s/he spent more than 60s.
 *     The exact amount of time can be changed via the timeThreshold 
 *     variable.
 * - timeToScroll (metric1): time in seconds between the loading of the
 *     page and the first scroll by the user.
 * - timeToContentEnd (metric2): time in seconds between the first scroll
 *     and reaching the end of the content section.
 * - totalTime (metric3): time between the first scroll and reaching
 *     the bottom of the page.
 *
 * My additions to Justin's script are:
 *
 * - Added the ContentRead event for when the user scrolls until the
 *   end of the content AND spends at least 60 seconds on the page.
 *
 * - Used the actual number of pixels scrolled down by the user to
 *   fire the StartReading event, rather than the bottom of the
 *   browser window.
 *
 * Created by Guido W. Pettinari on 23.12.2015.
 * Last version: https://gist.github.com/a1c715e2a448da2dfd69
 * Based on a script by Justin Cutroni.
 */

jQuery(function($) {
  // Debug flag, set to true to show useful alerts
  var debugMode = false;

  // Default time delay in ms before checking location
  var callBackTime = 100;

  // # px scrolled before tracking a reader
  var readerLocation = 350;

  // # seconds required to read the page
  var timeThreshold = 60;

  // Set some flags for tracking & execution
  var timer = 0;
  var scroller = false;
  var endContent = false;
  var didComplete = false;

  // Set some time variables to calculate reading time
  var startTime = new Date();
  var beginning = startTime.getTime();
  var totalTime = 0;

  // Get some information about the current page
  var pageTitle = document.title;

  // Track the article load
  if (!debugMode) {
      ga('send', 'event', 'Reading', 'ArticleLoaded', pageTitle, {'nonInteraction': 1});
  } else {
      alert('The page has loaded. Woohoo.');    
  }

  // Check the location and track user
  function trackLocation() {

    // number of pixels scrolled down by the user
    windowScroll = $(window).scrollTop()
    // lowest line shown in the browser window
    bottom = $(window).height() + windowScroll;
    // height of the document in pixels
    height = $(document).height();

    // If user starts to scroll send an event
    if (windowScroll > readerLocation && !scroller) {
      currentTime = new Date();
      scrollStart = currentTime.getTime();
      timeToScroll = Math.round((scrollStart - beginning) / 1000);
      if (!debugMode) {
        ga('send', 'event', 'Reading', 'StartReading', pageTitle, timeToScroll, {'metric1' : timeToScroll});
      } else {
        alert('started reading ' + timeToScroll);
      }
      scroller = true;
    }

    // If user has hit the bottom of the content send an event
    if (bottom >= $('.entry-content').scrollTop() + $('.entry-content').innerHeight() && !endContent) {
      currentTime = new Date();
      contentScrollEnd = currentTime.getTime();
      timeToContentEnd = Math.round((contentScrollEnd - scrollStart) / 1000);
      if (!debugMode) {
        // If the user reached the bottom of the content in less than a minute,
        // flag him/her as a scanner. Otherwise, flag him/her as a Reader, and
        // fire a ContentRead event.
        if (timeToContentEnd < timeThreshold) {
          ga('set', 'dimension1', 'Scanner');
        } else {
          ga('set', 'dimension1', 'Reader');
          ga('send', 'event', 'Reading', 'ContentRead', pageTitle, timeToContentEnd);
        }
        // Independently on whether the user is a scanner or a reader, s/he has reached
        // the bottom of the content
        ga('send', 'event', 'Reading', 'ContentBottom', pageTitle, timeToContentEnd, {'metric2' : timeToContentEnd});
      } else {
        if (timeToContentEnd < timeThreshold) {
          alert('end content section '+timeToContentEnd+', you are a scanner :-(');
        } else {
          alert('end content section '+timeToContentEnd+', you are a reader :-)');
        }
      }
      endContent = true;
    }

    // If user has hit the bottom of page send an event
    if (bottom >= height && !didComplete) {
      currentTime = new Date();
      end = currentTime.getTime();
      totalTime = Math.round((end - scrollStart) / 1000);
      if (!debugMode) {
        ga('send', 'event', 'Reading', 'PageBottom', pageTitle, totalTime, {'metric3' : totalTime});
      } else {
        alert('bottom of page '+totalTime);
      }
      didComplete = true;
    }
  }

  // Track the scrolling and track location
  $(window).scroll(function() {
    if (timer) {
        clearTimeout(timer);
    }

    // Use a buffer so we don't call trackLocation too often.
    timer = setTimeout(trackLocation, callBackTime);
  });
});
