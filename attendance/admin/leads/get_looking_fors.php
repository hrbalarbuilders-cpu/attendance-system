<?php
include_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
// Ensure table exists to avoid fatal errors
$con->query("CREATE TABLE IF NOT EXISTS lead_looking_for (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), status VARCHAR(32) DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP)");
$out = [];
$res = $con->query("SELECT id, name, status FROM lead_looking_for ORDER BY name ASC");
if ($res && $res->num_rows){ while ($r = $res->fetch_assoc()){ $out[] = $r; } }
echo json_encode(['success'=>true,'lookings'=>$out]);
?>