/**
 * Theme Manager live preview.
 *
 * Plain DOM, not Alpine.data(), for the same reason as templates.js: this file
 * loads after the Alpine CDN bundle and a late Alpine.data() call registers
 * nothing.
 *
 * The preview panel is styled entirely through CSS custom properties set on
 * one container element, so updating a colour is a single style write rather
 * than a re-render.
 */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    /** Mix a hex colour toward white or black, mirroring the server ramp. */
    function mix(hex, target, amount) {
        var c = parse(hex);
        if (!c) return hex;
        var t = target === 'white' ? 255 : 0;
        var f = amount / 100;

        return rgb(
            Math.round(c[0] + (t - c[0]) * f),
            Math.round(c[1] + (t - c[1]) * f),
            Math.round(c[2] + (t - c[2]) * f)
        );
    }

    function parse(hex) {
        var m = /^#?([0-9a-f]{6})$/i.exec(String(hex).trim());
        if (!m) return null;
        var n = parseInt(m[1], 16);

        return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
    }

    function rgb(r, g, b) {
        return 'rgb(' + r + ',' + g + ',' + b + ')';
    }

    ready(function () {
        var form = document.querySelector('[data-theme-form]');
        var preview = document.querySelector('[data-theme-preview]');
        if (!form) return;

        var shipped = {};
        var fields = {};

        form.querySelectorAll('[data-theme-token]').forEach(function (input) {
            var key = input.getAttribute('data-theme-token');
            fields[key] = input;
            shipped[key] = input.getAttribute('data-shipped') || input.value;
        });

        function value(key, fallback) {
            return fields[key] && fields[key].value ? fields[key].value : fallback;
        }

        /* ------------------------------------------------------------
         * Paint the preview from the current field values.
         * ---------------------------------------------------------- */
        function paint() {
            if (!preview) return;

            var brand = value('brand', '#0f4c81');
            var accent = value('accent', '#c8a45c');
            var chrome = value('chrome', '#061f35');
            var chromeSoft = value('chrome_soft', '#0a2b47');
            var fontSans = value('font_sans', 'system-ui, sans-serif');
            var fontDisplay = value('font_display', 'Georgia, serif');
            var fontScale = parseFloat(value('font_scale', '1')) || 1;
            var spacing = parseFloat(value('spacing', '1')) || 1;
            var radius = parseFloat(value('radius', '1'));
            if (isNaN(radius)) radius = 1;

            var s = preview.style;
            s.setProperty('--tp-brand', brand);
            s.setProperty('--tp-brand-soft', mix(brand, 'white', 88));
            s.setProperty('--tp-brand-dark', mix(brand, 'black', 25));
            s.setProperty('--tp-accent', accent);
            s.setProperty('--tp-chrome', chrome);
            s.setProperty('--tp-chrome-soft', chromeSoft);
            s.setProperty('--tp-font', fontSans);
            s.setProperty('--tp-font-display', fontDisplay);
            s.setProperty('--tp-scale', fontScale);
            s.setProperty('--tp-space', spacing);
            s.setProperty('--tp-radius', (radius * 0.75) + 'rem');
            s.setProperty('--tp-radius-sm', (radius * 0.5) + 'rem');

            paintSwatches(brand);
        }

        /** The generated brand ramp, so an operator sees what one hex becomes. */
        function paintSwatches(brand) {
            var host = preview.querySelector('[data-preview-swatches]');
            if (!host) return;

            var steps = [
                ['50', 'white', 92], ['100', 'white', 85], ['200', 'white', 72],
                ['300', 'white', 55], ['400', 'white', 30], ['500', null, 0],
                ['600', 'black', 12], ['700', 'black', 25], ['800', 'black', 40],
                ['900', 'black', 52], ['950', 'black', 68]
            ];

            host.textContent = '';
            steps.forEach(function (step) {
                var chip = document.createElement('span');
                chip.className = 'mm-tp-chip';
                chip.style.background = step[1] ? mix(brand, step[1], step[2]) : brand;
                chip.setAttribute('data-tip', 'brand-' + step[0]);
                host.appendChild(chip);
            });
        }

        /* ------------------------------------------------------------
         * Wiring.
         * ---------------------------------------------------------- */
        Object.keys(fields).forEach(function (key) {
            fields[key].addEventListener('input', paint);
            fields[key].addEventListener('change', paint);
        });

        // Native colour pickers drive their paired hex text input.
        form.querySelectorAll('[data-color-picker]').forEach(function (picker) {
            var target = document.getElementById(picker.getAttribute('data-color-picker'));
            if (!target) return;

            picker.addEventListener('input', function () {
                target.value = picker.value;
                paint();
            });
            target.addEventListener('input', function () {
                if (/^#[0-9a-f]{6}$/i.test(target.value)) picker.value = target.value;
                paint();
            });
        });

        // Range outputs.
        form.querySelectorAll('[data-output-for]').forEach(function (output) {
            var input = document.getElementById(output.getAttribute('data-output-for'));
            if (!input) return;

            var sync = function () { output.textContent = input.value; };
            input.addEventListener('input', sync);
            sync();
        });

        /* Chrome treatment: the visible control is a toggle switch (house
         * style, never a bare checkbox), and it writes the real value into a
         * hidden field the server reads. Watching the toggle's aria-checked is
         * how a plain script observes an Alpine-driven switch without having to
         * be an Alpine component itself. */
        var treatment = form.querySelector('[data-chrome-treatment]');
        if (treatment) {
            var toggle = treatment.closest('div') ? treatment.closest('div').querySelector('button[role="switch"]') : null;
            if (toggle) {
                var observer = new MutationObserver(function () {
                    treatment.value = toggle.getAttribute('aria-checked') === 'true' ? 'dark' : 'light';
                });
                observer.observe(toggle, { attributes: true, attributeFilter: ['aria-checked'] });
            }
        }

        /* Reset the form's fields back to the shipped values. Does not save. */
        var reset = form.querySelector('[data-theme-reset]');
        if (reset) {
            reset.addEventListener('click', function () {
                Object.keys(fields).forEach(function (key) {
                    fields[key].value = shipped[key];
                });
                form.querySelectorAll('[data-color-picker]').forEach(function (picker) {
                    var target = document.getElementById(picker.getAttribute('data-color-picker'));
                    if (target && /^#[0-9a-f]{6}$/i.test(target.value)) picker.value = target.value;
                });
                form.querySelectorAll('[data-output-for]').forEach(function (output) {
                    var input = document.getElementById(output.getAttribute('data-output-for'));
                    if (input) output.textContent = input.value;
                });
                paint();
            });
        }

        paint();
    });
})();
