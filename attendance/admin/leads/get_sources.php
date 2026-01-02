<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/db.php';

// Select all and normalize server-side so we handle enum/text or numeric status representations
// Order by id to avoid referencing `name` when that column may not exist in older schemas
$res = $con->query("SELECT * FROM lead_sources ORDER BY id DESC");
$out = ['sources'=>[]];
if ($res && $res->num_rows){
  while($r = $res->fetch_assoc()){
    // normalize name/title
    if (!isset($r['name']) && isset($r['title'])) $r['name'] = $r['title'];
    $raw = $r['status'] ?? '';
    $isActive = false;
    if ($raw === 'active' || $raw === 'Active' || $raw === 'ACTIVE') $isActive = true;
    if ($raw === '1' || $raw === 1 || $raw === 1.0) $isActive = true;
    // also accept boolean-ish values
    if ($raw === true) $isActive = true;
    if ($isActive){
      $out['sources'][] = ['id'=> (int)$r['id'], 'name'=> (string)($r['name'] ?? '')];
    }
  }
}
echo json_encode($out);
