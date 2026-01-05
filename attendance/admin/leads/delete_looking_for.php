<?php
include_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$id){ echo json_encode(['success'=>false,'message'=>'Missing id']); exit; }
$stmt = $con->prepare('DELETE FROM lead_looking_for WHERE id=?');
if (!$stmt){ echo json_encode(['success'=>false,'message'=>'DB error']); exit; }
$stmt->bind_param('i',$id); $stmt->execute();
if ($stmt->affected_rows){ echo json_encode(['success'=>true,'message'=>'Deleted']); } else { echo json_encode(['success'=>false,'message'=>'Not found or failed']); }
?>
