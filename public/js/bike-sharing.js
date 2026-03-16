/**
 * Bike sharing toggle & availability configuration.
 * Used on bike/create and bike/edit pages.
 *
 * Expected DOM elements:
 *   #is_shared          - sharing checkbox
 *   #sharing-options    - container shown when shared
 *   #auto_accept        - auto-accept checkbox
 *   #availability-options - container shown when auto-accept
 *   #add-exclude-btn    - button to add excluded date
 *   #exclude-date-input - date input for exclusions
 *   #excluded-dates-list - container for excluded date tags
 */
(function () {
    'use strict';

    var isShared = document.getElementById('is_shared');
    var sharingOpts = document.getElementById('sharing-options');
    var autoAccept = document.getElementById('auto_accept');
    var availOpts = document.getElementById('availability-options');
    var addBtn = document.getElementById('add-exclude-btn');
    var dateInput = document.getElementById('exclude-date-input');
    var datesList = document.getElementById('excluded-dates-list');

    if (!isShared || !sharingOpts) return;

    function toggleSharing() {
        sharingOpts.style.display = isShared.checked ? '' : 'none';
        if (!isShared.checked) autoAccept.checked = false;
        toggleAutoAccept();
    }

    function toggleAutoAccept() {
        availOpts.style.display = autoAccept.checked ? '' : 'none';
    }

    isShared.addEventListener('change', toggleSharing);
    autoAccept.addEventListener('change', toggleAutoAccept);
    toggleSharing();

    // Format ISO date for display: "3. 4. 2026"
    function fmtDate(iso) {
        var p = iso.split('-');
        return parseInt(p[2]) + '. ' + parseInt(p[1]) + '. ' + p[0];
    }

    function createTag(val) {
        var span = document.createElement('span');
        span.className = 'excluded-tag';
        span.setAttribute('data-date', val);

        var icon = document.createElement('i');
        icon.setAttribute('data-lucide', 'calendar-x2');
        icon.className = 'excluded-tag-icon';
        span.appendChild(icon);

        span.appendChild(document.createTextNode(' ' + fmtDate(val) + ' '));

        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'excluded-tag-remove';
        removeBtn.title = 'Odebrat';
        removeBtn.textContent = '\u00d7';
        removeBtn.addEventListener('click', function () { span.remove(); });
        span.appendChild(removeBtn);

        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'excluded_dates[]';
        hidden.value = val;
        span.appendChild(hidden);

        datesList.appendChild(span);

        if (window.lucide) lucide.createIcons();
    }

    addBtn.addEventListener('click', function () {
        var val = dateInput.value;
        if (!val) return;
        if (datesList.querySelector('[data-date="' + val + '"]')) return;
        createTag(val);
        dateInput.value = '';
    });

    // Bind remove buttons on existing server-rendered tags (edit page)
    datesList.querySelectorAll('.excluded-tag-remove').forEach(function (btn) {
        btn.addEventListener('click', function () { btn.parentElement.remove(); });
    });
})();
