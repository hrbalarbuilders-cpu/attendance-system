<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate'); // Prevent caching

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "msg" => "Invalid request method"]);
    exit;
}

include "db.php";

// POST se values lo (Flutter se aayegi)
$user_id      = isset($_POST['user_id'])      ? trim($_POST['user_id'])      : null;
$type         = isset($_POST['type'])         ? trim($_POST['type'])         : null;
$time         = isset($_POST['time'])         ? trim($_POST['time'])         : null;
$device_id    = isset($_POST['device_id'])    ? trim($_POST['device_id'])    : null;
$lat          = isset($_POST['lat'])          ? trim($_POST['lat'])          : null;
$lng          = isset($_POST['lng'])          ? trim($_POST['lng'])          : null;
$working_from = isset($_POST['working_from']) ? trim($_POST['working_from']) : 'office';
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
$allowedReasons = ['lunch', 'tea', 'short_leave', 'shift_start', 'shift_end'];
if (!in_array($reason, $allowedReasons)) {
    $reason = 'shift_start'; // Default to shift_start if invalid
}

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
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode([
        "status" => "error",
        "msg"    => "DB error: " . $stmt->error
    ]);
}

$stmt->close();
?>
