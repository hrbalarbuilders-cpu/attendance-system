<?php
// working_from_settings.php
// Manage allowed "Working From" options for attendance

date_default_timezone_set('Asia/Kolkata');
include '../config/db.php';

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

$errors = [];
$success = '';

// Ensure table exists (for older DBs)
$con->query("CREATE TABLE IF NOT EXISTS working_from_master (
  id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL,
  label VARCHAR(100) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

// Handle add / update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $code  = trim($_POST['code'] ?? '');
  $label = trim($_POST['label'] ?? '');
  $id    = isset($_POST['id']) ? (int)$_POST['id'] : 0;

  if ($code === '' || $label === '') {
    $errors[] = 'Both Code and Label are required.';
  }

  if (empty($errors)) {
    if ($id > 0) {
      // Update
      $stmt = $con->prepare("UPDATE working_from_master SET code = ?, label = ?, updated_at = NOW() WHERE id = ?");
      if ($stmt) {
        $stmt->bind_param('ssi', $code, $label, $id);
        if ($stmt->execute()) {
          $success = 'Working From updated successfully.';
        } else {
          $errors[] = 'Failed to update. DB error.';
        }
      } else {
        $errors[] = 'Failed to prepare update statement.';
      }
    } else {
      // Insert (avoid duplicate code)
      $stmt = $con->prepare("SELECT id FROM working_from_master WHERE code = ? LIMIT 1");
      if ($stmt) {
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
          $errors[] = 'Code already exists.';
        }
      }

      if (empty($errors)) {
        $stmt = $con->prepare("INSERT INTO working_from_master (code, label, is_active) VALUES (?, ?, 1)");
        if ($stmt) {
          $stmt->bind_param('ss', $code, $label);
          if ($stmt->execute()) {
            $success = 'Working From added successfully.';
          } else {
            $errors[] = 'Failed to add. DB error.';
          }
        } else {
          $errors[] = 'Failed to prepare insert statement.';
        }
      }
    }
  }
}

// Handle activate/deactivate
if (isset($_GET['toggle'])) {
  $toggleId = (int)$_GET['toggle'];
  $con->query("UPDATE working_from_master SET is_active = 1 - is_active, updated_at = NOW() WHERE id = {$toggleId}");
  if ($isAjax) { exit; }
  header('Location: working_from_settings.php');
  exit;
}

// Handle delete
if (isset($_GET['delete'])) {
  $delId = (int)$_GET['delete'];
  $con->query("DELETE FROM working_from_master WHERE id = {$delId}");
  if ($isAjax) { exit; }
  header('Location: working_from_settings.php');
  exit;
}

// Fetch list
$list = $con->query("SELECT * FROM working_from_master ORDER BY is_active DESC, label ASC");

// --- AJAX mode: output only the content, no HTML shell ---
if ($isAjax) {
?>
<style>
  .actions-cell { display: flex; flex-wrap: wrap; justify-content: center; gap: 0.25rem; }
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h3 class="mb-0">Working From</h3>
    <div class="text-muted small">Manage working location options</div>
  </div>
  <button type="button" class="btn btn-dark btn-sm" id="openWorkingFromModal">+ Add Option</button>
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
            <th class="text-start px-2">Code</th>
            <th class="text-start px-2">Label</th>
            <th class="text-center px-2" style="width:80px;">Status</th>
            <th class="text-center px-2" style="width:140px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($list && $list->num_rows > 0): ?>
          <?php $i = 1; while ($row = $list->fetch_assoc()): ?>
            <tr>
              <td class="text-center"><?php echo $i++; ?></td>
              <td class="text-start"><?php echo htmlspecialchars($row['code']); ?></td>
              <td class="text-start"><?php echo htmlspecialchars($row['label']); ?></td>
              <td class="text-center">
                <?php if ((int)$row['is_active'] === 1): ?>
                  <span class="badge bg-success">Active</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Inactive</span>
                <?php endif; ?>
              </td>
              <td class="text-center actions-cell">
                <button type="button" class="btn btn-sm btn-outline-primary edit-working-from-btn"
                  data-id="<?php echo (int)$row['id']; ?>"
                  data-code="<?php echo htmlspecialchars($row['code']); ?>"
                  data-label="<?php echo htmlspecialchars($row['label']); ?>"
                >Edit</button>
                <button type="button" class="btn btn-sm btn-outline-warning working-from-toggle" data-id="<?php echo (int)$row['id']; ?>">Toggle</button>
                <button type="button" class="btn btn-sm btn-outline-danger working-from-delete" data-id="<?php echo (int)$row['id']; ?>">Delete</button>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5" class="text-muted text-center py-3">No records found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../includes/modal-working-from.php'; ?>
<?php
  exit;
}
// --- END AJAX mode ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Working From Master</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .actions-cell {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 0.25rem;
    }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Working From Master</h3>
    <a href="settings.php" class="btn btn-outline-secondary">&larr; Back to Settings</a>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger mb-2">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?php echo htmlspecialchars($e); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">Add / Edit</h5>
          <form method="POST">
            <input type="hidden" name="id" id="wfId" value="0">
            <div class="mb-2">
              <label class="form-label small">Code</label>
              <input type="text" name="code" id="wfCode" class="form-control form-control-sm" placeholder="office, home, client" required>
            </div>
            <div class="mb-3">
              <label class="form-label small">Label</label>
              <input type="text" name="label" id="wfLabel" class="form-control form-control-sm" placeholder="Office, Home, Client Site" required>
            </div>
            <button type="submit" class="btn btn-sm btn-dark">Save</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-md-8">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">Existing Options</h5>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th style="width:60px;">ID</th>
                  <th>Code</th>
                  <th>Label</th>
                  <th style="width:100px;">Status</th>
                  <th style="width:140px;">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php if ($list && $list->num_rows > 0): ?>
                <?php while ($row = $list->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo (int)$row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['code']); ?></td>
                    <td><?php echo htmlspecialchars($row['label']); ?></td>
                    <td>
                      <?php if ((int)$row['is_active'] === 1): ?>
                        <span class="badge bg-success">Active</span>
                      <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td class="actions-cell">
                      <button type="button" class="btn btn-sm btn-outline-primary"
                              onclick="editWorkingFrom(<?php echo (int)$row['id']; ?>, '<?php echo htmlspecialchars($row['code']); ?>', '<?php echo htmlspecialchars($row['label']); ?>')">
                        Edit
                      </button>
                      <a href="working_from_settings.php?toggle=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-warning">
                        Toggle
                      </a>
                      <a href="working_from_settings.php?delete=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-danger"
                         onclick="return confirm('Delete this option?');">
                        Delete
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="text-muted">No records found.</td>
                </tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function editWorkingFrom(id, code, label) {
  document.getElementById('wfId').value = id;
  document.getElementById('wfCode').value = code;
  document.getElementById('wfLabel').value = label;
}
</script>
</body>
</html>
