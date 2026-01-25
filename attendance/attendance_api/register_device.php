<?php
/**
 * register_device.php
 * Register a device for an employee (binds device to employee).
 * 
 * Input: user_id, device_id, device_name (optional)
 * Returns: { status: 'success' } or { status: 'error', msg: '...' }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid request method']);
    exit;
}

include "db.php";

$user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : null;
$device_id = isset($_POST['device_id']) ? trim($_POST['device_id']) : null;
$device_name = isset($_POST['device_name']) ? trim($_POST['device_name']) : '';

if (!$user_id || !$device_id) {
    echo json_encode(['status' => 'error', 'msg' => 'Missing user_id or device_id']);
    exit;
}

$uidInt = (int) $user_id;
$empCodePattern = "EMP" . str_pad((string) $uidInt, 3, '0', STR_PAD_LEFT);

// Step 1: Fetch global device limit
$device_limit = 1;
$resLimit = $con->query("SELECT setting_value FROM attendance_settings WHERE setting_key = 'device_limit'");
if ($resLimit && $r = $resLimit->fetch_assoc()) {
    $device_limit = (int) $r['setting_value'];
}
if ($device_limit < 1)
    $device_limit = 1;

// Step 2: Check if this device is already registered to another employee
$checkStmt = $con->prepare(
    "SELECT user_id FROM employee_devices WHERE device_id = ? AND user_id <> ? LIMIT 1"
);
$checkStmt->bind_param("si", $device_id, $uidInt);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult && $checkResult->num_rows > 0) {
    echo json_encode([
        'status' => 'error',
        'msg' => 'This device is already registered with another employee.'
    ]);
    $checkStmt->close();
    exit;
}
$checkStmt->close();

// Step 3: Check current employee's status and existing devices
$empStmt = $con->prepare(
    "SELECT user_id FROM employees WHERE (emp_code = ? OR user_id = ?) AND status = 1 LIMIT 1"
);
$empStmt->bind_param("si", $empCodePattern, $uidInt);
$empStmt->execute();
$empResult = $empStmt->get_result();

if (!$empResult || $empResult->num_rows === 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Employee not found or inactive']);
    $empStmt->close();
    exit;
}
$emp = $empResult->fetch_assoc();
$empId = (int) $emp['user_id'];
$empStmt->close();

// Step 4: Check if this device is already registered for THIS employee
$ownStmt = $con->prepare("SELECT id FROM employee_devices WHERE user_id = ? AND device_id = ?");
$ownStmt->bind_param("is", $empId, $device_id);
$ownStmt->execute();
if ($ownStmt->get_result()->num_rows > 0) {
    echo json_encode(['status' => 'success', 'msg' => 'Device already registered']);
    $ownStmt->close();
    exit;
}
$ownStmt->close();

// Step 5: Check if employee has reached the device limit
$countStmt = $con->prepare("SELECT COUNT(*) as c FROM employee_devices WHERE user_id = ?");
$countStmt->bind_param("i", $empId);
$countStmt->execute();
$currentCount = 0;
if ($resCount = $countStmt->get_result()->fetch_assoc()) {
    $currentCount = (int) $resCount['c'];
}
$countStmt->close();

if ($currentCount >= $device_limit) {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Maximum device limit reached (' . $device_limit . '). Please contact HR to add/reset devices.'
    ]);
    exit;
}

// Step 6: Register the new device
$insStmt = $con->prepare("INSERT INTO employee_devices (user_id, device_id) VALUES (?, ?)");
$insStmt->bind_param("is", $empId, $device_id);

if ($insStmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'msg' => 'Device registered successfully. (' . ($currentCount + 1) . '/' . $device_limit . ')'
    ]);
} else {
    echo json_encode(['status' => 'error', 'msg' => 'Failed to register device: ' . $con->error]);
}
$insStmt->close();
$con->close();
?>