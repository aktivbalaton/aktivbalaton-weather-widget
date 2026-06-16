<?php
namespace AktivBalaton;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

defined('ABSPATH') || exit;

class Weather_Elementor_Widget extends Widget_Base {

    public function get_name(): string    { return 'ab_weather'; }
    public function get_title(): string   { return 'AB – Időjárás'; }
    public function get_icon(): string    { return 'eicon-weather'; }
    public function get_categories(): array { return ['general']; }
    public function get_keywords(): array {
        return ['időjárás', 'weather', 'balaton', 'hőmérséklet', 'előrejelzés'];
    }

    // ═══════════════════════════════════════════════════════════════
    //  KONTROLOK
    // ═══════════════════════════════════════════════════════════════
    protected function register_controls(): void {

        // ── 1. HELYSZÍN & ADATOK ─────────────────────────────────
        $this->start_controls_section('sec_data', [
            'label' => '📍 Helyszín & adatok',
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('location', [
            'label'       => 'Helyszín',
            'type'        => Controls_Manager::TEXT,
            'default'     => 'Balatonlelle',
            'placeholder' => 'pl. Balatonlelle, Siófok, Keszthely',
            'description' => 'Visual Crossing által felismerhető helyszín neve.',
        ]);

        $this->add_control('days_count', [
            'label'       => 'Előrejelzés napok száma',
            'type'        => Controls_Manager::NUMBER,
            'default'     => 5,
            'min'         => 1,
            'max'         => 10,
            'description' => 'Hány napos előrejelzést mutasson (max. 10).',
        ]);

        $this->add_control('show_water_temp', [
            'label'        => 'Vízhőmérséklet megjelenítése',
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => 'Igen',
            'label_off'    => 'Nem',
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('water_lat', [
            'label'       => 'Vízhőmérséklet – szélesség',
            'type'        => Controls_Manager::NUMBER,
            'default'     => 46.83,
            'step'        => 0.01,
            'condition'   => ['show_water_temp' => 'yes'],
            'description' => 'GPS koordináta a Balatonon (alapért. Balatonlelle közelében).',
        ]);

        $this->add_control('water_lng', [
            'label'       => 'Vízhőmérséklet – hosszúság',
            'type'        => Controls_Manager::NUMBER,
            'default'     => 17.73,
            'step'        => 0.01,
            'condition'   => ['show_water_temp' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── 2. MEGJELENÍTETT ELEMEK ──────────────────────────────
        $this->start_controls_section('sec_elements', [
            'label' => '🔘 Megjelenített adatok',
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('default_view', [
            'label'   => 'Alapértelmezett nézet',
            'type'    => Controls_Manager::SELECT,
            'default' => 'today',
            'options' => [
                'today'    => 'Ma – részletes',
                'tomorrow' => 'Holnap – részletes',
                'forecast' => 'Előrejelzés (több nap)',
            ],
        ]);

        $this->add_control('show_view_switcher', [
            'label'        => 'Nézet váltó gombok',
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => 'Igen',
            'label_off'    => 'Nem',
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => 'Ma / Holnap / Előrejelzés váltó megjelenítése.',
        ]);

        $this->add_control('show_feelslike', [
            'label'        => 'Hőérzet',
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_humidity', [
            'label'        => 'Páratartalom',
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_wind', [
            'label'        => 'Szélsebesség',
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_uv', [
            'label'        => 'UV index',
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_sunrise', [
            'label'        => 'Napkelte / Napnyugta',
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_hourly', [
            'label'        => 'Óránkénti bontás (Ma / Holnap nézetben)',
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->end_controls_section();

        // ── 3. MOBIL BEÁLLÍTÁSOK ─────────────────────────────────
        $this->start_controls_section('sec_mobile', [
            'label' => '📱 Mobil beállítások',
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('mobile_days', [
            'label'       => 'Előrejelzés: max. napok mobilon',
            'type'        => Controls_Manager::NUMBER,
            'default'     => 3,
            'min'         => 1,
            'max'         => 10,
            'description' => 'Mobilon (600px alatt) ennyi napot mutat az előrejelzés. Pl. asztali: 7, mobil: 3.',
        ]);

        $this->add_control('mobile_hide_heading', [
            'label'     => 'Mobilon elrejtett elemek',
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('mobile_hide_wind', [
            'label'        => 'Szélsebesség elrejtése mobilon',
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'wind',
            'default'      => '',
        ]);

        $this->add_control('mobile_hide_uv', [
            'label'        => 'UV index elrejtése mobilon',
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'uv',
            'default'      => 'uv',
        ]);

        $this->add_control('mobile_hide_sunrise', [
            'label'        => 'Napkelte/napnyugta elrejtése mobilon',
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'sunrise',
            'default'      => 'sunrise',
        ]);

        $this->add_control('mobile_hide_humidity', [
            'label'        => 'Páratartalom elrejtése mobilon',
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'humidity',
            'default'      => '',
        ]);

        $this->add_control('mobile_hide_hourly', [
            'label'        => 'Óránkénti bontás elrejtése mobilon',
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'hourly',
            'default'      => 'hourly',
        ]);

        $this->end_controls_section();

        // ── 4. DESIGN (Style) ────────────────────────────────────
        $this->start_controls_section('sec_style', [
            'label' => '🎨 Design',
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('widget_style', [
            'label'   => 'Widget stílus',
            'type'    => Controls_Manager::SELECT,
            'default' => 'glass',
            'options' => [
                'glass'  => 'Glassmorphism (áttetsző)',
                'card'   => 'Kártya (fehér háttér)',
                'dark'   => 'Sötét',
                'minimal'=> 'Minimál (keret nélkül)',
            ],
        ]);

        $this->add_control('accent_color', [
            'label'     => 'Akcentus szín',
            'type'      => Controls_Manager::COLOR,
            'default'   => '#1A6EA3',
            'selectors' => [
                '{{WRAPPER}} .ab-weather-tab.active'      => 'background: {{VALUE}}; border-color: {{VALUE}};',
                '{{WRAPPER}} .ab-weather-temp-main'       => 'color: {{VALUE}};',
                '{{WRAPPER}} .ab-weather-water-temp span' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('card_radius', [
            'label'      => 'Kártya sarokkerekítés (px)',
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 32]],
            'default'    => ['unit' => 'px', 'size' => 16],
            'selectors'  => ['{{WRAPPER}} .ab-weather-widget' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('bg_color', [
            'label'     => 'Háttérszín',
            'type'      => Controls_Manager::COLOR,
            'default'   => '',
            'selectors' => ['{{WRAPPER}} .ab-weather-widget' => 'background: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'widget_typo',
            'label'    => 'Szöveg tipográfia',
            'selector' => '{{WRAPPER}} .ab-weather-widget',
        ]);

        $this->end_controls_section();
    }

    // ═══════════════════════════════════════════════════════════════
    //  RENDER
    // ═══════════════════════════════════════════════════════════════
    protected function render(): void {
        $s = $this->get_settings_for_display();

        $location      = esc_attr($s['location']        ?? 'Balatonlelle');
        $days          = (int)($s['days_count']          ?? 5);
        $water_lat     = (float)($s['water_lat']         ?? 46.83);
        $water_lng     = (float)($s['water_lng']         ?? 17.73);
        $show_water    = ($s['show_water_temp']           ?? 'yes') === 'yes';
        $default_view  = $s['default_view']              ?? 'today';
        $show_switcher = ($s['show_view_switcher']        ?? 'yes') === 'yes';
        $widget_style  = $s['widget_style']              ?? 'glass';

        // Megjelenített elemek
        $opts = json_encode([
            'feelslike' => ($s['show_feelslike'] ?? 'yes') === 'yes',
            'humidity'  => ($s['show_humidity']  ?? 'yes') === 'yes',
            'wind'      => ($s['show_wind']       ?? 'yes') === 'yes',
            'uv'        => ($s['show_uv']         ?? 'yes') === 'yes',
            'sunrise'   => ($s['show_sunrise']    ?? 'yes') === 'yes',
            'hourly'    => ($s['show_hourly']     ?? 'yes') === 'yes',
        ]);

        // Mobil beállítások
        $mobile_days = (int)($s['mobile_days'] ?? 3);
        $hide_parts  = array_filter([
            $s['mobile_hide_wind']     ?? '',
            $s['mobile_hide_uv']       ?? '',
            $s['mobile_hide_sunrise']  ?? '',
            $s['mobile_hide_humidity'] ?? '',
            $s['mobile_hide_hourly']   ?? '',
        ]);
        $hide_mobile = implode(' ', $hide_parts);

        $widget_id = 'ab-weather-' . $this->get_id();
        ?>

        <div id="<?php echo $widget_id; ?>"
             class="ab-weather-widget ab-weather--<?php echo esc_attr($widget_style); ?>"
             data-location="<?php echo $location; ?>"
             data-days="<?php echo $days; ?>"
             data-mobile-days="<?php echo $mobile_days; ?>"
             data-water="<?php echo $show_water ? '1' : '0'; ?>"
             data-water-lat="<?php echo $water_lat; ?>"
             data-water-lng="<?php echo $water_lng; ?>"
             data-view="<?php echo esc_attr($default_view); ?>"
             data-hide-mobile="<?php echo esc_attr($hide_mobile); ?>"
             data-opts='<?php echo esc_attr($opts); ?>'>

            <!-- Betöltő állapot -->
            <div class="ab-weather-loading">
                <div class="ab-weather-spinner"></div>
                <p>Időjárás betöltése...</p>
            </div>

            <!-- Tartalom – JS tölti be -->
            <div class="ab-weather-content" style="display:none;">

                <?php if ($show_switcher) : ?>
                <div class="ab-weather-tabs">
                    <button class="ab-weather-tab <?php echo $default_view === 'today'    ? 'active' : ''; ?>" data-tab="today">Ma</button>
                    <button class="ab-weather-tab <?php echo $default_view === 'tomorrow' ? 'active' : ''; ?>" data-tab="tomorrow">Holnap</button>
                    <button class="ab-weather-tab <?php echo $default_view === 'forecast' ? 'active' : ''; ?>" data-tab="forecast">Előrejelzés</button>
                </div>
                <?php endif; ?>

                <!-- Ma nézet -->
                <div class="ab-weather-view" data-view="today" <?php echo $default_view !== 'today' ? 'style="display:none;"' : ''; ?>>
                    <div class="ab-weather-day-detail" data-day="0"></div>
                </div>

                <!-- Holnap nézet -->
                <div class="ab-weather-view" data-view="tomorrow" <?php echo $default_view !== 'tomorrow' ? 'style="display:none;"' : ''; ?>>
                    <div class="ab-weather-day-detail" data-day="1"></div>
                </div>

                <!-- Előrejelzés nézet -->
                <div class="ab-weather-view" data-view="forecast" <?php echo $default_view !== 'forecast' ? 'style="display:none;"' : ''; ?>>
                    <div class="ab-weather-forecast-grid"></div>
                </div>

            </div><!-- .ab-weather-content -->

            <div class="ab-weather-error" style="display:none;">
                <p>❌ Nem sikerült betölteni az időjárási adatokat.</p>
            </div>

        </div>

        <?php
    }
}
