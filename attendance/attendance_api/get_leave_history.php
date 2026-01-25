<?php
// API: get_leave_history.php
// Returns leave application history for an employee
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include 'db.php';

$emp_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : (isset($_GET['emp_id']) ? (int) $_GET['emp_id'] : 0);
if ($emp_id <= 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Missing or invalid employee id']);
    exit;
}

$sql = "SELECT la.id, lt.name AS leave_type, la.from_date, la.to_date, la.reason, la.status, la.created_at
        FROM leave_applications la
        JOIN leave_types lt ON la.leave_type_id = lt.id
        WHERE la.user_id = ?
        ORDER BY la.created_at DESC";
$stmt = $con->prepare($sql);
$stmt->bind_param('i', $emp_id);
$stmt->execute();
$result = $stmt->get_result();
$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}
$stmt->close();
$con->close();
echo json_encode(['status' => 'success', 'history' => $history]);
