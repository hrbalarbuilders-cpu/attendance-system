<?php
date_default_timezone_set('Asia/Kolkata');
header('Cache-Control: no-cache, must-revalidate'); // Prevent caching
include "db.php";

// Get user_id from GET parameter
$raw_user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';
$date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d'); // Default to today

if (!$raw_user_id) {
    echo json_encode([
        "status" => "error",
        "msg" => "Invalid user_id"
    ]);
    exit;
}

$response = [
    "status" => "success",
    "shift" => null,
    "attendance" => null,
    "wishes" => []
];

// 1. RESOLVE EMPLOYEE AND GET SHIFT DETAILS
$empCodePattern = "EMP" . str_pad($raw_user_id, 3, '0', STR_PAD_LEFT);
$stmt = $con->prepare("
    SELECT e.user_id as resolved_uid, e.shift_id, e.status, e.weekoff_days,
           s.shift_name, s.start_time, s.end_time, s.total_punches,
           e.default_working_from,
           w.label AS working_from_label
    FROM employees e
    LEFT JOIN shifts s ON s.id = e.shift_id
    LEFT JOIN working_from_master w ON w.code = e.default_working_from
    WHERE (e.emp_code = ? OR e.user_id = ?) AND e.status = 1
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("ss", $empCodePattern, $raw_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_id = (int) $row['resolved_uid']; // Use the PK for subsequent queries
        $response["shift"] = [
            "name" => $row['shift_name'] ?: 'No Shift',
            "start" => $row['start_time'] ? date('H:i', strtotime($row['start_time'])) : '09:00',
            "end" => $row['end_time'] ? date('H:i', strtotime($row['end_time'])) : '18:00',
            "working_from" => $row['working_from_label'] ?: ($row['default_working_from'] ?: ''),
            "weekoff_days" => $row['weekoff_days'] ?: ''
        ];
    } else {
        echo json_encode(["status" => "error", "msg" => "User disabled", "inactive" => true]);
        exit;
    }
    $stmt->close();
}

// 1.5 CHECK HOLIDAY
$holidayName = '';
$hStmt = $con->prepare("SELECT holiday_name FROM holidays WHERE holiday_date = ? LIMIT 1");
$hStmt->bind_param("s", $date);
$hStmt->execute();
$hRes = $hStmt->get_result();
if ($hRow = $hRes->fetch_assoc()) {
    $holidayName = $hRow['holiday_name'];
}
$hStmt->close();
$response["shift"]["holiday"] = $holidayName;

// 2. GET TODAY'S ATTENDANCE
$startDateTime = $date . ' 00:00:00';
$endDateTime = $date . ' 23:59:59';
$stmt = $con->prepare("
    SELECT type, time 
    FROM attendance_logs 
    WHERE user_id = ? AND time BETWEEN ? AND ?
    ORDER BY time ASC
");

if ($stmt) {
    $stmt->bind_param("iss", $user_id, $startDateTime, $endDateTime);
    $stmt->execute();
    $result = $stmt->get_result();
    $clockIn = null;
    $clockOut = null;
    $lastType = null;
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
        if ($row['type'] == 'in') {
            $clockIn = date('H:i', strtotime($row['time']));
            $clockOut = null; // Reset clock out if a new punch-in occurs
        }
        if ($row['type'] == 'out') {
            $clockOut = date('H:i', strtotime($row['time']));
        }
        $lastType = $row['type'];
    }

    $effective = 0;
    $breaks = 0;
    if (!empty($logs)) {
        for ($i = 0; $i < count($logs) - 1; $i++) {
            $start = new DateTime($logs[$i]['time']);
            $end = new DateTime($logs[$i + 1]['time']);
            $diff = $end->diff($start);
            $mins = ($diff->h * 60) + $diff->i;

            if ($logs[$i]['type'] == 'in' && $logs[$i + 1]['type'] == 'out') {
                $effective += $mins;
            } else if ($logs[$i]['type'] == 'out' && $logs[$i + 1]['type'] == 'in') {
                $breaks += $mins;
            }
        }
        // If currently clocked in, add time from last punch to now
        if ($lastType == 'in') {
            $start = new DateTime(end($logs)['time']);
            $now = new DateTime();
            if ($now->format('Y-m-d') == $date) {
                $diff = $now->diff($start);
                $effective += ($diff->h * 60) + $diff->i;
            }
        }
    }

    $firstClockIn = null;
    if (!empty($logs)) {
        foreach ($logs as $log) {
            if ($log['type'] == 'in') {
                $firstClockIn = date('H:i', strtotime($log['time']));
                break;
            }
        }
    }

    $response["attendance"] = [
        "clock_in" => $clockIn,
        "first_clock_in" => $firstClockIn,
        "clock_out" => $clockOut,
        "last_punch_type" => $lastType,
        "effective_minutes" => $effective,
        "break_minutes" => $breaks
    ];
    $stmt->close();
}

