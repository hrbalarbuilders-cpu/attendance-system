<?php
include_once __DIR__ . '/../config/db.php';

// Query leads and left-join lead_sources so we can display the source name (handles name/title fallback)
// Support pagination via `?page=` and `?per_page=` parameters
// Read pagination and filter params
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 10;
$offset = ($page - 1) * $per_page;

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_status = isset($_GET['lead_status']) ? trim($_GET['lead_status']) : '';
$filter_sales = isset($_GET['sales_person']) ? trim($_GET['sales_person']) : '';

// Build WHERE clauses
$where_parts = [];
if ($q !== '') {
  $esc = $con->real_escape_string($q);
  $where_parts[] = "(l.name LIKE '%$esc%' OR l.contact_number LIKE '%$esc%' OR l.email LIKE '%$esc%' OR l.purpose LIKE '%$esc%')";
}
if ($filter_status !== '') {
  $esc = $con->real_escape_string(strtolower($filter_status));
  $where_parts[] = "LOWER(l.lead_status) = '$esc'";
}
if ($filter_sales !== '') {
  $esc = $con->real_escape_string($filter_sales);
  // comparing to stored sales_person (text)
  $where_parts[] = "l.sales_person = '$esc'";
}
$where_sql = '';
if (count($where_parts))
  $where_sql = ' WHERE ' . implode(' AND ', $where_parts);
// Detect whether `name` or `title` columns exist in `lead_sources` to build a safe SELECT
$hasName = false;
$hasTitle = false;
$col = $con->query("SHOW COLUMNS FROM lead_sources LIKE 'name'");
if ($col && $col->num_rows)
  $hasName = true;
$col = $con->query("SHOW COLUMNS FROM lead_sources LIKE 'title'");
if ($col && $col->num_rows)
  $hasTitle = true;
$selectSourceExpr = '';
if ($hasName && $hasTitle) {
  $selectSourceExpr = "COALESCE(ls.name, ls.title) AS source_name";
} elseif ($hasName) {
  $selectSourceExpr = "ls.name AS source_name";
} elseif ($hasTitle) {
  $selectSourceExpr = "ls.title AS source_name";
} else {
  // fallback to id if no readable column exists
  $selectSourceExpr = "ls.id AS source_name";
}

$countRes = $con->query("SELECT COUNT(*) AS c FROM leads l" . $where_sql);
$totalCount = 0;
if ($countRes && $countRes->num_rows) {
  $r = $countRes->fetch_assoc();
  $totalCount = (int) ($r['c'] ?? 0);
}

$con->query("CREATE TABLE IF NOT EXISTS lead_looking_for_types (id INT AUTO_INCREMENT PRIMARY KEY, looking_for_id INT NOT NULL, name VARCHAR(255))");
$con->query("CREATE TABLE IF NOT EXISTS lead_looking_for_type_subtypes (id INT AUTO_INCREMENT PRIMARY KEY, type_id INT NOT NULL, name VARCHAR(255))");
// detect whether the leads table has our new selection columns (to avoid fatal SQL errors on older DBs)
$hasLfType = false;
$hasLfSub = false;
$c = $con->query("SHOW COLUMNS FROM leads LIKE 'looking_for_type_id'");
if ($c && $c->num_rows)
  $hasLfType = true;
$c = $con->query("SHOW COLUMNS FROM leads LIKE 'looking_for_subtypes'");
if ($c && $c->num_rows)
  $hasLfSub = true;

$selectLFType = $hasLfType ? "(SELECT name FROM lead_looking_for_types t2 WHERE t2.id = l.looking_for_type_id LIMIT 1) AS looking_for_type_name" : "'' AS looking_for_type_name";
$selectLFSub = $hasLfSub ? "(SELECT GROUP_CONCAT(DISTINCT st.name ORDER BY st.id SEPARATOR ', ') FROM lead_looking_for_type_subtypes st WHERE l.looking_for_subtypes IS NOT NULL AND l.looking_for_subtypes <> '' AND FIND_IN_SET(st.id, l.looking_for_subtypes)) AS looking_for_subtypes_names" : "'' AS looking_for_subtypes_names";

// Detect and select human-readable 'Looking For' name if available
$hasLookingFor = false;
$c = $con->query("SHOW COLUMNS FROM leads LIKE 'looking_for_id'");
if ($c && $c->num_rows)
  $hasLookingFor = true;
$selectLookingFor = $hasLookingFor ? "(SELECT name FROM lead_looking_for lf WHERE lf.id = l.looking_for_id LIMIT 1) AS looking_for_name" : "'' AS looking_for_name";

$res = $con->query(
  "SELECT l.*, " . $selectSourceExpr . ", " . $selectLookingFor . ", " . $selectLFType . ", " . $selectLFSub . " FROM leads l LEFT JOIN lead_sources ls ON l.lead_source_id = ls.id " . $where_sql . " ORDER BY l.id DESC LIMIT " . (int) $offset . "," . (int) $per_page
);

