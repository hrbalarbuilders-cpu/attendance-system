<?php
// Suppress errors from being sent to output, log them instead
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$date = isset($_GET['date']) ? trim($_GET['date']) : '';

if ($user_id <= 0 || empty($date)) {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Invalid parameters'
    ]);
    exit;
}

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Invalid date format'
    ]);
    exit;
}

try {
    // Get employee shift info
    $empCodePattern = "EMP" . str_pad((string)$user_id, 3, '0', STR_PAD_LEFT);
    $shiftStmt = $con->prepare("
        SELECT e.id, s.start_time, s.end_time, s.late_mark_after, s.lunch_start, s.lunch_end
        FROM employees e
        LEFT JOIN shifts s ON e.shift_id = s.id
        WHERE (e.emp_code = ? OR e.id = ?) AND e.status = 1
        LIMIT 1
    ");
    
    $shiftStmt->bind_param("si", $empCodePattern, $user_id);
    $shiftStmt->execute();
    $shiftResult = $shiftStmt->get_result();
    
    $shiftStartTime = null;
    $shiftEndTime = null;
    $lateMarkAfter = 30; // Default 30 minutes
    $lunchStartTime = null;
    $lunchEndTime = null;
    
    if ($shiftResult && $shiftResult->num_rows > 0) {
        $shiftRow = $shiftResult->fetch_assoc();
        $shiftStartTime = $shiftRow['start_time'];
        $shiftEndTime = $shiftRow['end_time'];
        $lateMarkAfter = (int)($shiftRow['late_mark_after'] ?? 30);
        $lunchStartTime = $shiftRow['lunch_start'] ?? null;
        $lunchEndTime = $shiftRow['lunch_end'] ?? null;
    }
    $shiftStmt->close();
    
    // Get attendance logs for the day
    $stmt = $con->prepare("
        SELECT type, time, reason
        FROM attendance_logs
        WHERE user_id = ? AND DATE(time) = ?
        ORDER BY time ASC
    ");
    
    $stmt->bind_param("is", $user_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    $stmt->close();
    
    if (empty($logs)) {
        echo json_encode([
            'status' => 'success',
            'data' => [
                'late_minutes' => 0,
                'gross_minutes' => 0,
                'effective_minutes' => 0,
                'break_minutes' => 0,
                'has_attendance' => false
            ]
        ]);
        exit;
    }
    
    // Calculate time metrics
    $grossMinutes = 0;
    $breakMinutes = 0;
    $lateMinutes = 0;
    
    // Calculate gross time (first IN to last OUT)
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
        // Normal case: we have a last OUT punch
        if ($lastOutTime) {
            $diff = $lastOutTime->diff($firstInTime);
            $grossMinutes = (int)$diff->format('%h') * 60 + (int)$diff->format('%i');
        }
        // If user forgot to clock out, approximate end at shift end time
        elseif ($shiftEndTime) {
            $shiftEnd = new DateTime($date . ' ' . $shiftEndTime);

            // Handle overnight shifts: if end is before/equal first in, move to next day
            if ($shiftEnd <= $firstInTime) {
                $shiftEnd->modify('+1 day');
            }

            // For today, don't go beyond "now"; for past days, use full shift end
            $now = new DateTime();
            $effectiveEnd = ($date === $now->format('Y-m-d') && $now < $shiftEnd)
                ? $now
                : $shiftEnd;

            if ($effectiveEnd > $firstInTime) {
                $diff = $effectiveEnd->diff($firstInTime);
                $grossMinutes = (int)$diff->format('%h') * 60 + (int)$diff->format('%i');
            }
        }
    }
    
    // Calculate break time (OUT to IN pairs)
    $hasExplicitLunch = false;
    for ($i = 0; $i < count($logs) - 1; $i++) {
        if ($logs[$i]['reason'] === 'lunch' || $logs[$i + 1]['reason'] === 'lunch') {
            $hasExplicitLunch = true;
        }

        if ($logs[$i]['type'] === 'out' && $logs[$i + 1]['type'] === 'in') {
            $outTime = new DateTime($logs[$i]['time']);
            $inTime = new DateTime($logs[$i + 1]['time']);
            $breakDuration = (int)$inTime->diff($outTime)->format('%i') + 
                           (int)$inTime->diff($outTime)->format('%h') * 60;
            $breakMinutes += $breakDuration;
        }
    }

    // Auto-deduct fixed lunch break based on shift timings when:
    // - Shift has lunch_start and lunch_end configured
    // - User worked across the full lunch window (first IN before lunch_start and last OUT after lunch_end)
    // - No explicit lunch punches are present for the day (to avoid double counting)
    if ($firstInTime && $lastOutTime && $lunchStartTime && $lunchEndTime && !$hasExplicitLunch) {
        $lunchStart = new DateTime($date . ' ' . $lunchStartTime);
        $lunchEnd = new DateTime($date . ' ' . $lunchEndTime);

        // Handle overnight lunch window (rare, but for completeness)
        if ($lunchEnd <= $lunchStart) {
            $lunchEnd->modify('+1 day');
        }

        if ($firstInTime <= $lunchStart && $lastOutTime >= $lunchEnd) {
            $lunchDiff = $lunchEnd->diff($lunchStart);
            $lunchMinutes = (int)$lunchDiff->format('%i') + (int)$lunchDiff->format('%h') * 60;
            $breakMinutes += $lunchMinutes;
        }
    }
    
    // Calculate effective time (gross - break)
    $effectiveMinutes = max(0, $grossMinutes - $breakMinutes);
    
    // Calculate late time
    if ($shiftStartTime && $firstInTime) {
        $shiftStart = new DateTime($date . ' ' . $shiftStartTime);
        $gracePeriod = clone $shiftStart;
        $gracePeriod->modify("+{$lateMarkAfter} minutes");
        
        if ($firstInTime > $gracePeriod) {
            $lateMinutes = (int)$firstInTime->diff($shiftStart)->format('%i') + 
                          (int)$firstInTime->diff($shiftStart)->format('%h') * 60;
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'late_minutes' => $lateMinutes,
            'gross_minutes' => $grossMinutes,
            'effective_minutes' => $effectiveMinutes,
            'break_minutes' => $breakMinutes,
            'has_attendance' => true
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Server error'
    ]);
}
