<?php
include_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$id){ echo json_encode(['success'=>false,'message'=>'Missing id']); exit; }
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'active';
if ($name === ''){ echo json_encode(['success'=>false,'message'=>'Name required']); exit; }
// ensure subtypes table exists
$con->query("CREATE TABLE IF NOT EXISTS lead_looking_for_types (id INT AUTO_INCREMENT PRIMARY KEY, looking_for_id INT NOT NULL, name VARCHAR(255), FOREIGN KEY (looking_for_id) REFERENCES lead_looking_for(id) ON DELETE CASCADE)");
$con->query("CREATE TABLE IF NOT EXISTS lead_looking_for_type_subtypes (id INT AUTO_INCREMENT PRIMARY KEY, type_id INT NOT NULL, name VARCHAR(255), FOREIGN KEY (type_id) REFERENCES lead_looking_for_types(id) ON DELETE CASCADE)");
$stmt = $con->prepare('UPDATE lead_looking_for SET name=?, status=? WHERE id=?');
if (!$stmt){ echo json_encode(['success'=>false,'message'=>'DB error']); exit; }
$stmt->bind_param('ssi',$name,$status,$id); $stmt->execute();
if ($stmt->affected_rows >= 0){
	// replace structured types if provided (delete existing and insert new)
	if (isset($_POST['types_json']) && $_POST['types_json']){
		$types = json_decode($_POST['types_json'], true);
		// remove existing types/subtypes for this looking_for
		$delSub = $con->prepare('DELETE st FROM lead_looking_for_type_subtypes st JOIN lead_looking_for_types t ON st.type_id = t.id WHERE t.looking_for_id = ?');
		$delSub->bind_param('i', $id); $delSub->execute();
		$delTypes = $con->prepare('DELETE FROM lead_looking_for_types WHERE looking_for_id = ?'); $delTypes->bind_param('i', $id); $delTypes->execute();
		if (is_array($types)){
			$insType = $con->prepare('INSERT INTO lead_looking_for_types (looking_for_id, name) VALUES (?,?)');
			$insSub = $con->prepare('INSERT INTO lead_looking_for_type_subtypes (type_id, name) VALUES (?,?)');
			foreach ($types as $t){ $tname = trim($t['name'] ?? ''); if ($tname === '') continue; $insType->bind_param('is', $id, $tname); $insType->execute(); $typeId = $con->insert_id; if ($typeId && !empty($t['subtypes']) && is_array($t['subtypes'])){ foreach ($t['subtypes'] as $st){ $stn = trim($st); if ($stn==='') continue; $insSub->bind_param('is', $typeId, $stn); $insSub->execute(); } } }
		}
	}
	echo json_encode(['success'=>true,'message'=>'Updated']);
} else { echo json_encode(['success'=>false,'message'=>'Failed to update']); }
?>
