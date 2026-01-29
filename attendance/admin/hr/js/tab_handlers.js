// Employees list ke buttons/toggles ko init karne ka function
function initEmployeesListEvents() {
    // Dynamically inject and execute the search/filter script if present in loaded HTML (for AJAX)
    var scriptInHtml = document.querySelector('#employeeSearchScriptSource');
    if (scriptInHtml) {
        var old = document.getElementById('employeeSearchScript');
        if (old && old.parentNode) old.parentNode.removeChild(old);
        var newScript = document.createElement('script');
        newScript.id = 'employeeSearchScript';
        newScript.textContent = scriptInHtml.textContent;
        document.body.appendChild(newScript);
    }
    const toggles = document.querySelectorAll('.status-toggle');
    const deleteButtons = document.querySelectorAll('.delete-employee');
    const deleteModalEl = document.getElementById('deleteConfirmModal');

    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
    let deleteId = null;

    // STATUS TOGGLE AJAX
    toggles.forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            const checkbox = this;
            const employeeId = this.dataset.id;
            const newStatus = this.checked ? 1 : 0;

            checkbox.disabled = true;

            fetch('toggle_employee_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(employeeId) +
                    '&status=' + encodeURIComponent(newStatus)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showStatus(
                            data.message || (newStatus ? 'Account activated successfully.' : 'Account deactivated successfully.'),
                            'success'
                        );
                    } else {
                        showStatus(data.message || 'Status update failed. Please try again.', 'danger');
                        checkbox.checked = !checkbox.checked;
                    }
                })
                .catch(() => {
                    showStatus('Something went wrong. Please check your connection.', 'danger');
                    checkbox.checked = !checkbox.checked;
                })
                .finally(() => {
                    checkbox.disabled = false;
                });
        });
    });

    // DELETE BUTTON -> custom modal
    deleteButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (!deleteModal) return;
            deleteId = this.dataset.id;
            deleteModal.show();
        });
    });

    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function () {
            if (!deleteModal) return;
            if (!deleteId) return;
            window.location.href = 'delete_employee.php?id=' + encodeURIComponent(deleteId);
        });
    }

    // SEARCH: filter employees in the table as user types (migrated from employees_list.php)
    var scriptStatus = document.getElementById('scriptStatus');
    if (scriptStatus) {
        scriptStatus.style.display = 'block';
        scriptStatus.textContent = 'Employee search script is running.';
        scriptStatus.classList.remove('alert-danger');
        scriptStatus.classList.add('alert-info');
    }
    var testDiv = document.getElementById('testDiv');
    if (testDiv) {
        testDiv.textContent = 'TEST DIV: JS IS RUNNING!';
        testDiv.style.background = 'lime';
        testDiv.style.color = 'black';
    }
    function filterEmployeeTable() {
        var input = document.getElementById('employeeSearch');
        if (scriptStatus && input) {
            scriptStatus.textContent = 'Employee search script is running. Current filter: "' + input.value + '"';
        }
        var filter = input ? input.value.toLowerCase().trim() : '';
        var tbody = document.getElementById('employeeTableBody');
        var rows = tbody ? tbody.getElementsByTagName('tr') : [];
        var anyVisible = false;
        var noDataRow = null;
        var matchedNames = [];
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            // Identify the 'no employees found' row by its unique text
            if (row.innerText && row.innerText.trim().toLowerCase().includes('no employees found')) {
                noDataRow = row;
                continue;
            }
            // Only filter rows with actual employee data
            // Get relevant columns: Emp Code, Name, Department, Shift
            var tds = row.getElementsByTagName('td');
            var searchText = '';
            var nameText = '';
            if (tds.length > 4) {
                searchText = [tds[1], tds[2], tds[3], tds[4]]
                    .map(function (td) { return (td.innerText || td.textContent || '').toLowerCase().trim(); })
                    .join(' ');
                // Name column (tds[2])
                nameText = (tds[2].innerText || tds[2].textContent || '').trim();
            }
            // Use includes for partial match
            if (filter === '' || searchText.includes(filter)) {
                row.style.display = '';
                anyVisible = true;
                if (filter !== '' && nameText) {
                    matchedNames.push(nameText);
                }
            } else {
                row.style.display = 'none';
            }
        }
        if (noDataRow) {
            noDataRow.style.display = anyVisible ? 'none' : '';
        }
        // Show matched names under the table
        var resultsDiv = document.getElementById('searchResults');
        if (resultsDiv) {
            if (filter !== '' && matchedNames.length > 0) {
                resultsDiv.innerHTML = '<strong>Matching Employees:</strong><ul class="list-group list-group-flush">' +
                    matchedNames.map(function (name) { return '<li class="list-group-item py-1">' + name + '</li>'; }).join('') + '</ul>';
            } else if (filter !== '') {
                resultsDiv.innerHTML = '<span class="text-danger">No matching employees found.</span>';
            } else {
                resultsDiv.innerHTML = '';
            }
        }
    }
    var searchInput = document.getElementById('employeeSearch');
    if (searchInput) {
        searchInput.addEventListener('input', filterEmployeeTable);
        // Run once on load to ensure correct state
        filterEmployeeTable();
    }
}

