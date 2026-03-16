/**
 * BikeSwap – Device fingerprint collector
 * Collects basic device info and sends it to the server.
 * Only runs if user has given full cookie consent (cookie_consent=all).
 * Sends once per session (tracked via sessionStorage).
 */
(function() {
    'use strict';

    // Only collect if full consent given
    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    if (getCookie('cookie_consent') !== 'all') {
        return;
    }

    // Only send once per session
    if (sessionStorage.getItem('bs_fp_sent')) {
        return;
    }

    var data = {
        screenResolution: screen.width + 'x' + screen.height,
        colorDepth: screen.colorDepth || 0,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || '',
        language: navigator.language || '',
        platform: navigator.platform || '',
        touchSupport: ('ontouchstart' in window) || (navigator.maxTouchPoints > 0),
        doNotTrack: navigator.doNotTrack === '1' || window.doNotTrack === '1'
    };

    fetch('/api/device-fingerprint', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    }).then(function(r) {
        if (r.ok) {
            sessionStorage.setItem('bs_fp_sent', '1');
        }
    }).catch(function() {});
})();