// 3. GET WISHES (Next 7 days)
try {
    $windowDays = 7;
    $sql = "SELECT user_id, name, dob, joining_date FROM employees WHERE status = 1";
    $res = $con->query($sql);
    $today = new DateTime('today');
    $currentYear = (int) $today->format('Y');

    while ($row = $res->fetch_assoc()) {
        $empId = (int) $row['user_id'];
        $name = $row['name'];

        // Birthday
        if (!empty($row['dob']) && $row['dob'] !== '0000-00-00') {
            $dob = DateTime::createFromFormat('Y-m-d', $row['dob']);
            if ($dob) {
                $mmdd = $dob->format('m-d');
                $occurrence = DateTime::createFromFormat('Y-m-d', $currentYear . '-' . $mmdd);
                if ($occurrence < $today)
                    $occurrence->modify('+1 year');
                $daysUntil = (int) $today->diff($occurrence)->format('%a');
                if ($daysUntil <= $windowDays) {
                    $response["wishes"][] = [
                        'id' => $empId,
                        'name' => $name,
                        'type' => 'birthday',
                        'date' => $occurrence->format('Y-m-d'),
                        'days_until' => $daysUntil,
                    ];
                }
            }
        }

        // Anniversary
        if (!empty($row['joining_date']) && $row['joining_date'] !== '0000-00-00') {
            $jd = DateTime::createFromFormat('Y-m-d', $row['joining_date']);
            if ($jd) {
                $mmdd = $jd->format('m-d');
                $occurrence = DateTime::createFromFormat('Y-m-d', $currentYear . '-' . $mmdd);
                if ($occurrence < $today)
                    $occurrence->modify('+1 year');
                $daysUntil = (int) $today->diff($occurrence)->format('%a');
                if ($daysUntil <= $windowDays) {
                    $years = (int) $occurrence->format('Y') - (int) $jd->format('Y');
                    $response["wishes"][] = [
                        'id' => $empId,
                        'name' => $name,
                        'type' => 'anniversary',
                        'date' => $occurrence->format('Y-m-d'),
                        'days_until' => $daysUntil,
                        'years' => max(0, $years),
                    ];
                }
            }
        }
    }
    // Sort wishes
    usort($response["wishes"], function ($a, $b) {
        if ($a['days_until'] === $b['days_until'])
            return strcasecmp($a['name'], $b['name']);
        return $a['days_until'] <=> $b['days_until'];
    });
} catch (Throwable $e) {
}

// 4. GET WEEKLY LOG
try {
    $currentDate = new DateTime($date);
    $dayOfWeek = (int) $currentDate->format('w'); // 0 (Sun) to 6 (Sat)
    $startOfWeek = clone $currentDate;
    $startOfWeek->modify("-{$dayOfWeek} days");

    $weeklyLogs = [];
    for ($i = 0; $i < 7; $i++) {
        $loopDate = clone $startOfWeek;
        $loopDate->modify("+{$i} days");
        $loopDateStr = $loopDate->format('Y-m-d');

        $dayStart = $loopDateStr . ' 00:00:00';
        $dayEnd = $loopDateStr . ' 23:59:59';

        $stmt = $con->prepare("SELECT type, time FROM attendance_logs WHERE user_id = ? AND time BETWEEN ? AND ? ORDER BY time ASC");
        $stmt->bind_param("iss", $user_id, $dayStart, $dayEnd);
        $stmt->execute();
        $res = $stmt->get_result();

        $logs = [];
        while ($row = $res->fetch_assoc())
            $logs[] = $row;
        $stmt->close();

        $gross = 0;
        $effective = 0;
        $break = 0;

        if (!empty($logs)) {
            $firstIn = null;
            $lastOut = null;
            foreach ($logs as $l) {
                if ($l['type'] === 'in' && $firstIn === null)
                    $firstIn = new DateTime($l['time']);
                if ($l['type'] === 'out')
                    $lastOut = new DateTime($l['time']);
            }

            if ($firstIn && $lastOut) {
                $diff = $lastOut->diff($firstIn);
                $gross = ($diff->h * 60) + $diff->i;
            }

            for ($j = 0; $j < count($logs) - 1; $j++) {
                if ($logs[$j]['type'] === 'out' && $logs[$j + 1]['type'] === 'in') {
                    $o = new DateTime($logs[$j]['time']);
                    $i_ = new DateTime($logs[$j + 1]['time']);
                    $d = $i_->diff($o);
                    $break += ($d->h * 60) + $d->i;
                }
            }
            $effective = max(0, $gross - $break);
        }

        $weeklyLogs[$loopDateStr] = [
            "gross" => $gross,
            "effective" => $effective,
            "break" => $break
        ];
    }
    $response["weekly_log"] = $weeklyLogs;
} catch (Throwable $e) {
}

echo json_encode($response);
?>