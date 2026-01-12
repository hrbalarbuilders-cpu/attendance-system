<?php
// shifts.php
// Ensure all times on this page are handled/displayed in Indian time
date_default_timezone_set('Asia/Kolkata');
include '../config/db.php';

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

$errors = [];

// ---------------- HANDLE FORM SUBMIT (ADD / EDIT) ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id                     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $shift_name             = trim($_POST['shift_name'] ?? '');
  $shift_color            = trim($_POST['shift_color'] ?? '#0d6efd');
    $start_time             = $_POST['start_time'] ?? '';
    $end_time               = $_POST['end_time'] ?? '';
    $lunch_start            = $_POST['lunch_start'] ?: null; // optional
    $lunch_end              = $_POST['lunch_end'] ?: null;   // optional
    $early_clock_in_before  = (int)($_POST['early_clock_in_before'] ?? 0);
    $late_mark_after        = (int)($_POST['late_mark_after'] ?? 0);
    $total_punches          = (int)($_POST['total_punches'] ?? 0);
    $half_day_time          = $_POST['half_day_time'] ?? '';

    // Normalize all time inputs to 24-hour "H:i" format while allowing AM/PM entry
    $normalizeTime = function ($t) {
      $t = trim((string)$t);
      if ($t === '') {
        return '';
      }
      $ts = strtotime($t);
      if ($ts === false) {
        return $t; // leave as-is, will be caught by validation if invalid
      }
      return date('H:i', $ts);
    };

    $start_time = $normalizeTime($start_time);
    $end_time   = $normalizeTime($end_time);
    if ($lunch_start !== null && $lunch_start !== '') {
      $lunch_start = $normalizeTime($lunch_start);
    }
    if ($lunch_end !== null && $lunch_end !== '') {
      $lunch_end = $normalizeTime($lunch_end);
    }

    // Basic validation
    if ($shift_name === '') {
        $errors[] = "Shift name is required.";
    }
    if ($shift_color === '') {
      $shift_color = '#0d6efd';
    }
    if (!preg_match('/^#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})$/', $shift_color)) {
      $errors[] = "Shift color must be a valid hex color (example: #0d6efd).";
    }
    if ($start_time === '') {
        $errors[] = "Start time is required.";
    }
    if ($end_time === '') {
        $errors[] = "End time is required.";
    }
    if ($late_mark_after <= 0) {
        $errors[] = "Late mark after (minutes) must be greater than 0.";
    }
    if ($total_punches <= 0) {
        $errors[] = "Total punches per day must be greater than 0.";
    }

    // Half time validation + minutes calculation
    // If admin leaves half_day_time empty, auto-calc as half of shift duration
    // (based on start_time and end_time), but still allow manual override.
    if ($start_time === '') {
      $errors[] = "Start time is required to calculate half time.";
    } else {
      $startTs = strtotime($start_time);
      $endTs   = $end_time !== '' ? strtotime($end_time) : false;

      // Compute full shift duration in minutes (handle overnight shifts)
      $shiftMinutes = null;
      if ($startTs !== false && $endTs !== false) {
        if ($endTs <= $startTs) {
          // Overnight shift (e.g. 22:00 to 07:30 next day)
          $endTs += 24 * 60 * 60;
        }
        $shiftMinutes = (int)(($endTs - $startTs) / 60);
      }

      if ($half_day_time === '') {
        if ($shiftMinutes === null) {
          $errors[] = "Half time is required.";
        } else {
          // Auto: half time after half of total shift duration
          $half_day_after = (int)round($shiftMinutes / 2);
        }
      } else {
        $halfTs = strtotime($half_day_time);

        // Validate manual half time, supporting overnight shifts (e.g. 8 PM to 8 AM)
        if ($halfTs === false || $startTs === false || $endTs === false) {
          $errors[] = "Invalid half time.";
        } else {
          $startMin = (int)date('H', $startTs) * 60 + (int)date('i', $startTs);
          $endMin   = (int)date('H', $endTs) * 60 + (int)date('i', $endTs);
          $halfMin  = (int)date('H', $halfTs) * 60 + (int)date('i', $halfTs);

          $isOvernight = $endMin <= $startMin;
          if ($isOvernight) {
            // Shift crosses midnight: move end to next day
            $endMin += 24 * 60;
            // If half time appears before start on clock, treat as next day
            if ($halfMin <= $startMin) {
              $halfMin += 24 * 60;
            }
          }

          if ($halfMin <= $startMin) {
            $errors[] = "Half time must be after shift start time.";
          } elseif ($halfMin >= $endMin) {
            $errors[] = "Half time must be before shift end time.";
          } else {
            // Store minutes from shift start to half time
            $half_day_after = $halfMin - $startMin;
          }
        }
      }
    }

    if (empty($errors)) {
        if ($id === 0) {
            // INSERT
            $sql = "INSERT INTO shifts 
            (shift_name, shift_color, start_time, end_time, lunch_start, lunch_end,
                 early_clock_in_before, late_mark_after, half_day_after, total_punches)
            VALUES (?,?,?,?,?,?,?,?,?,?)";

            $stmt = $con->prepare($sql);
            if ($stmt) {
                $stmt->bind_param(
                  "ssssssiiii",
                    $shift_name,
                  $shift_color,
                    $start_time,
                    $end_time,
                    $lunch_start,
                    $lunch_end,
                    $early_clock_in_before,
                    $late_mark_after,
                    $half_day_after,
                    $total_punches
                );

                if (!$stmt->execute()) {
                    $errors[] = "Database error: " . $con->error;
                }
            } else {
                $errors[] = "Failed to prepare insert query: " . $con->error;
            }
        } else {
            // UPDATE
            $updated_at = date("Y-m-d H:i:s");
            $sql = "UPDATE shifts SET 
                        shift_name = ?, 
                  shift_color = ?,
                        start_time = ?, 
                        end_time = ?, 
                        lunch_start = ?, 
                        lunch_end = ?, 
                        early_clock_in_before = ?, 
                        late_mark_after = ?, 
                        half_day_after = ?, 
                        total_punches = ?, 
                        updated_at = ?
                    WHERE id = ?";

            $stmt = $con->prepare($sql);
            if ($stmt) {
                $stmt->bind_param(
                  "ssssssiiiisi",
                    $shift_name,
                  $shift_color,
                    $start_time,
                    $end_time,
                    $lunch_start,
                    $lunch_end,
                    $early_clock_in_before,
                    $late_mark_after,
                    $half_day_after,
                    $total_punches,
                    $updated_at,
                    $id
                );

                if (!$stmt->execute()) {
                    $errors[] = "Database error: " . $con->error;
                }
            } else {
                $errors[] = "Failed to prepare update query: " . $con->error;
            }
        }
    }

    // Response handling
    if ($isAjax) {
        header('Content-Type: application/json');
        if (empty($errors)) {
            echo json_encode([
                'success' => true,
                'reload'  => 'shifts.php?ajax=1',
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'errors'  => $errors,
            ]);
        }
        exit;
    } else {
        if (empty($errors)) {
            header("Location: shifts.php");
            exit;
        }
        // agar errors hain & normal mode hai to neeche form ke saath show karenge
    }
}

