/**
 * Javascript for use with Universal Google Analytics (GA) that tracks the
 * scrolling actions performed by the user of a website.
 *
 * This script is a version of the fantastic Advanced Content Tracking
 * script by Justin Cutroni, modified by Guido Pettinari to add flexibility
 * and documentation. The original script can be found on Justin's blog at
 * http://cutroni.com/blog/2014/02/12/advanced-content-tracking-with-unive
 * rsal-analytics/.
 *
 * This script defines the following GA events:
 *
 * - ArticleLoaded: the page has been loaded completely in the user browser.
 * - StartReading: the user was shown at least 200 pixels of content;
 *     the exact number of pixels can be changed via the readingThreshold
 *     variable.
 * - ContentBottom: the user reached the end of the content section
 *     of the page.
 * - PageBottom: the user reached the end of the page.
 * - ContentRead: the user spent more than 60 seconds scrolling the content
 *     part of the page.
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
 * The script attempts to autodetect the content type based on the 
 * HTML tags and attributes. The supported data formats are:
 *
 *  > h-entry microformat (entry-content)
 *  > Schema Products (http://schema.org/Product)
 *  > Schema Recipes (http://schema.org/Recipe)
 *
 * My additions to Justin's script are:
 *
 * - Jan. 2016: Added support for more content types:
 *     > h-entry microformat (http://microformats.org/wiki/h-entry)
 *     > Schema Products (http://schema.org/Product)
 *     > Schema Recipes (http://schema.org/Recipe)
 *
 * - Dec. 2015: Added the ContentRead event for when the user scrolls
 *   until the end of the content AND spends at least 60 seconds on
 *   the page.
 *
 * - Dec. 2015: Used the actual number of pixels scrolled down by the
 *   user to fire the StartReading event, rather than the bottom of the
 *   browser window.
 *
 * N.B: Test this script on your browser without the console! It
 * messes up with estimated size of the window and of the content.
 * 
 * Created by Guido W. Pettinari on 23.12.2015.
 * Part of Wordpress Analytics:
 * https://github.com/coccoinomane/wordpress_analytics
 * Based on a script by Justin Cutroni.
 */

