/**
 * Javascript for use with Google Analytics 4 (GA4) that tracks the
 * scrolling actions performed by the user of a website.
 *
 * This script is a version of the fantastic Advanced Content Tracking
 * script by Justin Cutroni, modified by Guido Pettinari to add flexibility
 * and documentation. The original script can be found on Justin's blog at
 * http://cutroni.com/blog/2014/02/12/advanced-content-tracking-with-unive
 * rsal-analytics/.
 *
 * This script sends the following GA4 events, all with the page_path
 * parameter:
 *
 * - article_loaded: the page has loaded completely in the user browser,
 *     images included.
 * - start_reading: the user was shown at least 300 pixels of content;
 *     the exact number of pixels can be changed via the pixelThreshold
 *     variable. Parameter time_to_scroll: time in seconds between the
 *     loading of the page and the first scroll by the user.
 * - content_bottom: the user reached the end of the content section
 *     of the page. Parameters: time_to_content_end, time in seconds
 *     between the first scroll and reaching the end of the content
 *     section; reading_behaviour, either 'Scanner' if that took less
 *     than 60s or 'Reader' if it took more. The exact amount of time
 *     can be changed via the timeThreshold variable.
 * - content_read: the user is a 'Reader', i.e. spent more than 60
 *     seconds scrolling the content part of the page. Parameter:
 *     time_to_content_end.
 * - page_bottom: the user reached the end of the page. Parameter
 *     total_time: time in seconds between the first scroll and
 *     reaching the bottom of the page.
 * - content_guessed, content_too_short: the content of the page could
 *     not be identified properly.
 *
 * To see the parameters in the GA4 reports, register them in the GA4
 * admin as custom metrics (the times) and as a custom dimension
 * (reading_behaviour).
 *
 * The events are sent with gtag.js or, if Google Analytics 4 is loaded by
 * Google Tag Manager, pushed to the dataLayer.
 *
 * The pixel and time thresholds can be customized by specifying the
 * pixelThreshold and timeThreshold attributes in the script tag,
 * respectively.
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
 * - Feb. 2016: The script now automatically excludes comments from the
 *   the definition of content.
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
 * Part of WordPress Analytics:
 * https://github.com/coccoinomane/wordpress_analytics
 * Based on a script by Justin Cutroni.
 */

