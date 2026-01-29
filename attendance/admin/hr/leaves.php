<?php
// leaves.php (MPA mode)
date_default_timezone_set('Asia/Kolkata');
include '../config/db.php';
// Check if AJAX
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

if ($isAjax) {
    include 'leaves_tab.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Leaves</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="css/hr_dashboard.css" rel="stylesheet">
    <?php include_once __DIR__ . '/../includes/table-styles.php'; ?>
</head>

<body>

    <?php include_once '../includes/header.php'; ?>

    <div class="main-content-scroll">

        <!-- loader -->
        <div id="loaderOverlay" class="d-none">
            <div class="loader-spinner"></div>
        </div>

        <!-- toast -->
        <div id="statusAlertWrapper" class="position-fixed start-50 translate-middle-x p-3">
            <div id="statusAlert"
                class="alert alert-success shadow-sm d-none align-items-center justify-content-between mb-0 text-center"
                role="alert">
                <span id="statusAlertText"></span>
                <button type="button" class="btn-close ms-2" aria-label="Close"
                    onclick="document.getElementById('statusAlert').classList.add('d-none');">
                </button>
            </div>
        </div>

        <div class="container-fluid py-3 d-flex justify-content-center" style="padding-top:72px;">
            <div class="page-wrapper w-100">

                <!-- Top tabs row -->
                <div class="d-flex justify-content-center align-items-center gap-3 mb-4">
                    <?php include_once __DIR__ . '/../includes/navbar-hr.php'; ?>
                    <!-- settings icon -> settings.php -->
                    <a href="settings.php" class="btn-round-icon" title="Settings">
                        <i class="bi bi-gear-fill"></i>
                    </a>
                </div>

                <!-- Content area -->
                <div id="contentArea">
                    <?php include 'leaves_tab.php'; ?>
                </div>

            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Initial init for leaves tab
                if (typeof initLeavesTabEvents === 'function') {
                    initLeavesTabEvents();
                }
            });
        </script>

        <script src="js/utils.js"></script>
        <script src="js/tab_handlers.js"></script>
        <script src="js/form_handlers.js"></script>

    </div>
</body>

</html>