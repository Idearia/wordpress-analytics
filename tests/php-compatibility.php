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
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function current_user_can($capability) { return 'manage_options' === $capability; }
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

// A legacy settings.yaml is read by the plugin's own minimal reader, and gives
// the very same structure as the bundled JSON files generated from it.
require_once $root . '/settings/settings_yaml.php';
foreach (['settings', 'settings.example'] as $name) {
    check_same(
        json_decode(file_get_contents($root . '/settings/' . $name . '.json'), true),
        wpan_parse_settings_yaml(file_get_contents(__DIR__ . '/fixtures/' . $name . '.yaml')),
        $name . '.yaml matches ' . $name . '.json'
    );
}

$blocks = [
    "value: >\n  first line\n  second line\n" => "first line second line\n",
    "value: |\n  first line\n  second line\n" => "first line\nsecond line\n",
    "value: >-\n  first line\n  second line\n" => 'first line second line',
    "value: |-\n  first line\n  second line\n" => "first line\nsecond line",
    "value: |+\n  first line\n\n" => "first line\n\n",
    "value: >\n" => '',
    "value: >\n  Città, qualità, più, perché.\n" => "Città, qualità, più, perché.\n",
    "value: >\n  one\n\n  two\n    indented\n  three\n" => "one\ntwo\n  indented\nthree\n",
];
foreach ($blocks as $yaml => $expected) {
    check_same(['value' => $expected], wpan_parse_settings_yaml($yaml), 'Block scalar');
}
check_same(
    ['text' => 'hello', 'number' => 42, 'bool' => true, 'empty' => null, 'quoted' => "it's", 'string' => '1', 'url' => 'http://a.b/c#d'],
    wpan_parse_settings_yaml("text: hello # comment\nnumber: 42\nbool: true\nempty: null\nquoted: 'it''s'\nstring: '1'\nurl: http://a.b/c#d\n"),
    'Scalar types'
);
foreach (["a: [1, 2]\n", "a:\n  - x\n", "a: &x 1\n", "a: {b: 1}\n", "a: 1\n   b: 2\n", "a: 1\na: 2\n"] as $yaml) {
    $rejected = false;
    try {
        wpan_parse_settings_yaml($yaml);
    } catch (Exception $exception) {
        $rejected = true;
    }
    check_same(true, $rejected, 'Unsupported YAML is rejected');
}

// A customised settings.yaml is loaded as is; one that cannot be read falls
// back to the bundled JSON file instead of breaking the website.
$legacy = sys_get_temp_dir() . '/wpan-legacy-' . getmypid() . '.yaml';
$custom = file_get_contents(__DIR__ . '/fixtures/settings.yaml')
    . "            extra_section:\n                id: extra_section\n                display: Extra\n                fields:\n"
    . "                    extra_field:\n                        id: extra_field\n                        title: Extra\n                        default: ''\n                        type: text\n";
file_put_contents($legacy, $custom);
unset($GLOBALS['wpan_menu_structure']);
wpan_load_menu_structure($legacy);
check_same('wpan:extra_section', $GLOBALS['wpan_menu_structure']['menus']['advanced_settings']['sections']['extra_section']['db_key'], 'Custom YAML section');

file_put_contents($legacy, "menus:\n  - unsupported\n");
unset($GLOBALS['wpan_menu_structure']);
ini_set('error_log', '/dev/null');
wpan_load_menu_structure($legacy);
ini_restore('error_log');
unlink($legacy);
check_same(true, isset($GLOBALS['wpan_menu_structure']['menus']['wordpress_analytics']['sections']['general_settings']), 'Unreadable YAML falls back to JSON');
check_same(false, isset($GLOBALS['wpan_menu_structure']['menus']['advanced_settings']['sections']['extra_section']), 'Fallback uses the bundled structure');

// The fallback is shown to the administrators, and only when it happens.
ob_start();
wpan_config_fallback_notice();
$notice = ob_get_clean();
check_same(true, strpos($notice, 'notice-error') !== false && strpos($notice, 'Unsupported syntax at line 2') !== false, 'Fallback admin notice');
unset($GLOBALS['wpan_config_fallback']);
ob_start();
wpan_config_fallback_notice();
check_same('', ob_get_clean(), 'No admin notice without fallback');

unset($GLOBALS['wpan_menu_structure']);
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
