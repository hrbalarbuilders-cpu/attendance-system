<?php
// API: cancel_leave.php
// Cancels a leave application by setting its status to 'cancelled' if it is pending
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$leave_id = isset($data['leave_id']) ? (int)$data['leave_id'] : 0;

if ($leave_id <= 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Missing or invalid leave id']);
    exit;
}

$stmt = $con->prepare("UPDATE leave_applications SET status = 'cancelled' WHERE id = ? AND status = 'pending'");
$stmt->bind_param('i', $leave_id);
$stmt->execute();
if ($stmt->affected_rows > 0) {
    echo json_encode(['status' => 'success', 'msg' => 'Leave cancelled successfully']);
} else {
    echo json_encode(['status' => 'error', 'msg' => 'Unable to cancel leave. It may already be processed.']);
}
$stmt->close();
$con->close();
