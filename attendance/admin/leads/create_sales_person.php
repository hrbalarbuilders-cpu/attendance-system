<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/db.php';

$empId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
if (!$empId) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

// Verify employee exists and is active
$check = $con->prepare("SELECT status FROM employees WHERE user_id = ? LIMIT 1");
if (!$check) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}
$check->bind_param('i', $empId);
$check->execute();
$res = $check->get_result();
if (!$res || $res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    $check->close();
    exit;
}
$emp = $res->fetch_assoc();
$check->close();
$empStatus = isset($emp['status']) ? (int) $emp['status'] : 0;
if ($empStatus !== 1) {
    echo json_encode(['success' => false, 'message' => 'Employee is not active']);
    exit;
}

// create table if missing
$con->query("CREATE TABLE IF NOT EXISTS sales_persons (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, status TINYINT DEFAULT 1, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL, UNIQUE KEY(user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$stmt = $con->prepare("INSERT INTO sales_persons (user_id, status) VALUES (?,1) ON DUPLICATE KEY UPDATE status=1");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'DB prepare failed']);
    exit;
}
$stmt->bind_param('i', $empId);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Execute failed']);
    $stmt->close();
    exit;
}
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Sales person saved']);
