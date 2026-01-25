<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/db.php';
$out = ['employees' => []];
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = '';
if ($q !== '') {
  $esc = $con->real_escape_string($q);
  $where = " WHERE (name LIKE '%$esc%' OR emp_code LIKE '%$esc%')";
}
// By default return only active employees
$onlyActive = isset($_GET['active']) ? (int) $_GET['active'] : 1;
if ($onlyActive) {
  $where = $where ? ($where . " AND status=1") : " WHERE status=1";
}
$res = $con->query("SELECT user_id, emp_code, name, status FROM employees " . $where . " ORDER BY user_id DESC LIMIT 100");
if ($res && $res->num_rows) {
  while ($r = $res->fetch_assoc()) {
    $out['employees'][] = ['id' => (int) $r['user_id'], 'emp_code' => (string) ($r['emp_code'] ?? ''), 'name' => (string) ($r['name'] ?? ''), 'status' => (string) ($r['status'] ?? '')];
  }
}
echo json_encode($out);
?>