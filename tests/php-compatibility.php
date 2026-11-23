<?php
/**
 * YAML/settings regression smoke tests. No WordPress server or dev packages needed.
 * Run after composer install --no-dev: php tests/php-compatibility.php
 * WordPress APIs are stubbed: this is not a full WordPress integration test.
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

// Minimal WordPress boundary, with no database writes or network calls.
function plugin_dir_path($file) { return dirname($file) . '/'; }
function plugin_dir_url($file) { return 'https://example.test/plugins/wordpress-analytics/'; }
function is_admin() { return true; }
function is_multisite() { return false; }
function get_option($name) { return false; }
function add_action(...$args) {}
function add_filter(...$args) {}
function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function add_settings_error(...$args) {
    throw new RuntimeException('Unexpected validation error: ' . implode(' ', $args));
}

define('WPINC', 'wp-includes');
require dirname(__DIR__) . '/wordpress-analytics.php';

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

// Symfony 3.2.6 passed the empty/non-numeric block indentation marker to abs().
$blocks = [
    "value: >\n  first line\n  second line\n" => "first line second line\n",
    "value: |\n  first line\n  second line\n" => "first line\nsecond line\n",
    "value: >-\n  first line\n  second line\n" => 'first line second line',
    "value: |-\n  first line\n  second line\n" => "first line\nsecond line",
    "value: |+\n  first line\n\n" => "first line\n\n",
    "value: >2\n  first line\n  second line\n" => "first line second line\n",
    "value: |2-\n  first line\n  second line\n" => "first line\nsecond line",
    "value: >\n" => '',
    "value: >\n  Città, qualità, più, perché.\n" => "Città, qualità, più, perché.\n",
];
foreach ($blocks as $yaml => $expected) {
    check_same(['value' => $expected], Yaml::parse($yaml), 'Block scalar');
}

check_same(
    ['text' => 'hello', 'number' => 42, 'bool' => true, 'empty' => null, 'list' => ['one', 'two']],
    Yaml::parse("text: hello\nnumber: 42\nbool: true\nempty: null\nlist: [one, two]\n"),
    'Scalar types'
);
check_same(
    ['base' => ['enabled' => true], 'copy' => ['enabled' => true, 'label' => 'copy']],
    Yaml::parse("base: &base\n  enabled: true\ncopy:\n  <<: *base\n  label: copy\n"),
    'Anchors and merge keys'
);

$invalidRejected = false;
try {
    Yaml::parse("broken: [unterminated\n");
} catch (ParseException $exception) {
    $invalidRejected = true;
}
check_same(true, $invalidRejected, 'Invalid YAML reports a parse error');

foreach (['settings.yaml', 'settings.example.yaml'] as $filename) {
    $path = dirname(__DIR__) . '/settings/' . $filename;
    $parsed = Yaml::parse(file_get_contents($path));
    check_same(true, isset($parsed['menus']['wordpress_analytics']), $filename . ' menu');
    check_same('wordpress_analytics', $parsed['menus']['wordpress_analytics']['slug'], $filename . ' slug');
    if (isset($parsed['menus']['wordpress_analytics']['desc'])) {
        check_same(true, is_string($parsed['menus']['wordpress_analytics']['desc']), $filename . ' folded description');
    }
    foreach ($parsed['menus']['wordpress_analytics']['sections'] as $section) {
        check_same(true, isset($section['id'], $section['fields']), $filename . ' section');
    }
    unset($GLOBALS['wpan_menu_structure']);
    wpan_load_menu_structure($path);
    foreach ($GLOBALS['wpan_menu_structure']['menus']['wordpress_analytics']['sections'] as $section) {
        check_same('wpan:' . $section['id'], $section['db_key'], $filename . ' database key');
        $original = $parsed['menus']['wordpress_analytics']['sections'];
        $expectedVisible = true;
        foreach ($original as $originalSection) {
            if ($originalSection['id'] === $section['id']) {
                $expectedVisible = $originalSection['visible'] ?? true;
            }
        }
        check_same($expectedVisible, $section['visible'], $filename . ' visibility');
    }
}
check_same(true, defined('WPAN_YAML_LOADED') && WPAN_YAML_LOADED, 'YAML loader');
check_same([], wpan_get_options(), 'Empty WordPress options');

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
