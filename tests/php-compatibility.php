<?php
/**
 * Settings/output regression smoke tests. No WordPress server, Composer
 * packages or vendor/ folder needed: php tests/php-compatibility.php
 * WordPress APIs are stubbed: this is not a full WordPress integration test.
 * Must run unchanged on every supported PHP version (7.2 to 8.5).
 */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$checks = 0;
function check_same($expected, $actual, $label) {
    global $checks;
    if ($expected !== $actual) {
        throw new RuntimeException($label . ': ' . var_export($actual, true));
    }
    ++$checks;
}

// Options returned by the stubbed get_option(), keyed by database key.
$wpan_test_options = [];

// Minimal WordPress boundary, with no database writes or network calls.
function plugin_dir_path($file) { return dirname($file) . '/'; }
function plugin_dir_url($file) { return 'https://example.test/plugins/wordpress-analytics/'; }
function is_admin() { return true; }
function is_multisite() { return false; }
function is_single() { return false; }
function get_option($name) {
    global $wpan_test_options;
    return isset($wpan_test_options[$name]) ? $wpan_test_options[$name] : false;
}
function add_action(...$args) {}
function add_filter(...$args) {}
function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function add_settings_error(...$args) {
    throw new RuntimeException('Unexpected validation error: ' . implode(' ', $args));
}

$root = dirname(__DIR__);

// The plugin must not need Composer packages to run.
check_same(false, file_exists($root . '/vendor'), 'Tests run without a vendor/ folder');

define('WPINC', 'wp-includes');
require $root . '/wordpress-analytics.php';

check_same('json', pathinfo(WPAN_CONFIG_FILE, PATHINFO_EXTENSION), 'Config file is JSON');
check_same(true, is_readable(WPAN_CONFIG_FILE), 'Config file is readable');

foreach (['settings.json', 'settings.example.json'] as $filename) {
    $path = $root . '/settings/' . $filename;
    $parsed = json_decode(file_get_contents($path), true);
    check_same(JSON_ERROR_NONE, json_last_error(), $filename . ' is valid JSON');
    check_same(true, isset($parsed['menus']['wordpress_analytics']), $filename . ' menu');
    check_same('wordpress_analytics', $parsed['menus']['wordpress_analytics']['slug'], $filename . ' slug');
    if (isset($parsed['menus']['wordpress_analytics']['desc'])) {
        check_same(true, is_string($parsed['menus']['wordpress_analytics']['desc']), $filename . ' description');
    }
    foreach ($parsed['menus'] as $menu) {
        foreach ($menu['sections'] as $section) {
            check_same(true, isset($section['id'], $section['fields']), $filename . ' section');
        }
    }
    unset($GLOBALS['wpan_menu_structure']);
    wpan_load_menu_structure($path);
    foreach ($GLOBALS['wpan_menu_structure']['menus'] as $menuId => $menu) {
        foreach ($menu['sections'] as $section) {
            check_same('wpan:' . $section['id'], $section['db_key'], $filename . ' database key');
            $originalSection = $parsed['menus'][$menuId]['sections'][$section['id']];
            $expectedVisible = isset($originalSection['visible']) ? $originalSection['visible'] : true;
            check_same($expectedVisible, $section['visible'], $filename . ' visibility');
        }
    }
}

// The legacy YAML format is refused cleanly, without fatal errors.
unset($GLOBALS['wpan_menu_structure']);
$legacy = sys_get_temp_dir() . '/wpan-legacy-' . getmypid() . '.yaml';
file_put_contents($legacy, "menus: {}\n");
wpan_load_menu_structure($legacy);
unlink($legacy);
check_same(false, isset($GLOBALS['wpan_menu_structure']), 'YAML config is not loaded');
check_same([], wpan_get_options(), 'Empty WordPress options');

// Options stored in the database are read through the JSON structure.
$wpan_test_options = [
    'wpan:general_settings' => ['tracking_uid' => 'UA-000000-1', 'call_tracking' => '1', 'email_tracking' => '1'],
    'wpan:advanced_settings' => ['tracker_name' => 'ga', 'create_tracker' => '1', 'send_page_view' => '1'],
    'wpan:custom_code' => ['custom_code' => '<meta name="wpan-test" content="<?php echo 1 + 1; ?>">'],
];
$options = wpan_get_options();
check_same('UA-000000-1', $options['tracking_uid'], 'Tracking ID read from options');

// Full <head> output: tracker, call/email tracking and custom code.
require_once $root . '/call_tracking.php';
require_once $root . '/email_tracking.php';
require_once $root . '/tracking_code.php';
ob_start();
wpan_tracking_code();
$head = ob_get_clean();
check_same(true, strpos($head, "ga('create', 'UA-000000-1', 'auto');") !== false, 'Tracker creation');
check_same(true, strpos($head, 'js/call_tracking.js?ver=') !== false, 'Call tracking script');
check_same(true, strpos($head, 'js/email_tracking.js?ver=') !== false, 'Email tracking script');
check_same(true, strpos($head, '<meta name="wpan-test" content="2">') !== false, 'Custom code');
check_same(true, strpos($head, "ga('send', 'pageview');") !== false, 'Pageview');
$wpan_test_options = [];

// PHP 8.1+ must not receive null as preg_match() subject.
$patterns = ['phone_include_regex_pattern' => '^39', 'phone_exclude_regex_pattern' => '^800'];
check_same($patterns, wpan_sanitize_options($patterns), 'Regex settings');

// An optional missing CodeMirror library must not cause an undefined-constant Error.
ob_start();
wpan_display_code_field([
    'db_key' => 'wpan:custom_code', 'name' => 'custom_code', 'value' => '<p>test</p>',
    'attributes' => ['rows' => 5, 'cols' => 50, 'placeholder' => ''],
]);
$html = ob_get_clean();
check_same(true, strpos($html, '&lt;p&gt;test&lt;/p&gt;') !== false, 'Plain code textarea');
check_same(false, strpos($html, 'CodeMirror.fromTextArea') !== false, 'No CodeMirror when absent');

printf("OK: %d checks on PHP %s (E_ALL, warnings/deprecations fail the test)\n", $checks, PHP_VERSION);
