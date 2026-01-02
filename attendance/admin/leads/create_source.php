<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/db.php';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'active';
if ($name === ''){ echo json_encode(['success'=>false,'message'=>'Name required']); exit; }

// detect whether DB uses `name` or `title` column
$hasName = false;
$colCheck = $con->query("SHOW COLUMNS FROM lead_sources LIKE 'name'");
if ($colCheck && $colCheck->num_rows > 0) $hasName = true;

$statusParam = $status;
$statusIsInt = false;
// detect status column type; if numeric, convert 'active'/'inactive' to 1/0
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
	$sql = "INSERT INTO lead_sources (name,description,status,created_at,updated_at) VALUES (?,?,?,NOW(),NOW())";
} else {
	$sql = "INSERT INTO lead_sources (title,description,status,created_at,updated_at) VALUES (?,?,?,NOW(),NOW())";
}
$stmt = $con->prepare($sql);
if (!$stmt){ echo json_encode(['success'=>false,'message'=>'DB prepare failed']); exit; }
$types = ($statusIsInt ? 'ssi' : 'sss');
$stmt->bind_param($types, $name, $description, $statusParam);
$ok = $stmt->execute(); $stmt->close();
if ($ok) echo json_encode(['success'=>true,'message'=>'Source created']); else echo json_encode(['success'=>false,'message'=>'Insert failed']);
