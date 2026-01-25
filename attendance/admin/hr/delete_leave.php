<?php
include '../config/db.php';

$leave_id = isset($_POST['leave_id']) ? (int) $_POST['leave_id'] : 0;

if ($leave_id > 0) {
    // Get leave details
    $stmt = $con->prepare("SELECT user_id, from_date, to_date FROM leave_applications WHERE id = ?");
    $stmt->bind_param('i', $leave_id);
    $stmt->execute();
    $stmt->bind_result($emp_id, $from_date, $to_date);
    if ($stmt->fetch()) {
        $stmt->close();

        // Delete from leave_applications
        $delLeave = $con->prepare("DELETE FROM leave_applications WHERE id = ?");
        $delLeave->bind_param('i', $leave_id);
        $delLeave->execute();
        $delLeave->close();

        // Delete from attendance_logs for all dates in range
        $start = new DateTime($from_date);
        $end = new DateTime($to_date);
        $end->modify('+1 day');
        for ($d = $start; $d < $end; $d->modify('+1 day')) {
            $dateStr = $d->format('Y-m-d');
            $delLog = $con->prepare("DELETE FROM attendance_logs WHERE user_id = ? AND type = 'leave' AND DATE(time) = ?");
            $emp_id_str = (string) $emp_id;
            $delLog->bind_param('ss', $emp_id_str, $dateStr);
            $delLog->execute();
            $delLog->close();
        }
        echo json_encode(['status' => 'success', 'msg' => 'Leave and attendance logs deleted.']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Leave not found.']);
    }
} else {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid leave id.']);
}
$con->close();
