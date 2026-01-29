// NOTE: showStatus() is now globally available from /includes/status-toast.php
// It's loaded in header.php before this script, so no need to redefine it here.

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
window.showLoader = showLoader;
window.hideLoader = hideLoader;
window.toISODate = toISODate;