// ---------------- HANDLE DELETE ----------------
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $con->query("DELETE FROM shifts WHERE id = $delId");

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'reload'  => 'shifts.php?ajax=1',
        ]);
        exit;
    } else {
        header("Location: shifts.php");
        exit;
    }
}

// ---------------- EDIT MODE: FETCH SINGLE SHIFT ----------------
$editRow = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $res = $con->query("SELECT * FROM shifts WHERE id = $editId");
    $editRow = $res->fetch_assoc();
}

// ---------------- LIST ALL SHIFTS ----------------
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 10;
if ($per_page > 100) $per_page = 100;
$offset = ($page - 1) * $per_page;

$totalCount = 0;
$countRes = $con->query("SELECT COUNT(*) AS c FROM shifts");
if ($countRes && $countRes->num_rows) {
  $r = $countRes->fetch_assoc();
  $totalCount = (int)($r['c'] ?? 0);
}

$list = $con->query(
  "SELECT * FROM shifts ORDER BY shift_name ASC LIMIT " . (int)$offset . "," . (int)$per_page
);

// ---------------- RENDER FUNCTION ----------------
function renderShiftContent($errors, $editRow, $list, $totalCount, $page, $per_page, $offset) {
?>
<div class="container py-4">
  <?php if ($GLOBALS['isAjax']): ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h3 class="mb-0">Shift Master</h3>
        <div class="text-muted small">Create and manage shifts</div>
      </div>
      <?php if (!$editRow): ?>
        <button type="button"
                class="btn btn-dark btn-sm"
                id="openCreateShiftModal"
                data-bs-toggle="modal"
                data-bs-target="#createShiftModal">
          + Create Shift
        </button>
      <?php else: ?>
        <a href="shifts.php?ajax=1" class="btn btn-outline-secondary btn-sm">Back</a>
      <?php endif; ?>
    </div>

    <?php
      $modalIsEdit = (bool)$editRow;
      $modalTitle = $modalIsEdit ? 'Edit Shift' : 'Create Shift';
      $modalSubmitText = $modalIsEdit ? 'Update Shift' : 'Save Shift';
      $modalId = $modalIsEdit ? (int)($editRow['id'] ?? 0) : 0;
      $modalShiftName = $modalIsEdit ? (string)($editRow['shift_name'] ?? '') : '';
      $modalShiftColor = $modalIsEdit ? (string)($editRow['shift_color'] ?? '#0d6efd') : '#0d6efd';

      $modalStart = '';
      if ($modalIsEdit && !empty($editRow['start_time'])) {
        $modalStart = date('h:i A', strtotime($editRow['start_time']));
      }
      $modalEnd = '';
      if ($modalIsEdit && !empty($editRow['end_time'])) {
        $modalEnd = date('h:i A', strtotime($editRow['end_time']));
      }
      $modalLunchStart = '';
      if ($modalIsEdit && !empty($editRow['lunch_start'])) {
        $modalLunchStart = date('h:i A', strtotime($editRow['lunch_start']));
      }
      $modalLunchEnd = '';
      if ($modalIsEdit && !empty($editRow['lunch_end'])) {
        $modalLunchEnd = date('h:i A', strtotime($editRow['lunch_end']));
      }
      $modalHalf = '';
      if ($modalIsEdit && !empty($editRow['start_time']) && isset($editRow['half_day_after'])) {
        $halfTs = strtotime($editRow['start_time']) + ((int)$editRow['half_day_after'] * 60);
        if ($halfTs) $modalHalf = date('h:i A', $halfTs);
      }
      $modalEarly = $modalIsEdit ? (int)($editRow['early_clock_in_before'] ?? 0) : 0;
      $modalLate = $modalIsEdit ? (int)($editRow['late_mark_after'] ?? 10) : 10;
      $modalPunches = $modalIsEdit ? (int)($editRow['total_punches'] ?? 4) : 4;
    ?>

    <?php include '../includes/modal-shift.php'; ?>
  <?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-0">Shift Master</h3>
      <a href="employees.php" class="btn btn-outline-secondary">Go to HR</a>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)) { ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $e) { ?>
          <li><?php echo htmlspecialchars($e); ?></li>
        <?php } ?>
      </ul>
    </div>
  <?php } ?>

  <!-- ADD / EDIT FORM (not used inside Settings; Settings uses modal) -->
  <?php if (!$GLOBALS['isAjax']): ?>
  <div class="card mb-4">
    <div class="card-header">
      <?php echo $editRow ? 'Edit Shift' : 'Add New Shift'; ?>
    </div>
    <div class="card-body">
      <form method="POST" action="shifts.php">
        <input type="hidden" name="id" value="<?php echo $editRow['id'] ?? 0; ?>">

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Shift Name</label>
            <input type="text"
                   name="shift_name"
                   class="form-control"
                   required
                   value="<?php echo htmlspecialchars($editRow['shift_name'] ?? ($_POST['shift_name'] ?? '')); ?>">
          </div>

          <div class="col-md-2">
            <label class="form-label">Shift Color</label>
            <input type="color"
                   name="shift_color"
                   class="form-control form-control-color"
                   style="width: 100%;"
                   value="<?php echo htmlspecialchars($editRow['shift_color'] ?? ($_POST['shift_color'] ?? '#0d6efd')); ?>"
                   title="Choose shift color">
          </div>

          <div class="col-md-3">
            <label class="form-label">Start Time</label>
            <div class="input-group">
              <input type="text"
                     name="start_time"
                     id="start_time"
                     class="form-control time-input"
                     placeholder="09:00 AM"
                     readonly
                     required
                     value="<?php
                       if (!empty($_POST['start_time'])) {
                           echo htmlspecialchars($_POST['start_time']);
                       } elseif (!empty($editRow['start_time'])) {
                           echo htmlspecialchars(date('h:i A', strtotime($editRow['start_time'])));
                       }
                     ?>">
            </div>
          </div>

          <div class="col-md-3">
            <label class="form-label">End Time</label>
            <div class="input-group">
              <input type="text"
                     name="end_time"
                     id="end_time"
                     class="form-control time-input"
                     placeholder="06:30 PM"
                     readonly
                     required
                     value="<?php
                       if (!empty($_POST['end_time'])) {
                           echo htmlspecialchars($_POST['end_time']);
                       } elseif (!empty($editRow['end_time'])) {
                           echo htmlspecialchars(date('h:i A', strtotime($editRow['end_time'])));
                       }
                     ?>">
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Lunch Start (optional)</label>
            <div class="input-group">
              <input type="text"
                     name="lunch_start"
                     id="lunch_start"
                     class="form-control time-input"
                     placeholder="01:00 PM"
                     readonly
                     value="<?php
                       if (!empty($_POST['lunch_start'])) {
                           echo htmlspecialchars($_POST['lunch_start']);
                       } elseif (!empty($editRow['lunch_start'])) {
                           echo htmlspecialchars(date('h:i A', strtotime($editRow['lunch_start'])));
                       }
                     ?>">
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Lunch End (optional)</label>
            <div class="input-group">
              <input type="text"
                     name="lunch_end"
                     id="lunch_end"
                     class="form-control time-input"
                     placeholder="01:30 PM"
                     readonly
                     value="<?php
                       if (!empty($_POST['lunch_end'])) {
                           echo htmlspecialchars($_POST['lunch_end']);
                       } elseif (!empty($editRow['lunch_end'])) {
                           echo htmlspecialchars(date('h:i A', strtotime($editRow['lunch_end'])));
                       }
                     ?>">
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Early Clock-In</label>
            <input type="number"
                   name="early_clock_in_before"
                   class="form-control"
                   min="0"
                   value="<?php echo htmlspecialchars($editRow['early_clock_in_before'] ?? ($_POST['early_clock_in_before'] ?? '0')); ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Late Mark After (minutes from start)</label>
            <input type="number"
                   name="late_mark_after"
                   class="form-control"
                   min="1"
                   required
                   value="<?php echo htmlspecialchars($editRow['late_mark_after'] ?? ($_POST['late_mark_after'] ?? '10')); ?>">
          </div>

          <!-- Half Time input (UI) -->
          <div class="col-md-4">
            <label class="form-label">Half Time After</label>
            <div class="input-group">
              <input type="text"
                     name="half_day_time"
                     id="half_day_time"
                     class="form-control time-input"
                     placeholder="Auto / 02:30 PM"
                     readonly
                     value="<?php
                       if (!empty($_POST['half_day_time'])) {
                           echo htmlspecialchars($_POST['half_day_time']);
                       } elseif (!empty($editRow['start_time']) && isset($editRow['half_day_after'])) {
                         $halfTs = strtotime($editRow['start_time']) + ((int)$editRow['half_day_after'] * 60);
                         echo date('h:i A', $halfTs);
                       }
                     ?>">
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Total Punches Per Day</label>
            <input type="number"
                   name="total_punches"
                   class="form-control"
                   min="1"
                   required
                   value="<?php echo htmlspecialchars($editRow['total_punches'] ?? ($_POST['total_punches'] ?? '4')); ?>">
          </div>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-dark">
            <?php echo $editRow ? 'Update Shift' : 'Save Shift'; ?>
          </button>
          <?php if ($editRow) { ?>
            <a href="shifts.php" class="btn btn-secondary ms-2">Cancel</a>
          <?php } ?>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  

   <!-- SHIFT LIST TABLE -->
  <div class="card settings-card shadow-sm border-0" style="border-radius:18px;">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover table-borderless align-middle mb-0 w-100" style="font-size: 0.97rem;">
          <thead class="table-light" style="font-size:0.93em;">
            <tr class="text-nowrap text-center">
              <th class="text-center px-2" style="width: 50px;">#</th>
              <th class="text-start px-2">Shift</th>
              <th class="text-center px-2" style="width: 90px;">Color</th>
              <th class="text-center px-2">Timing</th>
              <th class="text-center px-2">Lunch</th>
              <th class="text-center px-2">Early In</th>
              <th class="text-center px-2">Late Mark</th>
              <th class="text-center px-2">Half Time</th>
              <th class="text-center px-2">Punches</th>
              <th class="text-center px-2">Updated</th>
              <th class="text-center px-2" style="width: 130px;">Action</th>
            </tr>
          </thead>
          <tbody>
          <?php
            $i = $totalCount > 0 ? ($offset + 1) : 1;
          if ($list && $list->num_rows > 0) {
              while ($row = $list->fetch_assoc()) {

                  $startDisp = date('h:i A', strtotime($row['start_time']));
                  $endDisp   = date('h:i A', strtotime($row['end_time']));
                  $timing    = "$startDisp – $endDisp";

                  if (!empty($row['lunch_start']) && !empty($row['lunch_end'])) {
                      $lunchStart = date('h:i A', strtotime($row['lunch_start']));
                      $lunchEnd   = date('h:i A', strtotime($row['lunch_end']));
                      $lunch      = "$lunchStart – $lunchEnd";
                  } else {
                      $lunch = '—';
                  }

                  $halfTime = '—';
                  if (!empty($row['start_time']) && isset($row['half_day_after'])) {
                      $halfTs   = strtotime($row['start_time']) + ((int)$row['half_day_after'] * 60);
                      $halfTime = date('h:i A', $halfTs);
                  }

                  $updatedTs = $row['updated_at'] ?: $row['created_at'];
                  $updated   = date('d M Y, h:i A', strtotime($updatedTs));
          ?>
            <tr class="text-center text-nowrap">
              <td><?php echo $i++; ?></td>

              <td class="text-start">
                <div class="fw-semibold">
                  <?php echo htmlspecialchars($row['shift_name']); ?>
                </div>
              </td>

              <td>
                <?php $c = $row['shift_color'] ?? '#0d6efd'; ?>
                <span class="d-inline-block rounded" style="width:18px;height:18px;background:<?php echo htmlspecialchars($c); ?>;border:1px solid rgba(0,0,0,.15);"></span>
                <div class="small text-muted" style="line-height:1.1;"><?php echo htmlspecialchars($c); ?></div>
              </td>

              <td><?php echo $timing; ?></td>
              <td><?php echo $lunch; ?></td>

              <td><?php echo (int)$row['early_clock_in_before']; ?> min</td>
              <td><?php echo (int)$row['late_mark_after']; ?> min</td>

              <td><?php echo $halfTime; ?></td>
              <td><?php echo (int)$row['total_punches']; ?></td>
              <td><?php echo $updated; ?></td>

              <td class="text-center text-nowrap">
                <?php if ($GLOBALS['isAjax']) { ?>
                  <a href="javascript:void(0)"
                     class="btn btn-sm btn-outline-primary me-1 shift-edit"
                     data-edit-id="<?php echo $row['id']; ?>">
                    Edit
                  </a>
                  <a href="javascript:void(0)"
                     class="btn btn-sm btn-outline-danger shift-delete"
                     data-del-id="<?php echo $row['id']; ?>">
                    Delete
                  </a>
                <?php } else { ?>
                  <a href="shifts.php?edit=<?php echo $row['id']; ?>"
                     class="btn btn-sm btn-outline-primary me-1">
                    Edit
                  </a>
                  <a href="shifts.php?delete=<?php echo $row['id']; ?>"
                     class="btn btn-sm btn-outline-danger"
                     onclick="return confirm('Delete this shift?');">
                    Delete
                  </a>
                <?php } ?>
              </td>
            </tr>
          <?php
              }
          } else {
          ?>
            <tr>
              <td colspan="11" class="text-center py-4 text-muted">
                No shifts found. Please add one.
              </td>
            </tr>
          <?php } ?>
          </tbody>
        </table>
      </div>

    <div id="shiftsPagingMeta"
         data-page="<?php echo (int)$page; ?>"
         data-per-page="<?php echo (int)$per_page; ?>"
         style="display:none;"></div>

    <?php
      $totalPages = max(1, (int)ceil(($totalCount ?: 0) / max(1, $per_page)));
      $startRec = $totalCount > 0 ? ($offset + 1) : 0;
      $endRec = $totalCount > 0 ? ($offset + ($list ? $list->num_rows : 0)) : 0;
    ?>

    <?php if ($totalPages > 1): ?>
      <nav class="mt-3 px-3">
        <ul class="pagination mb-0">
          <?php
            $start = max(1, $page - 3);
            $end = min($totalPages, $page + 3);
            if ($page > 1) echo '<li class="page-item"><a href="#" class="page-link shifts-page-link" data-page="'.($page-1).'">Previous</a></li>';
            if ($start > 1) echo '<li class="page-item"><a href="#" class="page-link shifts-page-link" data-page="1">1</a></li>' . ($start>2 ? '<li class="page-item disabled"><span class="page-link">...</span></li>':'' );
            for ($p = $start; $p <= $end; $p++){
              $cls = $p == $page ? ' page-item active' : ' page-item';
              echo '<li class="'.$cls.'"><a href="#" class="page-link shifts-page-link" data-page="'.$p.'">'.$p.'</a></li>';
            }
            if ($end < $totalPages) echo ($end < $totalPages-1 ? '<li class="page-item disabled"><span class="page-link">...</span></li>':'') . '<li class="page-item"><a href="#" class="page-link shifts-page-link" data-page="'.$totalPages.'">'.$totalPages.'</a></li>';
            if ($page < $totalPages) echo '<li class="page-item"><a href="#" class="page-link shifts-page-link" data-page="'.($page+1).'">Next</a></li>';
          ?>
        </ul>
      </nav>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mt-2 px-3 pb-3">
      <div class="small text-muted">Record <?php echo (int)$startRec; ?>–<?php echo (int)$endRec; ?> of <?php echo (int)$totalCount; ?></div>
      <div class="d-flex align-items-center gap-2">
        <label class="small text-muted mb-0">Rows:</label>
        <select id="shiftsPerPageFooter" class="form-select form-select-sm" style="width:80px;">
          <option value="10" <?php if($per_page==10) echo 'selected'; ?>>10</option>
          <option value="25" <?php if($per_page==25) echo 'selected'; ?>>25</option>
          <option value="50" <?php if($per_page==50) echo 'selected'; ?>>50</option>
          <option value="100" <?php if($per_page==100) echo 'selected'; ?>>100</option>
        </select>
      </div>
    </div>
    </div>
  </div>

