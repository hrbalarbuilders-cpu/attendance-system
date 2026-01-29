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
    <!-- Mark Attendance Modal -->
    <?php include __DIR__ . '/../includes/modal-mark-attendance.php'; ?>

    <!-- Time Picker Modal -->
    <?php include __DIR__ . '/../includes/modal-time-picker.php'; ?>

    <!-- Attendance Details Modal -->
    <div class="modal fade" id="attendanceDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <!-- Header -->
                <div class="modal-header att-details-header border-0 pb-0 shadow-sm d-flex align-items-center justify-content-between"
                    style="z-index:100;">
                    <h5 class="modal-title fw-bold text-dark m-0">Attendance Details</h5>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-light rounded-circle shadow-sm" id="deleteAttendanceBtn"
                            style="display:none; width:40px; height:40px; color:#ef4444;">
                            <i class="bi bi-trash3-fill"></i>
                        </button>
                        <button type="button" class="btn btn-light rounded-circle shadow-sm" data-bs-dismiss="modal"
                            style="width:40px; height:40px;">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="modal-body p-4 bg-light bg-opacity-10">
                    <div class="row g-4">
                        <!-- Left Side: Profile & Stats -->
                        <div class="col-md-5 border-end">
                            <!-- Employee Profile -->
                            <div class="bg-white p-3 rounded-4 shadow-sm mb-4 border border-light">
                                <div class="d-flex align-items-center mb-0">
                                    <div class="emp-avatar shadow-sm border border-light"
                                        style="width:64px; height:64px; border-radius:18px; background:#f1f5f9; color:#2563eb; font-size:24px; font-weight:700; display:flex; align-items:center; justify-content:center;"
                                        id="modalEmpAvatar">E</div>
                                    <div class="ms-3 flex-grow-1">
                                        <h5 class="mb-0 fw-bold text-dark" id="modalEmpName">Employee Name</h5>
                                        <div class="text-muted small fw-medium" id="modalEmpRole">Designation</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="badge bg-light text-dark border py-2 px-3 rounded-3 shadow-none">
                                            <i class="bi bi-calendar3 me-2 text-primary"></i> <span
                                                id="modalDate">--/--/----</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Clock In Card -->
                            <div class="status-card status-card-in mb-4" id="clockInBox" style="display:none;">
                                <div class="label">Clock In</div>
                                <div class="time" id="clockInTime">--:-- --</div>
                                <div class="greeting mt-2" id="clockInGreeting">Good morning! 👋</div>
                            </div>

                            <!-- Work Progress Ring -->
                            <div id="totalWorkBox" class="bg-white p-4 rounded-4 shadow-sm border border-light mb-4"
                                style="display:none;">
                                <div class="work-progress-container d-flex flex-column align-items-center py-2">
                                    <div class="position-relative" style="width: 150px; height: 150px;">
                                        <svg style="transform: rotate(-90deg);" width="150" height="150">
                                            <circle cx="75" cy="75" r="68" stroke="#f1f5f9" stroke-width="10"
                                                fill="none" />
                                            <circle cx="75" cy="75" r="68" stroke="#6366f1" stroke-width="10"
                                                fill="none" stroke-dasharray="427" stroke-dashoffset="427"
                                                id="workProgressCircle" stroke-linecap="round"
                                                style="transition: stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1);" />
                                            <circle cx="75" cy="75" r="68" stroke="#facc15" stroke-width="10"
                                                fill="none" stroke-dasharray="427" stroke-dashoffset="427"
                                                id="breakProgressCircle" stroke-linecap="round"
                                                style="transition: stroke-dashoffset 0.8s; display:none;">
                                                <title id="breakTooltip"></title>
                                            </circle>
                                            <circle cx="75" cy="75" r="68" stroke="#f97316" stroke-width="10"
                                                fill="none" stroke-dasharray="427" stroke-dashoffset="427"
                                                id="lateProgressCircle" stroke-linecap="round"
                                                style="transition: stroke-dashoffset 0.8s; display:none;">
                                                <title id="lateTooltip"></title>
                                            </circle>
                                        </svg>
                                        <div class="progress-text">
                                            <div class="label">Total Work</div>
                                            <div class="value" id="totalWorkTime">0hr 0min</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-center">
                                        <div id="grossWorkTime" class="text-muted small fw-medium"
                                            style="display:none;">Gross: 0hr 0min</div>
                                        <div id="breakTime" class="text-warning small fw-bold" style="display:none;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Clock Out Card -->
                            <div class="status-card status-card-out" id="clockOutBox" style="display:none;">
                                <div class="label">Clock Out</div>
                                <div class="time" id="clockOutTime">--:-- --</div>
                                <div class="greeting mt-2" id="clockOutGreeting">Have a great day! 👋</div>
                            </div>
                        </div>

                        <!-- Right Side: Timeline -->
                        <div class="col-md-7">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="mb-0 fw-bold text-dark">Activity Timeline</h5>
                                <div class="d-flex align-items-center gap-3">
                                    <small class="text-muted fw-bold" id="activityCount">0 activities today</small>
                                    <button class="btn btn-dark btn-sm rounded-3 px-3 py-2 fw-bold shadow-sm"
                                        id="addActivityBtn">
                                        <i class="bi bi-plus-lg me-1"></i> Activity
                                    </button>
                                </div>
                            </div>

                            <!-- Timeline Container -->
                            <div id="activityTimeline"
                                style="max-height: 520px; overflow-y: auto; padding-right: 10px; padding-bottom: 80px;">
                                <!-- Activity items will be injected here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Location Map Modal (Dedicated) -->
    <div class="modal fade" id="attendanceLocationMapModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content map-modal-content border-0 shadow-lg">
                <div class="modal-header border-0 bg-white px-4 pt-4 pb-2" style="z-index: 10;">
                    <h5 class="modal-title fw-bold">Attendance Location Map</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 position-relative" style="height: 550px;">
                    <!-- Map Controls Overlay -->
                    <div class="map-control-overlay ms-3 mt-3 shadow-lg border border-light">
                        <button class="map-control-btn active" id="btnMapRoad">Map</button>
                        <button class="map-control-btn" id="btnMapSatellite">Satellite</button>
                    </div>
                    <!-- Leaflet Container -->
                    <div id="attendance_map_dedicated" style="height: 100%; width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>