let statusTimer;

// toast function
function showStatus(message, type = 'success') {
    const box = document.getElementById('statusAlert');
    const text = document.getElementById('statusAlertText');
    if (!box || !text) return;

    text.textContent = message;

    box.classList.remove('d-none', 'alert-success', 'alert-danger', 'd-flex');
    box.classList.add(type === 'danger' ? 'alert-danger' : 'alert-success', 'd-flex');

    if (statusTimer) clearTimeout(statusTimer);
    statusTimer = setTimeout(() => {
        box.classList.add('d-none');
    }, 2500);
}

// loader helpers
function showLoader() {
    const loader = document.getElementById('loaderOverlay');
    if (loader) loader.classList.remove('d-none');
}

function hideLoader() {
    const loader = document.getElementById('loaderOverlay');
    if (loader) loader.classList.add('d-none');
}

function toISODate(d) {
    var y = d.getFullYear();
    var m = String(d.getMonth() + 1).padStart(2, '0');
    var day = String(d.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + day;
}

// Global scope expose
window.showStatus = showStatus;
window.showLoader = showLoader;
window.hideLoader = hideLoader;
window.toISODate = toISODate;
