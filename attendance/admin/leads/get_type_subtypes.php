<?php
include_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
$id = isset($_GET['type_id']) ? intval($_GET['type_id']) : 0;
if (!$id){ echo json_encode(['success'=>false,'message'=>'Missing type_id']); exit; }
// ensure table exists
$con->query("CREATE TABLE IF NOT EXISTS lead_looking_for_type_subtypes (id INT AUTO_INCREMENT PRIMARY KEY, type_id INT NOT NULL, name VARCHAR(255), FOREIGN KEY (type_id) REFERENCES lead_looking_for_types(id) ON DELETE CASCADE)");
$res = $con->query('SELECT id, name FROM lead_looking_for_type_subtypes WHERE type_id=' . $id . ' ORDER BY id ASC');
$out = [];
if ($res && $res->num_rows){ while ($r = $res->fetch_assoc()){ $out[] = ['id'=>(int)$r['id'],'name'=>$r['name']]; } }
echo json_encode(['success'=>true,'data'=>$out]);

?>
