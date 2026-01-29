<?php
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate'); // Prevent caching

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "msg" => "Invalid request method"]);
    exit;
}

include "db.php";

/**
 * Calculate distance between two points in meters using Haversine formula
 */
function haversineDistance($lat1, $lon1, $lat2, $lon2)
{
    if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null)
        return 9999999;
    $earthRadius = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

/**
 * Check if a point is inside a polygon using ray-casting algorithm
 * Polygon is expected as GeoJSON array of [longitude, latitude]
 */
function isPointInPolygon($pointLat, $pointLng, $polygonJson)
{
    if (empty($polygonJson))
        return false;
    $vertices = json_decode($polygonJson, true);
    if (!is_array($vertices) || count($vertices) < 3)
        return false;

    $intersections = 0;
    $verticesCount = count($vertices);

    for ($i = 0; $i < $verticesCount; $i++) {
        $j = ($i + 1) % $verticesCount;

        $v1Lng = $vertices[$i][0];
        $v1Lat = $vertices[$i][1];
        $v2Lng = $vertices[$j][0];
        $v2Lat = $vertices[$j][1];

        if (
            $v1Lat > $pointLat != $v2Lat > $pointLat &&
            $pointLng < ($v2Lng - $v1Lng) * ($pointLat - $v1Lat) / ($v2Lat - $v1Lat) + $v1Lng
        ) {
            $intersections++;
        }
    }

    return ($intersections % 2 != 0);
}

// Read POST values from Flutter
$user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : null;
$type = isset($_POST['type']) ? trim($_POST['type']) : null;
// CRITICAL: Do NOT trust device time. Use server time.
$time = date('Y-m-d H:i:s');
$device_id = isset($_POST['device_id']) ? trim($_POST['device_id']) : null;
$lat = isset($_POST['lat']) ? trim($_POST['lat']) : null;
$lng = isset($_POST['lng']) ? trim($_POST['lng']) : null;
$working_from = isset($_POST['working_from']) ? trim($_POST['working_from']) : '';
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : 'shift_start';
$is_auto = isset($_POST['is_auto']) ? (int) $_POST['is_auto'] : 0;
$is_mocked = isset($_POST['is_mocked']) ? (int) $_POST['is_mocked'] : 0;

// Basic validation (time is now generated on server)
if (!$user_id || !$type || !$device_id) {
    echo json_encode(["status" => "error", "msg" => "Missing parameters"]);
    exit;
}

if ($is_mocked) {
    echo json_encode(["status" => "error", "msg" => "Mock location detected. Attendance rejected."]);
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
$empCodePattern = "EMP" . str_pad((string) intval($user_id), 3, '0', STR_PAD_LEFT);
$uidInt = (int) $user_id;
$currentEmployeeId = $uidInt;
$defaultWorkingFrom = '';

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

        if ($defaultWorkingFrom === '') {
            echo json_encode([
                "status" => "error",
                "msg" => "Working From is not assigned. Please contact administrator."
            ]);
            $checkStmt->close();
            exit;
        }
    } else {
        echo json_encode([
            "status" => "error",
            "msg" => "Employee not found or inactive. Please contact administrator."
        ]);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();
}

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

// ---------------- GEO-FENCE VALIDATION (Polygon Only) ----------------
$uLat = ($lat !== null && $lat !== '') ? (float) $lat : null;
$uLng = ($lng !== null && $lng !== '') ? (float) $lng : null;

if ($uLat === null || $uLng === null) {
    echo json_encode(["status" => "error", "msg" => "Location coordinates are required for attendance."]);
    exit;
}

$locRes = $con->query("SELECT geofence_polygon FROM geo_settings WHERE is_active = 1");
$inGeofence = false;

if ($locRes) {
    while ($loc = $locRes->fetch_assoc()) {
        $polygonJson = $loc['geofence_polygon'];
        if ($polygonJson && trim($polygonJson) !== '') {
            if (isPointInPolygon($uLat, $uLng, $polygonJson)) {
                $inGeofence = true;
                break;
            }
        }
    }
}

