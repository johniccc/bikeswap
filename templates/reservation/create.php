<h1>Rezervovat kolo</h1>

<?php if (isset($session) && $session->hasFlash('error')): ?>
    <div class="alert alert-error"><?= e($session->getFlash('error')) ?></div>
<?php endif; ?>

<!-- Bike preview card -->
<div class="card" style="margin-bottom: 2rem;">
    <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
        <?php $photo = $bike->getPrimaryPhoto(); ?>
        <?php if ($photo): ?>
            <img src="<?= e($photo->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>"
                 style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px;">
        <?php endif; ?>
        <div>
            <h2 style="margin: 0;"><?= e($bike->getFullName()) ?></h2>
            <p class="text-muted" style="margin: 0.25rem 0;">Barva: <?= e($bike->getColor()) ?></p>
            <a href="/bike/<?= e($bike->getQrHash()) ?>">Zobrazit detail kola</a>
        </div>
    </div>
</div>

<!-- Calendar legend -->
<div class="card" style="margin-bottom: 1.5rem;">
    <h2>Vyberte termín výpůjčky</h2>
    <div class="calendar-legend">
        <span><span class="legend-dot legend-available"></span> Volné</span>
        <span><span class="legend-dot legend-unavailable"></span> Obsazené</span>
        <span><span class="legend-dot legend-selected"></span> Váš výběr</span>
    </div>

    <!-- Calendar widget -->
    <div id="calendar-container"></div>
</div>

<!-- Reservation form -->
<form method="POST" action="/reservation/new/<?= $bike->getId() ?>" id="reservation-form">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="date_from" id="input-date-from" value="">
    <input type="hidden" name="date_to" id="input-date-to" value="">

    <div class="card" style="margin-bottom: 1.5rem;">
        <h3>Vybraný termín</h3>
        <p id="selected-range-text" class="text-muted">Klikněte na kalendář pro výběr datumů.</p>

        <div class="form-group">
            <label for="message">Zpráva pro majitele (volitelné)</label>
            <textarea id="message" name="message" rows="3"
                      placeholder="Představte se, sdělte účel výpůjčky…"></textarea>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary" id="submit-btn" disabled>Odeslat žádost o výpůjčku</button>
        <a href="/shared" class="btn">Zpět na seznam</a>
    </div>
</form>

