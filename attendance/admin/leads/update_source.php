<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/db.php';
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'active';
if (!$id){ echo json_encode(['success'=>false,'message'=>'Missing id']); exit; }
if ($name === ''){ echo json_encode(['success'=>false,'message'=>'Name required']); exit; }

// detect whether DB uses `name` or `title` column
$hasName = false;
$colCheck = $con->query("SHOW COLUMNS FROM lead_sources LIKE 'name'");
if ($colCheck && $colCheck->num_rows > 0) $hasName = true;

$statusParam = $status;
$statusIsInt = false;
$colStatus = $con->query("SHOW COLUMNS FROM lead_sources LIKE 'status'");
if ($colStatus && $colStatus->num_rows){
	$c = $colStatus->fetch_assoc();
	$type = strtolower($c['Type'] ?? '');
	if (strpos($type, 'tinyint') !== false || strpos($type, 'int') !== false || strpos($type, 'bit') !== false){
		$statusIsInt = true;
		$statusParam = ($status === 'active') ? 1 : 0;
	}
}

if ($hasName) {
	$sql = "UPDATE lead_sources SET name=?, description=?, status=?, updated_at=NOW() WHERE id=?";
} else {
	$sql = "UPDATE lead_sources SET title=?, description=?, status=?, updated_at=NOW() WHERE id=?";
}
$stmt = $con->prepare($sql);
if (!$stmt){ echo json_encode(['success'=>false,'message'=>'DB prepare failed']); exit; }
$types = ($statusIsInt ? 'ssii' : 'sssi');
$stmt->bind_param($types, $name, $description, $statusParam, $id);
$ok = $stmt->execute(); $stmt->close();
if ($ok) echo json_encode(['success'=>true,'message'=>'Source updated']); else echo json_encode(['success'=>false,'message'=>'Update failed']);
