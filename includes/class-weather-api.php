<?php
defined('ABSPATH') || exit;

/**
 * AB_Weather_API
 *
 * Szerver oldalon kezeli az összes API hívást.
 * Az API kulcs soha nem hagyja el a szervert.
 * Eredmények WordPress tranziensben cachelve.
 */
class AB_Weather_API {

    private string $api_key;
    private int    $cache_seconds = 3600; // 1 óra

    public function __construct(string $api_key) {
        $this->api_key = $api_key;
    }

    // ── Időjárás előrejelzés (Visual Crossing) ──────────────────
    public function get_forecast(string $location, int $days = 5): array|\WP_Error {
        $cache_key = 'ab_weather_' . md5($location . $days) . '_' . date('YmdH');
        $cached    = get_transient($cache_key);
        if ($cached !== false) return $cached;

        $url = add_query_arg([
            'unitGroup'   => 'metric',
            'key'         => $this->api_key,
            'contentType' => 'json',
            'include'     => 'days,hours,current',
            'elements'    => 'datetime,temp,tempmax,tempmin,feelslike,humidity,precip,precipprob,windspeed,uvindex,conditions,description,sunrise,sunset,icon',
            'lang'        => 'hu',
        ], "https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/"
           . urlencode($location) . "/next{$days}days");

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => ['Accept' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            return new \WP_Error('api_error', 'Időjárás API hiba: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return new \WP_Error('api_error', "API hiba: HTTP {$code}");
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['days'])) {
            return new \WP_Error('api_error', 'Érvénytelen API válasz.');
        }

        // Csak a szükséges adatokat tároljuk
        $result = [
            'location'    => $body['resolvedAddress'] ?? $location,
            'timezone'    => $body['timezone'] ?? 'Europe/Budapest',
            'current'     => $this->parse_current($body['currentConditions'] ?? []),
            'days'        => array_map([$this, 'parse_day'], array_slice($body['days'], 0, $days)),
        ];

        set_transient($cache_key, $result, $this->cache_seconds);
        return $result;
    }

    // ── Vízhőmérséklet (Open-Meteo, ingyenes, kulcs nélkül) ─────
    public function get_water_temperature(float $lat, float $lng): ?float {
        $cache_key = 'ab_water_temp_' . md5("{$lat},{$lng}") . '_' . date('YmdH');
        $cached    = get_transient($cache_key);
        if ($cached !== false) return (float) $cached;

        $url = add_query_arg([
            'latitude'          => $lat,
            'longitude'         => $lng,
            'hourly'            => 'lake_surface_water_temperature',
            'timezone'          => 'Europe/Budapest',
            'forecast_days'     => 1,
        ], 'https://api.open-meteo.com/v1/forecast');

        $response = wp_remote_get($url, ['timeout' => 10]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        // Az aktuális órához legközelebbi érték
        $temps = $body['hourly']['lake_surface_water_temperature'] ?? [];
        $hour  = (int) date('G');

        // Keressük a legutóbbi nem-null értéket az aktuális óra körül
        $water_temp = null;
        for ($i = $hour; $i >= 0; $i--) {
            if (isset($temps[$i]) && $temps[$i] !== null) {
                $water_temp = round((float)$temps[$i], 1);
                break;
            }
        }

        if ($water_temp !== null) {
            set_transient($cache_key, $water_temp, $this->cache_seconds);
        }

        return $water_temp;
    }

    // ── Parsers ──────────────────────────────────────────────────
    private function parse_current(array $d): array {
        return [
            'temp'       => round($d['temp'] ?? 0),
            'feelslike'  => round($d['feelslike'] ?? 0),
            'humidity'   => round($d['humidity'] ?? 0),
            'windspeed'  => round($d['windspeed'] ?? 0),
            'uvindex'    => $d['uvindex'] ?? 0,
            'conditions' => $d['conditions'] ?? '',
            'icon'       => $d['icon'] ?? 'clear-day',
        ];
    }

    private function parse_day(array $d): array {
        return [
            'date'        => $d['datetime']   ?? '',
            'tempmax'     => round($d['tempmax'] ?? 0),
            'tempmin'     => round($d['tempmin'] ?? 0),
            'temp'        => round($d['temp']    ?? 0),
            'precip'      => round($d['precip']  ?? 0, 1),
            'precipprob'  => round($d['precipprob'] ?? 0),
            'windspeed'   => round($d['windspeed']  ?? 0),
            'humidity'    => round($d['humidity']   ?? 0),
            'uvindex'     => $d['uvindex']    ?? 0,
            'conditions'  => $d['conditions'] ?? '',
            'description' => $d['description'] ?? '',
            'icon'        => $d['icon']        ?? 'clear-day',
            'sunrise'     => substr($d['sunrise'] ?? '', 0, 5),
            'sunset'      => substr($d['sunset']  ?? '', 0, 5),
            'hours'       => array_map(fn($h) => [
                'time'       => substr($h['datetime'] ?? '', 0, 5),
                'temp'       => round($h['temp'] ?? 0),
                'precipprob' => round($h['precipprob'] ?? 0),
                'windspeed'  => round($h['windspeed']  ?? 0),
                'icon'       => $h['icon'] ?? 'clear-day',
                'conditions' => $h['conditions'] ?? '',
            ], array_filter(
                $d['hours'] ?? [],
                fn($h) => in_array((int)substr($h['datetime'] ?? '00', 0, 2), [6, 9, 12, 15, 18, 21])
            )),
        ];
    }
}