// Attendance tab ke pagination/per-page ko init karne ka function
function initAttendanceTabEvents() {
    var meta = document.getElementById('attendancePagingMeta');
    var perSel = document.getElementById('attendancePerPageFooter');
    if (!meta || !perSel) return;

    var month = meta.getAttribute('data-month') || '';
    var year = meta.getAttribute('data-year') || '';
    var page = parseInt(meta.getAttribute('data-page') || '1', 10) || 1;
    var per = parseInt(meta.getAttribute('data-per-page') || '10', 10) || 10;

    perSel.value = String(per);

    function loadAttendancePage(p, perPage) {
        var tabBtn = document.querySelector('[data-page^="attendance_tab.php"]');
        var url = 'attendance_tab.php?ajax=1'
            + '&month=' + encodeURIComponent(month)
            + '&year=' + encodeURIComponent(year)
            + '&page=' + encodeURIComponent(p)
            + '&per_page=' + encodeURIComponent(perPage);
        loadPage(url, tabBtn);
    }

    window.refreshAttendanceTable = function () {
        loadAttendancePage(page, per);
    };

    perSel.onchange = function () {
        var newPer = parseInt(this.value || '10', 10) || 10;
        loadAttendancePage(1, newPer);
    };

    document.querySelectorAll('.att-page-link').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var p = parseInt(this.dataset.page || '1', 10) || 1;
            var currentPer = parseInt(perSel.value || '10', 10) || 10;
            loadAttendancePage(p, currentPer);
        });
    });
}

function initLeavesTabEvents() {
    var meta = document.getElementById('leavesPagingMeta');
    var perSel = document.getElementById('leavesPerPageFooter');
    if (!meta || !perSel) return;

    var page = parseInt(meta.getAttribute('data-page') || '1', 10) || 1;
    var per = parseInt(meta.getAttribute('data-per-page') || '10', 10) || 10;
    perSel.value = String(per);

    function loadLeavesPage(p, perPage) {
        var tabBtn = document.querySelector('[data-page^="leaves_tab.php"]');
        var url = 'leaves_tab.php?ajax=1'
            + '&page=' + encodeURIComponent(p)
            + '&per_page=' + encodeURIComponent(perPage);
        loadPage(url, tabBtn);
    }

    perSel.onchange = function () {
        var newPer = parseInt(this.value || '10', 10) || 10;
        loadLeavesPage(1, newPer);
    };

    document.querySelectorAll('.leaves-page-link').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var p = parseInt(this.dataset.page || '1', 10) || 1;
            var currentPer = parseInt(perSel.value || '10', 10) || 10;
            loadLeavesPage(p, currentPer);
        });
    });

    // Apply Leave modal
    var openBtn = document.getElementById('openApplyLeaveModal');
    var modalEl = document.getElementById('applyLeaveModal');
    var formEl = document.getElementById('applyLeaveForm');
    var submitBtn = document.getElementById('applyLeaveSubmitBtn');
    if (openBtn && modalEl && typeof bootstrap !== 'undefined') {
        if (openBtn.dataset.bound !== '1') {
            openBtn.dataset.bound = '1';
            openBtn.addEventListener('click', function () {
                var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            });
        }
    }

    if (formEl) {
        if (formEl.dataset.bound !== '1') {
            formEl.dataset.bound = '1';
            formEl.addEventListener('submit', function (e) {
                e.preventDefault();

                if (submitBtn) submitBtn.disabled = true;
                showLoader();

                fetch('apply_leave_hr.php?ajax=1', {
                    method: 'POST',
                    body: new FormData(formEl)
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data && data.success) {
                            try {
                                if (modalEl && typeof bootstrap !== 'undefined') {
                                    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                                    modal.hide();
                                }
                            } catch (e) { }

                            // Reset form for next time
                            try { formEl.reset(); } catch (e) { }

                            showStatus(data.message || 'Leave applied.', 'success');
                            var currentPage = parseInt(meta.getAttribute('data-page') || '1', 10) || 1;
                            var currentPer = parseInt(perSel.value || '10', 10) || 10;
                            loadLeavesPage(currentPage, currentPer);
                        } else {
                            showStatus((data && data.message) ? data.message : 'Failed to apply leave.', 'danger');
                        }
                    })
                    .catch(function () {
                        showStatus('Failed to apply leave.', 'danger');
                    })
                    .finally(function () {
                        if (submitBtn) submitBtn.disabled = false;
                        hideLoader();
                    });
            });
        }
    }
}

