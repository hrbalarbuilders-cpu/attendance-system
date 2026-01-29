<?php
include '../config/db.php';

$isAjax = isset($_GET['ajax']) && $_GET['ajax'];

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 10;
// prevent very large page sizes
if ($per_page > 100)
  $per_page = 100;
$offset = ($page - 1) * $per_page;

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$whereSql = '';
if ($q !== '') {
  $esc = $con->real_escape_string($q);
  $whereSql = " WHERE (e.emp_code LIKE '%$esc%' OR e.name LIKE '%$esc%' OR d.department_name LIKE '%$esc%' OR s.shift_name LIKE '%$esc%')";
}

$countSql = "
SELECT COUNT(*) AS c
FROM employees e
LEFT JOIN departments d ON d.id = e.department_id
LEFT JOIN shifts s ON s.id = e.shift_id
" . $whereSql;

$totalCount = 0;
$countRes = $con->query($countSql);
if ($countRes && $countRes->num_rows) {
  $r = $countRes->fetch_assoc();
  $totalCount = (int) ($r['c'] ?? 0);
}

$sql = "
SELECT e.user_id, e.emp_code, e.name, e.department_id, e.shift_id, e.status, e.updated_at, e.created_at,
       d.department_name, s.shift_name, s.start_time, s.end_time
FROM employees e
LEFT JOIN departments d ON d.id = e.department_id
LEFT JOIN shifts s ON s.id = e.shift_id
" . $whereSql . "
ORDER BY e.user_id DESC
LIMIT " . (int) $offset . "," . (int) $per_page;

$result = $con->query($sql);

if ($isAjax) {
  include __DIR__ . '/employees_list_fragment.php';
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Management</title>
  <link rel="icon" href="data:,">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

  <?php include __DIR__ . '/employees_list_fragment.php'; ?>

</body>

</html>