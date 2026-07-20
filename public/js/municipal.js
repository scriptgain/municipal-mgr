/**
 * MunicipalMGR front-end behaviour.
 *
 * All of it lives here rather than inline in Blade (Smarty-style separation:
 * markup in templates, logic in classes, JS in public/js). Everything is
 * progressive — the page works without it, it just works better with it.
 */
(function () {
    'use strict';

    /* ------------------------------------------------------------------
     * Tooltips. A single fixed-position node appended to <body>, never a
     * CSS ::after — an ancestor's overflow can clip a pseudo-element tip,
     * and on a table that is exactly where tips are needed most.
     * ---------------------------------------------------------------- */
    var tip = null;

    function tipNode() {
        if (!tip) {
            tip = document.createElement('div');
            tip.className = 'mm-tip';
            tip.setAttribute('role', 'tooltip');
            document.body.appendChild(tip);
        }
        return tip;
    }

    function showTip(el) {
        var text = el.getAttribute('data-tip');
        if (!text) return;
        var node = tipNode();
        node.textContent = text;
        node.style.display = 'block';
        node.style.opacity = '0';

        var anchor = el.getBoundingClientRect();
        var box = node.getBoundingClientRect();
        var left = Math.max(8, Math.min(anchor.left + anchor.width / 2 - box.width / 2, window.innerWidth - box.width - 8));
        var top = anchor.top - box.height - 8;
        if (top < 8) top = anchor.bottom + 8; // flip below when there is no room above
        node.style.left = left + 'px';
        node.style.top = top + 'px';
        node.style.opacity = '1';
    }

    function hideTip() {
        if (tip) {
            tip.style.opacity = '0';
            tip.style.display = 'none';
        }
    }

    document.addEventListener('mouseover', function (e) {
        var el = e.target.closest('[data-tip]');
        if (el) showTip(el);
    });
    document.addEventListener('mouseout', function (e) {
        if (e.target.closest('[data-tip]')) hideTip();
    });
    document.addEventListener('focusin', function (e) {
        var el = e.target.closest('[data-tip]');
        if (el) showTip(el);
    });
    document.addEventListener('focusout', hideTip);
    document.addEventListener('scroll', hideTip, true);
    window.addEventListener('resize', hideTip);

    /* ------------------------------------------------------------------
     * Truncated table cells get their full text as a native tooltip.
     * ---------------------------------------------------------------- */
    function tagTruncated() {
        document.querySelectorAll('.mm-table td').forEach(function (td) {
            if (td.querySelector('[data-tip]') || td.hasAttribute('data-tip') || td.title) return;
            if (td.scrollWidth > td.clientWidth + 1) td.title = td.textContent.trim();
        });
    }

    /* ------------------------------------------------------------------
     * Alert banner dismissal, remembered per alert so a resident who has
     * closed today's advisory is not shown it on every page.
     * ---------------------------------------------------------------- */
    function initAlert() {
        var banner = document.querySelector('[data-alert-id]');
        if (!banner) return;
        var key = 'mm-alert-dismissed-' + banner.getAttribute('data-alert-id');

        try {
            if (window.localStorage.getItem(key) === '1') {
                banner.remove();
                return;
            }
        } catch (e) { /* private mode: just show it */ }

        var close = banner.querySelector('[data-alert-dismiss]');
        if (close) {
            close.addEventListener('click', function () {
                banner.remove();
                try { window.localStorage.setItem(key, '1'); } catch (e) {}
            });
        }
    }

    /* ------------------------------------------------------------------
     * Copy-to-clipboard buttons (tracking references, contact emails).
     * ---------------------------------------------------------------- */
    function initCopy() {
        document.querySelectorAll('[data-copy]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var value = btn.getAttribute('data-copy');
                var done = function () {
                    var was = btn.getAttribute('data-copy-label') || btn.textContent;
                    btn.setAttribute('data-copy-label', was);
                    btn.textContent = 'Copied';
                    setTimeout(function () { btn.textContent = was; }, 1600);
                };
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(value).then(done);
                } else {
                    var ta = document.createElement('textarea');
                    ta.value = value;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    done();
                }
            });
        });
    }

    /* ------------------------------------------------------------------
     * Auto-submit filter selects (document library, news, directory).
     * ---------------------------------------------------------------- */
    function initAutoFilters() {
        document.querySelectorAll('[data-auto-submit]').forEach(function (el) {
            el.addEventListener('change', function () {
                var form = el.closest('form');
                if (form) form.submit();
            });
        });
    }

    function ready() {
        tagTruncated();
        initAlert();
        initCopy();
        initAutoFilters();
    }

    if (document.readyState !== 'loading') ready();
    else document.addEventListener('DOMContentLoaded', ready);
})();
