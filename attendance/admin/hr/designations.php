<?php
include '../config/db.php';

// Kya yeh AJAX se bulaya gaya hai?
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// All departments for dropdown
$deptRes = $con->query("SELECT id, department_name FROM departments ORDER BY department_name");

// INSERT / UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $designation_name = trim($_POST['designation_name'] ?? '');
  $department_id = (int) ($_POST['department_id'] ?? 0);
  $id = $_POST['id'] ?? '';

  if ($designation_name !== '' && $department_id > 0) {
    if ($id == '') {
      $stmt = $con->prepare("INSERT INTO designations (department_id, designation_name) VALUES (?, ?)");
      $stmt->bind_param("is", $department_id, $designation_name);
      $stmt->execute();
    } else {
      $stmt = $con->prepare("UPDATE designations SET department_id=?, designation_name=? WHERE id=?");
      $stmt->bind_param("isi", $department_id, $designation_name, $id);
      $stmt->execute();
    }
  }

  // AJAX mode: JSON return, page reload nahi
  if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
      'success' => true,
      'reload' => 'designations.php?ajax=1'
    ]);
    exit;
  }

  // Normal mode
  header("Location: designations.php");
  exit;
}

// DELETE
if (isset($_GET['delete'])) {
  $id = (int) $_GET['delete'];
  $con->query("DELETE FROM designations WHERE id = $id");

  if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
      'success' => true,
      'reload' => 'designations.php?ajax=1'
    ]);
    exit;
  }

  header("Location: designations.php");
  exit;
}

// EDIT
$editRow = null;
if (isset($_GET['edit'])) {
  $id = (int) $_GET['edit'];
  $resE = $con->query("SELECT * FROM designations WHERE id = $id");
  $editRow = $resE->fetch_assoc();
}

// List with department name
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 10;
if ($per_page > 100)
  $per_page = 100;
$offset = ($page - 1) * $per_page;

$totalCount = 0;
$countRes = $con->query("SELECT COUNT(*) AS c FROM designations");
if ($countRes && $countRes->num_rows) {
  $r = $countRes->fetch_assoc();
  $totalCount = (int) ($r['c'] ?? 0);
}

$sql = "SELECT dsg.*, dept.department_name
  FROM designations dsg
  JOIN departments dept ON dsg.department_id = dept.id
  ORDER BY dept.department_name, dsg.designation_name
  LIMIT " . (int) $offset . "," . (int) $per_page;
$list = $con->query($sql);

// --------- Common render function ----------
function renderDesignationsContent($deptRes, $editRow, $list, $isAjax, $totalCount, $page, $per_page, $offset)
{
  ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Designations</h3>
    <button type="button" class="btn btn-dark" id="openDesignationModal">Add Designation</button>
  </div>

  <!-- LIST TABLE -->
  <div class="card card-main">
    <div class="card-header card-main-header d-flex justify-content-between align-items-center">
      <span class="fw-semibold">Designation List</span>
      <small class="text-muted">Total: <?php echo (int) $totalCount; ?></small>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr class="text-nowrap">
              <th>#</th>
              <th>Department</th>
              <th>Designation</th>
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
                  <td><?php echo htmlspecialchars($row['designation_name']); ?></td>
                  <td>
                    <?php
                    echo !empty($row['created_at'])
                      ? date('d M Y, h:i A', strtotime($row['created_at']))
                      : '-';
                    ?>
                  </td>
                  <td class="text-end">
                    <?php if ($isAjax) { ?>
                      <!-- SPA mode: Edit via JS -->
                      <a href="javascript:void(0)" class="btn btn-sm btn-outline-primary desig-edit"
                        data-edit-id="<?php echo $row['id']; ?>">
                        Edit
                      </a>
                    <?php } else { ?>
                      <!-- Normal mode: direct link -->
                      <a href="designations.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    <?php } ?>

                    <a href="designations.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger"
                      onclick="return confirm('Delete this designation?');">
                      Delete
                    </a>
                  </td>
                </tr>
                <?php
              }
            } else { ?>
              <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                  No designations found. Please add one.
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>

      <div id="designationsPagingMeta" data-page="<?php echo (int) $page; ?>"
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
              echo '<li class="page-item"><a href="#" class="page-link designations-page-link" data-page="' . ($page - 1) . '">Previous</a></li>';
            if ($start > 1)
              echo '<li class="page-item"><a href="#" class="page-link designations-page-link" data-page="1">1</a></li>' . ($start > 2 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : '');
            for ($p = $start; $p <= $end; $p++) {
              $cls = $p == $page ? ' page-item active' : ' page-item';
              echo '<li class="' . $cls . '"><a href="#" class="page-link designations-page-link" data-page="' . $p . '">' . $p . '</a></li>';
            }
            if ($end < $totalPages)
              echo ($end < $totalPages - 1 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : '') . '<li class="page-item"><a href="#" class="page-link designations-page-link" data-page="' . $totalPages . '">' . $totalPages . '</a></li>';
            if ($page < $totalPages)
              echo '<li class="page-item"><a href="#" class="page-link designations-page-link" data-page="' . ($page + 1) . '">Next</a></li>';
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
          <select id="designationsPerPageFooter" class="form-select form-select-sm" style="width:80px;">
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

  <div id="designationsModalMeta" data-open="<?php echo $editRow ? '1' : '0'; ?>" style="display:none;"></div>

  <!-- Add/Edit Designation Modal -->
  <div class="modal fade" id="designationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="designationModalTitle">
            <?php echo $editRow ? 'Edit Designation' : 'Add Designation'; ?>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST" action="designations.php" id="designationModalForm">
            <input type="hidden" name="id" value="<?php echo $editRow['id'] ?? ''; ?>">

            <div class="mb-3">
              <label class="form-label">Department</label>
              <select name="department_id" class="form-select" required>
                <option value="">Select Department</option>
                <?php
                mysqli_data_seek($deptRes, 0);
                while ($d = $deptRes->fetch_assoc()) {
                  $selected = ($editRow && $editRow['department_id'] == $d['id']) ? 'selected' : '';
                  ?>
                  <option value="<?php echo $d['id']; ?>" <?php echo $selected; ?>>
                    <?php echo htmlspecialchars($d['department_name']); ?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Designation Name</label>
              <input type="text" name="designation_name" class="form-control" required
                value="<?php echo htmlspecialchars($editRow['designation_name'] ?? ''); ?>">
            </div>

            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-dark"><?php echo $editRow ? 'Update' : 'Save'; ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <?php
} // end renderDesignationsContent

// ---------- AJAX request -> sirf inner content ----------
if ($isAjax) {
  renderDesignationsContent($deptRes, $editRow, $list, $isAjax, $totalCount, $page, $per_page, $offset);
  exit;
}

// ---------- Normal full page ----------
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Designations</title>
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
          <?php renderDesignationsContent($deptRes, $editRow, $list, $isAjax, $totalCount, $page, $per_page, $offset); ?>
        </div>
      </div>
    </div>

    <!-- Shared HR Modals -->
    <?php include __DIR__ . '/../includes/hr-modals.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/utils.js"></script>
    <script src="js/tab_handlers.js"></script>
    <script src="js/form_handlers.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        // Initial init for designation tab
        if (typeof initDesignationsTabEvents === 'function') {
          initDesignationsTabEvents();
        }
      });
    </script>
  </div>
</body>

</html>