<style>
    .calendar-legend {
        display: flex; gap: 1.5rem; margin-bottom: 1rem; font-size: 0.9rem;
    }
    .legend-dot {
        display: inline-block; width: 14px; height: 14px; border-radius: 3px;
        vertical-align: middle; margin-right: 4px;
    }
    .legend-available { background: #e8f5e9; border: 1px solid #a5d6a7; }
    .legend-unavailable { background: #ffebee; border: 1px solid #ef9a9a; }
    .legend-selected { background: var(--color-primary, #2563eb); border: 1px solid var(--color-primary, #2563eb); }

    .calendar { user-select: none; }
    .calendar-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 0.75rem;
    }
    .calendar-header button {
        background: none; border: 1px solid var(--border-color, #ddd);
        padding: 0.3rem 0.8rem; border-radius: 4px; cursor: pointer; font-size: 1rem;
    }
    .calendar-header button:hover { background: var(--bg-secondary, #f5f5f5); }
    .calendar-grid {
        display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px;
        text-align: center;
    }
    .calendar-grid .day-header {
        font-weight: 600; font-size: 0.8rem; padding: 0.4rem;
        color: var(--text-muted, #666);
    }
    .calendar-grid .day {
        padding: 0.5rem 0.25rem; border-radius: 4px; cursor: pointer;
        font-size: 0.9rem; transition: background 0.15s;
    }
    .calendar-grid .day:hover:not(.day-unavailable):not(.day-empty):not(.day-past) {
        background: #e3f2fd;
    }
    .day-empty { cursor: default; }
    .day-past { color: #ccc; cursor: not-allowed; }
    .day-unavailable { background: #ffebee; color: #c62828; cursor: not-allowed; text-decoration: line-through; }
    .day-available { background: #e8f5e9; }
    .day-selected { background: var(--color-primary, #2563eb) !important; color: #fff !important; }
    .day-in-range { background: #bbdefb !important; color: #1565c0 !important; }
    .day-today { font-weight: 700; box-shadow: inset 0 0 0 1px var(--color-primary, #2563eb); }

    .calendars-row {
        display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;
    }
    @media (max-width: 640px) {
        .calendars-row { grid-template-columns: 1fr; }
    }
</style>

<script>
(function() {
    'use strict';

    // Unavailable date ranges from PHP (approved + active reservations)
    var unavailableRanges = <?= json_encode($unavailableDates) ?>;

    // Parse ranges into date sets for fast lookup
    var unavailableDates = new Set();
    unavailableRanges.forEach(function(range) {
        var d = new Date(range.date_from);
        var end = new Date(range.date_to);
        while (d <= end) {
            unavailableDates.add(d.toISOString().split('T')[0]);
            d.setDate(d.getDate() + 1);
        }
    });

    var today = new Date();
    today.setHours(0, 0, 0, 0);

    var selectedFrom = null;
    var selectedTo = null;

    // Current month offsets for the two calendars
    var baseMonth = new Date(today.getFullYear(), today.getMonth(), 1);

    var container = document.getElementById('calendar-container');
    var inputFrom = document.getElementById('input-date-from');
    var inputTo = document.getElementById('input-date-to');
    var rangeText = document.getElementById('selected-range-text');
    var submitBtn = document.getElementById('submit-btn');

    function formatDate(d) {
        return d.getFullYear() + '-' +
            String(d.getMonth() + 1).padStart(2, '0') + '-' +
            String(d.getDate()).padStart(2, '0');
    }

    function formatCzech(d) {
        return d.getDate() + '. ' + (d.getMonth() + 1) + '. ' + d.getFullYear();
    }

    function isUnavailable(dateStr) {
        return unavailableDates.has(dateStr);
    }

    function isPast(d) {
        return d < today;
    }

    /**
     * Check if any date in range [from, to] is unavailable.
     */
    function rangeHasConflict(from, to) {
        var d = new Date(from);
        while (d <= to) {
            if (isUnavailable(formatDate(d))) return true;
            d.setDate(d.getDate() + 1);
        }
        return false;
    }

    function isInRange(d) {
        if (!selectedFrom || !selectedTo) return false;
        return d >= selectedFrom && d <= selectedTo;
    }

    function isSelected(d) {
        if (selectedFrom && formatDate(d) === formatDate(selectedFrom)) return true;
        if (selectedTo && formatDate(d) === formatDate(selectedTo)) return true;
        return false;
    }

    function handleDayClick(dateStr) {
        var d = new Date(dateStr + 'T00:00:00');

        if (isPast(d) || isUnavailable(dateStr)) return;

        if (!selectedFrom || (selectedFrom && selectedTo)) {
            // Start new selection
            selectedFrom = d;
            selectedTo = null;
        } else {
            // Complete selection
            if (d < selectedFrom) {
                selectedTo = selectedFrom;
                selectedFrom = d;
            } else {
                selectedTo = d;
            }

            // Check max 30 days
            var diffDays = Math.ceil((selectedTo - selectedFrom) / (1000 * 60 * 60 * 24)) + 1;
            if (diffDays > 30) {
                alert('Maximální délka výpůjčky je 30 dní.');
                selectedTo = null;
                render();
                return;
            }

            // Check for conflicts in range
            if (rangeHasConflict(selectedFrom, selectedTo)) {
                alert('Vybraný rozsah obsahuje obsazené dny. Vyberte jiný termín.');
                selectedFrom = null;
                selectedTo = null;
                render();
                return;
            }
        }

        updateFormInputs();
        render();
    }

    function updateFormInputs() {
        if (selectedFrom && selectedTo) {
            inputFrom.value = formatDate(selectedFrom);
            inputTo.value = formatDate(selectedTo);
            var days = Math.ceil((selectedTo - selectedFrom) / (1000 * 60 * 60 * 24)) + 1;
            rangeText.innerHTML = '<strong>' + formatCzech(selectedFrom) + '</strong> – <strong>' +
                formatCzech(selectedTo) + '</strong> (' + days + ' ' + (days === 1 ? 'den' : days < 5 ? 'dny' : 'dní') + ')';
            submitBtn.disabled = false;
        } else if (selectedFrom) {
            inputFrom.value = formatDate(selectedFrom);
            inputTo.value = '';
            rangeText.textContent = 'Vyberte koncové datum.';
            submitBtn.disabled = true;
        } else {
            inputFrom.value = '';
            inputTo.value = '';
            rangeText.textContent = 'Klikněte na kalendář pro výběr datumů.';
            submitBtn.disabled = true;
        }
    }

    function renderMonth(year, month) {
        var firstDay = new Date(year, month, 1);
        var lastDay = new Date(year, month + 1, 0);
        var startDow = (firstDay.getDay() + 6) % 7; // Monday = 0

        var monthNames = ['Leden','Únor','Březen','Duben','Květen','Červen',
                          'Červenec','Srpen','Září','Říjen','Listopad','Prosinec'];

        var html = '<div class="calendar">';
        html += '<div style="text-align:center; font-weight:600; margin-bottom:0.5rem;">' +
                monthNames[month] + ' ' + year + '</div>';
        html += '<div class="calendar-grid">';

        var dayNames = ['Po','Út','St','Čt','Pá','So','Ne'];
        dayNames.forEach(function(n) {
            html += '<div class="day-header">' + n + '</div>';
        });

        // Empty cells before first day
        for (var i = 0; i < startDow; i++) {
            html += '<div class="day day-empty"></div>';
        }

        // Day cells
        for (var d = 1; d <= lastDay.getDate(); d++) {
            var dateObj = new Date(year, month, d);
            var dateStr = formatDate(dateObj);
            var classes = ['day'];

            if (isPast(dateObj)) {
                classes.push('day-past');
            } else if (isUnavailable(dateStr)) {
                classes.push('day-unavailable');
            } else {
                classes.push('day-available');
            }

            if (isSelected(dateObj)) {
                classes.push('day-selected');
            } else if (isInRange(dateObj)) {
                classes.push('day-in-range');
            }

            if (formatDate(dateObj) === formatDate(today)) {
                classes.push('day-today');
            }

            html += '<div class="' + classes.join(' ') + '" data-date="' + dateStr + '">' + d + '</div>';
        }

        html += '</div></div>';
        return html;
    }

    function render() {
        var m1 = new Date(baseMonth);
        var m2 = new Date(baseMonth.getFullYear(), baseMonth.getMonth() + 1, 1);

        var html = '<div class="calendar-header">';
        html += '<button type="button" id="cal-prev">&larr; Předchozí</button>';
        html += '<button type="button" id="cal-next">Další &rarr;</button>';
        html += '</div>';
        html += '<div class="calendars-row">';
        html += renderMonth(m1.getFullYear(), m1.getMonth());
        html += renderMonth(m2.getFullYear(), m2.getMonth());
        html += '</div>';

        container.innerHTML = html;

        // Bind navigation
        document.getElementById('cal-prev').addEventListener('click', function() {
            baseMonth.setMonth(baseMonth.getMonth() - 1);
            // Don't go before current month
            var minMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            if (baseMonth < minMonth) baseMonth = minMonth;
            render();
        });

        document.getElementById('cal-next').addEventListener('click', function() {
            baseMonth.setMonth(baseMonth.getMonth() + 1);
            render();
        });

        // Bind day clicks
        container.querySelectorAll('.day[data-date]').forEach(function(el) {
            el.addEventListener('click', function() {
                handleDayClick(this.getAttribute('data-date'));
            });
        });
    }

    // Prevent form submission without dates
    document.getElementById('reservation-form').addEventListener('submit', function(e) {
        if (!inputFrom.value || !inputTo.value) {
            e.preventDefault();
            alert('Nejprve vyberte termín výpůjčky.');
        }
    });

    render();
})();
</script>