<?php
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate'); // Prevent caching

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "msg" => "Invalid request method"]);
    exit;
}

include "db.php";

// Read POST values from Flutter
$user_id      = isset($_POST['user_id'])      ? trim($_POST['user_id'])      : null;
$type         = isset($_POST['type'])         ? trim($_POST['type'])         : null;
$time         = isset($_POST['time'])         ? trim($_POST['time'])         : null;
$device_id    = isset($_POST['device_id'])    ? trim($_POST['device_id'])    : null;
$lat          = isset($_POST['lat'])          ? trim($_POST['lat'])          : null;
$lng          = isset($_POST['lng'])          ? trim($_POST['lng'])          : null;
$working_from = isset($_POST['working_from']) ? trim($_POST['working_from']) : '';
$reason       = isset($_POST['reason'])       ? trim($_POST['reason'])       : 'shift_start';

// Basic validation
if (!$user_id || !$type || !$time || !$device_id) {
    echo json_encode(["status" => "error", "msg" => "Missing parameters"]);
    exit;
}

// Validate type
if ($type !== 'in' && $type !== 'out') {
    echo json_encode(["status" => "error", "msg" => "Invalid type. Must be 'in' or 'out'"]);
    exit;
}

// Validate user_id is numeric
if (!is_numeric($user_id)) {
    echo json_encode(["status" => "error", "msg" => "Invalid user_id"]);
    exit;
}

// Validate reason (must be one of the allowed values)
$allowedReasons = ['lunch', 'tea', 'shift_start', 'shift_end'];
if (!in_array($reason, $allowedReasons, true)) {
    $reason = 'shift_start'; // Default to shift_start if invalid
}

// ---------------- DEVICE LOCK LOGIC ----------------
// 1) Per-employee: if this employee already has a different device_id, block.
// 2) Global: if this device_id is already registered to some OTHER employee, block.

 $empCodePattern = "EMP" . str_pad((string)intval($user_id), 3, '0', STR_PAD_LEFT);
 $uidInt = (int)$user_id;

// Track the actual employee row id that this user_id maps to.
// This avoids treating the same employee as "another employee" in the global check
// when user_id is derived from emp_code (e.g., EMP006) but employees.id is different.
$currentEmployeeId = $uidInt;
$defaultWorkingFrom = '';

// Step 1: check current employee's registered device (if any) and ensure Working From is assigned
$checkStmt = $con->prepare(
    "SELECT id, emp_code, device_id, default_working_from
     FROM employees
     WHERE (emp_code = ? OR id = ?) AND status = 1
     LIMIT 1"
);

if ($checkStmt) {
    $checkStmt->bind_param("si", $empCodePattern, $uidInt);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult && $checkResult->num_rows > 0) {
        $empRow = $checkResult->fetch_assoc();
        $currentEmployeeId = (int)$empRow['id'];
        $registeredDeviceId = $empRow['device_id'];
        $defaultWorkingFrom = trim((string)($empRow['default_working_from'] ?? ''));

        // If this employee already has a different device registered, reject
        if (!empty($registeredDeviceId) && $registeredDeviceId !== $device_id) {
            echo json_encode([
                "status" => "error",
                "msg" => "Device not registered for this employee. Please contact administrator."
            ]);
            $checkStmt->close();
            exit;
        }

        // Do not allow clock in/out if no Working From is assigned
        if ($defaultWorkingFrom === '') {
            echo json_encode([
                "status" => "error",
                "msg" => "Working From is not assigned. Please contact administrator."
            ]);
            $checkStmt->close();
            exit;
        }
    } else {
        // No active employee found for this user_id
        echo json_encode([
            "status" => "error",
            "msg" => "Employee not found or inactive. Please contact administrator."
        ]);
        $checkStmt->close();
        exit;
    }

    $checkStmt->close();
}

// Step 2: ensure this device_id is not registered to any OTHER active employee
$deviceStmt = $con->prepare(
    "SELECT id, emp_code
     FROM employees
     WHERE device_id = ? AND status = 1 AND id <> ?
     LIMIT 1"
);

if ($deviceStmt) {
    $deviceStmt->bind_param("si", $device_id, $currentEmployeeId);
    $deviceStmt->execute();
    $deviceResult = $deviceStmt->get_result();

    if ($deviceResult && $deviceResult->num_rows > 0) {
        echo json_encode([
            "status" => "error",
            "msg" => "This device is already registered with another employee. You cannot clock in from this device."
        ]);
        $deviceStmt->close();
        exit;
    }

    $deviceStmt->close();
}

// ---------------- FIRST CLOCK-IN / SHIFT END VALIDATION ----------------

