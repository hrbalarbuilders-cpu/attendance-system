<?php
include_once __DIR__ . '/../config/db.php';

// pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 10;
$offset = ($page - 1) * $per_page;

$countRes = $con->query("SELECT COUNT(*) AS c FROM lead_sources");
$totalCount = 0;
if ($countRes && $countRes->num_rows){ $r = $countRes->fetch_assoc(); $totalCount = (int)($r['c'] ?? 0); }

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = '';
if ($q !== ''){ $esc = $con->real_escape_string($q); $where = " WHERE (name LIKE '%$esc%' OR description LIKE '%$esc%' OR title LIKE '%$esc%')"; }

$res = $con->query("SELECT * FROM lead_sources" . $where . " ORDER BY id DESC LIMIT " . (int)$offset . "," . (int)$per_page);

function esc($v){ return htmlspecialchars($v); }
?>
<!--SOURCES_TOTAL:<?php echo $totalCount; ?>-->
<div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Description</th>
        <th>Status</th>
        <th>Created At</th>
        <th>Updated At</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($res && $res->num_rows): while ($row = $res->fetch_assoc()): ?>
      <tr data-id="<?= esc($row['id']) ?>">
        <td><?= esc($row['id']) ?></td>
        <td><?= esc(isset($row['name']) ? $row['name'] : (isset($row['title']) ? $row['title'] : '')) ?></td>
        <td><?= esc(isset($row['description']) ? $row['description'] : '') ?></td>
        <?php
          $rawStatus = isset($row['status']) ? $row['status'] : '';
          if ($rawStatus === '1' || $rawStatus === 1) {
            $statusLabel = 'active';
          } elseif ($rawStatus === '0' || $rawStatus === 0) {
            $statusLabel = 'inactive';
          } else {
            $statusLabel = strtolower((string)$rawStatus) ?: 'inactive';
          }
        ?>
        <td><?= esc(ucfirst($statusLabel)) ?></td>
        <td><?= esc($row['created_at']) ?></td>
        <td><?= esc($row['updated_at']) ?></td>
        <td>
          <button class="btn btn-sm btn-outline-primary btn-edit-source">Edit</button>
          <button class="btn btn-sm btn-outline-danger btn-delete-source">Delete</button>
        </td>
      </tr>
    <?php endwhile; else: ?>
      <tr><td colspan="7" class="text-center py-3">No sources found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php
$totalPages = max(1, (int)ceil($totalCount / $per_page));
if ($totalPages > 1):
  $start = max(1, $page - 3);
  $end = min($totalPages, $page + 3);
  echo '<nav class="mt-3"><ul class="pagination">';
  if ($page > 1) echo '<li class="page-item"><a href="#" class="page-link sources-page-link" data-page="'.($page-1).'">Previous</a></li>';
  if ($start > 1) echo '<li class="page-item"><a href="#" class="page-link sources-page-link" data-page="1">1</a></li>' . ($start>2 ? '<li class="page-item disabled"><span class="page-link">...</span></li>':'' );
  for ($p = $start; $p <= $end; $p++){
    $cls = $p == $page ? ' page-item active' : ' page-item';
    echo '<li class="'.$cls.'"><a href="#" class="page-link sources-page-link" data-page="'.$p.'">'.$p.'</a></li>';
  }
  if ($end < $totalPages) echo ($end < $totalPages-1 ? '<li class="page-item disabled"><span class="page-link">...</span></li>':'') . '<li class="page-item"><a href="#" class="page-link sources-page-link" data-page="'.$totalPages.'">'.$totalPages.'</a></li>';
  if ($page < $totalPages) echo '<li class="page-item"><a href="#" class="page-link sources-page-link" data-page="'.($page+1).'">Next</a></li>';
  echo '</ul></nav>';
endif;
?>
<?php
// Footer: show record range and per-page selector
$startRec = $totalCount > 0 ? ($offset + 1) : 0;
$endRec = $totalCount > 0 ? ($offset + ($res ? $res->num_rows : 0)) : 0;
?>
<div class="d-flex justify-content-between align-items-center mt-2 px-2">
  <div class="small text-muted">Record <?php echo $startRec; ?>–<?php echo $endRec; ?> of <?php echo $totalCount; ?></div>
  <div class="d-flex align-items-center gap-2">
    <label class="small text-muted mb-0">Rows:</label>
    <select id="sourcePerPageFooter" class="form-select form-select-sm" style="width:80px;">
      <option value="10" <?php if($per_page==10) echo 'selected'; ?>>10</option>
      <option value="25" <?php if($per_page==25) echo 'selected'; ?>>25</option>
      <option value="50" <?php if($per_page==50) echo 'selected'; ?>>50</option>
      <option value="100" <?php if($per_page==100) echo 'selected'; ?>>100</option>
    </select>
  </div>
</div>
