<?php
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');

include "db.php";

// Get user_id from GET parameter
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($user_id <= 0) {
    echo json_encode([
        "status" => "error",
        "msg" => "Invalid user_id"
    ]);
    exit;
}

       // Get employee by user_id
       // Logic: user_id can be numeric from emp_code (EMP001 -> 1) or direct employee id
       // Use simple approach: try emp_code pattern and employee id
       $empCodePattern = "EMP" . str_pad($user_id, 3, '0', STR_PAD_LEFT);

       $stmt = $con->prepare("
           SELECT e.id, e.shift_id, e.status, 
                  s.shift_name, s.start_time, s.end_time, s.total_punches,
                  e.default_working_from,
                  w.label AS working_from_label,
                  s.early_clock_in_before, s.late_mark_after, s.half_day_after,
                  s.lunch_start, s.lunch_end
           FROM employees e
               LEFT JOIN shifts s ON s.id = e.shift_id
               LEFT JOIN working_from_master w ON w.code = e.default_working_from
           WHERE (e.emp_code = ? OR e.id = ?) AND e.status = 1
           LIMIT 1
       ");

$stmt->bind_param("si", $empCodePattern, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // Double-check: Ensure employee status is active (1)
    if ((int)$row['status'] !== 1) {
        echo json_encode([
            "status" => "error",
            "msg" => "Account is inactive",
            "inactive" => true
        ]);
        $stmt->close();
        exit;
    }
    
    if ($row['shift_id'] && $row['shift_name']) {
        // Format time from HH:MM:SS to HH:MM
        $startTime = date('H:i', strtotime($row['start_time']));
        $endTime = date('H:i', strtotime($row['end_time']));
        
        // Format lunch times if available
        $lunchStartTime = null;
        $lunchEndTime = null;
        if (!empty($row['lunch_start'])) {
            $lunchStartTime = date('H:i', strtotime($row['lunch_start']));
        }
        if (!empty($row['lunch_end'])) {
            $lunchEndTime = date('H:i', strtotime($row['lunch_end']));
        }
        
        // Determine working_from label (if any)
        $workingFromLabel = '';
        if (!empty($row['working_from_label'])) {
            $workingFromLabel = $row['working_from_label'];
        }

        echo json_encode([
            "status" => "success",
            "shift_name" => $row['shift_name'],
            "start_time" => $startTime,
            "end_time" => $endTime,
            "working_from" => $workingFromLabel,
            "total_punches" => (int)($row['total_punches'] ?? 4),
            "early_clock_in_before" => (int)($row['early_clock_in_before'] ?? 0),
            "late_mark_after" => (int)($row['late_mark_after'] ?? 0),
            "half_day_after" => (int)($row['half_day_after'] ?? 0),
            "lunch_start" => $lunchStartTime,
            "lunch_end" => $lunchEndTime
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "msg" => "No shift assigned"
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "msg" => "Employee not found or account is inactive",
        "inactive" => true
    ]);
}

$stmt->close();
?>

