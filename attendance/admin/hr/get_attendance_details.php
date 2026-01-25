<?php
date_default_timezone_set('Asia/Kolkata');
include '../config/db.php';

header('Content-Type: application/json');

$emp_id = isset($_GET['emp_id']) ? (int) $_GET['emp_id'] : 0;
$date = isset($_GET['date']) ? trim($_GET['date']) : '';

if ($emp_id <= 0 || $date === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Get employee info along with shift details (if any)
$empStmt = $con->prepare("SELECT 
                                                        e.user_id, 
                                                        e.name, 
                                                        e.emp_code, 
                                                        desig.designation_name,
                                                        s.start_time AS shift_start_time,
                                                        s.end_time AS shift_end_time,
                                                        s.late_mark_after AS shift_late_mark_after,
                                                        s.lunch_start AS shift_lunch_start,
                                                        s.lunch_end AS shift_lunch_end
                                                    FROM employees e 
                                                    LEFT JOIN designations desig ON desig.id = e.designation_id 
                                                    LEFT JOIN shifts s ON s.id = e.shift_id
                                                    WHERE e.user_id = ?");
$empStmt->bind_param("i", $emp_id);
$empStmt->execute();
$empResult = $empStmt->get_result();
$employee = $empResult->fetch_assoc();

if (!$employee) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit;
}

// Use actual Database Primary Key (user_id)
$num = (int) $employee['user_id'];

// Get attendance logs for this date
$startDateTime = $date . ' 00:00:00';
$endDateTime = $date . ' 23:59:59';

$logStmt = $con->prepare("
    SELECT time, type, working_from, reason 
    FROM attendance_logs 
    WHERE user_id = ? AND time BETWEEN ? AND ?
    ORDER BY time ASC
");
$logStmt->bind_param("iss", $num, $startDateTime, $endDateTime);
$logStmt->execute();
$logResult = $logStmt->get_result();

$logs = [];
while ($log = $logResult->fetch_assoc()) {
    $logs[] = [
        'time' => $log['time'],
        'type' => $log['type'],
        'working_from' => $log['working_from'],
        'reason' => $log['reason']
    ];
}

// Prepare shift information for the response (times formatted as 12-hour with AM/PM where available)
$shift = null;
if (!empty($employee['shift_start_time']) && !empty($employee['shift_end_time'])) {
    $shift = [
        'start_time' => date('h:i A', strtotime($employee['shift_start_time'])),
        'end_time' => date('h:i A', strtotime($employee['shift_end_time'])),
        'late_mark_after' => isset($employee['shift_late_mark_after']) ? (int) $employee['shift_late_mark_after'] : 0,
        'lunch_start' => !empty($employee['shift_lunch_start']) ? date('h:i A', strtotime($employee['shift_lunch_start'])) : null,
        'lunch_end' => !empty($employee['shift_lunch_end']) ? date('h:i A', strtotime($employee['shift_lunch_end'])) : null,
    ];
}

echo json_encode([
    'success' => true,
    'employee' => [
        'name' => $employee['name'],
        'role' => $employee['designation_name'] ?? '',
        'emp_code' => $employee['emp_code']
    ],
    'date' => $date,
    'logs' => $logs,
    'shift' => $shift
]);

