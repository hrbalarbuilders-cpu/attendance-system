<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/db.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$id){ echo json_encode(['success'=>false,'message'=>'Missing id']); exit; }

$stmt = $con->prepare("DELETE FROM sales_persons WHERE id = ?");
if (!$stmt){ echo json_encode(['success'=>false,'message'=>'DB prepare failed']); exit; }
$stmt->bind_param('i',$id);
if (!$stmt->execute()){ echo json_encode(['success'=>false,'message'=>'Execute failed']); $stmt->close(); exit; }
$stmt->close();

echo json_encode(['success'=>true,'message'=>'Deleted']);
