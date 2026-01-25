<?php
/**
 * check_device_status.php
 * Check if an employee's device is registered.
 * 
 * Input: user_id, device_id
 * Returns: { status: 'success', device_status: 'registered' | 'not_registered' | 'different_device' }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

include "db.php";

$user_id = isset($_REQUEST['user_id']) ? trim($_REQUEST['user_id']) : null;
$device_id = isset($_REQUEST['device_id']) ? trim($_REQUEST['device_id']) : null;

if (!$user_id || !$device_id) {
    echo json_encode(['status' => 'error', 'msg' => 'Missing user_id or device_id']);
    exit;
}

$uidInt = (int) $user_id;
$empCodePattern = "EMP" . str_pad((string) $uidInt, 3, '0', STR_PAD_LEFT);

// Step 1: Check employee existence and identity
$stmt = $con->prepare(
    "SELECT user_id, name FROM employees 
     WHERE (emp_code = ? OR user_id = ?) AND status = 1 
     LIMIT 1"
);

if (!$stmt) {
    echo json_encode(['status' => 'error', 'msg' => 'Database error']);
    exit;
}

$stmt->bind_param("si", $empCodePattern, $uidInt);
$stmt->execute();
$empRes = $stmt->get_result();

if (!$empRes || $empRes->num_rows === 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Employee not found or inactive']);
    $stmt->close();
    exit;
}

$emp = $empRes->fetch_assoc();
$empId = (int) $emp['user_id'];
$stmt->close();

// Step 2: Check if THIS specific device is registered for this employee
$devStmt = $con->prepare("SELECT id FROM employee_devices WHERE user_id = ? AND device_id = ?");
$devStmt->bind_param("is", $empId, $device_id);
$devStmt->execute();
$isRegistered = $devStmt->get_result()->num_rows > 0;
$devStmt->close();

if ($isRegistered) {
    echo json_encode([
        'status' => 'success',
        'device_status' => 'registered',
        'employee_name' => $emp['name']
    ]);
    exit;
}

// Step 3: Specific device is NOT registered. 
// Now we check if they have reached their limit or if they can add another one.
$resLimit = $con->query("SELECT setting_value FROM attendance_settings WHERE setting_key = 'device_limit'");
$limit = ($resLimit && $r = $resLimit->fetch_assoc()) ? (int) $r['setting_value'] : 1;
if ($limit < 1)
    $limit = 1;

$countRes = $con->query("SELECT COUNT(*) as c FROM employee_devices WHERE user_id = $empId");
$currentCount = ($countRes && $cr = $countRes->fetch_assoc()) ? (int) $cr['c'] : 0;

if ($currentCount < $limit) {
    // Can register a new device
    echo json_encode([
        'status' => 'success',
        'device_status' => 'not_registered',
        'employee_name' => $emp['name'],
        'msg' => 'Please register this device.'
    ]);
} else {
    // Already hit limit with other devices
    echo json_encode([
        'status' => 'success',
        'device_status' => 'different_device',
        'employee_name' => $emp['name'],
        'msg' => 'Maximum device limit reached (' . $limit . '). Please contact HR to reset/add devices.'
    ]);
}

$stmt->close();
$con->close();
?>