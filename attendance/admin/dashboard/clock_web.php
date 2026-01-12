<?php
/**
 * Web Dashboard Clock In/Out API
 * With geo-fence validation against geo_settings
 */
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "msg" => "Invalid request method"]);
    exit;
}

include "../config/db.php";

// Read POST values
$user_id      = isset($_POST['user_id'])      ? (int)$_POST['user_id']       : 0;
$type         = isset($_POST['type'])         ? trim($_POST['type'])         : '';
$working_from = isset($_POST['working_from']) ? trim($_POST['working_from']) : 'Office';
$reason       = isset($_POST['reason'])       ? trim($_POST['reason'])       : 'shift_start';
$lat          = isset($_POST['lat'])          ? (float)$_POST['lat']         : 0;
$lng          = isset($_POST['lng'])          ? (float)$_POST['lng']         : 0;

// Basic validation
if (!$user_id || !$type) {
    echo json_encode(["status" => "error", "msg" => "Missing parameters"]);
    exit;
}

// Validate type
if ($type !== 'in' && $type !== 'out') {
    echo json_encode(["status" => "error", "msg" => "Invalid type. Must be 'in' or 'out'"]);
    exit;
}

// Validate reason
$allowedReasons = ['lunch', 'tea', 'shift_start', 'shift_end'];
if (!in_array($reason, $allowedReasons, true)) {
    $reason = 'shift_start';
}

// Check if employee exists and is active
$empStmt = $con->prepare("SELECT id, name, default_working_from FROM employees WHERE id = ? AND status = 1 LIMIT 1");
if (!$empStmt) {
    echo json_encode(["status" => "error", "msg" => "Database error"]);
    exit;
}

$empStmt->bind_param("i", $user_id);
$empStmt->execute();
$empResult = $empStmt->get_result();

if (!$empResult || $empResult->num_rows === 0) {
    echo json_encode(["status" => "error", "msg" => "Employee not found or inactive"]);
    $empStmt->close();
    exit;
}

$employee = $empResult->fetch_assoc();
$empStmt->close();

// Use employee's default working_from if not provided
if (empty($working_from) && !empty($employee['default_working_from'])) {
    $working_from = $employee['default_working_from'];
}

// ============ GEO-FENCE VALIDATION ============
// Check if location provided
if ($lat == 0 && $lng == 0) {
    echo json_encode(["status" => "error", "msg" => "Location access required. Please enable location."]);
    exit;
}

// Get all active geo-fence locations
$geoStmt = $con->query("SELECT id, location_group, location_name, latitude, longitude, radius_meters FROM geo_settings WHERE is_active = 1");

if (!$geoStmt || $geoStmt->num_rows === 0) {
    // No geo-fence configured - allow clock in/out
    $withinGeoFence = true;
    $matchedLocation = 'No geo-fence configured';
} else {
    $withinGeoFence = false;
    $matchedLocation = '';
    $closestDistance = PHP_FLOAT_MAX;
    $closestRadius = 0;
    $closestLocationName = '';
    
    while ($geo = $geoStmt->fetch_assoc()) {
        $distance = calculateDistance($lat, $lng, (float)$geo['latitude'], (float)$geo['longitude']);
        $radius = (float)$geo['radius_meters'];
        
        if ($distance <= $radius) {
            $withinGeoFence = true;
            $matchedLocation = $geo['location_name'] ?: $geo['location_group'];
            break;
        }
        
        // Track closest location
        if ($distance < $closestDistance) {
            $closestDistance = $distance;
            $closestRadius = $radius;
            $closestLocationName = $geo['location_name'] ?: $geo['location_group'];
        }
    }
}

if (!$withinGeoFence) {
    // Calculate how far outside the closest geo-fence
    $outsideBy = $closestDistance - $closestRadius;
    
    // Format distance nicely
    if ($outsideBy >= 1000) {
        $distanceText = number_format($outsideBy / 1000, 2) . ' km';
    } else {
        $distanceText = round($outsideBy) . ' meters';
    }
    
    echo json_encode([
        "status" => "error", 
        "msg" => "You are " . $distanceText . " outside the allowed location (" . $closestLocationName . "). Please move closer to clock in/out.",
        "distance_outside" => round($outsideBy),
        "closest_location" => $closestLocationName
    ]);
    exit;
}

// ============ INSERT ATTENDANCE LOG ============
$time = date('Y-m-d H:i:s');

$stmt = $con->prepare("INSERT INTO attendance_logs 
    (user_id, type, time, device_id, latitude, longitude, working_from, reason, synced)
    VALUES (?, ?, ?, 'WEB_DASHBOARD', ?, ?, ?, ?, 1)");

if (!$stmt) {
    echo json_encode(["status" => "error", "msg" => "Database prepare error: " . $con->error]);
    exit;
}

$stmt->bind_param("issddss", $user_id, $type, $time, $lat, $lng, $working_from, $reason);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "msg" => "Clock " . strtoupper($type) . " recorded successfully",
        "time" => $time,
        "employee" => $employee['name'],
        "location" => $matchedLocation
    ]);
} else {
    echo json_encode(["status" => "error", "msg" => "Failed to record: " . $stmt->error]);
}

$stmt->close();
$con->close();

/**
 * Calculate distance between two coordinates using Haversine formula
 * Returns distance in meters
 */
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371000; // meters
    
    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);
    $deltaLat = deg2rad($lat2 - $lat1);
    $deltaLng = deg2rad($lng2 - $lng1);
    
    $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLng / 2) * sin($deltaLng / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}
?>
