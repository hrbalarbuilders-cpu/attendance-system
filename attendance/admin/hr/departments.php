<?php
// Use canonical DB include
include '../config/db.php'; // provides $con

// AJAX mode check
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// --------- INSERT / UPDATE HANDLE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $dept_name = trim($_POST['department_name'] ?? '');
  $dept_id = $_POST['id'] ?? '';

  if ($dept_name !== '') {
    if ($dept_id == '') {
      // NEW INSERT
      $stmt = $con->prepare("INSERT INTO departments (department_name) VALUES (?)");
      $stmt->bind_param("s", $dept_name);
      $stmt->execute();
    } else {
      // UPDATE
      $stmt = $con->prepare("UPDATE departments SET department_name=? WHERE id=?");
      $stmt->bind_param("si", $dept_name, $dept_id);
      $stmt->execute();
    }
  }

  // If AJAX → return JSON, NO redirect
  if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    echo json_encode([
      "success" => true,
      "reload" => "departments.php?ajax=1"
    ]);
    exit;
  }

  // Normal mode → redirect
  header("Location: departments.php");
  exit;
}


// --------- DELETE (HARD DELETE) ----------
if (isset($_GET['delete'])) {
  $id = (int) $_GET['delete'];
  $con->query("DELETE FROM departments WHERE id = $id");
  header("Location: departments.php");
  exit;
}

// EDIT ke liye record laana
$editDept = null;
if (isset($_GET['edit'])) {
  $id = (int) $_GET['edit'];
  $res = $con->query("SELECT * FROM departments WHERE id = $id");
  $editDept = $res->fetch_assoc();
}

// List data
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 10;
if ($per_page > 100)
  $per_page = 100;
$offset = ($page - 1) * $per_page;

$totalCount = 0;
$countRes = $con->query("SELECT COUNT(*) AS c FROM departments");
if ($countRes && $countRes->num_rows) {
  $r = $countRes->fetch_assoc();
  $totalCount = (int) ($r['c'] ?? 0);
}

$list = $con->query(
  "SELECT * FROM departments ORDER BY department_name ASC LIMIT " . (int) $offset . "," . (int) $per_page
);