if (!$inGeofence) {
    echo json_encode([
        "status" => "error",
        "msg" => "Location denied. You are outside the allowed office boundaries defined by the administrator."
    ]);
    exit;
}

// ---------------- REDUNDANT PUNCH PREVENTION ----------------
$todayStart = date('Y-m-d 00:00:00');
$lastPunchStmt = $con->prepare("SELECT type, time FROM attendance_logs WHERE user_id = ? AND time >= ? ORDER BY time DESC, id DESC LIMIT 1");
if ($lastPunchStmt) {
    $lastPunchStmt->bind_param("is", $currentEmployeeId, $todayStart);
    $lastPunchStmt->execute();
    $lastPunchResult = $lastPunchStmt->get_result();

    if ($lastPunchResult && $lastPunchResult->num_rows > 0) {
        $lastRow = $lastPunchResult->fetch_assoc();
        if ($lastRow['type'] === $type) {
            echo json_encode([
                "status" => "success",
                "msg" => "Already clocked $type today",
                "time" => $time,
                "next_allowed_start" => null
            ]);
            $lastPunchStmt->close();
            exit;
        }
    }
    $lastPunchStmt->close();
}

// ---------------- SHIFT TIME VALIDATION (Strict) ----------------
if ($type === 'in') {
    $timestamp = strtotime($time); // Uses server time
    if ($timestamp !== false) {
        $currentDate = date('Y-m-d', $timestamp);

        $holidayStmt = $con->prepare("SELECT holiday_name FROM holidays WHERE holiday_date = ? LIMIT 1");
        $holidayStmt->bind_param("s", $currentDate);
        $holidayStmt->execute();
        $holidayRes = $holidayStmt->get_result();
        if ($holidayRes && $holidayRes->num_rows > 0) {
            $hRow = $holidayRes->fetch_assoc();
            echo json_encode([
                "status" => "error",
                "msg" => "Today is a holiday: " . $hRow['holiday_name'] . ". Cannot clock in."
            ]);
            $holidayStmt->close();
            exit;
        }
        $holidayStmt->close();

        $empCodeForShift = "EMP" . str_pad((string) intval($user_id), 3, '0', STR_PAD_LEFT);
        $uidIntForShift = (int) $user_id;

        $shiftStmt = $con->prepare("
            SELECT s.start_time, s.end_time, s.early_clock_in_before, e.weekoff_days
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
                $weekOffData = trim($shiftRow['weekoff_days'] ?? '');
                if (!empty($weekOffData)) {
                    $currentDay = date('l', $timestamp);
                    $weekOffs = array_map('trim', explode(',', $weekOffData));
                    if (in_array($currentDay, $weekOffs)) {
                        echo json_encode([
                            "status" => "error",
                            "msg" => "Today ($currentDay) is your assigned week-off. Cannot clock in."
                        ]);
                        $shiftStmt->close();
                        exit;
                    }
                }

                if (!empty($shiftRow['start_time']) && !empty($shiftRow['end_time'])) {
                    $shiftStartTimestamp = strtotime($currentDate . ' ' . $shiftRow['start_time']);
                    $shiftEndTimestamp = strtotime($currentDate . ' ' . $shiftRow['end_time']);
                    if ($shiftEndTimestamp <= $shiftStartTimestamp) {
                        $shiftEndTimestamp += 24 * 60 * 60;
                    }

                    $earlyBuffer = isset($shiftRow['early_clock_in_before']) ? (int) $shiftRow['early_clock_in_before'] : 0;
                    $earliestAllowed = $shiftStartTimestamp - ($earlyBuffer * 60);

                    if ($timestamp < $earliestAllowed) {
                        $allowedTime = date('h:i A', $earliestAllowed);
                        echo json_encode([
                            "status" => "error",
                            "msg" => "Too early to clock in. Allowed from $allowedTime."
                        ]);
                        $shiftStmt->close();
                        exit;
                    }

                    if ($timestamp > $shiftEndTimestamp) {
                        echo json_encode([
                            "status" => "error",
                            "msg" => "Your shift ended at " . date('h:i A', $shiftEndTimestamp) . ". Cannot clock in."
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

if ($working_from === '') {
    $working_from = $defaultWorkingFrom;
}

// ---------------- INSERT ATTENDANCE LOG ----------------
$stmt = $con->prepare("INSERT INTO attendance_logs
        (user_id, type, time, device_id, latitude, longitude, working_from, reason, synced, is_auto)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");

if (!$stmt) {
    echo json_encode(["status" => "error", "msg" => "DB prepare error: " . $con->error]);
    exit;
}

$latFloat = ($lat !== null && $lat !== '') ? (float) $lat : null;
$lngFloat = ($lng !== null && $lng !== '') ? (float) $lng : null;

$stmt->bind_param(
    "isssddssi",
    $currentEmployeeId,
    $type,
    $time,
    $device_id,
    $latFloat,
    $lngFloat,
    $working_from,
    $reason,
    $is_auto
);

if ($stmt->execute()) {
    // ---------------- CALCULATE NEXT ALLOWED START ----------------
    // Find the next working day's earliest allowed clock-in time
    $nextAllowedStart = null;

    // Fetch shift and weekoff again to be sure
    $qShift = $con->prepare("
        SELECT s.start_time, s.end_time, s.early_clock_in_before, e.weekoff_days
        FROM employees e
        LEFT JOIN shifts s ON s.id = e.shift_id
        WHERE e.user_id = ?
    ");
    if ($qShift) {
        $qShift->bind_param("i", $currentEmployeeId);
        $qShift->execute();
        $rShift = $qShift->get_result();
        if ($rShift && $row = $rShift->fetch_assoc()) {
            $sTime = $row['start_time'];
            $endTime = $row['end_time'];
            $eMin = (int) ($row['early_clock_in_before'] ?? 0);
            $wOffs = array_map('trim', explode(',', $row['weekoff_days'] ?? ''));

            // Look ahead up to 14 days
            for ($i = 0; $i <= 14; $i++) {
                $checkDate = date('Y-m-d', strtotime("+$i days"));

                $dayName = date('l', strtotime($checkDate));
                if (in_array($dayName, $wOffs))
                    continue; // Skip week-off

                // Check Holiday
                $qH = $con->prepare("SELECT id FROM holidays WHERE holiday_date = ? LIMIT 1");
                $qH->bind_param("s", $checkDate);
                $qH->execute();
                if ($qH->get_result()->num_rows > 0) {
                    $qH->close();
                    continue;
                }
                $qH->close();

                // Found next working day
                $startTs = strtotime($checkDate . ' ' . $sTime);
                $endTs = strtotime($checkDate . ' ' . $endTime);
                if ($endTs <= $startTs)
                    $endTs += 86400; // Handle overnight shifts

                $allowedTs = $startTs - ($eMin * 60);

                // --- SMART BREAK LOGIC ---
                // If it's today (i=0) and the shift end hasn't happened yet, 
                // we don't return a lock timestamp. This allows auto-return from breaks.
                if ($i == 0 && time() < $endTs) {
                    $nextAllowedStart = null;
                    break;
                }

                // If this allowed time is in the future, it's our target
                if ($allowedTs > time()) {
                    $nextAllowedStart = date('Y-m-d H:i:s', $allowedTs);
                    break;
                }
            }
        }
        $qShift->close();
    }

    echo json_encode([
        "status" => "success",
        "time" => $time,
        "next_allowed_start" => $nextAllowedStart
    ]);
    exit;
} else {
    echo json_encode(["status" => "error", "msg" => "DB error: " . $stmt->error]);
    exit;
}

$stmt->close();
?>