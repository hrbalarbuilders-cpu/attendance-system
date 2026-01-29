<?php
include '../includes/auth_check.php';
// employees.php (MPA mode)
date_default_timezone_set('Asia/Kolkata');
include '../config/db.php';

// Note: Status messages (deleted/updated/added) are now handled globally
// by status-toast.php which auto-detects URL parameters

// Check if AJAX - we need to run LIST FETCHING logic but NOT Modal fetching logic.
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

if (!$isAjax) {
  /* ------- Departments & Employees for Mark Attendance Modal (Shared Logic) - ONLY NEEDED FOR FULL PAGE ------- */
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
} else {
  // If AJAX, we assume we might need just the list.
  // However, if we're saving/updating, that logic would be different.
  // For now, this file mainly renders the list view or the full page.
}

// ------------------------------------------------------------------
// DATA FETCHING FOR EMPLOYEES LIST (Initial Load & AJAX)
// ------------------------------------------------------------------
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
// ... rest of list fetching code continues ...
$per_page = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 10;
if ($per_page > 100)
  $per_page = 100;
$offset = ($page - 1) * $per_page;

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$whereSql = '';
if ($q !== '') {
  $esc = $con->real_escape_string($q);
  $whereSql = " WHERE (e.emp_code LIKE '%$esc%' OR e.name LIKE '%$esc%' OR d.department_name LIKE '%$esc%' OR s.shift_name LIKE '%$esc%')";
}

// Count total
$countSql = "
SELECT COUNT(*) AS c
FROM employees e
LEFT JOIN departments d ON d.id = e.department_id
LEFT JOIN shifts s ON s.id = e.shift_id
" . $whereSql;

$totalCount = 0;
$countRes = $con->query($countSql);
if ($countRes && $countRes->num_rows) {
  $r = $countRes->fetch_assoc();
  $totalCount = (int) ($r['c'] ?? 0);
}

// Fetch Page Data
$sql = "
SELECT e.user_id, e.emp_code, e.name, e.department_id, e.shift_id, e.status, e.updated_at, e.created_at,
       d.department_name, s.shift_name, s.start_time, s.end_time
FROM employees e
LEFT JOIN departments d ON d.id = e.department_id
LEFT JOIN shifts s ON s.id = e.shift_id
" . $whereSql . "
ORDER BY e.user_id DESC
LIMIT " . (int) $offset . "," . (int) $per_page;

$result = $con->query($sql);
// ------------------------------------------------------------------
// Check if AJAX
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

if ($isAjax) {
  include 'employees_list_fragment.php';
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Employees</title>
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
          <?php include 'employees_list_fragment.php'; ?>
        </div>

        <!-- Shared HR Modals -->
        <?php include __DIR__ . '/../includes/hr-modals.php'; ?>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
          // PHP se employees array JS me
          const ALL_EMPLOYEES = <?php echo json_encode($employeesForJs, JSON_UNESCAPED_UNICODE); ?>;

          document.addEventListener('DOMContentLoaded', () => {
            // Initial init for employees list events (toggle, filter, etc.)
            if (typeof initEmployeesListEvents === 'function') {
              initEmployeesListEvents();
            }
            // Note: Status messages are now handled globally by status-toast.php
            // which auto-detects ?deleted=1, ?updated=1, ?added=1 from URL
          });
        </script>

        <script src="js/utils.js"></script>
        <!-- tab_handlers might be generic, but we call specific inits manually now -->
        <script src="js/tab_handlers.js"></script>
        <script src="js/shift_time_picker.js"></script>
        <script src="js/mark_attendance.js"></script>
        <script src="js/attendance_details.js"></script>
        <script src="js/form_handlers.js"></script>
        <script src="js/dashboard.js"></script>

      </div>
</body>

</html>