jQuery(document).ready(function($) {

  $(document).imagesLoaded(function() {

    $(window).load(function() {
  
      // -----------------------------------------------------------------
      // -                       Initialisation                          -
      // -----------------------------------------------------------------

      /* Debug flag, set to true to log useful messages */
      var debugMode = true;

      /* First things first: send an event telling GA that the article has loaded */
      if (!debugMode)
        ga('send', 'event', 'Reading', 'ArticleLoaded', pageTitle, {'nonInteraction': 1});
      else
        console.log(' -> ArticleLoaded event sent');

      /* How often to track user location in ms */
      var callBackTime = 100;

      /* Pixels scrolled before tracking a reader */
      var readingThreshold = 200;

      /* Seconds required to read the content */
      var timeThreshold = 60;

      /* Set some flags for tracking & execution */
      var timer = 0;
      var startedReading = false;
      var endContent = false;
      var didComplete = false;

      /* Set some time variables to calculate reading time */
      var startTime = new Date();
      var beginning = startTime.getTime();
      var totalTime = 0;

      /* Get some information about the current page */
      var pageTitle = document.title;
      var documentLength = $(document).height();


      // -----------------------------------------------------------------
      // -                       Content prototype                       -
      // -----------------------------------------------------------------

      /* Prototype representing the content the user is supposed to read.
      By default, we assume the content is contained a single HTML element.
      The prototype also supports for contents that span multiple consecutive
      elements, from a start (startElement) to an end (endElement). For
      example, a Schema.org recipe that contains the following consecutive
      elements: details ,description, ingredients, instructions, will need to
      have:

        'startElement = #recipe-details-box'
        'endElement = .recipe-instructions_container'.
      
      TODO: Allow to input an arbitrary number of HTML elements; pick the top
      and bottom of these elements and compute the content length as the
      difference between the two. This approach is more flexible as some of
      the elements might be non-existing (eg. the optional 'recipe-note'
      element in the Schema recipe) and the methods will still work. */
      var Content = function () {
        
        /* Default values */
        this.resizeFactor = 1;
        this.isSet = false;
        this.name = "<content name undefined>";
        
        
        /**
         * Issues a warning if an element is not found in the DOM; returns
         * the element.
         */
        this.checkElement = function (element) {
          
          if (debugMode)
            console.log("Setting element: " + element.prop("tagName") +
                        " with ID=" + element.attr("id") +
                        " and class=" + element.attr("class"));
          
          if (element.length == 0)
            console.warn("Couldn't find element = '" + element.prop("tagName") + "'");
          
          return element;
          
        }


        /**
         * Set the HTML element where the content begins.
         */
        this.setStartElement = function (startElement) {
          
          this.startElement = this.checkElement (startElement);

        }
        

        /**
         * Set the HTML element where the content ends.
         */
        this.setEndElement = function (endElement) {

          this.endElement  = this.checkElement (endElement);

        }

        
        /**
         * Determines whether the content is ready to be used
         */
        this.isSet = function () {
          
          if (this.startElement !== undefined && this.startElement.length &&
              this.endElement !== undefined && this.endElement.length)
            this.isSet = true;
            
          return this.isSet;

        }
        

        /**
         * Compute distance of the content's beginning from the top of
         * the document.
         */
        this.computeStartPixel = function () {

          this.startPixel = this.startElement.offset().top;
          return this.startPixel;

        };


        /**
         * Compute distance of the content's ending from the top of
         * the document.
         */
        this.computeEndPixel = function () {

          this.endPixel = this.endElement.offset().top + this.endElement.outerHeight();
          return this.endPixel;

        };


        /**
         * Compute length/height of the content
         */
        this.computeHeight = function () {

          if (!this.startPixel)
            this.computeStartPixel();

          if (!this.endPixel)
            this.computeEndPixel();

          this.height = this.endPixel - this.startPixel;
          this.height = parseInt (this.height * this.resizeFactor);

          if (this.height <= 0)
            console.warn ("found negative length = " + this.height + "for content " + this.name);

          return this.height;
        };
        
      }; // Content constructor



      // -----------------------------------------------------------------
      // -                      Identify content                         -
      // -----------------------------------------------------------------

      var content = new Content();

      var postSelector = $('article[id^="post-"]').find('div.entry-content');
      var recipeSelector = $('div[itemtype="http://schema.org/Recipe"]');
      var productSelector = $('div[itemtype="http://schema.org/Product"]');
      
      /* Does this post contain a blog entry according to the hentry/hatom microformat? */
      if (postSelector.length && !recipeSelector.length && !productSelector.length) {
        content.setStartElement(postSelector);
        content.setEndElement(postSelector);
        content.name = 'Blog entry';
        content.resizeFactor = 0.85;
      }

      /* Does this post contain a recipe according to the Recipe schema? */
      else if (recipeSelector.length && !productSelector.length) {
        content.setStartElement($('#recipe-details-box'));
        content.setEndElement($('.recipe-instructions_container'));
        content.name = 'Recipe';
        content.resizeFactor = 0.85;
      }

      /* Does this post contain a product according to the Product schema? */
      else if (productSelector.length) {
        content.setStartElement(productSelector);
        content.setEndElement(productSelector);
        content.name = 'Product';
        content.resizeFactor = 0.85;
      }

      /* If the page does not belong to any of the above cases, try with the
      generic semantics, and issue a warning event to GA. */
      else {
        
        if ($('.single-content').length) {
          content.setStartElement($('.single-content'));
          content.setEndElement(content.startElement);
          content.name = '.single-content';
          content.resizeFactor = 0.85;
        }
      
        else if ($('#content').length) {
          content.setStartElement($('#content'));
          content.setEndElement(content.startElement);
          content.name = '#content';
          content.resizeFactor = 0.85;
        }
      
        else if ($('#main-content').length) {
          content.setStartElement($('#main-content'));
          content.setEndElement(content.startElement);
          content.name = '#main-content';
          content.resizeFactor = 0.85;
        }
      
        /* If we did not recognize the content type, take the whole body of
        the HTML page, issue a warning, and send an event to GA */
        else {
          content.setStartElement($(document.body));
          content.setEndElement(content.startElement);
          content.name = "<body>";
        }

        console.warn(" -> Content type could not be identified properly, using " + content.name);
        ga('send', 'event', 'Reading', 'ContentGuessed', pageTitle, {'nonInteraction': 1});
        
      }

      /* If both the start and end elements exist, compute the content height in pixels */
      if (content.isSet) {
        contentStart = content.computeStartPixel();
        contentLength = content.computeHeight();
      }

      /* Print some useful info */
      if (debugMode) {
        console.log("Identified the following content type: '" + content.name + "'");
        console.log("readingThreshold = " + readingThreshold);
        console.log("documentLength = " + documentLength);
        console.log("contentStart = " + contentStart);
        console.log("contentLength = " + contentLength);
      }


      // -----------------------------------------------------------------
      // -                      Track user location                      -
      // -----------------------------------------------------------------

      /* Function that will be run every time the user scrolls the page */

      function trackLocation() {

        /* How much the user has scrolled down so far */
        var windowScroll = $(window).scrollTop();

        /* Bottom of the user's browser window */
        var bottom = $(window).height() + windowScroll; 

        /* Amount of *content* the user has scrolled so far */
        var contentShown = bottom - contentStart;

        /* If the content is in a scrollable box, let's take
        note of how much the user scrolled down in that box.
        If not, this line won't do anything. */
        contentShown += content.startElement.scrollTop();
        if (content.startElement !== content.endElement)
          contentShown += content.endElement.scrollTop();

        /* Print some useful info */
        if (debugMode) {
          console.log("windowScroll = " + windowScroll);
          console.log("bottom = " + bottom);
          console.log("scrollTop = " + content.endElement.scrollTop());
          console.log("contentShown = " + contentShown);
        }

        /* If user was shown at least 'readingThreshold' pixels of content, send an
        event. If the content starts right at the top of the page, it might be that
        the shown content exceed the threshold even if the user scrolls one pixel;
        therefore, we ask the user to actually scroll at least readingThreshold/2
        pixels. */
        if (!startedReading && contentShown > readingThreshold && windowScroll > readingThreshold/2) {

          startedReading = true;
          currentTime = new Date();
          scrollStart = currentTime.getTime();
          timeToScroll = Math.round((scrollStart - beginning) / 1000);

          if (!debugMode) {
            ga('send', 'event', 'Reading', 'StartReading', pageTitle, timeToScroll, {'metric1' : timeToScroll});
          }
          else {
            console.log(' -> Started reading (' + timeToScroll + 's)');
          }
        }

        // If user reached the end of the content, send an event.    
        if (!endContent && contentShown > contentLength) {

          endContent = true;
          currentTime = new Date();
          contentScrollEnd = currentTime.getTime();
          timeToContentEnd = Math.round((contentScrollEnd - scrollStart) / 1000);

          if (!debugMode) {
            /* If the user reached the bottom of the content in less than a minute,
            flag him/her as a scanner. Otherwise, flag him/her as a Reader, and
            fire a ContentRead event. */
            if (timeToContentEnd < timeThreshold) {
              ga('set', 'dimension1', 'Scanner');
            }
            else {
              ga('set', 'dimension1', 'Reader');
              ga('send', 'event', 'Reading', 'ContentRead', pageTitle, timeToContentEnd);
            }

            /* In both cases, tell GA that the user reached the bottom of the content */
            ga('send', 'event', 'Reading', 'ContentBottom', pageTitle, timeToContentEnd, {'metric2' : timeToContentEnd});

          }
          else {
            if (timeToContentEnd < timeThreshold) {
              console.log(' -> End of content section ('+timeToContentEnd+'s), you are a scanner :-(');
            }
            else {
              console.log(' -> End of content section ('+timeToContentEnd+'s), you are a reader :-)');
            }
          }

        }

        /* If user has hit the bottom of page, send an event */
        if (!didComplete && bottom >= documentLength) {

          didComplete = true;
          currentTime = new Date();
          end = currentTime.getTime();
          totalTime = Math.round((end - scrollStart) / 1000);

          if (!debugMode) {
            ga('send', 'event', 'Reading', 'PageBottom', pageTitle, totalTime, {'metric3' : totalTime});
          }
          else {
            console.log(' -> Bottom of page (' + totalTime + 's)');
          }
        }

      } // end of trackLocation


      /* Track the scrolling and track location only when the user scrolls down */
      $(window).scroll(function() {
        if (timer) {
            clearTimeout(timer);
        }
        // Use a buffer so we don't call trackLocation too often.
        timer = setTimeout(trackLocation, callBackTime);
      });

    }); // $(window).load
  }); // $(document).imagesLoaded
}); // jQuery(document).ready
