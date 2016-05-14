<?php

  /**
   * Register the fields contained in the the "Custom code" section.
   */

  function wpan_register_custom_code_fields( $section, $displayed_values ) {

    $name = 'custom_code';
    $title = 'Custom code';
    $desc = 'Any code you write here will be included in all pages of your website just before the pageview is sent.';
    $desc .= 'Remember to include the PHP opening & closing tags.';
    add_settings_field(
      $name,
      $title,
      'wpan_display_' . $name,
      $section['page'],
      $section['name'],
      [
        'db_key'       => $section['db_key'],
        'options_vals' => $displayed_values,
        'name'         => $name,
        'desc'         => $desc,
        'section'      => $section,
        'value'        => $displayed_values[ $name ],
        'rows'         => 15,
        'cols'         => 120,
        'placeholder'  => 'Insert code here; leave blank for no effect',
        'label_for'    => $name,
      ]
    );

  }


  /**
   * Build the "Custom code" section.
   **/

  function wpan_display_custom_code_section () {

    /* Load from the CodeMirror library all the scripts needed to
    have PHP syntax highlighting */
    if ( WPAN_DO_SYNTAX_HIGHLIGHTING ) {
    ?>

<link rel="stylesheet" href=<?php echo WPAN_CODEMIRROR_URL.'lib/codemirror.css'; ?>>
<script src=<?php echo WPAN_CODEMIRROR_URL.'lib/codemirror.js'; ?>></script>
<script src=<?php echo WPAN_CODEMIRROR_URL.'addon/edit/matchbrackets.js'; ?>></script>
<script src=<?php echo WPAN_CODEMIRROR_URL.'mode/htmlmixed/htmlmixed.js'; ?>></script>
<script src=<?php echo WPAN_CODEMIRROR_URL.'mode/xml/xml.js'; ?>></script>
<script src=<?php echo WPAN_CODEMIRROR_URL.'mode/javascript/javascript.js'; ?>></script>
<script src=<?php echo WPAN_CODEMIRROR_URL.'mode/css/css.js'; ?>></script>
<script src=<?php echo WPAN_CODEMIRROR_URL.'mode/clike/clike.js'; ?>></script>
<script src=<?php echo WPAN_CODEMIRROR_URL.'mode/php/php.js'; ?>></script>

    <?php
    }
  }


  /**
   * Display the single fields in the "Custom code" section.
   */

  function wpan_display_custom_code ( $args ) {

    wpan_display_code_input ( $args );

  }

?>