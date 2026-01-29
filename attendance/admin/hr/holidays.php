<?php
date_default_timezone_set('Asia/Kolkata');
include '../config/db.php';

// Check if holidays table exists, if not create it
$tableCheck = $con->query("SHOW TABLES LIKE 'holidays'");
if ($tableCheck->num_rows == 0) {
  // Create holidays table
  $createTable = "
        CREATE TABLE `holidays` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `holiday_name` varchar(100) NOT NULL,
          `holiday_date` date NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `holiday_date` (`holiday_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
  $con->query($createTable);
}

// Kya yeh AJAX se bulaya gaya hai?
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// INSERT / UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $holiday_name = trim($_POST['holiday_name'] ?? '');
  $holiday_date = $_POST['holiday_date'] ?? '';
  $id = $_POST['id'] ?? '';

  if ($holiday_name !== '' && $holiday_date !== '') {
    if ($id == '') {
      $stmt = $con->prepare("INSERT INTO holidays (holiday_name, holiday_date) VALUES (?, ?)");
      $stmt->bind_param("ss", $holiday_name, $holiday_date);
      $stmt->execute();
    } else {
      $stmt = $con->prepare("UPDATE holidays SET holiday_name=?, holiday_date=? WHERE id=?");
      $stmt->bind_param("ssi", $holiday_name, $holiday_date, $id);
      $stmt->execute();
    }
  }

  // AJAX mode: JSON return, page reload nahi
  if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
      'success' => true,
      'reload' => 'holidays.php?ajax=1'
    ]);
    exit;
  }

  // Normal mode
  header("Location: holidays.php");
  exit;
}

// DELETE
if (isset($_GET['delete'])) {
  $id = (int) $_GET['delete'];
  $con->query("DELETE FROM holidays WHERE id = $id");

  if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
      'success' => true,
      'reload' => 'holidays.php?ajax=1'
    ]);
    exit;
  }

  header("Location: holidays.php");
  exit;
}

// EDIT
$editRow = null;
if (isset($_GET['edit'])) {
  $id = (int) $_GET['edit'];
  $resE = $con->query("SELECT * FROM holidays WHERE id = $id");
  $editRow = $resE->fetch_assoc();
}

// List holidays ordered by date
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 10;
if ($per_page > 100)
  $per_page = 100;
$offset = ($page - 1) * $per_page;

$totalCount = 0;
$countRes = $con->query("SELECT COUNT(*) AS c FROM holidays");
if ($countRes && $countRes->num_rows) {
  $r = $countRes->fetch_assoc();
  $totalCount = (int) ($r['c'] ?? 0);
}

$list = $con->query(
  "SELECT * FROM holidays ORDER BY holiday_date DESC LIMIT " . (int) $offset . "," . (int) $per_page
);

// --------- Common render function ----------
function renderHolidaysContent($editRow, $list, $isAjax, $totalCount, $page, $per_page, $offset)
{
  ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Holidays</h3>
    <button type="button" class="btn btn-dark" id="openHolidayModal">Add Holiday</button>
  </div>

  <!-- LIST TABLE -->
  <div class="card card-main">
    <div class="card-header card-main-header d-flex justify-content-between align-items-center">
      <span class="fw-semibold">Holiday List</span>
      <small class="text-muted">Total: <?php echo (int) $totalCount; ?></small>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr class="text-nowrap">
              <th>#</th>
              <th>Holiday Name</th>
              <th>Date</th>
              <th>Day</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $i = $totalCount > 0 ? ($offset + 1) : 1;
            if ($list && $list->num_rows > 0) {
              while ($row = $list->fetch_assoc()) {
                $dayName = date('l', strtotime($row['holiday_date']));
                $dateFormatted = date('d-m-Y', strtotime($row['holiday_date']));
                ?>
                <tr class="text-nowrap">
                  <td><?php echo $i++; ?></td>
                  <td><?php echo htmlspecialchars($row['holiday_name']); ?></td>
                  <td><?php echo $dateFormatted; ?></td>
                  <td><?php echo $dayName; ?></td>
                  <td class="text-end">
                    <?php if ($isAjax) { ?>
                      <!-- SPA mode: Edit via JS -->
                      <a href="javascript:void(0)" class="btn btn-sm btn-outline-primary holiday-edit"
                        data-edit-id="<?php echo $row['id']; ?>">
                        Edit
                      </a>
                    <?php } else { ?>
                      <!-- Normal mode: direct link -->
                      <a href="holidays.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    <?php } ?>

                    <a href="holidays.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger"
                      onclick="return confirm('Delete this holiday?');">
                      Delete
                    </a>
                  </td>
                </tr>
                <?php
              }
            } else { ?>
              <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                  No holidays found. Please add one.
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>

      <div id="holidaysPagingMeta" data-page="<?php echo (int) $page; ?>" data-per-page="<?php echo (int) $per_page; ?>"
        style="display:none;"></div>

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
              echo '<li class="page-item"><a href="#" class="page-link holidays-page-link" data-page="' . ($page - 1) . '">Previous</a></li>';
            if ($start > 1)
              echo '<li class="page-item"><a href="#" class="page-link holidays-page-link" data-page="1">1</a></li>' . ($start > 2 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : '');
            for ($p = $start; $p <= $end; $p++) {
              $cls = $p == $page ? ' page-item active' : ' page-item';
              echo '<li class="' . $cls . '"><a href="#" class="page-link holidays-page-link" data-page="' . $p . '">' . $p . '</a></li>';
            }
            if ($end < $totalPages)
              echo ($end < $totalPages - 1 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : '') . '<li class="page-item"><a href="#" class="page-link holidays-page-link" data-page="' . $totalPages . '">' . $totalPages . '</a></li>';
            if ($page < $totalPages)
              echo '<li class="page-item"><a href="#" class="page-link holidays-page-link" data-page="' . ($page + 1) . '">Next</a></li>';
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
          <select id="holidaysPerPageFooter" class="form-select form-select-sm" style="width:80px;">
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

  <div id="holidaysModalMeta" data-open="<?php echo $editRow ? '1' : '0'; ?>" style="display:none;"></div>

  <!-- Add/Edit Holiday Modal -->
  <div class="modal fade" id="holidayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="holidayModalTitle"><?php echo $editRow ? 'Edit Holiday' : 'Add Holiday'; ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST" action="holidays.php" id="holidayModalForm">
            <input type="hidden" name="id" value="<?php echo $editRow['id'] ?? ''; ?>">

            <div class="mb-3">
              <label class="form-label">Holiday Name</label>
              <input type="text" name="holiday_name" class="form-control" required
                placeholder="e.g., New Year, Diwali, Christmas"
                value="<?php echo htmlspecialchars($editRow['holiday_name'] ?? ''); ?>">
            </div>

            <div class="mb-3">
              <label class="form-label">Holiday Date</label>
              <input type="date" name="holiday_date" class="form-control" required
                value="<?php echo $editRow['holiday_date'] ?? ''; ?>">
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
} // end renderHolidaysContent

// ---------- AJAX request -> sirf inner content ----------
if ($isAjax) {
  renderHolidaysContent($editRow, $list, $isAjax, $totalCount, $page, $per_page, $offset);
  exit;
}

// ---------- Normal full page ----------
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Holidays</title>
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
          <?php renderHolidaysContent($editRow, $list, $isAjax, $totalCount, $page, $per_page, $offset); ?>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/utils.js"></script>
    <script src="js/tab_handlers.js"></script>
    <script src="js/form_handlers.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        // Initial init for holidays tab
        if (typeof initHolidaysTabEvents === 'function') {
          initHolidaysTabEvents();
        }
      });
    </script>
  </div>
</body>

</html>