// ---------- Common content rendering function ----------
function renderDepartmentsContent($editDept, $list, $totalCount, $page, $per_page, $offset)
{
  ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Departments</h3>
    <button type="button" class="btn btn-dark" id="openDepartmentModal">Add Department</button>
  </div>

  <!-- LIST TABLE -->
  <div class="card card-main">
    <div class="card-header card-main-header d-flex justify-content-between align-items-center">
      <span class="fw-semibold">Department List</span>
      <small class="text-muted">Total: <?php echo (int) $totalCount; ?></small>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr class="text-nowrap">
              <th>#</th>
              <th>Department Name</th>
              <th>Created</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $i = $totalCount > 0 ? ($offset + 1) : 1;
            if ($list && $list->num_rows > 0) {
              while ($row = $list->fetch_assoc()) { ?>
                <tr class="text-nowrap">
                  <td><?php echo $i++; ?></td>
                  <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                  <td>
                    <?php
                    echo !empty($row['created_at'])
                      ? date('d M Y, h:i A', strtotime($row['created_at']))
                      : '-';
                    ?>
                  </td>
                  <td class="text-end">
                    <a href="javascript:void(0)" class="btn btn-sm btn-outline-primary dept-edit"
                      data-edit-id="<?php echo $row['id']; ?>">
                      Edit
                    </a>
                    <a href="departments.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger"
                      onclick="return confirm('Delete this department?');">
                      Delete
                    </a>
                  </td>
                </tr>
                <?php
              }
            } else { ?>
              <tr>
                <td colspan="4" class="text-center py-4 text-muted">
                  No departments found. Please add one.
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>

      <div id="departmentsPagingMeta" data-page="<?php echo (int) $page; ?>"
        data-per-page="<?php echo (int) $per_page; ?>" style="display:none;"></div>

      <?php
      $totalPages = max(1, (int) ceil(($totalCount ?: 0) / max(1, $per_page)));
      $startRec = $totalCount > 0 ? ($offset + 1) : 0;
      $endRec = $totalCount > 0 ? ($offset + ($list ? $list->num_rows : 0)) : 0;
      ?>

      <?php if ($totalPages > 1): ?>
        <nav class="mt-3 px-2">
          <ul class="pagination mb-0">
            <?php
            $start = max(1, $page - 3);
            $end = min($totalPages, $page + 3);
            if ($page > 1)
              echo '<li class="page-item"><a href="#" class="page-link departments-page-link" data-page="' . ($page - 1) . '">Previous</a></li>';
            if ($start > 1)
              echo '<li class="page-item"><a href="#" class="page-link departments-page-link" data-page="1">1</a></li>' . ($start > 2 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : '');
            for ($p = $start; $p <= $end; $p++) {
              $cls = $p == $page ? ' page-item active' : ' page-item';
              echo '<li class="' . $cls . '"><a href="#" class="page-link departments-page-link" data-page="' . $p . '">' . $p . '</a></li>';
            }
            if ($end < $totalPages)
              echo ($end < $totalPages - 1 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : '') . '<li class="page-item"><a href="#" class="page-link departments-page-link" data-page="' . $totalPages . '">' . $totalPages . '</a></li>';
            if ($page < $totalPages)
              echo '<li class="page-item"><a href="#" class="page-link departments-page-link" data-page="' . ($page + 1) . '">Next</a></li>';
            ?>
          </ul>
        </nav>
      <?php endif; ?>

      <div class="d-flex justify-content-between align-items-center mt-2 px-2 pb-2">
        <div class="small text-muted">Record <?php echo (int) $startRec; ?>–<?php echo (int) $endRec; ?> of
          <?php echo (int) $totalCount; ?>
        </div>
        <div class="d-flex align-items-center gap-2">
          <label class="small text-muted mb-0">Rows:</label>
          <select id="departmentsPerPageFooter" class="form-select form-select-sm" style="width:80px;">
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
    </div>
  </div>

  <div id="departmentsModalMeta" data-open="<?php echo $editDept ? '1' : '0'; ?>" style="display:none;"></div>

  <!-- Add/Edit Department Modal -->
  <div class="modal fade" id="departmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="departmentModalTitle">
            <?php echo $editDept ? 'Edit Department' : 'Add Department'; ?>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST" action="departments.php" id="departmentModalForm">
            <input type="hidden" name="id" value="<?php echo $editDept['id'] ?? ''; ?>">
            <div class="mb-3">
              <label class="form-label">Department Name</label>
              <input type="text" name="department_name" class="form-control" required
                value="<?php echo htmlspecialchars($editDept['department_name'] ?? ''); ?>">
            </div>
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-dark"><?php echo $editDept ? 'Update' : 'Save'; ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <?php
} // end function

// ---------- If AJAX: sirf inner content bhejo, no HTML wrapper ----------
if ($isAjax) {
  renderDepartmentsContent($editDept, $list, $totalCount, $page, $per_page, $offset);
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Departments</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="css/hr_dashboard.css" rel="stylesheet">
  <?php include_once __DIR__ . '/../includes/table-styles.php'; ?>
</head>

<body>
  <?php include_once '../includes/header.php'; ?>

  <div class="main-content-scroll">
    <div id="loaderOverlay" class="d-none">
      <div class="loader-spinner"></div>
    </div>
    <div id="statusAlertWrapper" class="position-fixed start-50 translate-middle-x p-3">
      <div id="statusAlert"
        class="alert alert-success shadow-sm d-none align-items-center justify-content-between mb-0 text-center"
        role="alert">
        <span id="statusAlertText"></span>
        <button type="button" class="btn-close ms-2" aria-label="Close"
          onclick="document.getElementById('statusAlert').classList.add('d-none');">
        </button>
      </div>
    </div>

    <div class="container-fluid py-3 d-flex justify-content-center" style="padding-top:72px;">
      <div class="page-wrapper w-100">
        <div class="d-flex justify-content-center align-items-center gap-3 mb-4">
          <?php include_once __DIR__ . '/../includes/navbar-hr.php'; ?>
          <a href="settings.php" class="btn-round-icon" title="Settings">
            <i class="bi bi-gear-fill"></i>
          </a>
        </div>

        <div id="contentArea">
          <?php renderDepartmentsContent($editDept, $list, $totalCount, $page, $per_page, $offset); ?>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/utils.js"></script>
    <script src="js/tab_handlers.js"></script>
    <script src="js/form_handlers.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        // Initial init for departments tab
        if (typeof initDepartmentsTabEvents === 'function') {
          initDepartmentsTabEvents();
        }
      });
    </script>
  </div>
</body>

</html>