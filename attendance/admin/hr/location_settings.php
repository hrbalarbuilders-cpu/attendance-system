<?php
// location_settings.php
// Manage geo-fence locations - multiple coordinates per location group (single table)
date_default_timezone_set('Asia/Kolkata');
include '../config/db.php';

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

$errors = [];
$success = '';

// Ensure geo_settings table has required columns (MySQL on WAMP may not support `ADD COLUMN IF NOT EXISTS`)
function ensureColumnExists(mysqli $con, string $table, string $column, string $definition): void {
  $tableEsc = '`' . str_replace('`', '``', $table) . '`';
  // Some MySQL/MariaDB builds don't allow placeholders in SHOW statements.
  $stmt = $con->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
  $stmt->bind_param('ss', $table, $column);
  $stmt->execute();
  $result = $stmt->get_result();
  $exists = $result && $result->num_rows > 0;
  $stmt->close();

  if (!$exists) {
    $con->query("ALTER TABLE {$tableEsc} ADD COLUMN {$definition}");
  }
}

ensureColumnExists($con, 'geo_settings', 'is_active', '`is_active` TINYINT(1) NOT NULL DEFAULT 1');
ensureColumnExists($con, 'geo_settings', 'location_group', '`location_group` VARCHAR(100) DEFAULT NULL');

// Handle add / update location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['location_form'])) {
  $id             = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $location_group = trim($_POST['location_group'] ?? '');
  $location_name  = trim($_POST['location_name'] ?? '');
  $latitude       = trim($_POST['latitude'] ?? '');
  $longitude      = trim($_POST['longitude'] ?? '');
  $radius         = (int)($_POST['radius_meters'] ?? 100);

  if ($location_group === '') {
    $errors[] = 'Location group is required.';
  }
  if ($location_name === '') {
    $errors[] = 'Point name is required.';
  }
  if ($latitude === '' || $longitude === '') {
    $errors[] = 'Latitude and Longitude are required.';
  }
  if ($radius <= 0) {
    $radius = 100;
  }

  if (empty($errors)) {
    if ($id > 0) {
      // Update
      $stmt = $con->prepare("UPDATE geo_settings SET location_group = ?, location_name = ?, latitude = ?, longitude = ?, radius_meters = ? WHERE id = ?");
      $stmt->bind_param("ssssii", $location_group, $location_name, $latitude, $longitude, $radius, $id);
      if ($stmt->execute()) {
        $success = 'Location updated successfully.';
      } else {
        $errors[] = 'Failed to update location.';
      }
    } else {
      // Insert new location
      $stmt = $con->prepare("INSERT INTO geo_settings (location_group, location_name, latitude, longitude, radius_meters, is_active) VALUES (?, ?, ?, ?, ?, 1)");
      $stmt->bind_param("ssssi", $location_group, $location_name, $latitude, $longitude, $radius);
      if ($stmt->execute()) {
        $success = 'Location added successfully.';
      } else {
        $errors[] = 'Failed to add location.';
      }
    }
  }
}

// Handle toggle
if (isset($_GET['toggle'])) {
  $toggleId = (int)$_GET['toggle'];
  $con->query("UPDATE geo_settings SET is_active = 1 - is_active WHERE id = {$toggleId}");
  if ($isAjax) { exit; }
  header('Location: location_settings.php');
  exit;
}

// Handle delete
if (isset($_GET['delete'])) {
  $delId = (int)$_GET['delete'];
  $con->query("DELETE FROM geo_settings WHERE id = {$delId}");
  if ($isAjax) { exit; }
  header('Location: location_settings.php');
  exit;
}

// Fetch all locations grouped
$list = $con->query("SELECT * FROM geo_settings ORDER BY COALESCE(location_group, location_name) ASC, location_name ASC");

// Get distinct location groups for dropdown
$groups = $con->query("SELECT DISTINCT location_group FROM geo_settings WHERE location_group IS NOT NULL AND location_group != '' ORDER BY location_group ASC");
$groupList = [];
while ($g = $groups->fetch_assoc()) {
  $groupList[] = $g['location_group'];
}

