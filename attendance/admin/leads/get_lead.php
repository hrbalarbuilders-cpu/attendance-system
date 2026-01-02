<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id){ echo json_encode(['success'=>false,'message'=>'Missing id']); exit; }

// fetch lead and include source name (name or title) if available so client can use it
$stmt = $con->prepare("SELECT l.*, COALESCE(ls.name, ls.title) AS lead_source_name FROM leads l LEFT JOIN lead_sources ls ON l.lead_source_id = ls.id WHERE l.id = ? LIMIT 1");
if (!$stmt){ echo json_encode(['success'=>false,'message'=>'DB prepare failed']); exit; }
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row){ echo json_encode(['success'=>false,'message'=>'Not found']); exit; }

echo json_encode(['success'=>true,'data'=>$row]);
