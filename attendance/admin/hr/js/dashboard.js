const prefetchCache = new Map();

function loadPage(page, button, updateHistory = true) {
    const contentArea = document.getElementById("contentArea");

    // Active tab UI
    document.querySelectorAll('.top-nav-pill').forEach(btn => btn.classList.remove('active'));
    if (button) button.classList.add('active');

    // If we have a cached promise, use it
    let fetchUrl = page;
    if (!fetchUrl.includes('ajax=1')) {
        fetchUrl += (fetchUrl.includes('?') ? '&' : '?') + 'ajax=1';
    }

    // Check cache (prefetch)
    const cachedPromise = prefetchCache.get(fetchUrl);

    // Skeleton Loader if no cache
    if (!cachedPromise) {
        contentArea.classList.remove('fade-in');
        contentArea.innerHTML = `
            <div class="p-4">
                <div class="skeleton" style="height: 40px; margin-bottom: 20px; width: 30%;"></div>
                <div class="skeleton" style="height: 200px; margin-bottom: 20px;"></div>
                <div class="skeleton" style="height: 40px; width: 50%;"></div>
            </div>
            <style>
            .skeleton {
              background: #e0e0e0;
              border-radius: 8px;
              animation: sk-pulse 1.5s infinite ease-in-out;
            }
            @keyframes sk-pulse { 0% { opacity: 0.5; } 50% { opacity: 1; } 100% { opacity: 0.5; } }
            </style>
        `;
        // ensure loader spinner doesn't conflict
        // showLoader(); // Optional: might disable standard spinner if using skeleton
    }

    // showLoader(); 

    const request = cachedPromise || fetch(fetchUrl).then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.text();
    }).then(text => {
        // Cache the text result promise for future
        // We re-wrap it to be compatible with map
        return text;
    });

    // Save to cache if new
    if (!cachedPromise) {
        prefetchCache.set(fetchUrl, request);
    }

    request
        .then(html => {
            contentArea.innerHTML = html;

            // fade-in
            void contentArea.offsetWidth;
            contentArea.classList.add('fade-in');

            // Update URL
            if (updateHistory) {
                window.history.pushState({ page: page }, '', page);
            }

            // Re-initialize scripts for the specific tab
            if (page.includes('employees.php') || page.includes('employees_list')) {
                if (typeof initEmployeesListEvents === 'function') initEmployeesListEvents();
                if (typeof initShiftTimePicker === 'function') initShiftTimePicker();
            } else if (page.includes('attendance.php') || page.includes('attendance_tab')) {
                if (typeof initAttendanceTabEvents === 'function') initAttendanceTabEvents();
                if (typeof initShiftTimePicker === 'function') initShiftTimePicker();
            } else if (page.includes('leaves.php') || page.includes('leaves_tab')) {
                if (typeof initLeavesTabEvents === 'function') initLeavesTabEvents();
            } else if (page.includes('departments.php')) {
                if (typeof initDepartmentsTabEvents === 'function') initDepartmentsTabEvents();
            } else if (page.includes('designations.php')) {
                if (typeof initDesignationsTabEvents === 'function') initDesignationsTabEvents();
            } else if (page.includes('holidays.php')) {
                if (typeof initHolidaysTabEvents === 'function') initHolidaysTabEvents();
            } else if (page.includes('shift_roster.php')) {
                if (typeof initShiftRosterTabEvents === 'function') initShiftRosterTabEvents();
            }

            // Do NOT delete from cache to persist it (fetch once per session)
            // prefetchCache.delete(fetchUrl); 

        })
        .catch(err => {
            console.error(err);
            contentArea.innerHTML = "<div class='alert alert-danger m-3'>Failed to load page content.</div>";
        })
        .finally(() => {
            hideLoader();
        });
}

// Invalidate cache (helper for forms)
window.invalidateTabCache = function (partialUrl) {
    if (!partialUrl) {
        prefetchCache.clear();
        return;
    }
    for (const key of prefetchCache.keys()) {
        if (key.includes(partialUrl)) {
            prefetchCache.delete(key);
        }
    }
};

window.reloadCurrentTab = function () {
    const activeBtn = document.querySelector('.top-nav-pill.active');
    if (activeBtn) {
        const page = activeBtn.dataset.page || activeBtn.getAttribute('href');
        // Clear specifically this page from cache before reloading
        window.invalidateTabCache(page.split('?')[0]);
        loadPage(page, activeBtn, false); // false to not push duplicate history
    }
};

// Expose globally
window.loadPage = loadPage;

document.addEventListener('DOMContentLoaded', function () {

    // Handle Back/Forward buttons
    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.page) {
            loadPage(e.state.page, null, false);
            // Find active button
            const base = e.state.page.split('?')[0];
            const btn = document.querySelector(`.top-nav-pill[href="${base}"], .top-nav-pill[href^="${base}"]`);
            if (btn) {
                document.querySelectorAll('.top-nav-pill').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            }
        } else {
            window.location.reload();
        }
    });

    // Intercept clicks on .top-nav-pill links
    document.querySelectorAll('.top-nav-pill').forEach(link => {
        // Prefetch on hover
        link.addEventListener('mouseenter', function () {
            const href = this.getAttribute('href');
            if (!href || href === '#') return;

            let fetchUrl = href;
            if (!fetchUrl.includes('ajax=1')) {
                fetchUrl += (fetchUrl.includes('?') ? '&' : '?') + 'ajax=1';
            }

            if (!prefetchCache.has(fetchUrl)) {
                // Start fetching
                const promise = fetch(fetchUrl).then(r => r.text());
                prefetchCache.set(fetchUrl, promise);
            }
        });

        link.addEventListener("click", function (e) {
            // If it's a real link, prevent default and load via AJAX
            const href = this.getAttribute('href');
            if (href && href !== '#') {
                e.preventDefault();
                loadPage(href, this, true);
            }
        });
    });

    // We do NOT need to loadPage() on initial load anymore, 
    // because the server renders the initial page fully (SSR).
    // But we DO need to init the specific events for the current page.

    const path = window.location.pathname;
    if (path.includes('employees.php')) {
        if (typeof initEmployeesListEvents === 'function') initEmployeesListEvents();
        // dashboard.js is loaded AFTER utils/etc, but maybe before some others? 
        // We should wait or checking if functions exist is distinct enough.
    }
    // Actually, the specific PHP pages already have a <script> block at bottom to init their events 
    // (e.g. initHolidaysTabEvents) for the initial load.
    // So JS needs to only handle SUBSEQUENT clicks.
});
