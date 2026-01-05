<?php
include_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id){ echo json_encode(['success'=>false,'message'=>'Missing id']); exit; }
$res = $con->query('SELECT * FROM lead_looking_for WHERE id=' . $id . ' LIMIT 1');
if (!$res || !$res->num_rows){ echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
$row = $res->fetch_assoc();
// fetch subtypes
// fetch structured types and subtypes
$types = [];
// ensure types tables exist to avoid fatal errors
$con->query("CREATE TABLE IF NOT EXISTS lead_looking_for_types (id INT AUTO_INCREMENT PRIMARY KEY, looking_for_id INT NOT NULL, name VARCHAR(255), FOREIGN KEY (looking_for_id) REFERENCES lead_looking_for(id) ON DELETE CASCADE)");
$con->query("CREATE TABLE IF NOT EXISTS lead_looking_for_type_subtypes (id INT AUTO_INCREMENT PRIMARY KEY, type_id INT NOT NULL, name VARCHAR(255), FOREIGN KEY (type_id) REFERENCES lead_looking_for_types(id) ON DELETE CASCADE)");

$tRes = $con->query('SELECT id, name FROM lead_looking_for_types WHERE looking_for_id=' . $id . ' ORDER BY id ASC');
if ($tRes && $tRes->num_rows){
	while ($t = $tRes->fetch_assoc()){
		$type = ['id' => (int)$t['id'], 'name' => $t['name'], 'subtypes' => []];
		$st = $con->query('SELECT id,name FROM lead_looking_for_type_subtypes WHERE type_id=' . (int)$t['id'] . ' ORDER BY id ASC');
		if ($st && $st->num_rows){ while ($s = $st->fetch_assoc()){ $type['subtypes'][] = $s['name']; } }
		$types[] = $type;
	}
}
$row['types'] = $types;
// `types` already populated above
echo json_encode(['success'=>true,'data'=>$row]);
?>