jQuery(document).ready(function($) {

  /* Wait for the page to be completely loaded, images included, lest the
  size of the content is not estimated correctly */
  var whenLoaded = function (callback) {
    if (document.readyState === 'complete')
      callback();
    else
      $(window).on('load', callback);
  };

  whenLoaded(function() {

    (function() {
  
      // -----------------------------------------------------------------
      // -                       Initialisation                          -
      // -----------------------------------------------------------------

      /* Get the current script, using a selector that matches any src attributes
      that end with the filename of this file */
      var this_js_script = $('script[src*=scroll_tracking\\.js]');

      /* Function that sends an event to Google Analytics 4, defined in the
      head of the page by WordPress Analytics */
      var sendEvent = window.wpanSendEvent;

      /* Debug flag, set to true to log useful messages */
      var debugMode = parseInt (this_js_script.attr('debug'));
      if (debugMode === undefined)
        debugMode = false;
      
      /* Pixels scrolled before considering the user engaged */
      var pixelThreshold = this_js_script.attr('pixelThreshold');
      if (pixelThreshold === undefined)
        pixelThreshold = 300;

      /* Seconds required to read the content */
      var timeThreshold = this_js_script.attr('timeThreshold');
      if (timeThreshold === undefined)
        timeThreshold = 60;

      /* How often to track user location in ms */
      var callBackTime = 100;

      /* We don't want our user to really read all of that stuff, don't we? :-) */
      var resizeFactor = 0.85;

      /* Set some flags for tracking & execution */
      var timer = 0;
      var startedReading = false;
      var endContent = false;
      var didComplete = false;

      /* Set some time variables to calculate reading time */
      var startTime = new Date();
      var beginning = startTime.getTime();
      var totalTime = 0;
      var currentTime, scrollStart, timeToScroll, contentScrollEnd, timeToContentEnd, end;

      /* Get some information about the current page */
      var documentLength = $(document).height();

      /* First analytics action: send an event telling GA that the article has loaded */
      sendEvent('article_loaded');
      if (debugMode)
        console.log(' -> ArticleLoaded event sent');


      // -----------------------------------------------------------------
      // -                       Content prototype                       -
      // -----------------------------------------------------------------

      /* Object representing the content the user is supposed to read.
      We represent a content as a group of consecutive HTML elements. For
      example, a Schema.org contains the following elements: details,
      description, ingredients, instructions. */

      var Content = function (name, resizeFactor, debugMode) {

        /* Attributes that need to be set externally by the user */
        this.name = (typeof name !== 'undefined') ? name : '<undefined>';
        this.resizeFactor = (typeof resizeFactor !== 'undefined') ? resizeFactor : 1;
        this.debugMode = (typeof debugMode !== 'undefined') ? debugMode : false;
        
        /* Attributes computed internally */
        this.elements = $([]);
        this.n_elements = 0;
        this.startPixel = $(document).height();
        this.endPixel = 0;
        this.height = 0;

        /* Use 'self' instead of 'this' in functions that are supposed to
        be called via the .each() method */
        var self = this;


        /**
         * Add an HTML element to the content.
         *
         * This function has to be called via the .each() method.
         */
        this.addElement = function (index) {

          var element = $(this);

          /* Skip the current element if it is invalid */
          if (!(element instanceof jQuery)) {
            console.warn ('You are trying to add an invalid element: ' + element);
            return false;
          }

          /* Skip the current element if it was not found in the page */
          if (element.length == 0) {
            return false;
          }

          if (debugMode)
            console.log ("Added " + element[0].outerHTML.split(element.html())[0] + " to content '" + self.name + "'");
          
          /* Add the element to the array of elements making up the content */
          self.elements = self.elements.add (element);
          self.n_elements++;
          
          /* Self consistency check */
          if (self.n_elements !== self.elements.length)
            console.warn ('Error in elements count: ' + self.n_elements + '!=' + self.elements.length);

          /* Where the current element starts */
          var elementStartPixel = element.offset().top;

          /* Update the start pixel of the whole content */
          self.startPixel = Math.min (self.startPixel, elementStartPixel);
          self.startPixel = parseInt (self.startPixel);

          /* Update the end pixel of the whole content */
          self.endPixel = Math.max (self.endPixel, elementStartPixel + element.outerHeight());
          self.endPixel = parseInt (self.endPixel);
        
          /* Update the total length of the content */
          self.updateHeight ();
        
        };
        

        /**
         * Update the pixel height of the content
         */
        this.updateHeight = function (index) {
          
          /* Update the total length of the content */
          self.height = self.endPixel - self.startPixel;
        
          /* Apply the rescaling factor */
          self.height = parseInt (self.height * self.resizeFactor);
          
          /* Self consistency check */
          if (self.height < 0)
            console.warn ('Found negative length for content: start=' + self.startPixel + ', end=' + self.endPixel);

        };

      }; // Content constructor



      // -----------------------------------------------------------------
      // -                      Identify content                         -
      // -----------------------------------------------------------------

      /* Variable that will contain the content */
      var content;

      /* Selector for a WordPress post */
      var postSelector = $('article[id^="post-"], article.single-post, #blogread');

      /* Selector for a Schema.org recipe */
      var recipeSelector = $('article[itemtype="http://schema.org/Recipe"]');
      if (!recipeSelector.length)
        recipeSelector = $('div[itemtype="http://schema.org/Recipe"]');

      /* Selector for a Schema.org product */
      var productSelector = $('div[itemtype="http://schema.org/Product"]');
      
      /* Does this post contain a blog entry according to the hentry/hatom microformat? */
      if (postSelector.length && !recipeSelector.length && !productSelector.length) {
        content = new Content ('Blog entry', resizeFactor, debugMode);
        var postSelectorStrings = [
          'div.entry-content',
          '.article__content',
          '.article_content',
          '.blog-content',
        ];
        postSelector.find(postSelectorStrings.join(',')).each(content.addElement);

        /* Do not consider the top image as content */
        /* TODO: For more accurate results, look for the top image in content.elements
        rather than in postSelector. */
        var imageSelector = postSelector.find('p:first').find('img:first');
        if (imageSelector.length) {
          if (debugMode)
            console.log ('Will not consider the first image as content');
          content.startPixel = parseInt (imageSelector.offset().top + imageSelector.outerHeight());
          content.updateHeight();
        }
      }

      /* Does this post contain a recipe according to the Recipe schema? Note that we 
      do not consider recipes generated with the EasyRecipe plugin as recipes, because
      their markup is not complete. */
      else if (recipeSelector.length && !productSelector.length && !recipeSelector.hasClass('easyrecipe')) {
        content = new Content ('Recipe', resizeFactor, debugMode);
        var recipeSelectorStrings = [
          /* Introduction */
          '.recipe-content',
          '.recipe-information-description',
          /* Ingredients */
          '[itemprop^="recipeIngredients"]',
          '[class^="recipe-ingredients"]',
          '.recipe-ingredients',
          /* Instructions */
          '[itemprop^="recipeInstructions"]',
          '[class^="recipe-instructions"]',
          '.recipe-making',
          '.recipe-notes',
          '.recipe__content',
          /* Easyrecipe plugin */
          '.easyrecipe',
        ];
        recipeSelector.find(recipeSelectorStrings.join(',')).each(content.addElement);
      }

      /* Does this post contain a product according to the Product schema? */
      else if (productSelector.length) {
        content = new Content ('Product', resizeFactor, debugMode);
        productSelector.each(content.addElement);
      }

      /* If the page does not belong to any of the above cases, try with the
      generic semantics, and issue a warning event to GA. */
      else {
        
        if ($('.single-content').length) {
          content = new Content ('.single-content', resizeFactor, debugMode);
          $('.single-content').each(content.addElement);
        }
      
        else if ($('#content').length) {
          content = new Content ('#content', resizeFactor, debugMode);
          $('#content').each(content.addElement);
        }
      
        else if ($('#main-content').length) {
          content = new Content ('#main-content', resizeFactor, debugMode);
          $('#main-content').each(content.addElement);
        }
      
        /* If we did not recognize the content type, take the whole body of
        the HTML page, issue a warning, and send an event to GA */
        else {
          content = new Content ('<body>', resizeFactor, debugMode);
          $('body').each(content.addElement);
        }

        console.warn(" -> Content type could not be identified properly, will use " + content.name);
        sendEvent('content_guessed');
        
      } // content identification

      /* Create an object containing the page comments */
      var comments = new Content ('Comments section', 1, debugMode);
      var commentsSelectorStrings = [
        '#comment-wrap',
        '#comments',
        '.comments_area',
        '.comment-respond',
      ];
      $(commentsSelectorStrings.join(',')).each(comments.addElement);

      /* Remove the comments from the content area */
      if (comments.height > 0 && comments.startPixel > content.startPixel) {
        content.endPixel = Math.min (content.endPixel, comments.startPixel);
        content.updateHeight();
      }

      /* Extract start, end and length of the content */
      var contentStart = content.startPixel;
      var contentEnd = content.endPixel;
      var contentLength = content.height;

      /* Check that the content is longer than the pixel threshold */
      if (contentLength < pixelThreshold) {
        console.warn(" -> Content too short or threshold too large for '" + content.name + "'");
        sendEvent('content_too_short');
      }

      /* Print some useful info */
      if (debugMode) {
        console.log("Identified the following content type: '" + content.name + "'");
        console.log("pixelThreshold = " + pixelThreshold);
        console.log("documentLength = " + documentLength);
        console.log("contentStart = " + contentStart);
        console.log("contentLength = " + contentLength);
        console.log("contentShown = " + ($(window).height() - contentStart));
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

        /* Print some useful info */
        if (debugMode) {
          console.log("windowScroll = " + windowScroll);
          console.log("bottom = " + bottom);
          console.log("contentShown = " + contentShown);
        }

        /* If user was shown at least 'pixelThreshold' pixels of content, send an
        event. If the content starts right at the top of the page, it might be that
        the shown content exceed the threshold even if the user scrolls one pixel;
        therefore, we ask the user to actually scroll at least pixelThreshold/2
        pixels. */
        if (!startedReading && contentShown > pixelThreshold && windowScroll > pixelThreshold/2.0) {

          startedReading = true;
          currentTime = new Date();
          scrollStart = currentTime.getTime();
          timeToScroll = Math.round((scrollStart - beginning) / 1000);

          sendEvent('start_reading', {'time_to_scroll': timeToScroll});
          if (debugMode)
            console.log(' -> Started reading (' + timeToScroll + 's)');
        }

        /* If user reached the end of the content, send an event */
        if (!endContent && contentShown > contentLength) {

          if (!startedReading) {
            if (debugMode)
              console.warn('Could not estimate correctly the height of the content');
            return;
          }

          endContent = true;
          currentTime = new Date();
          contentScrollEnd = currentTime.getTime();
          timeToContentEnd = Math.round((contentScrollEnd - scrollStart) / 1000);

          /* If the user reached the bottom of the content in less than a minute,
          flag him/her as a scanner. Otherwise, flag him/her as a Reader, and
          fire a content_read event. */
          var readingBehaviour = timeToContentEnd < timeThreshold ? 'Scanner' : 'Reader';

          if (readingBehaviour === 'Reader')
            sendEvent('content_read', {'time_to_content_end': timeToContentEnd});

          if (debugMode)
            console.log(' -> End of content section (' + timeToContentEnd + 's), you are a ' + readingBehaviour);

          /* In both cases, tell GA that the user reached the bottom of the content */
          sendEvent('content_bottom', {'time_to_content_end': timeToContentEnd, 'reading_behaviour': readingBehaviour});

        }

        /* If user has hit the bottom of page, send an event */
        if (!didComplete && bottom >= documentLength*resizeFactor) {

          if (!startedReading || !endContent) {
            if (debugMode)
              console.warn('Could not estimate correctly the height of the content');
            return;
          }

          didComplete = true;
          currentTime = new Date();
          end = currentTime.getTime();
          totalTime = Math.round((end - scrollStart) / 1000);

          sendEvent('page_bottom', {'total_time': totalTime});
          if (debugMode)
            console.log(' -> Bottom of page (' + totalTime + 's)');
        }

      }; // end of trackLocation


      /* Track the scrolling and track location only when the user scrolls down */
      $(window).scroll(function() {
        if (timer) {
          clearTimeout(timer);
        }
        // Use a buffer so we don't call trackLocation too often.
        timer = setTimeout(trackLocation, callBackTime);
      });

    })();
  }); // whenLoaded
}); // jQuery(document).ready

