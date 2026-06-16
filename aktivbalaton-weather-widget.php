<?php
/**
 * Plugin Name:  AktívBalaton – Időjárás Widget
 * Plugin URI:   https://aktivbalaton.hu
 * Description:  Balatoni időjárás Elementor widget: szerver oldali API hívás (biztonságos API kulcs),
 *               Ma/Holnap nézet váltó, vízhőmérséklet, testreszabható design.
 * Version:      2.1.0
 * Author:       AktívBalaton
 * Author URI:   https://aktivbalaton.hu
 * Text Domain:  ab-weather
 * Requires PHP: 8.0
 * Requires at least: 6.0
 */

defined('ABSPATH') || exit;

define('AB_WEATHER_VERSION', '2.1.0');
define('AB_WEATHER_PATH',    plugin_dir_path(__FILE__));
define('AB_WEATHER_URL',     plugin_dir_url(__FILE__));

// ── API kulcs – itt tároljuk, sosem kerül a böngészőbe ───────
// Ha szeretnéd, áthelyezheted a wp-config.php-ba:
// define('AB_WEATHER_API_KEY', 'sajat_kulcs');
if (!defined('AB_WEATHER_API_KEY')) {
    define('AB_WEATHER_API_KEY', '2VE2J6MHF476SMBGFF5MDXGMC');
}

// ── WordPress AJAX végpont – szerver oldalon hívja az API-t ──
add_action('wp_ajax_ab_weather_fetch',        'ab_weather_ajax_handler');
add_action('wp_ajax_nopriv_ab_weather_fetch', 'ab_weather_ajax_handler');

function ab_weather_ajax_handler(): void {
    // Nonce ellenőrzés
    check_ajax_referer('ab_weather_nonce', 'nonce');

    $location   = sanitize_text_field($_POST['location'] ?? 'Balatonlelle');
    $days       = min(max((int)($_POST['days'] ?? 5), 1), 15);
    $water_lat  = (float)($_POST['water_lat'] ?? 46.83);
    $water_lng  = (float)($_POST['water_lng'] ?? 17.73);

    require_once AB_WEATHER_PATH . 'includes/class-weather-api.php';
    $api = new AB_Weather_API(AB_WEATHER_API_KEY);

    $weather = $api->get_forecast($location, $days);
    $water   = $api->get_water_temperature($water_lat, $water_lng);

    if (is_wp_error($weather)) {
        wp_send_json_error(['message' => $weather->get_error_message()]);
    }

    wp_send_json_success([
        'weather' => $weather,
        'water'   => $water,
    ]);
}

// ── Elementor widget regisztráció ────────────────────────────
add_action('plugins_loaded', function () {
    if (!did_action('elementor/loaded')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-warning"><p>'
               . '<strong>AktívBalaton Időjárás:</strong> Az Elementor plugin szükséges a működéshez.'
               . '</p></div>';
        });
        return;
    }

    add_action('elementor/widgets/register', function ($wm) {
        require_once AB_WEATHER_PATH . 'includes/class-weather-elementor-widget.php';
        $wm->register(new \AktivBalaton\Weather_Elementor_Widget());
    });

    // CSS + JS betöltése – csak ha a widget szerepel az oldalon
    add_action('elementor/frontend/after_enqueue_styles', 'ab_weather_enqueue_assets');
    add_action('elementor/editor/after_enqueue_styles',   'ab_weather_enqueue_assets');
});

function ab_weather_enqueue_assets(): void {
    wp_enqueue_style(
        'ab-weather',
        AB_WEATHER_URL . 'assets/css/weather-widget.css',
        [],
        AB_WEATHER_VERSION
    );

    wp_enqueue_script(
        'ab-weather',
        AB_WEATHER_URL . 'assets/js/weather-widget.js',
        ['jquery'],
        AB_WEATHER_VERSION,
        true
    );

    // Az AJAX URL és nonce átadása a JS-nek – az API kulcs NEM kerül ide
    wp_localize_script('ab-weather', 'abWeather', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ab_weather_nonce'),
        'iconUrl' => AB_WEATHER_URL . 'icons/',
    ]);
}
