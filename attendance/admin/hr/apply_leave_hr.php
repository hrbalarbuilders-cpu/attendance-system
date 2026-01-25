<?php
// apply_leave_hr.php - create a leave application from HR admin Leaves tab
// Returns JSON in ajax mode

date_default_timezone_set('Asia/Kolkata');
include '../config/db.php';

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    header('Location: leaves_tab.php');
    exit;
}

$employeeId = isset($_POST['employee_id']) ? (int) $_POST['employee_id'] : 0;
$leaveTypeId = isset($_POST['leave_type_id']) ? (int) $_POST['leave_type_id'] : 0;
$fromDate = trim($_POST['from_date'] ?? '');
$toDate = trim($_POST['to_date'] ?? '');
$reason = trim($_POST['reason'] ?? '');

if ($employeeId <= 0 || $leaveTypeId <= 0 || $fromDate === '' || $toDate === '' || $reason === '') {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please fill all fields.']);
        exit;
    }
    header('Location: leaves_tab.php');
    exit;
}

$fromTs = strtotime($fromDate);
$toTs = strtotime($toDate);
if ($fromTs === false || $toTs === false || $toTs < $fromTs) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid date range.']);
        exit;
    }
    header('Location: leaves_tab.php');
    exit;
}

$stmt = $con->prepare('INSERT INTO leave_applications (user_id, leave_type_id, from_date, to_date, reason, status) VALUES (?, ?, ?, ?, ?, \'pending\')');
if (!$stmt) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to prepare query.']);
        exit;
    }
    header('Location: leaves_tab.php');
    exit;
}

$stmt->bind_param('iisss', $employeeId, $leaveTypeId, $fromDate, $toDate, $reason);
$ok = $stmt->execute();
$stmt->close();
$con->close();

if ($isAjax) {
    header('Content-Type: application/json');
    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Leave applied successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to apply leave.']);
    }
    exit;
}

header('Location: leaves_tab.php');
exit;
