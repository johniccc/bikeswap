/**
 * Year range slider for stolen bike filter form.
 *
 * Syncs dual range inputs with number inputs and updates the
 * visual fill bar between the two slider thumbs.
 */
(function () {
    'use strict';

    var form      = document.getElementById('stolen-filter-form');
    var toggleBtn = document.getElementById('stolen-filter-toggle');
    var fromRange = document.getElementById('stolen-year-from-range');
    var toRange   = document.getElementById('stolen-year-to-range');
    var fromNum   = document.getElementById('stolen-year-from-num');
    var toNum     = document.getElementById('stolen-year-to-num');
    var fill      = document.getElementById('stolen-year-slider-fill');

    if (!form || !toggleBtn) return;

    toggleBtn.addEventListener('click', function () {
        form.classList.toggle('filters-open');
        toggleBtn.setAttribute('aria-expanded', form.classList.contains('filters-open') ? 'true' : 'false');
    });

    if (!fromRange || !toRange) return;

    var sliderMin = parseInt(fromRange.min, 10);
    var sliderMax = parseInt(fromRange.max, 10);

    function clamp(v, lo, hi) { return Math.max(lo, Math.min(v, hi)); }

    function updateFill() {
        if (!fill) return;
        var lo  = parseInt(fromRange.value, 10);
        var hi  = parseInt(toRange.value, 10);
        var rng = sliderMax - sliderMin;
        var pLo = rng > 0 ? ((lo - sliderMin) / rng) * 100 : 0;
        var pHi = rng > 0 ? ((hi - sliderMin) / rng) * 100 : 100;
        fill.style.left  = pLo + '%';
        fill.style.width = Math.max(0, pHi - pLo) + '%';
    }

    function raiseThumb(active, other) {
        active.classList.add('thumb-top');
        other.classList.remove('thumb-top');
    }

    fromRange.classList.add('thumb-top');

    fromRange.addEventListener('mousedown',  function () { raiseThumb(fromRange, toRange); });
    fromRange.addEventListener('touchstart', function () { raiseThumb(fromRange, toRange); }, { passive: true });
    toRange.addEventListener('mousedown',    function () { raiseThumb(toRange, fromRange); });
    toRange.addEventListener('touchstart',   function () { raiseThumb(toRange, fromRange); }, { passive: true });

    fromRange.addEventListener('input', function () {
        var lo = parseInt(fromRange.value, 10);
        var hi = parseInt(toRange.value, 10);
        if (lo > hi) { lo = hi; fromRange.value = lo; }
        fromNum.value = lo;
        updateFill();
    });

    toRange.addEventListener('input', function () {
        var lo = parseInt(fromRange.value, 10);
        var hi = parseInt(toRange.value, 10);
        if (hi < lo) { hi = lo; toRange.value = hi; }
        toNum.value = hi;
        updateFill();
    });

    fromNum.addEventListener('input', function () {
        var lo = parseInt(fromNum.value, 10);
        if (!isNaN(lo)) {
            lo = clamp(lo, sliderMin, sliderMax);
            if (lo > parseInt(toRange.value, 10)) { toRange.value = lo; toNum.value = lo; }
            fromRange.value = lo;
            updateFill();
        }
    });

    toNum.addEventListener('input', function () {
        var hi = parseInt(toNum.value, 10);
        if (!isNaN(hi)) {
            hi = clamp(hi, sliderMin, sliderMax);
            if (hi < parseInt(fromRange.value, 10)) { fromRange.value = hi; fromNum.value = hi; }
            toRange.value = hi;
            updateFill();
        }
    });

    form.addEventListener('submit', function () {
        var lo = parseInt(fromNum.value, 10);
        var hi = parseInt(toNum.value, 10);
        if (isNaN(lo) || lo <= sliderMin) fromNum.value = '';
        if (isNaN(hi) || hi >= sliderMax) toNum.value = '';
    });

    updateFill();
}());
