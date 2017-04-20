<?php

  /**
   * Collection of useful variables and utility functions for the
   * WordPress Analytics plugin.
   *
   * Created by Guido W. Pettinari on 27.02.2016.
   * Part of WordPress Analytics:
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

    if ( ! defined( 'WPAN_GAMP_LOADED' ) ) {

      define( "WPAN_GAMP_DIR", WPAN_PLUGIN_DIR . 'vendor/ins0/google-measurement-php-client/src/' );
      define( "WPAN_GAMP_URL", WPAN_PLUGIN_URL . 'vendor/ins0/google-measurement-php-client/src/' );
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

  }


  /**
   * Check that Symfony's Yaml library is installed,
   * and define the constant WPAN_YAML_DIR.
   *
   * Yaml is used to read the plugin's options, and this function
   * is used to load Yaml. This means that here you cannot use
   * functions that call wpan_get_options(), lest you get a
   * recursive error.
   */  
  
  function wpan_load_yaml_library () {
    
    if ( ! defined( 'WPAN_YAML_LOADED' ) ) {
    
      define( "WPAN_YAML_DIR", WPAN_PLUGIN_DIR . 'vendor/symfony/yaml/' );
      define( "WPAN_YAML_URL", WPAN_PLUGIN_URL . 'vendor/symfony/yaml/' );

      if ( file_exists( WPAN_YAML_DIR ) ) {

        define( "WPAN_YAML_LOADED", true );
        return true;

      }
      else {

        wpan_debug_wp( __FILE__.':'.__LINE__.': Could not find Yaml library; folder ' . WPAN_YAML_DIR . ' could not be found.' );
      
      }
    
    }

  }
  

  /**
   * Debug function: write to WordPress level debug log (usually in
   * wp-content/debug.log) and to debug.log in the plugin's directory.
   *
   * The plugin's own debug.log file will be written if the plugin's
   * debug option is active, or if the 'force' argument is set to true.
   *
   * WordPress' debug log will be written if WP_DEBUG and WP_DEBUG_LOG are
   * both set to true (usually in wp-config.php) and if either the plugin's
   * debug option is active or the 'force' argument is set to true.
   */
  function wpan_debug ( $log, $force=false ) {

    /* Location of plugin's debug file */
    $plugin_debug_file = WPAN_PLUGIN_DIR . 'debug.log';

    /* If the $force argument is true, print the debug message regardless
    of the value of the global debug flag */
    if ( $force ) {
      $write = true;
    }
    else {
      /* Use the plugin's debug flag to determine whether to write or not */
      $options = wpan_get_options ();
      $write = isset ( $options['debug'] ) ? $options['debug'] : false;
    }

    if ( $write && defined( 'WPAN_PLUGIN_DIR' ) ) {

      /* Prepend file & line information to each line */
      $pre = preg_replace('/.*?public_html/', '', __FILE__) . ':' . __LINE__ . ': ';
    
      /* Line to be logged */
      if ( is_array( $log ) || is_object( $log ) ) {
        $line = $pre . print_r( $log, true );
      } else {
        $line = $pre . $log;
      }

      /* Write the line to the plugin's debug log */
      file_put_contents( $plugin_debug_file, $line . PHP_EOL, FILE_APPEND );

      /* Write the line to WordPress' debug log */
      error_log( $line );

    }

  }

  
  /** 
   * Write to WordPress debug.log file; courtesy of Elegant Themes.
   */
  function wpan_debug_wp ( $log ) {
    
    if ( is_array( $log ) || is_object( $log ) ) {
       error_log( print_r( $log, true ) );
    } else {
       error_log( $log );
    }
    
  }


  /**
   * Return the blog ID of the main blog of the network (usually 1)
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