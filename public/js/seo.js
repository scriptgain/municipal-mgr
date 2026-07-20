/**
 * Search Appearance panel behaviour.
 *
 * Deliberately plain DOM and NOT an Alpine.data() component. Anything calling
 * Alpine.data() has to be loaded before the Alpine CDN bundle, because Alpine
 * fires alpine:init the moment it starts and a component registered after that
 * never registers at all. Staying out of Alpine entirely means this file can
 * load in any order and keep working.
 *
 * Everything here is progressive: with JS off the fields still save, they just
 * lose the counters and the live preview.
 */
(function () {
    'use strict';

    /* Google truncates around these widths. They are guidance shown to the
       editor, not limits enforced on the field. */
    var TITLE_IDEAL = 60;
    var TITLE_MAX = 70;
    /* These three must match SeoAudit::DESC_MIN / DESC_MAX and the snippet
       length in HasSeo, or the panel and the SEO Health report disagree. */
    var DESC_MIN = 70;
    var DESC_IDEAL = 155;
    var DESC_MAX = 160;

    function clip(text, max) {
        text = (text || '').trim();
        return text.length > max ? text.slice(0, max - 1).trim() + '…' : text;
    }

    /**
     * Counter line under a field. Reports the automatic value when the field
     * is blank, because "blank" here means "derived", not "missing".
     */
    function describe(value, fallback, ideal, max, min) {
        var length = value.trim().length;

        if (length === 0) {
            return fallback
                ? ['Using the automatic value: ' + String(fallback.length) + ' characters.', 'text-slate-500']
                : ['Nothing to fall back on. Add a value so this is not left to the site default.', 'text-amber-700'];
        }
        if (min && length < min) {
            return [String(length) + ' characters. A little short; aim for ' + String(ideal) + '.', 'text-amber-700'];
        }
        if (length > max) {
            return [String(length) + ' characters. Over ' + String(max) + ', so search results will cut it off.', 'text-rose-600'];
        }
        if (length > ideal) {
            return [String(length) + ' characters. Slightly over ' + String(ideal) + '; it may be trimmed.', 'text-amber-700'];
        }
        return [String(length) + ' characters. Good length.', 'text-emerald-700'];
    }

    function paint(node, message, tone) {
        if (!node) return;
        node.textContent = message;
        node.className = 'mt-1.5 text-xs ' + tone;
    }

    function initPanel(panel) {
        var titleField = panel.querySelector('[data-seo-title]');
        var descField = panel.querySelector('[data-seo-description]');
        var titleCount = panel.querySelector('[data-seo-count-for="meta_title"]');
        var descCount = panel.querySelector('[data-seo-count-for="meta_description"]');
        var previewTitle = panel.querySelector('[data-seo-preview-title]');
        var previewDesc = panel.querySelector('[data-seo-preview-description]');

        function refresh() {
            var titleFallback = titleField ? titleField.getAttribute('data-seo-fallback') || '' : '';
            var descFallback = descField ? descField.getAttribute('data-seo-fallback') || '' : '';
            var titleValue = titleField ? titleField.value : '';
            var descValue = descField ? descField.value : '';

            if (titleField && titleCount) {
                var t = describe(titleValue, titleFallback, TITLE_IDEAL, TITLE_MAX, 0);
                paint(titleCount, t[0], t[1]);
            }
            if (descField && descCount) {
                var d = describe(descValue, descFallback, DESC_IDEAL, DESC_MAX, DESC_MIN);
                paint(descCount, d[0], d[1]);
            }

            if (previewTitle) {
                previewTitle.textContent = clip(titleValue || titleFallback, TITLE_MAX) || 'Untitled';
            }
            if (previewDesc) {
                var text = clip(descValue || descFallback, DESC_MAX);
                previewDesc.textContent = text || 'No description yet. One will be written from this page’s content.';
            }
        }

        if (titleField) titleField.addEventListener('input', refresh);
        if (descField) descField.addEventListener('input', refresh);
        refresh();
    }

    function ready() {
        var panels = document.querySelectorAll('[data-seo-panel]');
        Array.prototype.forEach.call(panels, initPanel);
    }

    if (document.readyState !== 'loading') ready();
    else document.addEventListener('DOMContentLoaded', ready);
})();