function col($row, $k)
{
  return isset($row[$k]) ? htmlspecialchars($row[$k]) : '';
}
?>
<!--LEADS_TOTAL:<?php echo $totalCount; ?>-->
<div class="table-responsive">
  <table class="table table-hover align-middle mb-0" id="leadsTable">
    <thead>
      <tr>
        <th style="width: 50px;">#</th>
        <th style="min-width: 150px;">Name</th>
        <th style="min-width: 120px;">Contact</th>
        <th style="min-width: 120px;">Lead Source</th>
        <th style="min-width: 120px;">Looking For</th>
        <th style="min-width: 100px;">LF Type</th>
        <th style="min-width: 150px;">LF Subtypes</th>
        <th style="min-width: 120px;">Sales Person</th>
        <th style="min-width: 100px;">Status</th>
        <th style="min-width: 150px;">Created</th>
        <th style="width: 80px;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($res && $res->num_rows):
        while ($row = $res->fetch_assoc()): ?>
          <tr data-id="<?= col($row, 'id') ?>">
            <td><?= col($row, 'id') ?></td>
            <td><?= col($row, 'name') ?></td>
            <td class="text-nowrap"><?= col($row, 'contact_number') ?></td>
            <td><?= col($row, 'source_name') ?></td>
            <td><?= (col($row, 'looking_for_name') !== '') ? col($row, 'looking_for_name') : col($row, 'looking_for_id') ?>
            </td>
            <td><?= col($row, 'looking_for_type_name') ?></td>
            <td><?= col($row, 'looking_for_subtypes_names') ?></td>
            <td><?= col($row, 'sales_person') ?></td>
            <?php
            $rawStatus = isset($row['lead_status']) ? strtolower(trim($row['lead_status'])) : '';
            $label = $rawStatus ? ucfirst($rawStatus) : '—';
            $badgeClass = '';
            if ($rawStatus === 'hot') {
              $badgeClass = 'badge bg-danger';
            } elseif ($rawStatus === 'warm' || $rawStatus === 'warn') {
              $badgeClass = 'badge bg-warning text-dark';
            } elseif ($rawStatus === 'cold') {
              $badgeClass = 'badge bg-primary';
            }
            ?>
            <td>
              <?php if ($badgeClass): ?>
                <span class="<?= $badgeClass ?>"><?= htmlspecialchars($label) ?></span>
              <?php else: ?>
                <?= htmlspecialchars($label) ?>
              <?php endif; ?>
            </td>
            <td><?= col($row, 'created_at') ?></td>
            <td>
              <div class="dropup position-relative">
                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" data-bs-display="static"
                  aria-expanded="false" aria-label="Actions" title="Actions">
                  &#8942;
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <button type="button" class="dropdown-item btn-edit-lead"
                      data-lead-id="<?= col($row, 'id') ?>">Edit</button>
                  </li>
                  <li>
                    <button type="button" class="dropdown-item text-danger btn-delete-lead">Delete</button>
                  </li>
                </ul>
              </div>
            </td>
          </tr>
        <?php endwhile; else: ?>
        <tr>
          <td colspan="11">No leads found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php
// Render pagination controls
$totalPages = max(1, (int) ceil($totalCount / $per_page));
if ($totalPages > 1):
  $start = max(1, $page - 3);
  $end = min($totalPages, $page + 3);
  echo '<nav class="mt-3"><ul class="pagination">';
  if ($page > 1)
    echo '<li class="page-item"><a href="#" class="page-link leads-page-link" data-page="' . ($page - 1) . '">Previous</a></li>';
  if ($start > 1)
    echo '<li class="page-item"><a href="#" class="page-link leads-page-link" data-page="1">1</a></li>' . ($start > 2 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : '');
  for ($p = $start; $p <= $end; $p++) {
    $cls = $p == $page ? ' page-item active' : ' page-item';
    echo '<li class="' . $cls . '"><a href="#" class="page-link leads-page-link" data-page="' . $p . '">' . $p . '</a></li>';
  }
  if ($end < $totalPages)
    echo ($end < $totalPages - 1 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : '') . '<li class="page-item"><a href="#" class="page-link leads-page-link" data-page="' . $totalPages . '">' . $totalPages . '</a></li>';
  if ($page < $totalPages)
    echo '<li class="page-item"><a href="#" class="page-link leads-page-link" data-page="' . ($page + 1) . '">Next</a></li>';
  echo '</ul></nav>';
endif;
?>
<?php
// Footer: show record range and per-page selector
$startRec = $totalCount > 0 ? ($offset + 1) : 0;
$endRec = $totalCount > 0 ? ($offset + ($res ? $res->num_rows : 0)) : 0;
?>
<div class="d-flex justify-content-between align-items-center mt-2 px-2">
  <div class="small text-muted">Record <?php echo $startRec; ?>–<?php echo $endRec; ?> of <?php echo $totalCount; ?>
  </div>
  <div class="d-flex align-items-center gap-2">
    <label class="small text-muted mb-0">Rows:</label>
    <select id="leadPerPageFooter" class="form-select form-select-sm" style="width:80px;">
      <option value="10" <?php if ($per_page == 10)
        echo 'selected'; ?>>10</option>
      <option value="25" <?php if ($per_page == 25)
        echo 'selected'; ?>>25</option>
      <option value="50" <?php if ($per_page == 50)
        echo 'selected'; ?>>50</option>
      <option value="100" <?php if ($per_page == 100)
        echo 'selected'; ?>>100</option>
    </select>
  </div>
</div>