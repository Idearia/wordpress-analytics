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
  function wpan_php_eval( $code ) {

    if ( empty( $code ) ) {
     return '';
    }

    ob_start();
    print eval( '?>' . $code );
    $output = ob_get_contents();
    ob_end_clean();

    return $output;
  }


  /**
   * Load the PHP client in order to send hits to Google Analytics
   * via the measurement protocol.
   *
   * In order to send hits to Google Analytics from the server, we employ Google's
   * measurement protocol (https://developers.google.com/analytics/devguides/
   * collection/protocol/v1/devguide). Rather than dealing with the raw library,
   * we use a lightweight PHP client from Racecore (https://github.com/ins0/google-
   * measurement-php-client) which we will refer to as ga-mp.
   */  
  
  function wpan_load_measurement_protocol_client () {
    
    define( "WPAN_GAMP_DIR", WPAN_PLUGIN_DIR . 'vendor/ga-mp/src/' );
    define( "WPAN_GAMP_URL", WPAN_PLUGIN_URL . 'vendor/ga-mp/src/' );
    $autoload_file = WPAN_GAMP_DIR . 'Racecore/GATracking/Autoloader.php';

    if ( file_exists( $autoload_file ) ) {

      try {

        require_once $autoload_file;
        Racecore\GATracking\Autoloader::register( WPAN_GAMP_DIR );
        define ("WPAN_GAMP_LOADED", true);
        wpan_debug( "Measurement protocol client loaded." );
        return true;
        
      } catch (Exception $e) {

        wpan_debug( "Could not load measurement protocol client; error message:" );
        wpan_debug( $e->getMessage() );
        
      }
      
    }
    else {
      
      wpan_debug( "Could not load measurement protocol client; file $autoload_file could not be found." );
      
    }

  }


  /**
   * Define directories where the syntax highglighting library can be found.
   *
   * In order to provide syntax highlighting in HTML text areas, we use
   * the CodeMirror Javascript library (https://codemirror.net/).
   */  
  
  function wpan_load_syntax_highlighting () {
    
    define( "WPAN_CODEMIRROR_DIR", WPAN_PLUGIN_DIR . 'vendor/codemirror/' );
    define( "WPAN_CODEMIRROR_URL", WPAN_PLUGIN_URL . 'vendor/codemirror/' );

    if ( file_exists( WPAN_CODEMIRROR_DIR ) ) {

      define( "WPAN_SYNTAX_HIGHLIGHTING_LOADED", true );
      wpan_debug( "Syntax highlighting library loaded." );
      return true;

    }
    else {
      
      wpan_debug( "Could not load syntax highlighting library; folder " . WPAN_CODEMIRROR_DIR . " could not be found." );
      
    }

  }
  

  /**
   * Debug function: write to debug.log in plugin directory.
   */
  function wpan_debug ( $log, $force=false ) {

    $options = wpan_get_options ();
    $debug = isset ( $options['debug'] ) ? $options['debug'] : $force;

    if ( $debug && defined( 'WPAN_PLUGIN_DIR' ) ) {
    
      $debug_file = WPAN_PLUGIN_DIR . 'debug.log';

      $pre = preg_replace('/.*?public_html/', '', __FILE__) . ':' . __LINE__ . ': ';

      if ( is_array( $log ) || is_object( $log ) ) {

         file_put_contents( $debug_file, $pre . print_r( $log, true ) . PHP_EOL, FILE_APPEND );

      } else {

         file_put_contents( $debug_file, $pre . $log . PHP_EOL, FILE_APPEND );

      }
    }

  }


  /**
   * Return the blog ID of the main blog of the newtork (usually 1)
   */
  function wpan_get_main_blog_id () {
    
    global $current_site;
    
    return $current_site->blog_id;

  }


  /**
   * Return true if the current blog is the main blog, false otherwise
   */
  function wpan_is_main_blog () {
        
    return wpan_get_main_blog_id() === get_current_blog_id();

  }
  

  /**
   * Return true if WordPress analytics is running in network mode,
   * which means that the plugin options can only be modified from
   * the network's admin interface.
   */
  function wpan_is_network_mode () {
    
    /* This is the way it should be when we will learn how to use the Settings
    API in the network admin (see long comment in settings.php) */
    // $advanced_options = get_site_option ( 'wpan:advanced_settings' );
    /* In the mean while we just check in the main blog options */
    $advanced_options = get_blog_option ( wpan_get_main_blog_id(), 'wpan:advanced_settings' );
    
    return is_multisite() && isset( $advanced_options['network_mode'] ) && $advanced_options['network_mode'];    
    
  }