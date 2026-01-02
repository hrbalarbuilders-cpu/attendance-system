<?php
include_once __DIR__ . '/../config/db.php';

$res = $con->query("SELECT * FROM lead_sources ORDER BY id DESC");

function esc($v){ return htmlspecialchars($v); }
?>
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
