<?php
// save_admin_attendance.php
date_default_timezone_set('Asia/Kolkata');
include '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

/*
  FORM FIELDS FROM MODAL:
  - employee_id   (required)
  - date          (required, Y-m-d)
  - clock_in      (required, H:i)
  - clock_out     (required, H:i)
  - working_from  (required: office / home / client)
  - reason        (optional, default: normal)
  - late          (0/1)  (optional – abhi use nahi kar rahe)
  - half_day      (0/1)  (optional – abhi use nahi kar rahe)
  - overwrite     (1 if overwrite existing logs)
*/

$employee_id   = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
$date          = trim($_POST['date'] ?? ''); // single date
$clock_in      = trim($_POST['clock_in'] ?? '');
$clock_out     = trim($_POST['clock_out'] ?? '');
$working_from  = trim($_POST['working_from'] ?? '');
$reason        = $_POST['reason'] ?? 'normal';   // NEW
$late          = isset($_POST['late']) ? (int)$_POST['late'] : 0;
$half_day      = isset($_POST['half_day']) ? (int)$_POST['half_day'] : 0;
$overwrite     = isset($_POST['overwrite']) ? 1 : 0;

$mark_by       = $_POST['mark_by'] ?? 'date';

$errors = [];

// Build list of dates based on mark_by
$datesToSave = [];
if ($mark_by === 'multiple') {
    $rawDates = isset($_POST['dates']) ? (array)$_POST['dates'] : [];
    foreach ($rawDates as $d) {
        $d = trim((string)$d);
        if ($d !== '') {
            $datesToSave[] = $d;
        }
    }
    if (empty($datesToSave)) {
        $errors[] = 'At least one date is required.';
    }
} else {
    if ($date === '') {
        $errors[] = 'Date is required.';
    } else {
        $datesToSave[] = $date;
    }
}

// Basic validation
if ($employee_id <= 0)  $errors[] = 'Employee is required.';
if ($clock_in === '')   $errors[] = 'Clock In time is required.';
if ($clock_out === '')  $errors[] = 'Clock Out time is required.';
if ($working_from === '') $errors[] = 'Working From is required.';

if ($reason === '') {
    $reason = 'normal';
}

// Normalize clock_in / clock_out to 24-hour H:i format while allowing AM/PM input
$normalizeTime = function ($t) {
    $t = trim((string)$t);
    if ($t === '') {
        return '';
    }
    $ts = strtotime($t);
    if ($ts === false) {
        return $t; // leave as-is; any invalid format will be caught when building datetime
    }
    return date('H:i', $ts);
};

$clock_in  = $normalizeTime($clock_in);
$clock_out = $normalizeTime($clock_out);

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => implode(' ', $errors)
    ]);
    exit;
}

/* ---------------- EMPLOYEE + user_id (numeric from emp_code) ---------------- */

// employees table se employee fetch
$stmt = $con->prepare("SELECT id, emp_code FROM employees WHERE id = ?");
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'SQL error (employee prepare): ' . $con->error
    ]);
    exit;
}
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$empRes = $stmt->get_result();
$emp    = $empRes->fetch_assoc();

if (!$emp) {
    echo json_encode([
        'success' => false,
        'message' => 'Employee not found.'
    ]);
    exit;
}

// Example: EMP001 -> 1
$empNumericId = (int) filter_var($emp['emp_code'], FILTER_SANITIZE_NUMBER_INT);
if ($empNumericId <= 0) {
    // fallback: agar emp_code se numeric nahi mila to employees.id use karo
    $empNumericId = (int)$emp['id'];
}

/* ---------------- Overwrite (same user + same date) ---------------- */

if ($overwrite) {
    $del = $con->prepare("
        DELETE FROM attendance_logs 
        WHERE user_id = ? 
                    AND DATE(time) = ?
    ");
    if ($del) {
                $del->bind_param("is", $empNumericId, $currentDate);
    }
}

/* ---------------- IN / OUT INSERT (with working_from + reason) ----------------
   Table: attendance_logs
   Columns: id, user_id, type, time, device_id, working_from, reason, latitude, longitude, synced

   - device_id  : 'ADMIN_WEB' (takki pata rahe ye admin ne mark kiya)
   - latitude   : NULL
   - longitude  : NULL
   - synced     : 1 (already synced)
-------------------------------------------------------------------- */

// IN
$insIn = $con->prepare("
    INSERT INTO attendance_logs 
        (user_id, type, time, device_id, working_from, reason, latitude, longitude, synced)
    VALUES 
        (?, 'in', ?, 'ADMIN_WEB', ?, ?, NULL, NULL, 1)
");
if (!$insIn) {
    echo json_encode([
        'success' => false,
        'message' => 'SQL error (IN prepare): ' . $con->error
    ]);
    exit;
}
$insIn->bind_param("isss", $empNumericId, $clockInDateTime, $working_from, $reason);

// OUT
$insOut = $con->prepare("
    INSERT INTO attendance_logs 
        (user_id, type, time, device_id, working_from, reason, latitude, longitude, synced)
    VALUES 
        (?, 'out', ?, 'ADMIN_WEB', ?, ?, NULL, NULL, 1)
");
if (!$insOut) {
    echo json_encode([
        'success' => false,
        'message' => 'SQL error (OUT prepare): ' . $con->error
    ]);
    exit;
}
$insOut->bind_param("isss", $empNumericId, $clockOutDateTime, $working_from, $reason);

/* ---------------- Execute for each date ---------------- */

$overallOk = true;
$clockInDateTime = '';
$clockOutDateTime = '';
$currentDate = '';

foreach ($datesToSave as $d) {
    $currentDate = $d;
    $clockInDateTime  = $currentDate . ' ' . $clock_in  . ':00';
    $clockOutDateTime = $currentDate . ' ' . $clock_out . ':00';

    if ($overwrite && isset($del) && $del) {
        if (!$del->execute()) {
            $overallOk = false;
            break;
        }
    }

    if (!$insIn->execute() || !$insOut->execute()) {
        $overallOk = false;
        break;
    }
}

if ($overallOk) {
    echo json_encode([
        'success' => true,
        'message' => 'Attendance saved successfully.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database error while saving attendance: ' . $con->error
    ]);
}
