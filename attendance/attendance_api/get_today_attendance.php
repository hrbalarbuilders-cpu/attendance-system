<?php
header('Content-Type: application/json');

include "db.php";

// Get user_id from GET parameter
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
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

echo json_encode([
    "status" => "success",
    "clock_in" => $clockInFormatted,
    "clock_out" => $clockOutFormatted,
    "total_punches_today" => $totalPunchesToday,
    "last_punch_type" => $lastPunchType, // Added last punch type
    "logs" => $logs
]);

$stmt->close();
?>

