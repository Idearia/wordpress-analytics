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
function is_single() { return !empty($GLOBALS['wpan_test_is_single']); }
function get_the_ID() { return 1; }
function get_the_category() { return [(object) ['category_parent' => 3, 'cat_name' => 'Child'], (object) ['category_parent' => 0, 'cat_name' => "News & \"Co\""]]; }
function get_post_meta($id, $key, $single) { return 'guide'; }
function get_option($name) {
    global $wpan_test_options;
    return isset($wpan_test_options[$name]) ? $wpan_test_options[$name] : false;
}
function add_action(...$args) {}
function add_filter(...$args) {}
function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_url($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function admin_url($path = '') { return 'https://example.test/sub/wp-admin/' . $path; }
function wp_json_encode($value) { return json_encode($value); }
function apply_filters($hook, $value) { return isset($GLOBALS['wpan_test_filters'][$hook]) ? call_user_func_array($GLOBALS['wpan_test_filters'][$hook], array_slice(func_get_args(), 1)) : $value; }
function has_filter($hook) { return isset($GLOBALS['wpan_test_filters'][$hook]); }
function do_action(...$args) { $GLOBALS['wpan_test_actions'][] = $args; }
function wp_remote_post($url, $args) { $GLOBALS['wpan_test_requests'][] = [$url, $args]; return ['response' => ['code' => 204], 'body' => '{"validationMessages":[]}']; }
function wp_remote_retrieve_body($response) { return $response['body']; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function url_to_postid($url) { return 7; }
function get_the_title($post = 0) { return 7 === $post ? 'Contatti' : ''; }
function gform_update_meta($entry_id, $key, $value) { $GLOBALS['wpan_test_meta'][$entry_id][$key] = $value; }
function gform_get_meta($entry_id, $key) { return isset($GLOBALS['wpan_test_meta'][$entry_id][$key]) ? $GLOBALS['wpan_test_meta'][$entry_id][$key] : false; }
function is_wp_error($thing) { return false; }
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

// A legacy settings.yaml (Universal Analytics structure, as found on the
// websites) is read by the plugin's own minimal reader, and gives the very same
// structure as the JSON files that were generated from it.
require_once $root . '/settings/settings_yaml.php';
foreach (['settings', 'settings.example'] as $name) {
    check_same(
        json_decode(file_get_contents(__DIR__ . '/fixtures/' . $name . '.legacy.json'), true),
        wpan_parse_settings_yaml(file_get_contents(__DIR__ . '/fixtures/' . $name . '.legacy.yaml')),
        $name . '.legacy.yaml matches ' . $name . '.legacy.json'
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

// A customised settings.yaml keeps its own sections, loses the Universal
// Analytics fields and gains the Google Analytics 4 ones; one that cannot be read falls
// back to the bundled JSON file instead of breaking the website.
$legacy = sys_get_temp_dir() . '/wpan-legacy-' . getmypid() . '.yaml';
$custom = file_get_contents(__DIR__ . '/fixtures/settings.legacy.yaml')
    . "            extra_section:\n                id: extra_section\n                display: Extra\n                fields:\n"
    . "                    extra_field:\n                        id: extra_field\n                        title: Extra\n                        default: ''\n                        type: text\n";
file_put_contents($legacy, $custom);
unset($GLOBALS['wpan_menu_structure']);
wpan_load_menu_structure($legacy);
$upgraded = $GLOBALS['wpan_menu_structure']['menus'];
check_same('wpan:extra_section', $upgraded['advanced_settings']['sections']['extra_section']['db_key'], 'Custom YAML section');
check_same(
    ['measurement_id', 'api_secret', 'load_mode', 'scroll_tracking', 'call_tracking', 'form_tracking', 'email_tracking', 'content_grouping'],
    array_keys($upgraded['wordpress_analytics']['sections']['general_settings']['fields']),
    'Legacy YAML general settings upgraded to GA4'
);
check_same(
    ['network_mode', 'send_page_view', 'debug', 'debug_email'],
    array_keys($upgraded['advanced_settings']['sections']['advanced_settings']['fields']),
    'Legacy YAML advanced settings without UA fields'
);
check_same(false, isset($upgraded['wordpress_analytics']['sections']['content_grouping']), 'Legacy YAML empty section removed');
check_same(['pixel_threshold', 'time_threshold'], array_keys($upgraded['wordpress_analytics']['sections']['scroll_tracking']['fields']), 'Legacy YAML scroll tracking kept');
check_same(true, isset($upgraded['wordpress_analytics']['sections']['call_tracking']['fields']['detect_phone_numbers']), 'Legacy YAML other fields kept');
// Once upgraded, the legacy file has the same fields as the bundled one
$bundled = json_decode(file_get_contents($root . '/settings/settings.json'), true)['menus'];
unset($upgraded['advanced_settings']['sections']['extra_section']);
foreach ($bundled as $menuId => $menu) {
    foreach ($menu['sections'] as $sectionId => $section) {
        check_same(array_keys($section['fields']), array_keys($upgraded[$menuId]['sections'][$sectionId]['fields']), 'Upgraded ' . $sectionId . ' fields');
    }
    check_same(array_keys($menu['sections']), array_keys($upgraded[$menuId]['sections']), 'Upgraded ' . $menuId . ' sections');
}

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

// Renders the <head> output for the given options.
function render_head($options) {
    global $wpan_test_options;
    $wpan_test_options = $options;
    ob_start();
    wpan_tracking_code();
    return ob_get_clean();
}
function has($haystack, $needle) { return strpos($haystack, $needle) !== false; }

require_once $root . '/scroll_tracking.php';
require_once $root . '/call_tracking.php';
require_once $root . '/email_tracking.php';
require_once $root . '/content_grouping.php';
require_once $root . '/tracking_code.php';

// A website just updated from the Universal Analytics version: the old options
// are still in the database, there is no GA4 measurement ID yet. Custom code
// and call/email tracking must keep working, and nothing is sent to Google.
$legacy_options = [
    'wpan:general_settings' => ['tracking_uid' => 'UA-000000-1', 'call_tracking' => '1', 'email_tracking' => '1'],
    'wpan:advanced_settings' => ['tracker_name' => 'ga', 'create_tracker' => '1', 'send_page_view' => '1'],
    'wpan:custom_code' => ['custom_code' => '<meta name="wpan-test" content="<?php echo 1 + 1; ?>"><script>ga(\'set\', \'x\', 1); fbq(\'init\');</script>'],
];
$head = render_head($legacy_options);
check_same('gtag', wpan_get_load_mode(), 'Default load mode');
check_same('', wpan_get_measurement_id(), 'No measurement ID after the update');
check_same(true, has($head, 'js/call_tracking.js?ver='), 'Call tracking script without GA');
check_same(true, has($head, 'js/email_tracking.js?ver='), 'Email tracking script without GA');
check_same(true, has($head, '<meta name="wpan-test" content="2">'), 'Custom code without GA');
check_same(true, has($head, "window['ga'] = window['ga'] || function(){};"), 'Legacy tracker stub for custom code');
check_same(false, has($head, 'googletagmanager.com'), 'No Google tag without measurement ID');
check_same(false, has($head, 'analytics.js'), 'No Universal Analytics');
check_same(false, has($head, 'UA-000000-1'), 'Legacy tracking ID ignored');
ob_start();
wpan_ga4_admin_notices();
$notice = ob_get_clean();
check_same(true, has($notice, 'measurement ID is missing') && has($notice, 'https://example.test/sub/wp-admin/admin.php?page=wordpress_analytics'), 'Missing measurement ID notice');

// WordPress Analytics loads GA4: Google tag, then custom code, then config.
$ga4_options = [
    'wpan:general_settings' => ['measurement_id' => 'G-TEST123', 'load_mode' => 'gtag', 'call_tracking' => '1'],
    'wpan:advanced_settings' => ['send_page_view' => '1'],
    'wpan:custom_code' => ['custom_code' => '<meta name="wpan-test" content="1">'],
];
$head = render_head($ga4_options);
check_same(true, has($head, '<script async src="https://www.googletagmanager.com/gtag/js?id=G-TEST123"></script>'), 'Google tag');
check_same(1, substr_count($head, "gtag('config', \"G-TEST123\");"), 'Single GA4 config');
check_same(true, strpos($head, 'gtag/js') < strpos($head, 'wpan-test') && strpos($head, 'wpan-test') < strpos($head, "gtag('config'"), 'Custom code before config');
check_same(false, has($head, "window['ga']"), 'No legacy stub when custom code does not need it');
ob_start();
wpan_ga4_admin_notices();
check_same('', ob_get_clean(), 'No notice when GA4 is configured');
$spaced = $legacy_options;
$spaced['wpan:custom_code']['custom_code'] = "<script>ga ('set', 'x', 1);</script>";
check_same(true, has(render_head($spaced), "window['ga'] = window['ga'] || function(){};"), 'Legacy tracker stub with a space before the parenthesis');

// The pageview can be turned off, as with Universal Analytics.
$no_pageview = $ga4_options;
unset($no_pageview['wpan:advanced_settings']['send_page_view']);
check_same(true, has(render_head($no_pageview), 'gtag(\'config\', "G-TEST123", {"send_page_view":false});'), 'Pageview turned off');

// Content grouping: category and content type are sent with the pageview.
$wpan_test_is_single = true;
$ga4_options['wpan:general_settings']['content_grouping'] = '1';
$ga4_options['wpan:general_settings']['scroll_tracking'] = '1';
$ga4_options['wpan:scroll_tracking'] = ['pixel_threshold' => 300, 'time_threshold' => 60];
$head = render_head($ga4_options);
check_same(true, has($head, 'gtag(\'config\', "G-TEST123", {"content_group":"News & \"Co\"","content_type":"guide"});'), 'Content group in config');
check_same(true, has($head, "js/scroll_tracking.js?ver=") && has($head, "timeThreshold='60' pixelThreshold='300'"), 'Scroll tracking script on single posts');
check_same(false, has($head, 'npmcdn') || has($head, 'gaTracker'), 'No external library, no UA tracker in scripts');
$wpan_test_is_single = false;
check_same(false, has(render_head($ga4_options), 'scroll_tracking.js'), 'No scroll tracking outside single posts');

// GA4 is loaded by Google Tag Manager: no Google tag (no double pageviews),
// everything else still there.
$ga4_options['wpan:general_settings']['load_mode'] = 'gtm';
$head = render_head($ga4_options);
check_same(false, has($head, 'googletagmanager.com') || has($head, "gtag("), 'No Google tag in GTM mode');
check_same(true, has($head, 'js/call_tracking.js?ver=') && has($head, 'wpan-test'), 'Call tracking and custom code in GTM mode');

// No tag at all, no measurement ID: no notice.
$head = render_head(['wpan:general_settings' => ['load_mode' => 'none']]);
check_same(false, has($head, 'googletagmanager.com'), 'No Google tag in none mode');
ob_start();
wpan_ga4_admin_notices();
check_same('', ob_get_clean(), 'No notice in none mode');

// Google Tag Manager for WordPress active while the plugin loads GA4 too.
define('GTM4WP_VERSION', '1.16.2');
render_head(['wpan:general_settings' => ['measurement_id' => 'G-TEST123']]);
ob_start();
wpan_ga4_admin_notices();
check_same(true, has(ob_get_clean(), 'counted twice'), 'Double pageview notice with GTM4WP');
$wpan_test_options = [];

// Settings validation.
check_same(['measurement_id' => 'G-ABC123', 'load_mode' => 'gtm'], wpan_sanitize_options(['measurement_id' => ' g-abc123 ', 'load_mode' => 'gtm']), 'GA4 settings');
foreach ([['measurement_id' => 'UA-000000-1'], ['measurement_id' => "G-1');alert(1"], ['load_mode' => 'other']] as $invalid) {
    $rejected = false;
    try {
        wpan_sanitize_options($invalid);
    } catch (RuntimeException $exception) {
        $rejected = true;
    }
    check_same(true, $rejected, 'Invalid GA4 setting rejected');
}

// The drop-down menu used by the load mode.
ob_start();
wpan_display_select_field(['db_key' => 'wpan:general_settings', 'name' => 'load_mode', 'value' => 'gtm', 'options' => ['gtag' => 'A', 'gtm' => 'B']]);
check_same('<select name="wpan:general_settings[load_mode]" id="load_mode"><option value="gtag">A</option><option value="gtm" selected="selected">B</option></select>', ob_get_clean(), 'Select field');

// ---------------------------------------------------------------------------
// Form tracking: Gravity Forms submissions sent to GA4 from the server.
// ---------------------------------------------------------------------------

// Google Analytics cookies.
check_same('1234567890.1700000000', wpan_ga4_client_id_from_cookie('GA1.1.1234567890.1700000000'), 'Client ID from _ga');
check_same('1234567890.1700000000', wpan_ga4_client_id_from_cookie('GA1.2.1234567890.1700000000'), 'Client ID from _ga (subdomain)');
foreach ([null, '', 'GA1.1.abc', '<script>'] as $cookie) {
    check_same('', wpan_ga4_client_id_from_cookie($cookie), 'Invalid _ga cookie');
}
check_same('1700000000', wpan_ga4_session_id_from_cookie('GS1.1.1700000000.5.1.1700000100.0.0.0'), 'Session ID, GS1 format');
check_same('1700000000', wpan_ga4_session_id_from_cookie('GS2.1.s1700000000$o5$g1$t1700000100$j60$l0$h0'), 'Session ID, GS2 format');
check_same('1700000000', wpan_ga4_session_id_from_cookie('GS2.1.o5$s1700000000$g1'), 'Session ID, GS2 format, any order');
foreach ([null, '', 'GS1.1', 'GS2.1.o5$g1', 'GS3.1.s1'] as $cookie) {
    check_same('', wpan_ga4_session_id_from_cookie($cookie), 'Invalid session cookie');
}
$visitor = wpan_ga4_visitor('G-TEST123', ['_ga' => 'GA1.1.11.22', '_ga_TEST123' => 'GS1.1.33.1.1.44.0.0.0', '_ga_OTHER' => 'GS1.1.99.1.1.44.0.0.0']);
check_same(['client_id' => '11.22', 'session_id' => '33'], $visitor, 'Visitor from cookies');
$anonymous = wpan_ga4_visitor('G-TEST123', []);
check_same(1, preg_match('/^\d+\.\d+$/', $anonymous['client_id']), 'Random client ID without cookies');
check_same('', $anonymous['session_id'], 'No session ID without cookies');

$form_options = [
    'wpan:general_settings' => ['measurement_id' => 'G-TEST123', 'api_secret' => 'sEcr3t_-', 'form_tracking' => '1'],
    'wpan:form_tracking' => ['wait_for_form_payment' => '1'],
];
$entry = ['id' => 5, 'form_id' => 3, 'post_id' => 99, 'source_url' => 'https://example.test/sub/contatti/?utm=1', 'currency' => 'EUR'];
$form = ['id' => 3, 'title' => 'Richiesta "info" & preventivo'];
$_COOKIE = ['_ga' => 'GA1.1.11.22', '_ga_TEST123' => 'GS2.1.s33$o1'];

// Nothing is sent without the API secret, or when form tracking is off.
$wpan_test_requests = [];
$wpan_test_options = ['wpan:general_settings' => ['measurement_id' => 'G-TEST123', 'form_tracking' => '1']];
wpan_send_form_submitted($entry, $form);
check_same('', wpan_get_api_secret(), 'No API secret');
check_same([], $wpan_test_requests, 'No request without API secret');
ob_start();
wpan_form_tracking_admin_notices();
check_same(true, has(ob_get_clean(), 'form submissions are <strong>not</strong> being sent'), 'Missing API secret notice');
$wpan_test_options = ['wpan:general_settings' => ['measurement_id' => 'G-TEST123', 'api_secret' => 'sEcr3t_-']];
wpan_send_form_submitted($entry, $form);
check_same([], $wpan_test_requests, 'No request when form tracking is off');
ob_start();
wpan_form_tracking_admin_notices();
check_same('', ob_get_clean(), 'No notice when form tracking is off');

// A submitted form is sent to GA4 with the measurement protocol.
$wpan_test_options = $form_options;
wpan_send_form_submitted($entry, $form);
check_same(1, count($wpan_test_requests), 'One request per submission');
list($url, $args) = $wpan_test_requests[0];
check_same('https://www.google-analytics.com/mp/collect?measurement_id=G-TEST123&api_secret=sEcr3t_-', $url, 'Measurement protocol URL');
check_same(false, $args['blocking'], 'The visitor does not wait for Google');
check_same('application/json', $args['headers']['Content-Type'], 'JSON request');
check_same(
    [
        'client_id' => '11.22',
        'events' => [[
            'name' => 'generate_lead',
            'params' => [
                'form_id' => '3',
                'form_title' => 'Richiesta "info" & preventivo',
                'page_location' => 'https://example.test/sub/contatti/?utm=1',
                'page_path' => '/sub/contatti/',
                'page_title' => 'Contatti',
                'session_id' => '33',
                'engagement_time_msec' => 1,
            ],
        ]],
    ],
    json_decode($args['body'], true),
    'Form submission payload'
);
check_same(['client_id' => '11.22', 'session_id' => '33'], gform_get_meta(5, 'wpan_ga4_visitor'), 'Visitor stored with the entry');
check_same('wpan_action_after_ga4_form_event', $wpan_test_actions[count($wpan_test_actions) - 1][0], 'Action after the event');
ob_start();
wpan_form_tracking_admin_notices();
check_same('', ob_get_clean(), 'No notice when form tracking is configured');

// Payment forms: the event waits for the payment, and is attributed to the
// visitor who submitted the form even if the confirmation has no cookies.
$wpan_test_requests = [];
$paid_entry = $entry + ['payment_status' => 'Processing'];
wpan_send_form_submitted($paid_entry, $form);
check_same([], $wpan_test_requests, 'Payment form waits for the payment');
$_COOKIE = [];
class GFAPI { public static function get_form($id) { return ['id' => $id, 'title' => 'Pagamento']; } }
wpan_send_payment_done($paid_entry, [], 'TX-1', '49.90');
$payload = json_decode($wpan_test_requests[0][1]['body'], true);
check_same('11.22', $payload['client_id'], 'Payment attributed to the visitor');
check_same(['TX-1', 49.9, 'EUR', '33', 'Pagamento'], [$payload['events'][0]['params']['transaction_id'], $payload['events'][0]['params']['value'], $payload['events'][0]['params']['currency'], $payload['events'][0]['params']['session_id'], $payload['events'][0]['params']['form_title']], 'Payment data');
$wpan_test_requests = [];
$wpan_test_options['wpan:form_tracking'] = [];
wpan_send_payment_done($paid_entry, [], 'TX-1', '49.90');
check_same([], $wpan_test_requests, 'No second event when not waiting for the payment');
wpan_send_form_submitted($paid_entry, $form);
check_same(1, count($wpan_test_requests), 'Payment form sent at once when not waiting');

// Developers can change or stop the event, and choose the endpoint.
$wpan_test_requests = [];
$wpan_test_filters['wpan_filter_ga4_endpoint'] = function ($endpoint) { return 'https://region1.google-analytics.com/'; };
$wpan_test_filters['wpan_filter_ga4_form_event'] = function ($event, $entry, $form) { $event['name'] = 'contact'; $event['params']['form_title'] = strtoupper($form['title']); return $event; };
wpan_send_form_submitted($entry, $form);
check_same(0, strpos($wpan_test_requests[0][0], 'https://region1.google-analytics.com/mp/collect?'), 'Filtered endpoint');
$payload = json_decode($wpan_test_requests[0][1]['body'], true);
check_same(['contact', 'RICHIESTA "INFO" & PREVENTIVO'], [$payload['events'][0]['name'], $payload['events'][0]['params']['form_title']], 'Filtered event');
$wpan_test_requests = [];
$wpan_test_filters['wpan_filter_ga4_form_event'] = function () { return false; };
wpan_send_form_submitted($entry, $form);
check_same([], $wpan_test_requests, 'Event stopped by filter');
$wpan_test_filters = [];

// Debug mode: the event is validated first, then sent waiting for the answer.
$wpan_test_options['wpan:advanced_settings'] = ['debug' => '1'];
$debug_log = WPAN_PLUGIN_DIR . 'debug.log';
ini_set('error_log', '/dev/null');
wpan_send_form_submitted($entry, $form);
ini_restore('error_log');
unlink($debug_log);
check_same(2, count($wpan_test_requests), 'Validation and event in debug mode');
check_same(true, has($wpan_test_requests[0][0], '/debug/mp/collect?') && true === $wpan_test_requests[1][1]['blocking'], 'Validation server, blocking request');
unset($wpan_test_options['wpan:advanced_settings']);

// Hooks of the Universal Analytics version are reported to the administrators.
$wpan_test_filters['wpan_filter_form_tracking_event'] = function ($event) { return $event; };
ob_start();
wpan_form_tracking_admin_notices();
check_same(true, has(ob_get_clean(), 'hooks that are not called anymore: <code>wpan_filter_form_tracking_event</code>'), 'Legacy hooks notice');
$wpan_test_filters = [];

// The API secret is validated; the constant in wp-config.php takes precedence
// over the field.
check_same(['api_secret' => 'aB1_-'], wpan_sanitize_options(['api_secret' => ' aB1_- ']), 'API secret setting');
$rejected = false;
try {
    wpan_sanitize_options(['api_secret' => 'a&b=c']);
} catch (RuntimeException $exception) {
    $rejected = true;
}
check_same(true, $rejected, 'Invalid API secret rejected');
define('WPAN_GA4_API_SECRET', 'fromConfig');
check_same('fromConfig', wpan_get_api_secret(), 'API secret from wp-config.php');
$wpan_test_options = [];
$_COOKIE = [];

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
