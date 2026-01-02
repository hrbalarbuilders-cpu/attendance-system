<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/db.php';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) { echo json_encode(['success'=>false,'message'=>'Missing id']); exit; }
// fetch all columns then normalize field names so code can use 'name'
$stmt = $con->prepare("SELECT * FROM lead_sources WHERE id=? LIMIT 1");
if (!$stmt){ echo json_encode(['success'=>false,'message'=>'DB prepare failed']); exit; }
$stmt->bind_param('i', $id);
$stmt->execute(); $res = $stmt->get_result(); $row = $res->fetch_assoc(); $stmt->close();
if ($row && !isset($row['name']) && isset($row['title'])){ $row['name'] = $row['title']; }
if (!$row) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
echo json_encode(['success'=>true,'data'=>$row]);
