/**
 * reCAPTCHA v3 helper.
 *
 * v3 is invisible: there is no checkbox, so a token has to be fetched with
 * grecaptcha.execute() and dropped into the hidden g-recaptcha-response field
 * just before the form submits. Loaded ONLY when the v3 provider is active
 * (the <x-captcha> component decides), so it costs nothing on other setups.
 *
 * Plain DOM, no Alpine.data(), so its position relative to the Alpine bundle
 * does not matter — the Alpine-ordering trap does not apply here.
 */
(function () {
    'use strict';

    function init() {
        var marker = document.querySelector('[data-recaptcha-v3]');
        if (!marker || typeof grecaptcha === 'undefined') return;

        var siteKey = marker.getAttribute('data-sitekey');
        var action = marker.getAttribute('data-action') || 'submit';
        var form = marker.closest('form');
        if (!form || !siteKey) return;

        var field = form.querySelector('input[name="g-recaptcha-response"]');
        if (!field) return;

        var submitting = false;

        form.addEventListener('submit', function (e) {
            if (submitting) return; // second pass, after we have a token
            e.preventDefault();

            grecaptcha.ready(function () {
                grecaptcha.execute(siteKey, { action: action }).then(function (token) {
                    field.value = token;
                    submitting = true;
                    // Use requestSubmit so native validation still runs.
                    if (form.requestSubmit) {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
            });
        });
    }

    if (document.readyState !== 'loading') init();
    else document.addEventListener('DOMContentLoaded', init);
})();
