/**
 * Client-side validation for every form marked with [data-validate].
 * (The server re-validates everything — this is only for quick feedback.)
 *
 * Field attributes understood:
 *   required        -> must not be blank
 *   data-label      -> friendly name used in messages
 *   data-min/max    -> trimmed length limits
 *   data-type=email -> basic email format
 *   data-strong     -> needs at least one letter and one number
 *   data-match=#id  -> must equal another field (confirm password)
 */
(function () {
    'use strict';

    var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function errorBox(input) {
        return document.getElementById('err-' + input.id);
    }

    function check(input) {
        var value = input.value;
        var trimmed = value.trim();
        var label = input.dataset.label || 'This field';
        var min = parseInt(input.dataset.min, 10);
        var max = parseInt(input.dataset.max, 10);

        if (input.hasAttribute('required') && trimmed === '') {
            return label + ' is required.';
        }
        if (trimmed === '') {
            return '';
        }
        if (min && trimmed.length < min) {
            return label + ' must be at least ' + min + ' character' + (min === 1 ? '' : 's') + '.';
        }
        if (max && value.length > max) {
            return label + ' must be at most ' + max + ' characters.';
        }
        if (input.dataset.type === 'email' && !EMAIL_RE.test(trimmed)) {
            return 'Enter a valid email address.';
        }
        if (input.hasAttribute('data-strong') && !(/[A-Za-z]/.test(value) && /\d/.test(value))) {
            return 'Password must contain at least one letter and one number.';
        }
        if (input.dataset.match) {
            var other = document.querySelector(input.dataset.match);
            if (other && other.value !== value) {
                return 'Passwords do not match.';
            }
        }
        return '';
    }

    function show(input, message) {
        var box = errorBox(input);
        if (box) { box.textContent = message; }
        input.classList.toggle('is-invalid', message !== '');
        input.setAttribute('aria-invalid', message !== '' ? 'true' : 'false');
    }

    function validateInput(input) {
        var message = check(input);
        show(input, message);
        return message === '';
    }

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        var fields = form.querySelectorAll('input[required], textarea[required], input[data-match]');

        fields.forEach(function (input) {
            input.addEventListener('blur', function () { validateInput(input); });
            input.addEventListener('input', function () {
                // Re-check live only after the field has already shown an error
                if (input.classList.contains('is-invalid')) { validateInput(input); }
            });
        });

        form.addEventListener('submit', function (event) {
            var firstInvalid = null;
            fields.forEach(function (input) {
                if (!validateInput(input) && !firstInvalid) { firstInvalid = input; }
            });
            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.focus();
            }
        });
    });

    // Confirmation prompt for delete buttons: <form data-confirm="Are you sure?">
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.dataset.confirm)) { event.preventDefault(); }
        });
    });
})();
