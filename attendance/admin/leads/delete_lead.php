<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/db.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$id){ echo json_encode(['success'=>false,'message'=>'Missing id']); exit; }

$stmt = $con->prepare("DELETE FROM leads WHERE id = ?");
if (!$stmt){ echo json_encode(['success'=>false,'message'=>'DB prepare failed']); exit; }
$stmt->bind_param('i', $id);
$ok = $stmt->execute();
$stmt->close();

if ($ok){ echo json_encode(['success'=>true,'message'=>'Lead deleted']); }
else { echo json_encode(['success'=>false,'message'=>'Delete failed']); }
