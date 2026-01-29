<?php
// delete_attendance_log.php - Clean JSON output version
// CRITICAL: Authentication required for security
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

date_default_timezone_set('Asia/Kolkata');
include '../includes/auth_check.php';
include '../config/db.php';

$output = ['success' => false, 'message' => 'Unknown error'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_id = isset($_POST['log_id']) ? (int) $_POST['log_id'] : 0;

    if ($log_id > 0) {
        $stmt = $con->prepare("DELETE FROM attendance_logs WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $log_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $output = ['success' => true, 'message' => 'Attendance log deleted successfully'];
                } else {
                    $output = ['success' => false, 'message' => 'Log not found or already deleted'];
                }
            } else {
                $output = ['success' => false, 'message' => 'Database execution error: ' . $con->error];
            }
        } else {
            $output = ['success' => false, 'message' => 'Query preparation failed: ' . $con->error];
        }
    } else {
        $output = ['success' => false, 'message' => 'Invalid or missing Log ID'];
    }
} else {
    $output = ['success' => false, 'message' => 'Invalid request method'];
}

// Clear any accidental output (like warnings from db.php)
ob_clean();
header('Content-Type: application/json');
echo json_encode($output);
exit;
?>