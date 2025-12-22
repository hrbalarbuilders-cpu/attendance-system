<?php
// API: apply_leave.php
// Accepts leave application from employee
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

include 'db.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$emp_id = isset($data['employee_id']) ? (int)$data['employee_id'] : 0;
$leave_type_id = isset($data['leave_type_id']) ? (int)$data['leave_type_id'] : 0;
$from_date = isset($data['from_date']) ? trim($data['from_date']) : '';
$to_date = isset($data['to_date']) ? trim($data['to_date']) : '';
$reason = isset($data['reason']) ? trim($data['reason']) : '';

if ($emp_id <= 0 || $leave_type_id <= 0 || $from_date === '' || $reason === '') {
    echo json_encode(['status' => 'error', 'msg' => 'Missing required fields']);
    exit;
}

if ($to_date === '') {
    $to_date = $from_date;
}



// Check for any overlap with existing pending leaves for the same type and employee
$conflictQuery = $con->prepare("SELECT id FROM leave_applications WHERE employee_id = ? AND leave_type_id = ? AND status = 'pending' AND NOT (to_date < ? OR from_date > ?)");
$conflictQuery->bind_param('iiss', $emp_id, $leave_type_id, $from_date, $to_date);
$conflictQuery->execute();
$conflictQuery->store_result();
if ($conflictQuery->num_rows > 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Leave already applied and pending for the selected date(s).']);
    $conflictQuery->close();
    $con->close();
    exit;
}
$conflictQuery->close();

// Prevent applying for leave on dates where an approved leave already exists (any type)
$approvedConflict = $con->prepare("SELECT id, from_date, to_date FROM leave_applications WHERE employee_id = ? AND status = 'approved' AND NOT (to_date < ? OR from_date > ?)");
$approvedConflict->bind_param('iss', $emp_id, $from_date, $to_date);
$approvedConflict->execute();
$approvedConflict->store_result();
if ($approvedConflict->num_rows > 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Leave already approved for the selected date(s).']);
    $approvedConflict->close();
    $con->close();
    exit;
}
$approvedConflict->close();

try {
    $stmt = $con->prepare("INSERT INTO leave_applications (employee_id, leave_type_id, from_date, to_date, reason, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->bind_param('iisss', $emp_id, $leave_type_id, $from_date, $to_date, $reason);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'msg' => 'Leave applied successfully']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Failed to apply leave']);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
$con->close();
