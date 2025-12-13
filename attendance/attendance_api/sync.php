<?php
header('Content-Type: application/json');
include "db.php";

$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (!is_array($data)) {
    echo json_encode(["status" => "error", "msg" => "Invalid JSON"]);
    exit;
}

foreach ($data as $row) {
    $user_id   = $row['user_id'];
    $type      = $row['type'];
    $time      = $row['time'];
    $device_id = $row['device_id'];
    $lat       = $row['latitude'];
    $lng       = $row['longitude'];

    $sql = "INSERT INTO attendance_logs
            (user_id, type, time, device_id, latitude, longitude, synced)
            VALUES ('$user_id', '$type', '$time', '$device_id', '$lat', '$lng', 1)";
    $con->query($sql);
}

echo json_encode(["status" => "synced"]);
?>
