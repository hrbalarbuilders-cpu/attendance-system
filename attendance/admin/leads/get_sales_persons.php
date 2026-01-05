<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/db.php';

$out = ['sales'=>[], 'total'=>0];

// pagination inputs
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;

// Check if sales_persons table exists
$tbl = $con->query("SHOW TABLES LIKE 'sales_persons'");
if ($tbl && $tbl->num_rows){
  // support search by employee name
  $q = isset($_GET['q']) ? trim($_GET['q']) : '';
  $where = '';
  if ($q !== ''){ $esc = $con->real_escape_string($q); $where = " WHERE e.name LIKE '%$esc%' "; }

  $countRes = $con->query("SELECT COUNT(*) AS c FROM sales_persons sp JOIN employees e ON sp.employee_id = e.id" . $where);
  if ($countRes && $countRes->num_rows){ $r = $countRes->fetch_assoc(); $out['total'] = (int)($r['c'] ?? 0); }

  $res = $con->query("SELECT sp.id, sp.employee_id, sp.status, e.name FROM sales_persons sp JOIN employees e ON sp.employee_id = e.id" . $where . " ORDER BY sp.id DESC LIMIT " . (int)$offset . "," . (int)$per_page);
  if ($res && $res->num_rows){
    while($r = $res->fetch_assoc()){
      $out['sales'][] = ['id'=>(int)$r['id'],'employee_id'=>(int)$r['employee_id'],'name'=> (string)($r['name'] ?? ''),'status'=> (string)($r['status'] ?? '')];
    }
  }
}

echo json_encode($out);
