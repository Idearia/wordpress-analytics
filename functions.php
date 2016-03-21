<?php

  /**
   * Collection of useful variables and utility functions for the
   * Wordpress Analytics plugin.
   *
   * Created by Guido W. Pettinari on 27.02.2016.
   * Part of Wordpress Analytics:
   * https://github.com/coccoinomane/wordpress_analytics
   */

  /**
   * Maximum number of characters allowed for a regex input via a
   * text input field
   */
  define ( "WPAN_MAX_REGEX_LENGTH", 200 );


  /**
   * Wrapper to eval, using output buffering.
   *
   * This function is based on php_eval in the Drupal API. Thank you very
   * much to the Drupal developers! Here is the original documentation
   * from https://api.drupal.org/api/drupal/modules!php!php.module/function
   * /php_eval/7:
   *
   * Evaluates a string of PHP code.
   * 
   * This is a wrapper around PHP's eval(). It uses output buffering to
   * capture both returned and printed text. Unlike eval(), we require
   * code to be surrounded by <?php ?> tags; in other words, we evaluate
   * the code as if it were a stand-alone PHP file.
   * 
   * Using this wrapper also ensures that the PHP code which is evaluated
   * can not overwrite any variables in the calling code, unlike a regular
   * eval() call.
   */
  function php_eval( $code ) {

    if ( empty( $code ) ) {
     return '';
    }

    ob_start();
    print eval( '?>' . $code );
    $output = ob_get_contents();
    ob_end_clean();

    return $output;
  }

?>