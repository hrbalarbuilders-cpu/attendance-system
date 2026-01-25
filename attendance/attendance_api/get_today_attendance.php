<?php
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');

include "db.php";

// Get user_id from GET parameter
$user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d'); // Default to today

if ($user_id <= 0) {
    echo json_encode([
        "status" => "error",
        "msg" => "Invalid user_id"
    ]);
    exit;
}

// Get attendance logs for today
$startDateTime = $date . ' 00:00:00';
$endDateTime = $date . ' 23:59:59';

$stmt = $con->prepare("
    SELECT type, time, reason 
    FROM attendance_logs 
    WHERE user_id = ? AND time BETWEEN ? AND ?
    ORDER BY time ASC
");

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "msg" => "DB prepare error: " . $con->error
    ]);
    exit;
}

$stmt->bind_param("iss", $user_id, $startDateTime, $endDateTime);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
$clockInTime = null;
$clockOutTime = null;
$lastPunchType = null; // Track the type of the very last punch

// For effective/gross/break/late minutes (reuse logic from get_day_summary)
$grossMinutes = 0;
$breakMinutes = 0;
$lateMinutes = 0;

while ($row = $result->fetch_assoc()) {
    $logs[] = $row;

    // Get first clock in and last clock out
    if ($row['type'] == 'in' && $clockInTime == null) {
        $clockInTime = $row['time'];
    }
    if ($row['type'] == 'out') {
        $clockOutTime = $row['time'];
    }
    $lastPunchType = $row['type']; // Update last punch type with each log
}

// If we have logs, compute gross/break/effective/late minutes similar to get_day_summary
if (!empty($logs)) {
    // Get employee shift info for late calculation and shift end
    $empCodePattern = "EMP" . str_pad((string) $user_id, 3, '0', STR_PAD_LEFT);
    $shiftStmt = $con->prepare("
        SELECT e.user_id, s.start_time, s.end_time, s.late_mark_after
        FROM employees e
        LEFT JOIN shifts s ON e.shift_id = s.id
        WHERE (e.emp_code = ? OR e.user_id = ?) AND e.status = 1
        LIMIT 1
    ");

    $shiftStartTime = null;
    $shiftEndTime = null;
    $lateMarkAfter = 30; // default

    if ($shiftStmt) {
        $shiftStmt->bind_param("si", $empCodePattern, $user_id);
        $shiftStmt->execute();
        $shiftResult = $shiftStmt->get_result();
        if ($shiftResult && $shiftResult->num_rows > 0) {
            $shiftRow = $shiftResult->fetch_assoc();
            $shiftStartTime = $shiftRow['start_time'];
            $shiftEndTime = $shiftRow['end_time'];
            $lateMarkAfter = isset($shiftRow['late_mark_after'])
                ? (int) $shiftRow['late_mark_after']
                : 30;
        }
        $shiftStmt->close();
    }

    // Gross time (first IN to last OUT)
    $firstInTime = null;
    $lastOutTime = null;
    foreach ($logs as $log) {
        if ($log['type'] === 'in' && $firstInTime === null) {
            $firstInTime = new DateTime($log['time']);
        }
        if ($log['type'] === 'out') {
            $lastOutTime = new DateTime($log['time']);
        }
    }

    if ($firstInTime) {
        if ($lastOutTime) {
            // Normal case: we have a final OUT
            $diff = $lastOutTime->diff($firstInTime);
            $grossMinutes = (int) $diff->format('%h') * 60 + (int) $diff->format('%i');
        } elseif ($shiftEndTime) {
            // No clock-out yet: approximate end at min(shift end, now)
            $shiftEnd = new DateTime($date . ' ' . $shiftEndTime);
            $now = new DateTime();
            $effectiveEnd = $now < $shiftEnd ? $now : $shiftEnd;

            if ($effectiveEnd > $firstInTime) {
                $diff = $effectiveEnd->diff($firstInTime);
                $grossMinutes = (int) $diff->format('%h') * 60 + (int) $diff->format('%i');
            }
        }
    }

    // Break time (OUT to IN pairs)
    for ($i = 0; $i < count($logs) - 1; $i++) {
        if ($logs[$i]['type'] === 'out' && $logs[$i + 1]['type'] === 'in') {
            $outTime = new DateTime($logs[$i]['time']);
            $inTime = new DateTime($logs[$i + 1]['time']);
            $diff = $inTime->diff($outTime);
            $breakMinutes += (int) $diff->format('%h') * 60 + (int) $diff->format('%i');
        }
    }

    // Late time
    if ($shiftStartTime && $firstInTime) {
        $shiftStart = new DateTime($date . ' ' . $shiftStartTime);
        $gracePeriod = clone $shiftStart;
        $gracePeriod->modify("+{$lateMarkAfter} minutes");

        if ($firstInTime > $gracePeriod) {
            $diff = $firstInTime->diff($shiftStart);
            $lateMinutes = (int) $diff->format('%h') * 60 + (int) $diff->format('%i');
        }
    }
}

// Format times (extract HH:MM from datetime)
$clockInFormatted = null;
$clockOutFormatted = null;

if ($clockInTime) {
    $parts = explode(' ', $clockInTime);
    if (count($parts) >= 2) {
        $timePart = explode(':', $parts[1]);
        if (count($timePart) >= 2) {
            $clockInFormatted = $timePart[0] . ':' . $timePart[1];
        }
    }
}

if ($clockOutTime) {
    $parts = explode(' ', $clockOutTime);
    if (count($parts) >= 2) {
        $timePart = explode(':', $parts[1]);
        if (count($timePart) >= 2) {
            $clockOutFormatted = $timePart[0] . ':' . $timePart[1];
        }
    }
}

// Count total punches today
$totalPunchesToday = count($logs);

// Effective minutes = gross - break (never negative)
$effectiveMinutes = max(0, $grossMinutes - $breakMinutes);

echo json_encode([
    "status" => "success",
    "clock_in" => $clockInFormatted,
    "clock_out" => $clockOutFormatted,
    "total_punches_today" => $totalPunchesToday,
    "last_punch_type" => $lastPunchType, // Added last punch type
    "logs" => $logs,
    "gross_minutes" => $grossMinutes,
    "effective_minutes" => $effectiveMinutes,
    "break_minutes" => $breakMinutes,
    "late_minutes" => $lateMinutes
]);

$stmt->close();
?>