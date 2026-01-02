<?php
include_once __DIR__ . '/../config/db.php';

// Query leads and left-join lead_sources so we can display the source name (handles name/title fallback)
// Detect whether `name` or `title` columns exist in `lead_sources` to build a safe SELECT
$hasName = false; $hasTitle = false;
$col = $con->query("SHOW COLUMNS FROM lead_sources LIKE 'name'"); if ($col && $col->num_rows) $hasName = true;
$col = $con->query("SHOW COLUMNS FROM lead_sources LIKE 'title'"); if ($col && $col->num_rows) $hasTitle = true;
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

$res = $con->query(
  "SELECT l.*, " . $selectSourceExpr . " FROM leads l LEFT JOIN lead_sources ls ON l.lead_source_id = ls.id ORDER BY l.id DESC"
);

function col($row, $k){
    return isset($row[$k]) ? htmlspecialchars($row[$k]) : '';
}
?>
<div class="table-responsive">
  <table class="table table-hover align-middle mb-0" id="leadsTable">
    <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Contact</th>
        <th>Email</th>
        <th>Lead Source</th>
        <th>Looking For</th>
        <th>Sales Person</th>
        <th>Status</th>
        <th>Created</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($res && $res->num_rows):
        while ($row = $res->fetch_assoc()): ?>
      <tr data-id="<?= col($row,'id') ?>">
        <td><?= col($row,'id') ?></td>
        <td><?= col($row,'name') ?></td>
        <td><?= col($row,'contact_number') ?></td>
        <td><?= col($row,'email') ?></td>
        <td><?= col($row,'source_name') ?></td>
        <td><?= col($row,'looking_for_id') ?></td>
        <td><?= col($row,'sales_person') ?></td>
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
        <td><?= col($row,'created_at') ?></td>
        <td>
          <button class="btn btn-sm btn-outline-primary btn-edit-lead" data-lead-id="<?= col($row,'id') ?>">Edit</button>
          <button class="btn btn-sm btn-outline-danger btn-delete-lead">Delete</button>
        </td>
      </tr>
    <?php endwhile; else: ?>
      <tr><td colspan="8">No leads found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
