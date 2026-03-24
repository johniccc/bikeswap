/**
 * Calendar Widget for reservation date selection.
 *
 * Expects window.calendarConfig to be set before this script loads:
 *   { unavailableDates: [...], availabilityDays: [...], excludedDates: [...] }
 */
(function() {
    'use strict';

    var config = window.calendarConfig || {};
    var unavailableRanges = config.unavailableDates || [];
    var unavailableSet = new Set();
    unavailableRanges.forEach(function(range) {
        var d = new Date(range.date_from);
        var end = new Date(range.date_to);
        while (d <= end) {
            unavailableSet.add(d.toISOString().split('T')[0]);
            d.setDate(d.getDate() + 1);
        }
    });

    var availabilityDays = new Set(config.availabilityDays || []);
    var excludedDates = new Set(config.excludedDates || []);

    var today = new Date(); today.setHours(0,0,0,0);
    var selectedFrom = null, selectedTo = null;
    var baseMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    var container = document.getElementById('calendar-container');
    var inputFrom = document.getElementById('input-date-from');
    var inputTo = document.getElementById('input-date-to');
    var rangeText = document.getElementById('selected-range-text');
    var submitBtn = document.getElementById('submit-btn');

    function fmt(d) {
        return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
    }
    function fmtCz(d) { return d.getDate()+'. '+(d.getMonth()+1)+'. '+d.getFullYear(); }
    function isUnavail(s) { return unavailableSet.has(s); }
    function isNotAvailable(dateStr, dateObj) {
        // JS getDay: 0=Sun..6=Sat → convert to ISO: 1=Mon..7=Sun
        var dow = dateObj.getDay() || 7;
        if (!availabilityDays.has(dow)) return true;
        if (excludedDates.has(dateStr)) return true;
        return false;
    }
    function isPast(d) { return d < today; }
    function inRange(d) { return selectedFrom && selectedTo && d >= selectedFrom && d <= selectedTo; }
    function isSel(d) {
        return (selectedFrom && fmt(d)===fmt(selectedFrom)) || (selectedTo && fmt(d)===fmt(selectedTo));
    }
    function rangeConflict(a,b) {
        var d=new Date(a); while(d<=b){var s=fmt(d);if(isUnavail(s)||isNotAvailable(s,d))return true;d.setDate(d.getDate()+1);} return false;
    }

    function handleClick(dateStr) {
        var d = new Date(dateStr+'T00:00:00');
        if (isPast(d)||isUnavail(dateStr)||isNotAvailable(dateStr,d)) return;
        if (!selectedFrom||(selectedFrom&&selectedTo)) { selectedFrom=d; selectedTo=null; }
        else {
            if (d<selectedFrom){selectedTo=selectedFrom;selectedFrom=d;} else {selectedTo=d;}
            var diff=Math.ceil((selectedTo-selectedFrom)/(864e5))+1;
            if(diff>30){alert('Max 30 dní.');selectedTo=null;render();return;}
            if(rangeConflict(selectedFrom,selectedTo)){alert('Rozsah obsahuje obsazené dny.');selectedFrom=null;selectedTo=null;render();return;}
        }
        updateInputs(); render();
    }

    function updateInputs() {
        if (selectedFrom&&selectedTo) {
            inputFrom.value=fmt(selectedFrom); inputTo.value=fmt(selectedTo);
            var days=Math.ceil((selectedTo-selectedFrom)/864e5)+1;
            rangeText.textContent=fmtCz(selectedFrom)+' – '+fmtCz(selectedTo)+' ('+days+' '+(days===1?'den':days<5?'dny':'dní')+')';
            submitBtn.disabled=false;
        } else if (selectedFrom) {
            inputFrom.value=fmt(selectedFrom); inputTo.value='';
            rangeText.textContent='Vyberte koncové datum.'; submitBtn.disabled=true;
        } else {
            inputFrom.value=''; inputTo.value='';
            rangeText.textContent='Klikněte na kalendář pro výběr datumu.'; submitBtn.disabled=true;
        }
    }

    var monthNames=['Leden','Únor','Březen','Duben','Květen','Červen','Červenec','Srpen','Září','Říjen','Listopad','Prosinec'];
    var dayNames=['Po','Út','St','Čt','Pá','So','Ne'];

    function buildMonth(year, month) {
        var first=new Date(year,month,1), last=new Date(year,month+1,0), start=(first.getDay()+6)%7;
        var cal=document.createElement('div'); cal.className='calendar';
        var hdr=document.createElement('div'); hdr.className='calendar-header';
        var h4=document.createElement('h4'); h4.textContent=monthNames[month]+' '+year;
        hdr.appendChild(h4); cal.appendChild(hdr);
        var wk=document.createElement('div'); wk.className='calendar-weekdays';
        dayNames.forEach(function(n){var dv=document.createElement('div');dv.className='calendar-weekday';dv.textContent=n;wk.appendChild(dv);});
        cal.appendChild(wk);
        var grid=document.createElement('div'); grid.className='calendar-grid';
        for(var i=0;i<start;i++){var e=document.createElement('div');e.className='day day-empty';grid.appendChild(e);}
        for(var d=1;d<=last.getDate();d++){
            var dateObj=new Date(year,month,d), dateStr=fmt(dateObj);
            var cell=document.createElement('div'); cell.className='day'; cell.textContent=String(d);
            cell.setAttribute('data-date',dateStr);
            if(isPast(dateObj))cell.classList.add('day-disabled');
            else if(isUnavail(dateStr))cell.classList.add('day-unavailable');
            else if(isNotAvailable(dateStr,dateObj))cell.classList.add('day-disabled');
            if(isSel(dateObj))cell.classList.add('day-selected');
            else if(inRange(dateObj))cell.classList.add('day-range');
            if(fmt(dateObj)===fmt(today))cell.classList.add('day-today');
            cell.addEventListener('click',function(){handleClick(this.getAttribute('data-date'));});
            grid.appendChild(cell);
        }
        cal.appendChild(grid);
        return cal;
    }

    function render() {
        while(container.firstChild)container.removeChild(container.firstChild);
        var nav=document.createElement('div');nav.className='flex justify-between items-center mb-md';
        var prev=document.createElement('button');prev.type='button';prev.className='btn btn-ghost btn-sm';prev.textContent='Předchozí';
        prev.addEventListener('click',function(){baseMonth.setMonth(baseMonth.getMonth()-1);var mn=new Date(today.getFullYear(),today.getMonth(),1);if(baseMonth<mn)baseMonth=mn;render();});
        var next=document.createElement('button');next.type='button';next.className='btn btn-ghost btn-sm';next.textContent='Další';
        next.addEventListener('click',function(){baseMonth.setMonth(baseMonth.getMonth()+1);render();});
        nav.appendChild(prev);nav.appendChild(next);container.appendChild(nav);
        var row=document.createElement('div');row.style.cssText='display:grid;grid-template-columns:1fr 1fr;gap:1rem';
        var m1=new Date(baseMonth),m2=new Date(baseMonth.getFullYear(),baseMonth.getMonth()+1,1);
        row.appendChild(buildMonth(m1.getFullYear(),m1.getMonth()));
        row.appendChild(buildMonth(m2.getFullYear(),m2.getMonth()));
        container.appendChild(row);
    }

    document.getElementById('reservation-form').addEventListener('submit',function(e){
        if(!inputFrom.value||!inputTo.value){e.preventDefault();alert('Nejprve vyberte termín.');return;}
        if(!window._turnstileReady){e.preventDefault();alert('Počkejte na dokončení ověření proti botům.');}
    });

    render();
})();
