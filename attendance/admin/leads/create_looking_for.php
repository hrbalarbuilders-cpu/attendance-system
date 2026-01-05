<?php
include_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
// ensure main table (no description column)
$con->query("CREATE TABLE IF NOT EXISTS lead_looking_for (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), status VARCHAR(32) DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP)");
// ensure subtypes table exists
// ensure types and subtypes tables exist
$con->query("CREATE TABLE IF NOT EXISTS lead_looking_for_types (id INT AUTO_INCREMENT PRIMARY KEY, looking_for_id INT NOT NULL, name VARCHAR(255), FOREIGN KEY (looking_for_id) REFERENCES lead_looking_for(id) ON DELETE CASCADE)");
$con->query("CREATE TABLE IF NOT EXISTS lead_looking_for_type_subtypes (id INT AUTO_INCREMENT PRIMARY KEY, type_id INT NOT NULL, name VARCHAR(255), FOREIGN KEY (type_id) REFERENCES lead_looking_for_types(id) ON DELETE CASCADE)");
// ensure project types table exists
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'active';
if ($name === ''){ echo json_encode(['success'=>false,'message'=>'Name required']); exit; }
	$stmt = $con->prepare('INSERT INTO lead_looking_for (name,status) VALUES (?,?)');
	if (!$stmt){ echo json_encode(['success'=>false,'message'=>'DB error']); exit; }
	$stmt->bind_param('ss',$name,$status); $stmt->execute();
	if ($stmt->affected_rows){
		$newId = $stmt->insert_id;
		// handle structured types JSON if provided
		if (isset($_POST['types_json']) && $_POST['types_json']){
			$types = json_decode($_POST['types_json'], true);
			if (is_array($types)){
				$insType = $con->prepare('INSERT INTO lead_looking_for_types (looking_for_id, name) VALUES (?,?)');
				$insSub = $con->prepare('INSERT INTO lead_looking_for_type_subtypes (type_id, name) VALUES (?,?)');
				foreach ($types as $t){ $tname = trim($t['name'] ?? ''); if ($tname === '') continue; $insType->bind_param('is', $newId, $tname); $insType->execute(); $typeId = $con->insert_id; if ($typeId && !empty($t['subtypes']) && is_array($t['subtypes'])){ foreach ($t['subtypes'] as $st){ $stn = trim($st); if ($stn==='') continue; $insSub->bind_param('is', $typeId, $stn); $insSub->execute(); } } }
			}
		}
		echo json_encode(['success'=>true,'message'=>'Created']);
	} else { echo json_encode(['success'=>false,'message'=>'Failed to create']); }
?>
