<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../admin/config/db.php';

$response = [
    'success' => false,
    'settings' => [
        'global_auto_attendance' => '0'
    ]
];

try {
    $res = $con->query("SELECT setting_key, setting_value FROM attendance_settings");
    if ($res) {
        $found = false;
        while ($row = $res->fetch_assoc()) {
            $response['settings'][$row['setting_key']] = $row['setting_value'];
            $found = true;
        }
        if ($found) {
            $response['success'] = true;
        } else {
            // If table empty, default succeeds
            $response['success'] = true;
        }
    } else {
        $response['message'] = "Database error or table missing";
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>