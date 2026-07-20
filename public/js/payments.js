/*
 * Pay Your Bill — card form.
 *
 * Stripe Elements does the card handling. Card details are entered into an
 * iframe served by Stripe and never touch this page, this JavaScript, or the
 * municipality's server: what comes back here is a confirmation result, not a
 * card number. Nothing card-shaped is ever logged or stored client side.
 *
 * The amount is NOT read from the DOM and sent anywhere. It was fixed on the
 * server when the PaymentIntent was created, and the client secret only
 * confirms that intent for that amount.
 *
 * No build step: plain ES, loaded with defer, per the project's no-Vite rule.
 */
(function () {
    'use strict';

    var root = document.getElementById('payment-form');
    if (!root || typeof Stripe === 'undefined') {
        return;
    }

    var publishableKey = root.dataset.publishableKey;
    var clientSecret = root.dataset.clientSecret;
    var returnUrl = root.dataset.returnUrl;
    var connectedAccount = root.dataset.connectedAccount;

    if (!publishableKey || !clientSecret) {
        return;
    }

    // Direct charges live on the municipality's connected account, so Stripe.js
    // must be told which account the intent belongs to or it cannot confirm it.
    var stripe = connectedAccount
        ? Stripe(publishableKey, { stripeAccount: connectedAccount })
        : Stripe(publishableKey);

    var elements = stripe.elements({
        clientSecret: clientSecret,
        appearance: {
            theme: 'stripe',
            variables: {
                colorPrimary: getComputedStyle(document.documentElement)
                    .getPropertyValue('--brand-600').trim() || '#0f4c81',
                colorDanger: '#e11d48',
                borderRadius: '8px',
                fontFamily: 'system-ui, sans-serif',
                fontSizeBase: '15px'
            }
        }
    });

    var paymentElement = elements.create('payment', { layout: 'tabs' });
    paymentElement.mount('#payment-element');

    var form = root.querySelector('form');
    var submitButton = document.getElementById('payment-submit');
    var buttonLabel = document.getElementById('payment-submit-label');
    var spinner = document.getElementById('payment-spinner');
    var errorBox = document.getElementById('payment-error');
    var errorText = document.getElementById('payment-error-text');

    // Guards a double submit at the browser as well: the server is idempotent,
    // but a resident should not see two spinners and wonder if they paid twice.
    var submitting = false;

    function setBusy(busy) {
        submitting = busy;
        submitButton.disabled = busy;
        if (spinner) {
            spinner.classList.toggle('hidden', !busy);
        }
        if (buttonLabel) {
            buttonLabel.textContent = busy ? 'Processing…' : buttonLabel.dataset.idleLabel;
        }
    }

    function showError(message) {
        if (!errorBox || !errorText) {
            return;
        }
        errorText.textContent = message;
        errorBox.classList.remove('hidden');
        errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function clearError() {
        if (errorBox) {
            errorBox.classList.add('hidden');
        }
    }

    if (buttonLabel) {
        buttonLabel.dataset.idleLabel = buttonLabel.textContent.trim();
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (submitting) {
            return;
        }

        clearError();
        setBusy(true);

        stripe.confirmPayment({
            elements: elements,
            confirmParams: {
                return_url: returnUrl
            }
        }).then(function (result) {
            // On success Stripe redirects and this never runs. Reaching here
            // means the payment did not go through, or needs another attempt.
            if (result.error) {
                var message = (result.error.type === 'card_error' || result.error.type === 'validation_error')
                    ? result.error.message
                    : 'Something went wrong while processing your payment. Your card has not been charged. Please try again.';
                showError(message);
                setBusy(false);
            }
        }).catch(function () {
            showError('We could not reach the card processor. Your card has not been charged. Please try again.');
            setBusy(false);
        });
    });

    // Surface field-level problems as the resident types, rather than only
    // after they press Pay.
    paymentElement.on('change', function (event) {
        if (event.error) {
            showError(event.error.message);
        } else {
            clearError();
        }
    });
})();
