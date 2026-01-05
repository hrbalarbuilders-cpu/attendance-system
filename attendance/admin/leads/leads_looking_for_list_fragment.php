<?php
include_once __DIR__ . '/../config/db.php';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 10;
$offset = ($page - 1) * $per_page;

// Ensure table exists to avoid fatal errors when first visiting the page
$con->query("CREATE TABLE IF NOT EXISTS lead_looking_for (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  status VARCHAR(32) DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
)");
// If a legacy `description` column exists, drop it now (safe idempotent operation)
$colDesc = $con->query("SHOW COLUMNS FROM lead_looking_for LIKE 'description'");
if ($colDesc && $colDesc->num_rows){ $con->query("ALTER TABLE lead_looking_for DROP COLUMN description"); }
// legacy flat subtypes table removed; use normalized `lead_looking_for_types` and `lead_looking_for_type_subtypes`
// project-type cleanup completed earlier; no leftovers here.

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = '';
if ($q !== ''){ $esc = $con->real_escape_string($q); $where = " WHERE (name LIKE '%$esc%')"; }

$countRes = $con->query("SELECT COUNT(*) AS c FROM lead_looking_for" . $where);
$totalCount = 0; if ($countRes && $countRes->num_rows){ $r = $countRes->fetch_assoc(); $totalCount = (int)($r['c'] ?? 0); }

$res = $con->query("SELECT l.* FROM lead_looking_for l" . $where . " ORDER BY l.id DESC LIMIT " . (int)$offset . "," . (int)$per_page);
// ensure types/subtypes tables exist
$con->query("CREATE TABLE IF NOT EXISTS lead_looking_for_types (id INT AUTO_INCREMENT PRIMARY KEY, looking_for_id INT NOT NULL, name VARCHAR(255), FOREIGN KEY (looking_for_id) REFERENCES lead_looking_for(id) ON DELETE CASCADE)");
$con->query("CREATE TABLE IF NOT EXISTS lead_looking_for_type_subtypes (id INT AUTO_INCREMENT PRIMARY KEY, type_id INT NOT NULL, name VARCHAR(255), FOREIGN KEY (type_id) REFERENCES lead_looking_for_types(id) ON DELETE CASCADE)");
// fetch with subtypes aggregated
$res = $con->query("SELECT l.*, GROUP_CONCAT(DISTINCT st.name ORDER BY st.id SEPARATOR ', ') AS subtypes FROM lead_looking_for l LEFT JOIN lead_looking_for_types t ON t.looking_for_id = l.id LEFT JOIN lead_looking_for_type_subtypes st ON st.type_id = t.id" . $where . " GROUP BY l.id ORDER BY l.id DESC LIMIT " . (int)$offset . "," . (int)$per_page);

function e($v){ return htmlspecialchars($v); }
?>
<!--LF_TOTAL:<?php echo $totalCount; ?>-->
<div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead>
      <tr><th>#</th><th>Name</th><th>Subtypes</th><th>Status</th><th>Created</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php if ($res && $res->num_rows): while ($row = $res->fetch_assoc()): ?>
          <tr data-id="<?= e($row['id']) ?>">
            <td><?= e($row['id']) ?></td>
            <td><?= e($row['name']) ?></td>
            <td><?= e($row['subtypes'] ?? '') ?></td>
            <td><?= e(ucfirst((string)$row['status'])) ?></td>
            <td><?= e($row['created_at'] ?? '') ?></td>
          <td>
            <button class="btn btn-sm btn-outline-primary btn-edit-lf">Edit</button>
            <button class="btn btn-sm btn-outline-danger btn-delete-lf">Delete</button>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="5" class="text-center py-3">No items found.</td></tr>
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
  if ($page > 1) echo '<li class="page-item"><a href="#" class="page-link lf-page-link" data-page="'.($page-1).'">Previous</a></li>';
  if ($start > 1) echo '<li class="page-item"><a href="#" class="page-link lf-page-link" data-page="1">1</a></li>' . ($start>2 ? '<li class="page-item disabled"><span class="page-link">...</span></li>':'' );
  for ($p = $start; $p <= $end; $p++){ $cls = $p == $page ? ' page-item active' : ' page-item'; echo '<li class="'.$cls.'"><a href="#" class="page-link lf-page-link" data-page="'.$p.'">'.$p.'</a></li>'; }
  if ($end < $totalPages) echo ($end < $totalPages-1 ? '<li class="page-item disabled"><span class="page-link">...</span></li>':'') . '<li class="page-item"><a href="#" class="page-link lf-page-link" data-page="'.$totalPages.'">'.$totalPages.'</a></li>';
  if ($page < $totalPages) echo '<li class="page-item"><a href="#" class="page-link lf-page-link" data-page="'.($page+1).'">Next</a></li>';
  echo '</ul></nav>';
endif;

$startRec = $totalCount > 0 ? ($offset + 1) : 0;
$endRec = $totalCount > 0 ? ($offset + ($res ? $res->num_rows : 0)) : 0;
?>
<div class="d-flex justify-content-between align-items-center mt-2 px-2">
  <div class="small text-muted">Record <?php echo $startRec; ?>–<?php echo $endRec; ?> of <?php echo $totalCount; ?></div>
  <div class="d-flex align-items-center gap-2">
    <label class="small text-muted mb-0">Rows:</label>
    <select id="lfPerPageFooter" class="form-select form-select-sm" style="width:80px;">
      <option value="10" <?php if($per_page==10) echo 'selected'; ?>>10</option>
      <option value="25" <?php if($per_page==25) echo 'selected'; ?>>25</option>
      <option value="50" <?php if($per_page==50) echo 'selected'; ?>>50</option>
      <option value="100" <?php if($per_page==100) echo 'selected'; ?>>100</option>
    </select>
  </div>
</div>
