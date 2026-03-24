/**
 * Auth modal enhancements: Czech phone number formatting
 * and password requirement indicators.
 *
 * Modal open/close/tab switching is handled by app.js.
 */
(function () {
    'use strict';

    function formatCzechPhone(input) {
        var raw = input.value.replace(/[^\d+]/g, '');
        var prefix = '', digits = '';

        if (raw.startsWith('+420')) {
            prefix = '+420'; digits = raw.slice(4);
        } else if (raw.startsWith('00420')) {
            prefix = '00420'; digits = raw.slice(5);
        } else {
            // User is still typing the prefix — don't reformat yet
            return;
        }

        digits = digits.slice(0, 9);
        var formatted = prefix;
        if (digits.length > 0) formatted += ' ' + digits.slice(0, 3);
        if (digits.length > 3) formatted += ' ' + digits.slice(3, 6);
        if (digits.length > 6) formatted += ' ' + digits.slice(6, 9);
        input.value = formatted;
    }

    function stripSpacesBeforeSubmit(form) {
        var phone = form.querySelector('input[name="phone"]');
        if (phone) phone.value = phone.value.replace(/\s+/g, '');
    }

    document.addEventListener('DOMContentLoaded', function () {
        var regPhone = document.getElementById('reg-phone');
        var regForm  = document.getElementById('auth-form-register');

        if (regPhone) {
            regPhone.addEventListener('input', function () { formatCzechPhone(regPhone); });
        }
        if (regForm) {
            regForm.addEventListener('submit', function () { stripSpacesBeforeSubmit(regForm); });
        }
    });
}());
