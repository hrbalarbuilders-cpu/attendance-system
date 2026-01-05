<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/db.php';

// Select all and normalize server-side so we handle enum/text or numeric status representations
// Order by id to avoid referencing `name` when that column may not exist in older schemas
$res = $con->query("SELECT * FROM lead_sources ORDER BY id DESC");
$out = ['sources'=>[]];
// return all sources; include status so client can decide how to present them
if ($res && $res->num_rows){
  while($r = $res->fetch_assoc()){
    if (!isset($r['name']) && isset($r['title'])) $r['name'] = $r['title'];
    $out['sources'][] = [
      'id' => (int)$r['id'],
      'name' => (string)($r['name'] ?? ''),
      'status' => isset($r['status']) ? (string)$r['status'] : ''
    ];
  }
}
echo json_encode($out);
