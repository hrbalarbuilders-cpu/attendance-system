document.addEventListener('DOMContentLoaded', function () {
    // 🔹 Department change -> Employee dropdown populate
    const deptSelect = document.getElementById('departmentSelect');
    const empSelect = document.getElementById('employeeSelect');

    if (deptSelect && empSelect) {
        deptSelect.addEventListener('change', function () {
            const deptId = this.value;
            empSelect.innerHTML = '<option value="">Select Employee</option>';

            if (!deptId) return;

            if (typeof ALL_EMPLOYEES !== 'undefined') {
                ALL_EMPLOYEES
                    .filter(e => String(e.department_id) === String(deptId))
                    .forEach(e => {
                        const opt = document.createElement('option');
                        opt.value = e.id;
                        opt.textContent = e.name;
                        empSelect.appendChild(opt);
                    });
            }
        });
    }

    // Mark Attendance By: Date / Multiple / Month
    const markByRadios = document.querySelectorAll('input[name="mark_by"]');
    const singleDateWrapper = document.getElementById('singleDateWrapper');
    const multiDatesWrapper = document.getElementById('multiDatesWrapper');
    const attendanceDateInput = document.getElementById('attendanceDate');
    const multiDatesList = document.getElementById('multiDatesList');
    const addDateRowBtn = document.getElementById('addDateRow');

    function createDateRow() {
        const row = document.createElement('div');
        row.className = 'd-flex align-items-center gap-2 date-row';
        row.innerHTML = `
<input type="date" class="form-control" name="dates[]">
<button type="button" class="btn btn-outline-danger btn-sm remove-date-row">&times;</button>
`;
        return row;
    }

    function updateMarkByUI(mode) {
        if (!singleDateWrapper || !multiDatesWrapper || !attendanceDateInput) return;

        if (mode === 'multiple') {
            singleDateWrapper.classList.add('d-none');
            multiDatesWrapper.classList.remove('d-none');
            attendanceDateInput.required = false;

            // Ensure at least one row exists and mark them required
            if (multiDatesList && multiDatesList.children.length === 0) {
                multiDatesList.appendChild(createDateRow());
            }
            if (multiDatesList) {
                multiDatesList.querySelectorAll('input[type="date"]').forEach(inp => {
                    inp.required = true;
                });
            }
        } else {
            // 'date' or 'month' -> use single date field
            singleDateWrapper.classList.remove('d-none');
            multiDatesWrapper.classList.add('d-none');
            attendanceDateInput.required = true;
            if (multiDatesList) {
                multiDatesList.querySelectorAll('input[type="date"]').forEach(inp => {
                    inp.required = false;
                });
            }
        }
    }

    // expose for external use (e.g. from attendance_details.js or clicked cells)
    window.updateMarkByUI = updateMarkByUI;

    if (markByRadios && markByRadios.length) {
        markByRadios.forEach(r => {
            r.addEventListener('change', function () {
                updateMarkByUI(this.value);
            });
        });
        // Initialize based on default checked radio
        const checked = Array.from(markByRadios).find(r => r.checked);
        if (checked) updateMarkByUI(checked.value);
    }

    if (addDateRowBtn && multiDatesList) {
        addDateRowBtn.addEventListener('click', function () {
            multiDatesList.appendChild(createDateRow());
        });

        multiDatesList.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-date-row')) {
                const row = e.target.closest('.date-row');
                if (row && multiDatesList.children.length > 1) {
                    row.remove();
                }
            }
        });
    }

    // Save attendance button -> REAL AJAX SAVE
    const saveBtn = document.getElementById('saveAttendanceBtn');
    const markForm = document.getElementById('markAttendanceForm');

    if (saveBtn && markForm) {
        saveBtn.addEventListener('click', function () {
            // HTML5 validation
            if (!markForm.reportValidity()) return;

            const formData = new FormData(markForm);

            showLoader();

            fetch('save_admin_attendance.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // modal close
                        const modalEl = document.getElementById('markAttendanceModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        modal.hide();

                        showStatus(data.message || 'Attendance saved successfully.', 'success');

                        // OPTIONAL: refresh tab if needed
                    } else {
                        showStatus(data.message || 'Failed to save attendance.', 'danger');
                    }
                })
                .catch(() => {
                    showStatus('Error while saving attendance. Please try again.', 'danger');
                })
                .finally(() => {
                    hideLoader();
                });
        });
    }

    // 🔹 EXPOSE GLOBAL FUNCTION TO OPEN AND PREFILL
    window.showMarkAttendanceModal = function (empId, date) {
        const modalEl = document.getElementById('markAttendanceModal');
        if (!modalEl) return;

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        // Reset form first
        markForm.reset();

        // If empId is provided, we need to find their department first
        if (empId && typeof ALL_EMPLOYEES !== 'undefined') {
            const emp = ALL_EMPLOYEES.find(e => String(e.id) === String(empId));
            if (emp) {
                deptSelect.value = emp.department_id;
                // Trigger change to fill employee list
                deptSelect.dispatchEvent(new Event('change'));
                empSelect.value = emp.id;
            }
        }

        if (date) {
            attendanceDateInput.value = date;
            updateMarkByUI('date');
            document.getElementById('markByDate').checked = true;
        }

        modal.show();
    };
});
