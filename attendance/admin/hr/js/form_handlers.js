document.addEventListener("click", function (e) {
    if (e.target.classList.contains("dept-edit")) {
        const id = e.target.dataset.editId;

        loadPage("departments.php?ajax=1&edit=" + id,
            document.querySelector('[data-page="departments.php?ajax=1"]'));
    }
});

// Designation Edit (AJAX load)
document.addEventListener("click", function (e) {
    const editLink = e.target.closest(".desig-edit");
    if (editLink) {
        e.preventDefault();
        const id = editLink.dataset.editId;
        const tabBtn = document.querySelector('[data-page="designations.php?ajax=1"]');
        loadPage("designations.php?ajax=1&edit=" + encodeURIComponent(id), tabBtn);
    }
});

// Holiday Edit (AJAX load)
document.addEventListener("click", function (e) {
    const editLink = e.target.closest(".holiday-edit");
    if (editLink) {
        e.preventDefault();
        const id = editLink.dataset.editId;
        const tabBtn = document.querySelector('[data-page="holidays.php?ajax=1"]');
        loadPage("holidays.php?ajax=1&edit=" + encodeURIComponent(id), tabBtn);
    }
});

document.addEventListener("submit", function (e) {
    const form = e.target;

    // SPA ke contentArea ke andar ka form hi intercept karna hai
    if (!form.closest("#contentArea")) return;

    // DEPARTMENT form
    if (form.action.includes("departments.php")) {
        e.preventDefault();

        // close modal if open
        const deptModalEl = document.getElementById('departmentModal');
        if (deptModalEl) {
            const m = bootstrap.Modal.getInstance(deptModalEl);
            if (m) m.hide();
        }

        const formData = new FormData(form);
        const url = "departments.php?ajax=1";

        showLoader();
        fetch(url, {
            method: "POST",
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const tabBtn = document.querySelector('[data-page="departments.php?ajax=1"]');
                    loadPage(data.reload, tabBtn);
                    showStatus("Department saved successfully.", "success");
                }
            })
            .catch(() => showStatus("Error saving department", "danger"))
            .finally(hideLoader);

        return;
    }

    // DESIGNATION form
    if (form.action.includes("designations.php")) {
        e.preventDefault();

        // close modal if open
        const desigModalEl = document.getElementById('designationModal');
        if (desigModalEl) {
            const m = bootstrap.Modal.getInstance(desigModalEl);
            if (m) m.hide();
        }

        const formData = new FormData(form);
        const url = "designations.php?ajax=1";

        showLoader();
        fetch(url, {
            method: "POST",
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const tabBtn = document.querySelector('[data-page="designations.php?ajax=1"]');
                    loadPage(data.reload, tabBtn);
                    showStatus("Designation saved successfully.", "success");
                }
            })
            .catch(() => showStatus("Error saving designation", "danger"))
            .finally(hideLoader);

        return;
    }

    // HOLIDAYS form
    if (form.action.includes("holidays.php")) {
        e.preventDefault();

        // close modal if open
        const holModalEl = document.getElementById('holidayModal');
        if (holModalEl) {
            const m = bootstrap.Modal.getInstance(holModalEl);
            if (m) m.hide();
        }

        const formData = new FormData(form);
        const url = "holidays.php?ajax=1";

        showLoader();
        fetch(url, {
            method: "POST",
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const tabBtn = document.querySelector('[data-page="holidays.php?ajax=1"]');
                    loadPage(data.reload, tabBtn);
                    showStatus("Holiday saved successfully.", "success");
                }
            })
            .catch(() => showStatus("Error saving holiday", "danger"))
            .finally(hideLoader);

        return;
    }
});

// Attendance filter (attendance_tab.php ke andar ka form)
document.addEventListener("submit", function (e) {
    const form = e.target;

    // 1) Attendance FILTER form (AJAX)
    if (form.id === "attendanceFilterForm") {
        e.preventDefault();

        const month = form.month.value;
        const year = form.year.value;

        const tabBtn = document.querySelector('[data-page^="attendance_tab.php"]');
        const url = "attendance_tab.php?ajax=1"
            + "&month=" + encodeURIComponent(month)
            + "&year=" + encodeURIComponent(year);

        loadPage(url, tabBtn);  // same animation + loader use hoga
        return;
    }
});