// --- AJAX mode: output only the content ---
if ($isAjax) {
?>
<style>
  .actions-cell { display: flex; flex-wrap: wrap; justify-content: center; gap: 0.25rem; }
  .group-header { background: #e9ecef; font-weight: 600; }
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h3 class="mb-0">Locations</h3>
    <div class="text-muted small">Manage geo-fence locations (multiple points per location)</div>
  </div>
  <button type="button" class="btn btn-dark btn-sm" id="openLocationModal">+ Add Point</button>
</div>
<?php if ($success): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
<?php endif; ?>
<div class="card settings-card shadow-sm border-0" style="border-radius:18px;">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover table-borderless align-middle mb-0 w-100" style="font-size: 0.97rem;">
        <thead class="table-light" style="font-size:0.93em;">
          <tr>
            <th class="text-center px-2" style="width:50px;">#</th>
            <th class="text-start px-2">Location Group</th>
            <th class="text-start px-2">Point Name</th>
            <th class="text-center px-2">Lat / Lng</th>
            <th class="text-center px-2">Radius</th>
            <th class="text-center px-2" style="width:80px;">Status</th>
            <th class="text-center px-2" style="width:140px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($list && $list->num_rows > 0): ?>
          <?php $i = 1; $lastGroup = null; while ($row = $list->fetch_assoc()): 
            $currentGroup = $row['location_group'] ?: $row['location_name'];
          ?>
            <tr>
              <td class="text-center"><?php echo $i++; ?></td>
              <td class="text-start">
                <strong><?php echo htmlspecialchars($row['location_group'] ?: '-'); ?></strong>
              </td>
              <td class="text-start"><?php echo htmlspecialchars($row['location_name']); ?></td>
              <td class="text-center small">
                <?php echo htmlspecialchars($row['latitude']); ?>,<br>
                <?php echo htmlspecialchars($row['longitude']); ?>
              </td>
              <td class="text-center"><?php echo (int)$row['radius_meters']; ?>m</td>
              <td class="text-center">
                <?php if ((int)($row['is_active'] ?? 1) === 1): ?>
                  <span class="badge bg-success">Active</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Inactive</span>
                <?php endif; ?>
              </td>
              <td class="text-center actions-cell">
                <button type="button" class="btn btn-sm btn-outline-primary edit-location-btn"
                  data-id="<?php echo (int)$row['id']; ?>"
                  data-group="<?php echo htmlspecialchars($row['location_group'] ?? ''); ?>"
                  data-name="<?php echo htmlspecialchars($row['location_name']); ?>"
                  data-lat="<?php echo htmlspecialchars($row['latitude']); ?>"
                  data-lng="<?php echo htmlspecialchars($row['longitude']); ?>"
                  data-radius="<?php echo (int)$row['radius_meters']; ?>"
                >Edit</button>
                <button type="button" class="btn btn-sm btn-outline-warning location-toggle" data-id="<?php echo (int)$row['id']; ?>">Toggle</button>
                <button type="button" class="btn btn-sm btn-outline-danger location-delete" data-id="<?php echo (int)$row['id']; ?>">Delete</button>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-muted text-center py-3">No locations defined yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../includes/modal-location.php'; ?>
<script>
  window.existingLocationGroups = <?php echo json_encode($groupList); ?>;
</script>
<?php
  exit;
}
// --- END AJAX mode ---

// Full page mode (standalone access)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Location Settings</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .actions-cell { display: flex; flex-wrap: wrap; justify-content: center; gap: 0.25rem; }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Locations (Geo-fence)</h3>
    <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#locationModal">+ Add Point</button>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Location Group</th>
              <th>Point Name</th>
              <th>Lat / Lng</th>
              <th>Radius</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($list && $list->num_rows > 0): ?>
            <?php $list->data_seek(0); $i = 1; while ($row = $list->fetch_assoc()): ?>
              <tr>
                <td><?php echo $i++; ?></td>
                <td><strong><?php echo htmlspecialchars($row['location_group'] ?: '-'); ?></strong></td>
                <td><?php echo htmlspecialchars($row['location_name']); ?></td>
                <td class="small"><?php echo htmlspecialchars($row['latitude']); ?>, <?php echo htmlspecialchars($row['longitude']); ?></td>
                <td><?php echo (int)$row['radius_meters']; ?>m</td>
                <td>
                  <?php if ((int)($row['is_active'] ?? 1) === 1): ?>
                    <span class="badge bg-success">Active</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Inactive</span>
                  <?php endif; ?>
                </td>
                <td class="actions-cell">
                  <button type="button" class="btn btn-sm btn-outline-primary edit-location-btn"
                    data-id="<?php echo (int)$row['id']; ?>"
                    data-group="<?php echo htmlspecialchars($row['location_group'] ?? ''); ?>"
                    data-name="<?php echo htmlspecialchars($row['location_name']); ?>"
                    data-lat="<?php echo htmlspecialchars($row['latitude']); ?>"
                    data-lng="<?php echo htmlspecialchars($row['longitude']); ?>"
                    data-radius="<?php echo (int)$row['radius_meters']; ?>"
                  >Edit</button>
                  <a href="location_settings.php?toggle=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-warning">Toggle</a>
                  <a href="location_settings.php?delete=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this point?');">Delete</a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="7" class="text-muted">No locations defined yet.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/modal-location.php'; ?>
<script>window.existingLocationGroups = <?php echo json_encode($groupList); ?>;</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var modal = document.getElementById('locationModal');
  var bsModal = new bootstrap.Modal(modal);

  document.querySelectorAll('.edit-location-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      modal.querySelector('[name="id"]').value = btn.dataset.id;
      modal.querySelector('[name="location_group"]').value = btn.dataset.group;
      modal.querySelector('[name="location_name"]').value = btn.dataset.name;
      modal.querySelector('[name="latitude"]').value = btn.dataset.lat;
      modal.querySelector('[name="longitude"]').value = btn.dataset.lng;
      modal.querySelector('[name="radius_meters"]').value = btn.dataset.radius;
      modal.querySelector('.modal-title').textContent = 'Edit Point';
      modal.querySelector('button[type="submit"]').textContent = 'Update';
      bsModal.show();
    });
  });

  modal.addEventListener('hidden.bs.modal', function() {
    modal.querySelector('[name="id"]').value = '0';
    modal.querySelector('[name="location_group"]').value = '';
    modal.querySelector('[name="location_name"]').value = '';
    modal.querySelector('[name="latitude"]').value = '';
    modal.querySelector('[name="longitude"]').value = '';
    modal.querySelector('[name="radius_meters"]').value = '100';
    modal.querySelector('.modal-title').textContent = 'Add Point';
    modal.querySelector('button[type="submit"]').textContent = 'Add Point';
  });
});
</script>
</body>
</html>
