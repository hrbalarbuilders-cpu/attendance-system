<?php
// Process leave approval or rejection
// CRITICAL: Authentication required for security
include '../includes/auth_check.php';
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_id = isset($_POST['leave_id']) ? (int) $_POST['leave_id'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($leave_id > 0 && in_array($action, ['approve', 'reject'])) {
        $new_status = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $con->prepare("UPDATE leave_applications SET status = ? WHERE id = ? AND status = 'pending'");
        $stmt->bind_param('si', $new_status, $leave_id);
        $stmt->execute();
        $stmt->close();

        // If approved, mark attendance for each date in the leave range
        if ($action === 'approve') {
            // Get leave details
            $leaveQ = $con->prepare("SELECT user_id, from_date, to_date FROM leave_applications WHERE id = ?");
            $leaveQ->bind_param('i', $leave_id);
            $leaveQ->execute();
            $leaveQ->bind_result($emp_id, $from_date, $to_date);
            if ($leaveQ->fetch()) {
                $leaveQ->close();
                $start = new DateTime($from_date);
                $end = new DateTime($to_date);
                $end->modify('+1 day'); // include end date
                for ($d = $start; $d < $end; $d->modify('+1 day')) {
                    $dateStr = $d->format('Y-m-d');
                    // Always use the actual employee id as user_id
                    $user_id = (string) $emp_id;
                    // Check if already marked
                    $check = $con->prepare("SELECT id FROM attendance_logs WHERE user_id = ? AND DATE(time) = ?");
                    $check->bind_param('ss', $user_id, $dateStr);
                    $check->execute();
                    $check->store_result();
                    if ($check->num_rows == 0) {
                        $check->close();
                        $ins = $con->prepare("INSERT INTO attendance_logs (user_id, type, reason, time, device_id, synced) VALUES (?, 'leave', 'leave', ?, '', 1)");
                        $ins->bind_param('ss', $user_id, $dateStr);
                        $ins->execute();
                        $ins->close();
                    } else {
                        $check->close();
                    }
                }
            } else {
                $leaveQ->close();
            }
        }
    }
}
$con->close();
header('Location: leaves.php?status=success');
exit;
