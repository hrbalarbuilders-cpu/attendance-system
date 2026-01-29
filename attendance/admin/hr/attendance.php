<?php
include '../includes/auth_check.php';
// attendance.php (MPA mode)
date_default_timezone_set('Asia/Kolkata');
include '../config/db.php';

// Check if AJAX - EARLY EXIT
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';
if ($isAjax) {
    include 'attendance_tab.php';
    exit;
}

/* ------- Departments & Employees for Mark Attendance Modal (Shared Logic) ------- */
$deptRes = $con->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");

$employeesForJs = [];
$empRes = $con->query("SELECT user_id, name, department_id FROM employees ORDER BY name ASC");
if ($empRes && $empRes->num_rows > 0) {
    while ($e = $empRes->fetch_assoc()) {
        $employeesForJs[] = [
            'id' => (int) $e['user_id'],
            'name' => $e['name'],
            'department_id' => (int) $e['department_id'],
        ];
    }
}

// Fetch Working From options
$workingFromOptions = [];
$wfCheck = $con->query("SHOW TABLES LIKE 'working_from_master'");
if ($wfCheck && $wfCheck->num_rows > 0) {
    $wfRes = $con->query("SELECT code, label FROM working_from_master WHERE is_active = 1 ORDER BY label ASC");
    if ($wfRes && $wfRes->num_rows > 0) {
        while ($wf = $wfRes->fetch_assoc()) {
            $code = trim((string) ($wf['code'] ?? ''));
            $label = trim((string) ($wf['label'] ?? ''));
            if ($code !== '') {
                $workingFromOptions[] = [
                    'code' => $code,
                    'label' => $label !== '' ? $label : ucfirst($code),
                ];
            }
        }
    }
}
if (empty($workingFromOptions)) {
    $workingFromOptions = [
        ['code' => 'office', 'label' => 'Office'],
        ['code' => 'home', 'label' => 'Home'],
        ['code' => 'client', 'label' => 'Client Site'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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
                    <?php include 'attendance_tab.php'; ?>
                </div>

                -->

            </div>
        </div>

        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            // PHP se employees array JS me
            const ALL_EMPLOYEES = <?php echo json_encode($employeesForJs, JSON_UNESCAPED_UNICODE); ?>;

            document.addEventListener('DOMContentLoaded', () => {
                // Initial init for attendance tab (pagination etc.)
                if (typeof initAttendanceTabEvents === 'function') {
                    initAttendanceTabEvents();
                }
                if (typeof initShiftTimePicker === 'function') {
                    initShiftTimePicker();
                }
            });
        </script>

        <script src="js/utils.js"></script>
        <script src="js/tab_handlers.js"></script>
        <script src="js/mark_attendance.js"></script>
        <script src="js/shift_time_picker.js"></script>
        <script src="js/attendance_details.js"></script>
        <script src="js/form_handlers.js"></script>
        <script src="js/dashboard.js"></script>

    </div>
    <!-- Shared HR Modals -->
    <?php include __DIR__ . '/../includes/hr-modals.php'; ?>
</body>

</html>