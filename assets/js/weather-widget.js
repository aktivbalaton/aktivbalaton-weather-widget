/**
 * AktívBalaton – Időjárás Widget JS  v2.0.0
 * Az API hívás szerver oldalon történik (WordPress AJAX).
 * Az API kulcs sosem jelenik meg itt.
 */
(function ($) {
    'use strict';

    // ── Ikon mapping (Visual Crossing icon → saját fájl) ────────
    const ICON_MAP = {
        'clear-day':           'sun.svg',
        'clear-night':         'sun.svg',
        'partly-cloudy-day':   'felhos_napos.svg',
        'partly-cloudy-night': 'felhos_napos.svg',
        'cloudy':              'felhos_napos.svg',
        'fog':                 'felhos_napos.svg',
        'wind':                'felhos_napos.svg',
        'rain':                'eso.svg',
        'showers-day':         'eso.svg',
        'showers-night':       'eso.svg',
        'thunder-rain':        'vihar.svg',
        'thunder-showers-day': 'vihar.svg',
        'thunder-showers-night':'vihar.svg',
        'snow':                'havazas.svg',
        'snow-showers-day':    'havazas.svg',
        'snow-showers-night':  'havazas.svg',
        'sleet':               'havazas.svg',
    };

    function getIconUrl(icon) {
        const file = ICON_MAP[icon] || 'sun.svg';
        return abWeather.iconUrl + file;
    }

    // ── Dátum formázás ────────────────────────────────────────────
    function formatDate(dateStr, format) {
        const d = new Date(dateStr + 'T00:00:00');
        if (format === 'short') {
            return d.toLocaleDateString('hu-HU', { month: 'short', day: 'numeric' });
        }
        if (format === 'weekday') {
            return d.toLocaleDateString('hu-HU', { weekday: 'long' });
        }
        if (format === 'full') {
            return d.toLocaleDateString('hu-HU', { month: 'long', day: 'numeric', weekday: 'long' });
        }
        return d.toLocaleDateString('hu-HU');
    }

    function isToday(dateStr) {
        return dateStr === new Date().toISOString().split('T')[0];
    }
    function isTomorrow(dateStr) {
        const tomorrow = new Date(); tomorrow.setDate(tomorrow.getDate() + 1);
        return dateStr === tomorrow.toISOString().split('T')[0];
    }

    // ── UV szöveg ─────────────────────────────────────────────────
    function uvLabel(uv) {
        if (uv <= 2)  return { text: 'Alacsony',    cls: 'uv-low' };
        if (uv <= 5)  return { text: 'Közepes',     cls: 'uv-mid' };
        if (uv <= 7)  return { text: 'Magas',       cls: 'uv-high' };
        if (uv <= 10) return { text: 'Nagyon magas',cls: 'uv-very-high' };
        return { text: 'Extrém', cls: 'uv-extreme' };
    }

    // ── Részletes nap nézet (Ma / Holnap) ────────────────────────
    function renderDayDetail(container, day, waterTemp, opts, isCurrentDay) {
        if (!day) { container.html('<p class="ab-weather-no-data">Nincs adat.</p>'); return; }

        const uv    = uvLabel(day.uvindex);
        const dateLabel = isCurrentDay ? 'Ma' : isTomorrow(day.date) ? 'Holnap' : formatDate(day.date, 'full');

        let html = `
        <div class="ab-weather-day-header">
            <div class="ab-weather-day-title">
                <span class="ab-weather-day-name">${dateLabel}</span>
                <span class="ab-weather-day-date">${formatDate(day.date, 'short')}</span>
            </div>
            <div class="ab-weather-main-row">
                <img src="${getIconUrl(day.icon)}" alt="${day.conditions}" class="ab-weather-icon-lg ab-icon-anim">
                <div class="ab-weather-temps">
                    <span class="ab-weather-temp-main">${day.tempmax}°</span>
                    <span class="ab-weather-temp-min">${day.tempmin}°</span>
                </div>
                <div class="ab-weather-condition">${day.conditions}</div>
            </div>
        </div>

        <div class="ab-weather-meta-grid">`;

        if (opts.feelslike !== false) {
            html += `<div class="ab-weather-meta-item ab-meta-item--feelslike">
                <span class="ab-meta-icon">🌡️</span>
                <span class="ab-meta-label">Hőérzet</span>
                <span class="ab-meta-value">${day.temp}°C</span>
            </div>`;
        }
        if (opts.humidity !== false) {
            html += `<div class="ab-weather-meta-item ab-meta-item--humidity">
                <span class="ab-meta-icon">💧</span>
                <span class="ab-meta-label">Páratartalom</span>
                <span class="ab-meta-value">${day.humidity}%</span>
            </div>`;
        }
        if (opts.wind !== false) {
            html += `<div class="ab-weather-meta-item ab-meta-item--wind">
                <span class="ab-meta-icon">💨</span>
                <span class="ab-meta-label">Szél</span>
                <span class="ab-meta-value">${day.windspeed} km/h</span>
            </div>`;
        }
        if (opts.uv !== false) {
            html += `<div class="ab-weather-meta-item ab-meta-item--uv">
                <span class="ab-meta-icon">☀️</span>
                <span class="ab-meta-label">UV index</span>
                <span class="ab-meta-value ${uv.cls}">${day.uvindex} – ${uv.text}</span>
            </div>`;
        }
        if (opts.sunrise !== false && day.sunrise) {
            html += `<div class="ab-weather-meta-item ab-meta-item--sunrise">
                <span class="ab-meta-icon">🌅</span>
                <span class="ab-meta-label">Napkelte / Napnyugta</span>
                <span class="ab-meta-value">${day.sunrise} / ${day.sunset}</span>
            </div>`;
        }
        if (day.precipprob > 0) {
            html += `<div class="ab-weather-meta-item">
                <span class="ab-meta-icon">🌧️</span>
                <span class="ab-meta-label">Csapadék esélye</span>
                <span class="ab-meta-value">${day.precipprob}%${day.precip > 0 ? ' / ' + day.precip + ' mm' : ''}</span>
            </div>`;
        }

        // Vízhőmérséklet
        if (waterTemp !== null && waterTemp !== undefined) {
            html += `<div class="ab-weather-meta-item ab-weather-water-temp">
                <span class="ab-meta-icon">🏊</span>
                <span class="ab-meta-label">Vízhőmérséklet</span>
                <span class="ab-meta-value"><span>${waterTemp}°C</span></span>
            </div>`;
        }

        html += `</div>`;

        // Óránkénti bontás
        if (opts.hourly !== false && day.hours && day.hours.length > 0) {
            html += `<div class="ab-weather-hourly">
                <div class="ab-weather-hourly-title">Óránkénti bontás</div>
                <div class="ab-weather-hourly-grid">`;
            day.hours.forEach(h => {
                html += `<div class="ab-weather-hour">
                    <div class="ab-hour-time">${h.time}</div>
                    <img src="${getIconUrl(h.icon)}" alt="${h.conditions}" class="ab-hour-icon">
                    <div class="ab-hour-temp">${h.temp}°</div>
                    ${h.precipprob > 10 ? `<div class="ab-hour-precip">💧${h.precipprob}%</div>` : ''}
                </div>`;
            });
            html += `</div></div>`;
        }

        container.html(html);
    }

    // ── Előrejelzés grid ─────────────────────────────────────────
    function renderForecast(container, days, waterTemp, opts, mobileDays) {
        const mDays = mobileDays || 3;
        let html = '';
        days.forEach((day, i) => {
            const hideMobileCls = i >= mDays ? ' ab-hide-mobile' : '';
            const label = isToday(day.date) ? 'Ma' : isTomorrow(day.date) ? 'Holnap' : formatDate(day.date, 'weekday');
            html += `<div class="ab-forecast-card${hideMobileCls}">
                <div class="ab-forecast-date">
                    <span class="ab-forecast-weekday">${label}</span>
                    <span class="ab-forecast-datenum">${formatDate(day.date, 'short')}</span>
                </div>
                <img src="${getIconUrl(day.icon)}" alt="${day.conditions}" class="ab-forecast-icon ab-icon-anim">
                <div class="ab-forecast-temps">
                    <span class="ab-forecast-max">${day.tempmax}°</span>
                    <span class="ab-forecast-min">${day.tempmin}°</span>
                </div>
                ${day.precipprob > 20 ? `<div class="ab-forecast-precip">💧 ${day.precipprob}%</div>` : ''}
                ${opts.wind !== false ? `<div class="ab-forecast-wind">💨 ${day.windspeed} km/h</div>` : ''}
                ${i === 0 && waterTemp !== null ? `<div class="ab-forecast-water">🏊 ${waterTemp}°C</div>` : ''}
            </div>`;
        });
        container.html(html);
    }

    // ── Tab váltás ────────────────────────────────────────────────
    function initTabs(widget) {
        widget.on('click', '.ab-weather-tab', function () {
            const tab = $(this).data('tab');
            widget.find('.ab-weather-tab').removeClass('active');
            $(this).addClass('active');
            widget.find('.ab-weather-view').hide();
            widget.find(`.ab-weather-view[data-view="${tab}"]`).show();
        });
    }

    // ── Fő init ───────────────────────────────────────────────────
    function initWidget(widget) {
        const location  = widget.data('location')  || 'Balatonlelle';
        const days      = widget.data('days')       || 5;
        const showWater = widget.data('water')      === 1;
        const waterLat  = widget.data('water-lat')  || 46.83;
        const waterLng  = widget.data('water-lng')  || 17.73;
        const opts      = widget.data('opts') || {};

        widget.find('.ab-weather-loading').show();
        widget.find('.ab-weather-content').hide();
        widget.find('.ab-weather-error').hide();

        $.ajax({
            url:  abWeather.ajaxUrl,
            type: 'POST',
            data: {
                action:    'ab_weather_fetch',
                nonce:     abWeather.nonce,
                location:  location,
                days:      days,
                water_lat: waterLat,
                water_lng: waterLng,
            },
            success: function (res) {
                if (!res.success) {
                    widget.find('.ab-weather-loading').hide();
                    widget.find('.ab-weather-error').show();
                    return;
                }

                const weather   = res.data.weather;
                const waterTemp = showWater ? res.data.water : null;
                const daysData  = weather.days || [];

                // Ma nézet
                const todayContainer = widget.find('[data-view="today"] .ab-weather-day-detail');
                renderDayDetail(todayContainer, daysData[0], waterTemp, opts, true);

                // Holnap nézet
                const tomorrowContainer = widget.find('[data-view="tomorrow"] .ab-weather-day-detail');
                renderDayDetail(tomorrowContainer, daysData[1], waterTemp, opts, false);

                // Előrejelzés
                const forecastContainer = widget.find('.ab-weather-forecast-grid');
                const mobileDays = parseInt(widget.data('mobile-days')) || 3;
                renderForecast(forecastContainer, daysData, waterTemp, opts, mobileDays);

                // Megjelenítés
                widget.find('.ab-weather-loading').hide();
                widget.find('.ab-weather-content').show();

                // Tab logika
                initTabs(widget);
            },
            error: function () {
                widget.find('.ab-weather-loading').hide();
                widget.find('.ab-weather-error').show();
            }
        });
    }

    // ── Indítás ───────────────────────────────────────────────────
    $(document).ready(function () {
        $('.ab-weather-widget').each(function () {
            initWidget($(this));
        });
    });

    // Elementor editor preview frissítés
    $(window).on('elementor/frontend/init', function () {
        if (window.elementorFrontend) {
            elementorFrontend.hooks.addAction('frontend/element_ready/ab_weather.default', function ($el) {
                initWidget($el.find('.ab-weather-widget'));
            });
        }
    });

})(jQuery);
