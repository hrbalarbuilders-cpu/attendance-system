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
$user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : null;
$type = isset($_POST['type']) ? trim($_POST['type']) : null;
$time = isset($_POST['time']) ? trim($_POST['time']) : null;
$device_id = isset($_POST['device_id']) ? trim($_POST['device_id']) : null;
$lat = isset($_POST['lat']) ? trim($_POST['lat']) : null;
$lng = isset($_POST['lng']) ? trim($_POST['lng']) : null;
$working_from = isset($_POST['working_from']) ? trim($_POST['working_from']) : '';
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : 'shift_start';
$is_auto = isset($_POST['is_auto']) ? (int) $_POST['is_auto'] : 0;

// Basic validation
if (!$user_id || !$type || !$time || !$device_id) {
    echo json_encode(["status" => "error", "msg" => "Missing parameters"]);
    exit;
}

// ---------------- IP ADDRESS RESTRICTION ----------------
$ipSettings = [];
$resSettings = $con->query("SELECT setting_key, setting_value FROM attendance_settings WHERE setting_key IN ('ip_restriction_enabled', 'allowed_ips')");
if ($resSettings) {
    while ($row = $resSettings->fetch_assoc()) {
        $ipSettings[$row['setting_key']] = $row['setting_value'];
    }
}

if (($ipSettings['ip_restriction_enabled'] ?? '0') === '1') {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $allowedIpsRaw = $ipSettings['allowed_ips'] ?? '';
    $allowedIps = array_filter(array_map('trim', explode("\n", str_replace("\r", "", $allowedIpsRaw))));

    if (!empty($allowedIps) && !in_array($clientIp, $allowedIps)) {
        echo json_encode([
            "status" => "error",
            "msg" => "Access denied. IP " . $clientIp . " is not authorized for attendance."
        ]);
        exit;
    }
}

// Ensure is_auto column exists in attendance_logs
$resCol = $con->query("SHOW COLUMNS FROM attendance_logs LIKE 'is_auto'");
if ($resCol && $resCol->num_rows === 0) {
    $con->query("ALTER TABLE attendance_logs ADD COLUMN is_auto TINYINT(1) DEFAULT 0 AFTER synced");
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

$empCodePattern = "EMP" . str_pad((string) intval($user_id), 3, '0', STR_PAD_LEFT);
$uidInt = (int) $user_id;

// Track the actual employee row id that this user_id maps to.
// This avoids treating the same employee as "another employee" in the global check
// when user_id is derived from emp_code (e.g., EMP006) but employees.user_id is different.
$currentEmployeeId = $uidInt;
$defaultWorkingFrom = '';

// Step 1: check current employee's registered device (if any) and ensure Working From is assigned
$checkStmt = $con->prepare(
    "SELECT user_id, emp_code, device_id, default_working_from
     FROM employees
     WHERE (emp_code = ? OR user_id = ?) AND status = 1
     LIMIT 1"
);

if ($checkStmt) {
    $checkStmt->bind_param("si", $empCodePattern, $uidInt);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult && $checkResult->num_rows > 0) {
        $empRow = $checkResult->fetch_assoc();
        $currentEmployeeId = (int) $empRow['user_id'];
        $registeredDeviceId = $empRow['device_id'];
        $defaultWorkingFrom = trim((string) ($empRow['default_working_from'] ?? ''));

        // Check if this device is registered for this employee
        $devStmt = $con->prepare("SELECT id FROM employee_devices WHERE user_id = ? AND device_id = ? LIMIT 1");
        $devStmt->bind_param("is", $currentEmployeeId, $device_id);
        $devStmt->execute();
        $isRegistered = $devStmt->get_result()->num_rows > 0;
        $devStmt->close();

        if (!$isRegistered) {
            echo json_encode([
                "status" => "error",
                "msg" => "Device not registered for this employee. Please contact administrator."
            ]);
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
    "SELECT user_id FROM employee_devices
     WHERE device_id = ? AND user_id <> ?
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
        // Use resolved $currentEmployeeId to match what is actually stored
        $firstInStmt = $con->prepare(
            "SELECT id FROM attendance_logs WHERE user_id = ? AND type = 'in' AND DATE(time) = ? LIMIT 1"
        );

        if ($firstInStmt) {
            $firstInStmt->bind_param("is", $currentEmployeeId, $currentDate);
            $firstInStmt->execute();
            $firstInResult = $firstInStmt->get_result();

            $isFirstClockInToday = $firstInResult && $firstInResult->num_rows === 0;
            $firstInStmt->close();

            if ($isFirstClockInToday) {
                // Fetch shift start/end time for this employee
                $empCodeForShift = "EMP" . str_pad((string) intval($user_id), 3, '0', STR_PAD_LEFT);
                $uidIntForShift = (int) $user_id;

                $shiftStmt = $con->prepare("
                    SELECT s.start_time, s.end_time
                    FROM employees e
                    LEFT JOIN shifts s ON s.id = e.shift_id
                    WHERE (e.emp_code = ? OR e.user_id = ?) AND e.status = 1
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
            } else {
                // User already has a clock-in today.
                // Prevent duplicate 'in' punches from the auto-heartbeat.
                // We return 'success' so the mobile app knows it's synced/safe.
                echo json_encode([
                    "status" => "success",
                    "msg" => "Already clocked in today."
                ]);
                exit;
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
        (user_id, type, time, device_id, latitude, longitude, working_from, reason, synced, is_auto)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "msg" => "DB prepare error: " . $con->error
    ]);
    exit;
}

// Convert lat/lng to float or NULL
$latFloat = ($lat !== null && $lat !== '') ? (float) $lat : null;
$lngFloat = ($lng !== null && $lng !== '') ? (float) $lng : null;

// Bind parameters
$stmt->bind_param(
    "isssddssi",
    $currentEmployeeId, // Use resolved DB ID, not raw input
    $type,         // s = string
    $time,         // s = string
    $device_id,    // s = string
    $latFloat,     // d = double
    $lngFloat,     // d = double
    $working_from, // s = string
    $reason,        // s = string
    $is_auto       // i = integer
);

if ($stmt->execute()) {
    // Device is already validated - if employee has no device, clock.php will block them
    // Device registration is now done explicitly via register_device.php
    echo json_encode(["status" => "success"]);
    exit;
} else {
    echo json_encode([
        "status" => "error",
        "msg" => "DB error: " . $stmt->error
    ]);
    exit;
}

$stmt->close();
?>