function initDepartmentsTabEvents() {
    var meta = document.getElementById('departmentsPagingMeta');
    var perSel = document.getElementById('departmentsPerPageFooter');
    if (!meta || !perSel) return;

    var page = parseInt(meta.getAttribute('data-page') || '1', 10) || 1;
    var per = parseInt(meta.getAttribute('data-per-page') || '10', 10) || 10;
    perSel.value = String(per);

    function loadDepartmentsPage(p, perPage) {
        var tabBtn = document.querySelector('[data-page^="departments.php"]');
        var url = 'departments.php?ajax=1'
            + '&page=' + encodeURIComponent(p)
            + '&per_page=' + encodeURIComponent(perPage);
        loadPage(url, tabBtn);
    }

    perSel.onchange = function () {
        var newPer = parseInt(this.value || '10', 10) || 10;
        loadDepartmentsPage(1, newPer);
    };

    document.querySelectorAll('.departments-page-link').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var p = parseInt(this.dataset.page || '1', 10) || 1;
            var currentPer = parseInt(perSel.value || '10', 10) || 10;
            loadDepartmentsPage(p, currentPer);
        });
    });

    // Modal open/reset + auto-open on edit
    var openBtn = document.getElementById('openDepartmentModal');
    var modalEl = document.getElementById('departmentModal');
    var modalMeta = document.getElementById('departmentsModalMeta');
    if (openBtn && modalEl && !openBtn.dataset.bound) {
        openBtn.dataset.bound = '1';
        openBtn.addEventListener('click', function () {
            var form = document.getElementById('departmentModalForm');
            if (form) {
                var idInput = form.querySelector('input[name="id"]');
                var nameInput = form.querySelector('input[name="department_name"]');
                if (idInput) idInput.value = '';
                if (nameInput) nameInput.value = '';
            }
            var title = document.getElementById('departmentModalTitle');
            if (title) title.textContent = 'Add Department';
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        });
    }
    if (modalEl && modalMeta && modalMeta.getAttribute('data-open') === '1') {
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function initDesignationsTabEvents() {
    var meta = document.getElementById('designationsPagingMeta');
    var perSel = document.getElementById('designationsPerPageFooter');
    if (!meta || !perSel) return;

    var page = parseInt(meta.getAttribute('data-page') || '1', 10) || 1;
    var per = parseInt(meta.getAttribute('data-per-page') || '10', 10) || 10;
    perSel.value = String(per);

    function loadDesignationsPage(p, perPage) {
        var tabBtn = document.querySelector('[data-page^="designations.php"]');
        var url = 'designations.php?ajax=1'
            + '&page=' + encodeURIComponent(p)
            + '&per_page=' + encodeURIComponent(perPage);
        loadPage(url, tabBtn);
    }

    perSel.onchange = function () {
        var newPer = parseInt(this.value || '10', 10) || 10;
        loadDesignationsPage(1, newPer);
    };

    document.querySelectorAll('.designations-page-link').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var p = parseInt(this.dataset.page || '1', 10) || 1;
            var currentPer = parseInt(perSel.value || '10', 10) || 10;
            loadDesignationsPage(p, currentPer);
        });
    });

    // Modal open/reset + auto-open on edit
    var openBtn = document.getElementById('openDesignationModal');
    var modalEl = document.getElementById('designationModal');
    var modalMeta = document.getElementById('designationsModalMeta');
    if (openBtn && modalEl && !openBtn.dataset.bound) {
        openBtn.dataset.bound = '1';
        openBtn.addEventListener('click', function () {
            var form = document.getElementById('designationModalForm');
            if (form) {
                var idInput = form.querySelector('input[name="id"]');
                var nameInput = form.querySelector('input[name="designation_name"]');
                var deptSelect = form.querySelector('select[name="department_id"]');
                if (idInput) idInput.value = '';
                if (nameInput) nameInput.value = '';
                if (deptSelect) deptSelect.value = '';
            }
            var title = document.getElementById('designationModalTitle');
            if (title) title.textContent = 'Add Designation';
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        });
    }
    if (modalEl && modalMeta && modalMeta.getAttribute('data-open') === '1') {
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function initHolidaysTabEvents() {
    var meta = document.getElementById('holidaysPagingMeta');
    var perSel = document.getElementById('holidaysPerPageFooter');
    if (!meta || !perSel) return;

    var page = parseInt(meta.getAttribute('data-page') || '1', 10) || 1;
    var per = parseInt(meta.getAttribute('data-per-page') || '10', 10) || 10;
    perSel.value = String(per);

    function loadHolidaysPage(p, perPage) {
        var tabBtn = document.querySelector('[data-page^="holidays.php"]');
        var url = 'holidays.php?ajax=1'
            + '&page=' + encodeURIComponent(p)
            + '&per_page=' + encodeURIComponent(perPage);
        loadPage(url, tabBtn);
    }

    perSel.onchange = function () {
        var newPer = parseInt(this.value || '10', 10) || 10;
        loadHolidaysPage(1, newPer);
    };

    document.querySelectorAll('.holidays-page-link').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var p = parseInt(this.dataset.page || '1', 10) || 1;
            var currentPer = parseInt(perSel.value || '10', 10) || 10;
            loadHolidaysPage(p, currentPer);
        });
    });

    // Modal open/reset + auto-open on edit
    var openBtn = document.getElementById('openHolidayModal');
    var modalEl = document.getElementById('holidayModal');
    var modalMeta = document.getElementById('holidaysModalMeta');
    if (openBtn && modalEl && !openBtn.dataset.bound) {
        openBtn.dataset.bound = '1';
        openBtn.addEventListener('click', function () {
            var form = document.getElementById('holidayModalForm');
            if (form) {
                var idInput = form.querySelector('input[name="id"]');
                var nameInput = form.querySelector('input[name="holiday_name"]');
                var dateInput = form.querySelector('input[name="holiday_date"]');
                if (idInput) idInput.value = '';
                if (nameInput) nameInput.value = '';
                if (dateInput) dateInput.value = '';
            }
            var title = document.getElementById('holidayModalTitle');
            if (title) title.textContent = 'Add Holiday';
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        });
    }
    if (modalEl && modalMeta && modalMeta.getAttribute('data-open') === '1') {
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function initShiftRosterTabEvents() {
    var meta = document.getElementById('shiftRosterMeta');
    if (!meta) return;

    var view = meta.getAttribute('data-view') || 'week';
    var start = meta.getAttribute('data-start') || '';

    var weekBtn = document.getElementById('shiftRosterWeekBtn');
    var monthBtn = document.getElementById('shiftRosterMonthBtn');
    var prevBtn = document.getElementById('shiftRosterPrev');
    var nextBtn = document.getElementById('shiftRosterNext');

    function setActiveViewButtons() {
        if (weekBtn) weekBtn.classList.toggle('active', view === 'week');
        if (monthBtn) monthBtn.classList.toggle('active', view === 'month');
    }
    setActiveViewButtons();

    function toISODate(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function loadRoster(newView, newStart) {
        var tabBtn = document.querySelector('[data-page^="shift_roster.php"]');
        var url = 'shift_roster.php?ajax=1'
            + '&view=' + encodeURIComponent(newView)
            + '&start=' + encodeURIComponent(newStart);
        loadPage(url, tabBtn);
    }

    function parseStartDate() {
        if (!start) return new Date();
        // Force local date parsing
        var parts = String(start).split('-');
        if (parts.length !== 3) return new Date();
        return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
    }

    if (weekBtn && !weekBtn.dataset.bound) {
        weekBtn.dataset.bound = '1';
        weekBtn.addEventListener('click', function () {
            view = 'week';
            setActiveViewButtons();
            loadRoster('week', start || toISODate(new Date()));
        });
    }

    if (monthBtn && !monthBtn.dataset.bound) {
        monthBtn.dataset.bound = '1';
        monthBtn.addEventListener('click', function () {
            view = 'month';
            setActiveViewButtons();
            loadRoster('month', start || toISODate(new Date()));
        });
    }

    if (prevBtn && !prevBtn.dataset.bound) {
        prevBtn.dataset.bound = '1';
        prevBtn.addEventListener('click', function () {
            var d = parseStartDate();
            if (view === 'month') {
                d.setMonth(d.getMonth() - 1);
                d.setDate(1);
            } else {
                d.setDate(d.getDate() - 7);
            }
            loadRoster(view, toISODate(d));
        });
    }

    if (nextBtn && !nextBtn.dataset.bound) {
        nextBtn.dataset.bound = '1';
        nextBtn.addEventListener('click', function () {
            var d = parseStartDate();
            if (view === 'month') {
                d.setMonth(d.getMonth() + 1);
                d.setDate(1);
            } else {
                d.setDate(d.getDate() + 7);
            }
            loadRoster(view, toISODate(d));
        });
    }
}

// Expose these to global window so loadPage can verify them or use them if needed
window.initEmployeesListEvents = initEmployeesListEvents;
window.initAttendanceTabEvents = initAttendanceTabEvents;
window.initLeavesTabEvents = initLeavesTabEvents;
window.initDepartmentsTabEvents = initDepartmentsTabEvents;
window.initDesignationsTabEvents = initDesignationsTabEvents;
window.initHolidaysTabEvents = initHolidaysTabEvents;
window.initShiftRosterTabEvents = initShiftRosterTabEvents;