</div>
<?php
}

// ------------- AJAX vs FULL PAGE OUTPUT -------------
if ($isAjax) {
  renderShiftContent($errors, $editRow, $list, $totalCount, $page, $per_page, $offset);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Shift Master</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php renderShiftContent($errors, $editRow, $list, $totalCount, $page, $per_page, $offset); ?>

<!-- Shared Time Picker Modal for Shift Master (copied from settings_shift_tab.php) -->
<div class="modal fade" id="shiftTimePickerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3">
      <div class="modal-body text-center">
        <div class="fs-3 fw-semibold mb-3" id="shiftTpDisplay">09:00 AM</div>
        <div class="row g-3 justify-content-center mb-3">
          <div class="col-4">
            <label class="form-label mb-1 small">Hour</label>
            <input type="number" min="1" max="12" class="form-control text-center" id="shiftTpHour" value="9">
          </div>
          <div class="col-4">
            <label class="form-label mb-1 small">Min</label>
            <input type="number" min="0" max="59" class="form-control text-center" id="shiftTpMinute" value="0">
          </div>
          <div class="col-4">
            <label class="form-label mb-1 small">Period</label>
            <div class="btn-group w-100" role="group">
              <button type="button" class="btn btn-outline-dark active" id="shiftTpAm">AM</button>
              <button type="button" class="btn btn-outline-dark" id="shiftTpPm">PM</button>
            </div>
          </div>
        </div>
        <div class="d-flex justify-content-between mt-2">
          <button type="button" class="btn btn-outline-secondary" id="shiftTpCancel" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-dark" id="shiftTpApply">Apply</button>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Custom time picker logic for Shift Master (from settings_shift_tab.php)
function initShiftTimePicker() {
  const modalEl = document.getElementById('shiftTimePickerModal');
  if (!modalEl || typeof bootstrap === 'undefined') return;

  const modal = new bootstrap.Modal(modalEl);
  const displayEl = document.getElementById('shiftTpDisplay');
  const hourEl = document.getElementById('shiftTpHour');
  const minuteEl = document.getElementById('shiftTpMinute');
  const amBtn = document.getElementById('shiftTpAm');
  const pmBtn = document.getElementById('shiftTpPm');
  const applyBtn = document.getElementById('shiftTpApply');
  const cancelBtn = document.getElementById('shiftTpCancel');

  if (!displayEl || !hourEl || !minuteEl || !amBtn || !pmBtn || !applyBtn) return;

  let currentTargetInput = null;

  function parseToMinutes(value) {
    if (!value) return null;
    const match = value.trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!match) return null;
    let h = parseInt(match[1], 10);
    const m = parseInt(match[2], 10);
    const period = match[3].toUpperCase();
    if (isNaN(h) || isNaN(m)) return null;
    if (h === 12) h = 0;
    if (period === 'PM') h += 12;
    return h * 60 + m;
  }

  function formatFromMinutes(totalMinutes) {
    totalMinutes = ((totalMinutes % (24 * 60)) + (24 * 60)) % (24 * 60);
    let h24 = Math.floor(totalMinutes / 60);
    const m = totalMinutes % 60;
    let period = 'AM';
    if (h24 >= 12) {
      period = 'PM';
      if (h24 > 12) h24 -= 12;
    }
    if (h24 === 0) h24 = 12;
    const hStr = h24.toString().padStart(2, '0');
    const mStr = m.toString().padStart(2, '0');
    return `${hStr}:${mStr} ${period}`;
  }

  function updateDisplay() {
    let h = parseInt(hourEl.value || '0', 10);
    let m = parseInt(minuteEl.value || '0', 10);
    if (isNaN(h) || h < 1) h = 1;
    if (h > 12) h = 12;
    if (isNaN(m) || m < 0) m = 0;
    if (m > 59) m = 59;
    hourEl.value = h;
    minuteEl.value = m;
    const period = amBtn.classList.contains('active') ? 'AM' : 'PM';
    const hStr = h.toString().padStart(2, '0');
    const mStr = m.toString().padStart(2, '0');
    displayEl.textContent = `${hStr}:${mStr} ${period}`;
  }

  function parseExisting(value) {
    if (!value) return;
    const match = value.trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!match) return;
    const h = parseInt(match[1], 10);
    const m = parseInt(match[2], 10);
    const period = match[3].toUpperCase();
    if (!isNaN(h)) hourEl.value = h;
    if (!isNaN(m)) minuteEl.value = m;
    if (period === 'PM') {
      pmBtn.classList.add('active');
      amBtn.classList.remove('active');
    } else {
      amBtn.classList.add('active');
      pmBtn.classList.remove('active');
    }
    updateDisplay();
  }

  function openPickerForInput(input) {
    currentTargetInput = input;
    if (!currentTargetInput) return;

    hourEl.value = 9;
    minuteEl.value = 0;
    amBtn.classList.add('active');
    pmBtn.classList.remove('active');

    parseExisting(currentTargetInput.value);
    updateDisplay();
    modal.show();
  }

  document.querySelectorAll('.time-input').forEach(input => {
    if (input.dataset.tpBound === '1') return;
    input.dataset.tpBound = '1';
    input.addEventListener('click', function() {
      openPickerForInput(this);
    });
  });

  hourEl.addEventListener('input', updateDisplay);
  minuteEl.addEventListener('input', updateDisplay);
  amBtn.addEventListener('click', function() {
    amBtn.classList.add('active');
    pmBtn.classList.remove('active');
    updateDisplay();
  });
  pmBtn.addEventListener('click', function() {
    pmBtn.classList.add('active');
    amBtn.classList.remove('active');
    updateDisplay();
  });

  if (cancelBtn) {
    cancelBtn.addEventListener('click', function() {
      currentTargetInput = null;
      modal.hide();
    });
  }

  applyBtn.addEventListener('click', function() {
    if (!currentTargetInput) return;
    updateDisplay();
    currentTargetInput.value = displayEl.textContent;

    // Auto-calculate Half Time when Start or End time is set
    if (currentTargetInput.id === 'start_time' || currentTargetInput.id === 'end_time') {
      const startInput = document.getElementById('start_time');
      const endInput = document.getElementById('end_time');
      const halfInput = document.getElementById('half_day_time');
      if (startInput && endInput && halfInput) {
        const sMin = parseToMinutes(startInput.value);
        const eMinRaw = parseToMinutes(endInput.value);
        if (sMin !== null && eMinRaw !== null) {
          let eMin = eMinRaw;
          if (eMin <= sMin) {
            // Overnight shift: end is next day
            eMin += 24 * 60;
          }
          const halfMin = Math.round(sMin + (eMin - sMin) / 2);
          halfInput.value = formatFromMinutes(halfMin);
        }
      }
    }
    modal.hide();
  });
}

// Initialize on load (for full page context)
document.addEventListener('DOMContentLoaded', function () {
  initShiftTimePicker();
});
</script>
</body>
</html>
