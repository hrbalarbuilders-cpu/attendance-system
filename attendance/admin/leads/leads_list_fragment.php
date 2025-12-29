<?php
include_once __DIR__ . '/../config/db.php';

// Query all columns and render gracefully if some fields are missing in the schema.
$res = $con->query("SELECT * FROM leads ORDER BY id DESC");

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
        <th>Source ID</th>
        <th>Looking For ID</th>
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
        <td><?= col($row,'lead_source_id') ?></td>
        <td><?= col($row,'looking_for_id') ?></td>
        <td><?= col($row,'sales_person') ?></td>
        <td><?= col($row,'lead_status') ?></td>
        <td><?= col($row,'created_at') ?></td>
        <td>
          <button class="btn btn-sm btn-outline-primary btn-edit-lead">Edit</button>
          <button class="btn btn-sm btn-outline-danger btn-delete-lead">Delete</button>
        </td>
      </tr>
    <?php endwhile; else: ?>
      <tr><td colspan="8">No leads found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