// Do not allow the *first* clock-in of the day after the shift end time
if ($type === 'in') {
    $timestamp = strtotime($time);

    if ($timestamp !== false) {
        $currentDate = date('Y-m-d', $timestamp);

        // Check if this user already has a clock-in for this date
        $firstInStmt = $con->prepare(
            "SELECT id FROM attendance_logs WHERE user_id = ? AND type = 'in' AND DATE(time) = ? LIMIT 1"
        );

        if ($firstInStmt) {
            $firstInStmt->bind_param("is", $user_id, $currentDate);
            $firstInStmt->execute();
            $firstInResult = $firstInStmt->get_result();

            $isFirstClockInToday = $firstInResult && $firstInResult->num_rows === 0;
            $firstInStmt->close();

            if ($isFirstClockInToday) {
                // Fetch shift start/end time for this employee
                $empCodeForShift = "EMP" . str_pad((string)intval($user_id), 3, '0', STR_PAD_LEFT);
                $uidIntForShift = (int)$user_id;

                $shiftStmt = $con->prepare("
                    SELECT s.start_time, s.end_time
                    FROM employees e
                    LEFT JOIN shifts s ON s.id = e.shift_id
                    WHERE (e.emp_code = ? OR e.id = ?) AND e.status = 1
                    LIMIT 1
                ");

                if ($shiftStmt) {
                    $shiftStmt->bind_param("si", $empCodeForShift, $uidIntForShift);
                    $shiftStmt->execute();
                    $shiftResult = $shiftStmt->get_result();

                    if ($shiftResult && $shiftResult->num_rows > 0) {
                        $shiftRow = $shiftResult->fetch_assoc();

                        if (!empty($shiftRow['end_time'])) {
                            $shiftEndTimestamp = strtotime($currentDate . ' ' . $shiftRow['end_time']);

                            // Handle overnight shifts (end before or equal start -> next day)
                            if (!empty($shiftRow['start_time'])) {
                                $shiftStartTimestamp = strtotime($currentDate . ' ' . $shiftRow['start_time']);
                                if ($shiftEndTimestamp !== false && $shiftStartTimestamp !== false && $shiftEndTimestamp <= $shiftStartTimestamp) {
                                    $shiftEndTimestamp += 24 * 60 * 60; // add 1 day
                                }
                            }

                            // If the current time is after the (possibly adjusted) shift end time, block first clock-in
                            if ($shiftEndTimestamp !== false && $timestamp > $shiftEndTimestamp) {
                                echo json_encode([
                                    "status" => "error",
                                    "msg" => "Cannot clock in. Your shift has already ended for today."
                                ]);
                                $shiftStmt->close();
                                exit;
                            }
                        }
                    }

                    $shiftStmt->close();
                }
            }
        }
    }
}

// If app did not explicitly send working_from, fall back to the assigned default
if ($working_from === '') {
    $working_from = $defaultWorkingFrom;
}

// ---------------- INSERT ATTENDANCE LOG ----------------

// Use prepared statements for security and performance
$stmt = $con->prepare("INSERT INTO attendance_logs
        (user_id, type, time, device_id, latitude, longitude, working_from, reason, synced)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "msg"    => "DB prepare error: " . $con->error
    ]);
    exit;
}

// Convert lat/lng to float or NULL
$latFloat = ($lat !== null && $lat !== '') ? (float)$lat : null;
$lngFloat = ($lng !== null && $lng !== '') ? (float)$lng : null;

// Bind parameters
$stmt->bind_param("isssddss",
    $user_id,      // i = integer
    $type,         // s = string
    $time,         // s = string
    $device_id,    // s = string
    $latFloat,     // d = double
    $lngFloat,     // d = double
    $working_from, // s = string
    $reason        // s = string
);

if ($stmt->execute()) {
    // On first successful clock, auto-register device_id for the employee if missing
    $empCodePattern = "EMP" . str_pad((string)intval($user_id), 3, '0', STR_PAD_LEFT);
    $upd = $con->prepare(
        "UPDATE employees
         SET device_id = ?
         WHERE (emp_code = ? OR id = ?)
           AND (device_id IS NULL OR device_id = '')
         LIMIT 1"
    );
    if ($upd) {
        $uidInt = (int)$user_id;
        $upd->bind_param("ssi", $device_id, $empCodePattern, $uidInt);
        $upd->execute();
        $upd->close();
    }

    echo json_encode(["status" => "success"]);
    exit;
} else {
    echo json_encode([
        "status" => "error",
        "msg"    => "DB error: " . $stmt->error
    ]);
    exit;
}

$stmt->close();
?>
