// Initialize custom time picker for Shift Master (Start/End time)
function initShiftTimePicker() {
    const modalEl = document.getElementById('shiftTimePickerModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;

    const modal = new bootstrap.Modal(modalEl);
    const displayEl = document.getElementById('shiftTpDisplay');
    const hourEl = document.getElementById('shiftTpHour');
    const minuteEl = document.getElementById('shiftTpMinute');
    const amBtn = document.getElementById('shiftTpAm');
    const pmBtn = document.getElementById('shiftTpPm');
    const applyBtn = document.getElementById('shiftTpApply');
    const cancelBtn = document.getElementById('shiftTpCancel');

    if (!displayEl || !hourEl || !minuteEl || !amBtn || !pmBtn || !applyBtn) return;

    let currentTargetInput = null;

    function parseToMinutes(value) {
        if (!value) return null;
        const match = value.trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
        if (!match) return null;
        let h = parseInt(match[1], 10);
        const m = parseInt(match[2], 10);
        const period = match[3].toUpperCase();
        if (isNaN(h) || isNaN(m)) return null;
        if (h === 12) h = 0;
        if (period === 'PM') h += 12;
        return h * 60 + m;
    }

    function formatFromMinutes(totalMinutes) {
        totalMinutes = ((totalMinutes % (24 * 60)) + (24 * 60)) % (24 * 60);
        let h24 = Math.floor(totalMinutes / 60);
        const m = totalMinutes % 60;
        let period = 'AM';
        if (h24 >= 12) {
            period = 'PM';
            if (h24 > 12) h24 -= 12;
        }
        if (h24 === 0) h24 = 12;
        const hStr = h24.toString().padStart(2, '0');
        const mStr = m.toString().padStart(2, '0');
        return `${hStr}:${mStr} ${period}`;
    }

    function updateDisplay() {
        let h = parseInt(hourEl.value || '0', 10);
        let m = parseInt(minuteEl.value || '0', 10);
        if (isNaN(h) || h < 1) h = 1;
        if (h > 12) h = 12;
        if (isNaN(m) || m < 0) m = 0;
        if (m > 59) m = 59;
        hourEl.value = h;
        minuteEl.value = m;
        const period = amBtn.classList.contains('active') ? 'AM' : 'PM';
        const hStr = h.toString().padStart(2, '0');
        const mStr = m.toString().padStart(2, '0');
        displayEl.textContent = `${hStr}:${mStr} ${period}`;
    }

    function parseExisting(value) {
        if (!value) return;
        const match = value.trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
        if (!match) return;
        const h = parseInt(match[1], 10);
        const m = parseInt(match[2], 10);
        const period = match[3].toUpperCase();
        if (!isNaN(h)) hourEl.value = h;
        if (!isNaN(m)) minuteEl.value = m;
        if (period === 'PM') {
            pmBtn.classList.add('active');
            amBtn.classList.remove('active');
        } else {
            amBtn.classList.add('active');
            pmBtn.classList.remove('active');
        }
        updateDisplay();
    }

    function openPickerForInput(input) {
        currentTargetInput = input;
        if (!currentTargetInput) return;

        hourEl.value = 9;
        minuteEl.value = 0;
        amBtn.classList.add('active');
        pmBtn.classList.remove('active');

        parseExisting(currentTargetInput.value);
        updateDisplay();
        modal.show();
    }

    // Bind to time picker buttons/inputs (both in loaded content and Mark Attendance modal)
    document.querySelectorAll('#contentArea .time-picker-btn, #markAttendanceModal .time-picker-btn').forEach(btn => {
        if (btn.dataset.tpBound === '1') return;
        btn.dataset.tpBound = '1';
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target-input');
            const input = document.getElementById(targetId);
            openPickerForInput(input);
        });
    });

    document.querySelectorAll('#contentArea .time-input, #markAttendanceModal .time-input').forEach(input => {
        if (input.dataset.tpBound === '1') return;
        input.dataset.tpBound = '1';
        input.addEventListener('click', function () {
            openPickerForInput(this);
        });
    });

    hourEl.addEventListener('input', updateDisplay);
    minuteEl.addEventListener('input', updateDisplay);
    amBtn.addEventListener('click', function () {
        amBtn.classList.add('active');
        pmBtn.classList.remove('active');
        updateDisplay();
    });
    pmBtn.addEventListener('click', function () {
        pmBtn.classList.add('active');
        amBtn.classList.remove('active');
        updateDisplay();
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            currentTargetInput = null;
            modal.hide();
        });
    }

    applyBtn.addEventListener('click', function () {
        if (!currentTargetInput) return;
        updateDisplay();
        currentTargetInput.value = displayEl.textContent;

        // Auto-calculate Half Time when Start or End time is set
        if (currentTargetInput.id === 'start_time' || currentTargetInput.id === 'end_time') {
            const startInput = document.getElementById('start_time');
            const endInput = document.getElementById('end_time');
            const halfInput = document.getElementById('half_day_time');
            if (startInput && endInput && halfInput) {
                const sMin = parseToMinutes(startInput.value);
                const eMinRaw = parseToMinutes(endInput.value);
                if (sMin !== null && eMinRaw !== null) {
                    let eMin = eMinRaw;
                    if (eMin <= sMin) {
                        // Overnight shift: end is next day
                        eMin += 24 * 60;
                    }
                    const halfMin = Math.round(sMin + (eMin - sMin) / 2);
                    halfInput.value = formatFromMinutes(halfMin);
                }
            }
        }
        modal.hide();
    });
}

// expose
window.initShiftTimePicker = initShiftTimePicker;
