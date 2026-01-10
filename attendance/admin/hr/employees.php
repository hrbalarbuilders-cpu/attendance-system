<?php
// employees.php  (main dashboard wrapper)
date_default_timezone_set('Asia/Kolkata');
include '../config/db.php';

// flags for toast messages (delete / update)
$deleted = isset($_GET['deleted']) ? 1 : 0;
$updated = isset($_GET['updated']) ? 1 : 0;

/* ------- Departments & Employees for Mark Attendance Modal ------- */
$deptRes = $con->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");

$employeesForJs = [];
$empRes = $con->query("SELECT id, name, department_id FROM employees ORDER BY name ASC");
if ($empRes && $empRes->num_rows > 0) {
  while ($e = $empRes->fetch_assoc()) {
    $employeesForJs[] = [
      'id'            => (int)$e['id'],
      'name'          => $e['name'],
      'department_id' => (int)$e['department_id'],
    ];
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Employees</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background: #f3f5fb;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .page-wrapper {
      max-width: 1200px;
    }

    /* top tabs nav */
    .top-nav-wrapper {
      background: #ffffff;
      border-radius: 8px;
      padding: 6px 10px;
      box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
      display: inline-flex;
      gap: 16px;
      align-items: center;
    }
    .top-nav-pill {
      padding: 8px 20px;
      border-radius: 6px;
      border: none;
      background: transparent;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 0.9rem;
      font-weight: 500;
      color: #4b5563;
      cursor: pointer;
      text-decoration: none;
    }
    .top-nav-pill.active {
      background: #111827;
      color: #ffffff;
    }
    .top-nav-pill span.icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 22px;
      height: 22px;
      border-radius: 4px;
      background: rgba(255,255,255,0.12);
      font-size: 0.9rem;
    }

    .section-title {
      font-size: 1.8rem;
      font-weight: 700;
      letter-spacing: 0.02em;
    }

    .btn-round-icon {
      width: 40px;
      height: 40px;
      border-radius: 6px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: none;
      background: #111827;
      color: #fff;
      font-size: 1.1rem;
      text-decoration: none;
    }

    /* toast */
    #statusAlertWrapper { z-index: 1080; }

    /* Loader overlay */
    #loaderOverlay {
      position: fixed;
      inset: 0;
      background: rgba(15,23,42,0.08);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 2000;
    }
    #loaderOverlay.d-none {
      display: none;
    }
    .loader-spinner {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      border: 4px solid #e5e7eb;
      border-top-color: #111827;
      animation: spin 0.7s linear infinite;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Fade-in animation for content */
    .fade-in {
      animation: fadeIn .25s ease-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(4px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* Ensure modal is hidden by default and only shows as popup overlay */
    #attendanceDetailsModal {
      display: none !important;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1055;
      width: 100%;
      height: 100%;
      overflow-x: hidden;
      overflow-y: auto;
      outline: 0;
    }
    #attendanceDetailsModal.show {
      display: block !important;
    }
    .modal-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1050;
      width: 100vw;
      height: 100vh;
      background-color: rgba(0, 0, 0, 0.5);
    }

    /* iOS switch (used inside employees_list) */
    .switch {
      position: relative;
      display: inline-block;
      width: 52px;
      height: 28px;
    }
    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }
    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #d1d5db;
      transition: .3s;
      border-radius: 999px;
    }
    .slider:before {
      position: absolute;
      content: "";
      height: 22px;
      width: 22px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      transition: .3s;
      border-radius: 50%;
    }
    input:checked + .slider {
      background-color: #000;
    }
    input:checked + .slider:before {
      transform: translateX(24px);
    }

    /* dropdown fix inside tables */
    .table-responsive { overflow: visible !important; }
    .dropdown-menu {
      position: absolute !important;
      transform: translate3d(0,0,0) !important;
      min-width: 140px;
      font-size: 0.85rem;
    }

  </style>
</head>
<body>

  <?php include_once '../includes/header.php'; ?>

  <div class="main-content-scroll">

<!-- loader -->
<div id="loaderOverlay" class="d-none">
  <div class="loader-spinner"></div>
</div>

<!-- toast -->
<div id="statusAlertWrapper"
     class="position-fixed top-0 start-50 translate-middle-x p-3">
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

    <!-- Top tabs row -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <?php include_once __DIR__ . '/../includes/navbar-hr.php'; ?>

      <!-- Mark Attendance Modal -->
      <div class="modal fade" id="markAttendanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
          <div class="modal-content border-0 rounded-4">

            <!-- Header -->
            <div class="modal-header border-0 pb-0">
              <div>
                <h5 class="modal-title fw-bold" style="font-size: 22px;">Mark Attendance</h5>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body pt-2">

              <form id="markAttendanceForm">

                <div class="row g-4">

                  <!-- Department -->
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">
                      Department <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                      <select class="form-select" id="departmentSelect" name="department_id" required>
                        <option value="">Select Department...</option>
                        <?php
                        if ($deptRes && $deptRes->num_rows > 0) {
                          mysqli_data_seek($deptRes, 0);
                          while ($d = $deptRes->fetch_assoc()) {
                            ?>
                            <option value="<?php echo $d['id']; ?>">
                              <?php echo htmlspecialchars($d['department_name']); ?>
                            </option>
                            <?php
                          }
                        }
                        ?>
                      </select>
                    </div>
                  </div>

                  <!-- Employee -->
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">
                      Employee <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                      <select class="form-select" id="employeeSelect" name="employee_id" required>
                        <option value="">Select Employee</option>
                        <!-- JS se dept wise fill hoga -->
                      </select>
                    </div>
                  </div>

                  <!-- Late -->
                  <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-semibold">Late</label>
                    <div class="d-flex align-items-center gap-3 mt-1">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="late" id="lateYes" value="1">
                        <label class="form-check-label" for="lateYes">Yes</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="late" id="lateNo" value="0" checked>
                        <label class="form-check-label" for="lateNo">No</label>
                      </div>
                    </div>
                  </div>

                  <!-- Half Day -->
                  <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-semibold">Half Day</label>
                    <div class="d-flex align-items-center gap-3 mt-1">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="half_day" id="halfYes" value="1">
                        <label class="form-check-label" for="halfYes">Yes</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="half_day" id="halfNo" value="0" checked>
                        <label class="form-check-label" for="halfNo">No</label>
                      </div>
                    </div>
                  </div>

                  <!-- Mark Attendance By -->
                  <div class="col-md-12">
                    <label class="form-label fw-semibold">Mark Attendance By</label>
                    <div class="d-flex align-items-center gap-4 mt-1 flex-wrap">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="mark_by" id="markByDate" value="date" checked>
                        <label class="form-check-label" for="markByDate">Date</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="mark_by" id="markByMultiple" value="multiple">
                        <label class="form-check-label" for="markByMultiple">Multiple</label>
                      </div>
                    </div>
                  </div>

                  <!-- Select Date (single) -->
                  <div class="col-md-6 col-lg-3" id="singleDateWrapper">
                    <label class="form-label fw-semibold">
                      Select Date <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                      <span class="input-group-text">
                        📅
                      </span>
                      <input type="date" class="form-control" name="date" id="attendanceDate" required>
                    </div>
                  </div>

                  <!-- Select Multiple Dates -->
                  <div class="col-12 d-none" id="multiDatesWrapper">
                    <label class="form-label fw-semibold">
                      Select Dates <span class="text-danger">*</span>
                    </label>
                    <div id="multiDatesList" class="d-flex flex-column gap-2">
                      <!-- rows with name="dates[]" will be added via JS -->
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addDateRow">
                      + Add Date
                    </button>
                    <small class="text-muted d-block mt-1">Same Clock In/Out will be applied to all selected dates.</small>
                  </div>

                  <!-- Clock In -->
                  <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-semibold">
                      Clock In <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                      <span class="input-group-text">
                        ⏰
                      </span>
                      <input type="text" class="form-control time-input" name="clock_in" id="clockIn" placeholder="09:00 AM" readonly required>
                    </div>
                  </div>

                  <!-- Clock Out -->
                  <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-semibold">
                      Clock Out <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                      <span class="input-group-text">
                        ⏱
                      </span>
                      <input type="text" class="form-control time-input" name="clock_out" id="clockOut" placeholder="06:30 PM" readonly required>
                    </div>
                  </div>

                  <!-- Working From -->
                  <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-semibold">
                      Working From <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                      <select class="form-select" name="working_from" id="workingFrom" required>
                        <option value="">Select Working From...</option>
                        <?php foreach ($workingFromOptions as $wf) { ?>
                          <option value="<?php echo htmlspecialchars($wf['code']); ?>">
                            <?php echo htmlspecialchars($wf['label']); ?>
                          </option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>

                  <!-- Reason / Break Type -->
            <div class="col-md-6 col-lg-3">
              <label class="form-label fw-semibold">
                Reason / Break Type
              </label>
              <select class="form-select" name="reason" id="attendanceReason" required>
                <option value="">Select</option>
                <option value="lunch">Lunch Break</option>
                <option value="shift_end">Shift End</option>
              </select>
            </div>

                  <!-- Attendance Overwrite -->
                  <div class="col-12">
                    <div class="form-check mt-2">
                      <input class="form-check-input" type="checkbox" value="1" id="overwrite" name="overwrite" checked>
                      <label class="form-check-label fw-semibold" for="overwrite">
                        Attendance Overwrite
                      </label>
                    </div>
                  </div>

                </div><!-- /.row -->

              </form>

            </div><!-- /.modal-body -->

            <!-- Footer -->
            <div class="modal-footer border-0 pt-0 d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                Cancel
              </button>
              <button type="button" class="btn btn-dark" id="saveAttendanceBtn">
                Apply
              </button>
            </div>

          </div>
          
        </div>
      </div>

      <!-- Modal styling -->
      <style>
        #markAttendanceModal .modal-content{
          box-shadow:0 18px 45px rgba(15,23,42,.25);
        }
        #markAttendanceModal .form-label{
          font-size:13px;
          text-transform:none;
        }
        #markAttendanceModal .form-control,
        #markAttendanceModal .form-select{
          border-radius:12px;
          font-size:14px;
        }
        #markAttendanceModal .input-group-text{
          border-radius:12px 0 0 12px;
        }
      </style>

      <!-- settings icon -> settings.php -->
      <a href="settings.php" class="btn-round-icon" title="Settings">
        ⚙
      </a>
    </div>

    <!-- Content area: yahi par sab sections AJAX se load honge -->
    <div id="contentArea"></div>

  </div>
</div>

    <!-- Attendance Details Modal -->
    <div class="modal fade" id="attendanceDetailsModal" tabindex="-1" aria-labelledby="attendanceDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <!-- Header -->
                <div class="modal-header border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="modal-title fw-bold" style="font-size: 22px;">Attendance Details</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-danger me-2" id="deleteAttendanceBtn" style="display:none;">
                            🗑️
                        </button>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <!-- Body -->
                <div class="modal-body pt-2">
                    <div class="row">
                        <!-- Left Side: Employee Info & Clock Status -->
                        <div class="col-md-5">
                            <!-- Employee Info -->
                            <div class="d-flex align-items-center mb-4">
                                <div class="emp-avatar" style="width:60px; height:60px; font-size:24px;" id="modalEmpAvatar">
                                    E
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 fw-bold" id="modalEmpName">Employee Name</h6>
                                    <small class="text-muted" id="modalEmpRole">Designation</small>
                                </div>
                            </div>

                            <!-- Clock In Status -->
                            <div class="mb-4" id="clockInBox" style="display:none;">
                              <div class="rounded-3 p-3 text-white" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                <div class="small mb-1">Clock In</div>
                                <div class="h4 mb-2 fw-bold" id="clockInTime">--:-- --</div>
                                <div class="small" id="clockInGreeting">Good morning! 👋</div>
                              </div>
                            </div>

                            <!-- Total Work (circle) -->
                            <div class="mb-4 text-center" id="totalWorkBox" style="display:none;">
                              <div class="position-relative d-inline-block" style="width: 150px; height: 150px;">
                                <svg class="transform-rotate-90" width="150" height="150">
                                  <!-- Base track -->
                                  <circle cx="75" cy="75" r="70" stroke="#E5E7EB" stroke-width="8" fill="none"/>
                                  <!-- Work (blue) -->
                                  <circle cx="75" cy="75" r="70" stroke="#6366F1" stroke-width="8" fill="none"
                                    stroke-dasharray="440" stroke-dashoffset="0" id="workProgressCircle"
                                    stroke-linecap="round" style="transition: stroke-dashoffset 0.5s;"/>
                                  <!-- Break (yellow) -->
                                  <circle cx="75" cy="75" r="70" stroke="#FACC15" stroke-width="8" fill="none"
                                    stroke-dasharray="440" stroke-dashoffset="0" id="breakProgressCircle"
                                    stroke-linecap="round" style="transition: stroke-dashoffset 0.5s; display:none;">
                                    <title id="breakTooltip"></title>
                                  </circle>
                                  <!-- Late (orange/red) -->
                                  <circle cx="75" cy="75" r="70" stroke="#F97373" stroke-width="8" fill="none"
                                    stroke-dasharray="440" stroke-dashoffset="0" id="lateProgressCircle"
                                    stroke-linecap="round" style="transition: stroke-dashoffset 0.5s; display:none;">
                                    <title id="lateTooltip"></title>
                                  </circle>
                                </svg>
                                <div class="position-absolute top-50 start-50 translate-middle text-center">
                                  <div class="small text-muted">Effective Work</div>
                                  <div class="fw-bold" id="totalWorkTime">0hr 0min</div>
                                </div>
                              </div>
                              <!-- Text summary below circle -->
                              <div class="mt-2 small text-muted">
                                <div id="grossWorkTime" style="display:none;">Gross: 0hr 0min</div>
                                <div id="breakTime" class="text-warning" style="display:none;"></div>
                              </div>
                            </div>

                                  <!-- Clock Out Status -->
                                  <div class="mb-4" id="clockOutBox" style="display:none;">
                                    <div class="rounded-3 p-3 text-white" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                                      <div class="small mb-1">Clock Out</div>
                                      <div class="h4 mb-2 fw-bold" id="clockOutTime">--:-- --</div>
                                      <div class="small" id="clockOutGreeting">Have a great day! 👋</div>
                                    </div>
                                  </div>
                        </div>

                        <!-- Right Side: Date & Activity Timeline -->
                        <div class="col-md-7">
                            <!-- Date -->
                            <div class="d-flex align-items-center mb-3">
                                <span class="me-2">📅</span>
                                <span id="modalDate">--/--/----</span>
                            </div>

                            <!-- Activity Timeline -->
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 fw-bold">Activity Timeline</h6>
                                    <small class="text-muted" id="activityCount">0 activities today</small>
                                </div>
                                
                                <div id="activityTimeline">
                                    <!-- Activities will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </div>

    <!-- Shared Time Picker Modal for Shift Master -->
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
// PHP se employees array JS me
const ALL_EMPLOYEES = <?php echo json_encode($employeesForJs, JSON_UNESCAPED_UNICODE); ?>;

let statusTimer;

// toast function
function showStatus(message, type = 'success') {
  const box  = document.getElementById('statusAlert');
  const text = document.getElementById('statusAlertText');
  if (!box || !text) return;

  text.textContent = message;

  box.classList.remove('d-none', 'alert-success', 'alert-danger', 'd-flex');
  box.classList.add(type === 'danger' ? 'alert-danger' : 'alert-success', 'd-flex');

  if (statusTimer) clearTimeout(statusTimer);
  statusTimer = setTimeout(() => {
    box.classList.add('d-none');
  }, 2500);
}

// loader helpers
function showLoader() {
  document.getElementById('loaderOverlay').classList.remove('d-none');
}
function hideLoader() {
  document.getElementById('loaderOverlay').classList.add('d-none');
}

// Employees list ke buttons/toggles ko init karne ka function
function initEmployeesListEvents() {
    // Dynamically inject and execute the search/filter script if present in loaded HTML (for AJAX)
    var scriptInHtml = document.querySelector('#employeeSearchScriptSource');
    if (scriptInHtml) {
      var old = document.getElementById('employeeSearchScript');
      if (old && old.parentNode) old.parentNode.removeChild(old);
      var newScript = document.createElement('script');
      newScript.id = 'employeeSearchScript';
      newScript.textContent = scriptInHtml.textContent;
      document.body.appendChild(newScript);
    }
  const toggles       = document.querySelectorAll('.status-toggle');
  const deleteButtons = document.querySelectorAll('.delete-employee');
  const deleteModalEl = document.getElementById('deleteConfirmModal');

  if (!deleteModalEl) return; // agar employees_list load hi nahi hua

  const deleteModal   = new bootstrap.Modal(deleteModalEl);
  let deleteId        = null;

  // STATUS TOGGLE AJAX
  toggles.forEach(function (toggle) {
    toggle.addEventListener('change', function () {
      const checkbox   = this;
      const employeeId = this.dataset.id;
      const newStatus  = this.checked ? 1 : 0;

      checkbox.disabled = true;

      fetch('toggle_employee_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + encodeURIComponent(employeeId) +
              '&status=' + encodeURIComponent(newStatus)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showStatus(
            data.message || (newStatus ? 'Account activated successfully.' : 'Account deactivated successfully.'),
            'success'
          );
        } else {
          showStatus(data.message || 'Status update failed. Please try again.', 'danger');
          checkbox.checked = !checkbox.checked;
        }
      })
      .catch(() => {
        showStatus('Something went wrong. Please check your connection.', 'danger');
        checkbox.checked = !checkbox.checked;
      })
      .finally(() => {
        checkbox.disabled = false;
      });
    });
  });

  // DELETE BUTTON -> custom modal
  deleteButtons.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      deleteId = this.dataset.id;
      deleteModal.show();
    });
  });

  const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener('click', function () {
      if (!deleteId) return;
      window.location.href = 'delete_employee.php?id=' + encodeURIComponent(deleteId);
    });
  }

  // SEARCH: filter employees in the table as user types (migrated from employees_list.php)
  var scriptStatus = document.getElementById('scriptStatus');
  if (scriptStatus) {
    scriptStatus.style.display = 'block';
    scriptStatus.textContent = 'Employee search script is running.';
    scriptStatus.classList.remove('alert-danger');
    scriptStatus.classList.add('alert-info');
  }
  var testDiv = document.getElementById('testDiv');
  if (testDiv) {
    testDiv.textContent = 'TEST DIV: JS IS RUNNING!';
    testDiv.style.background = 'lime';
    testDiv.style.color = 'black';
  }
  function filterEmployeeTable() {
    var input = document.getElementById('employeeSearch');
    if (scriptStatus && input) {
      scriptStatus.textContent = 'Employee search script is running. Current filter: "' + input.value + '"';
    }
    var filter = input ? input.value.toLowerCase().trim() : '';
    var tbody = document.getElementById('employeeTableBody');
    var rows = tbody ? tbody.getElementsByTagName('tr') : [];
    var anyVisible = false;
    var noDataRow = null;
    var matchedNames = [];
    for (var i = 0; i < rows.length; i++) {
      var row = rows[i];
      // Identify the 'no employees found' row by its unique text
      if (row.innerText && row.innerText.trim().toLowerCase().includes('no employees found')) {
        noDataRow = row;
        continue;
      }
      // Only filter rows with actual employee data
      // Get relevant columns: Emp Code, Name, Department, Shift
      var tds = row.getElementsByTagName('td');
      var searchText = '';
      var nameText = '';
      if (tds.length > 4) {
        searchText = [tds[1], tds[2], tds[3], tds[4]]
          .map(function(td) { return (td.innerText || td.textContent || '').toLowerCase().trim(); })
          .join(' ');
        // Name column (tds[2])
        nameText = (tds[2].innerText || tds[2].textContent || '').trim();
      }
      // Use includes for partial match
      if (filter === '' || searchText.includes(filter)) {
        row.style.display = '';
        anyVisible = true;
        if (filter !== '' && nameText) {
          matchedNames.push(nameText);
        }
      } else {
        row.style.display = 'none';
      }
    }
    if (noDataRow) {
      noDataRow.style.display = anyVisible ? 'none' : '';
    }
    // Show matched names under the table
    var resultsDiv = document.getElementById('searchResults');
    if (resultsDiv) {
      if (filter !== '' && matchedNames.length > 0) {
        resultsDiv.innerHTML = '<strong>Matching Employees:</strong><ul class="list-group list-group-flush">' +
          matchedNames.map(function(name) { return '<li class="list-group-item py-1">' + name + '</li>'; }).join('') + '</ul>';
      } else if (filter !== '') {
        resultsDiv.innerHTML = '<span class="text-danger">No matching employees found.</span>';
      } else {
        resultsDiv.innerHTML = '';
      }
    }
  }
  var searchInput = document.getElementById('employeeSearch');
  if (searchInput) {
    searchInput.addEventListener('input', filterEmployeeTable);
    // Run once on load to ensure correct state
    filterEmployeeTable();
  }
}

// generic AJAX loader with animation
function loadPage(page, button) {
  const contentArea = document.getElementById("contentArea");

  // Active tab UI
  document.querySelectorAll('.top-nav-pill').forEach(btn => btn.classList.remove('active'));
  if (button) button.classList.add('active');

  // Remember last opened section/page so refresh stays on same tab
  try {
    window.localStorage.setItem('adminLastPage', page);
  } catch (e) {
    // ignore storage issues
  }

  contentArea.classList.remove('fade-in');
  showLoader();

  fetch(page)
    .then(response => {
      if (!response.ok) throw new Error('Network error');
      return response.text();
    })
    .then(html => {
      contentArea.innerHTML = html;
      // fade-in
      void contentArea.offsetWidth;
      contentArea.classList.add('fade-in');

      // agar employees list load hui hai to uske events init karo
      if (page.startsWith('employees_list.php')) {
        initEmployeesListEvents();
        initShiftTimePicker(); // also enable time picker for Mark Attendance modal
      } else if (page.startsWith('shifts.php')) {
        initShiftTimePicker();
      }
    })
    .catch(err => {
      console.error(err);
      contentArea.innerHTML =
        "<div class='alert alert-danger m-3'>Failed to load page.</div>";
    })
    .finally(() => {
      hideLoader();
    });
}

// Initialize custom time picker for Shift Master (Start/End time)
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

  // Bind to time picker buttons/inputs (both in loaded content and Mark Attendance modal)
  document.querySelectorAll('#contentArea .time-picker-btn, #markAttendanceModal .time-picker-btn').forEach(btn => {
    if (btn.dataset.tpBound === '1') return;
    btn.dataset.tpBound = '1';
    btn.addEventListener('click', function() {
      const targetId = this.getAttribute('data-target-input');
      const input = document.getElementById(targetId);
      openPickerForInput(input);
    });
  });

  document.querySelectorAll('#contentArea .time-input, #markAttendanceModal .time-input').forEach(input => {
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

document.addEventListener('DOMContentLoaded', function () {
  // success messages from PHP flags (delete / update)
  const deletedFlag = <?php echo json_encode((bool)$deleted); ?>;
  const updatedFlag = <?php echo json_encode((bool)$updated); ?>;
  if (deletedFlag) {
    showStatus('Employee deleted successfully.', 'success');
  } else if (updatedFlag) {
    showStatus('Employee updated successfully.', 'success');
  }

  // Tab click handlers
  document.querySelectorAll('.top-nav-pill').forEach(btn => {
    btn.addEventListener("click", () => {
      const page = btn.dataset.page;
      if (page) loadPage(page, btn);
    });
  });

  // Default: load last opened section if available, otherwise Employees tab
  let initialPage = null;
  try {
    initialPage = window.localStorage.getItem('adminLastPage');
  } catch (e) {
    initialPage = null;
  }

  let initialBtn = null;
  if (initialPage) {
    const tabs = Array.from(document.querySelectorAll('.top-nav-pill'));
    initialBtn = tabs.find(btn => {
      const dp = btn.dataset.page || '';
      const base = dp.split('?')[0];
      return initialPage === dp || initialPage.startsWith(base);
    }) || null;
  }

  if (!initialBtn) {
    initialBtn = document.querySelector('.top-nav-pill.active');
  }

  if (initialBtn) {
    const pageToLoad = initialPage || initialBtn.dataset.page;
    loadPage(pageToLoad, initialBtn);
  }

  // 🔹 Department change -> Employee dropdown populate
  const deptSelect = document.getElementById('departmentSelect');
  const empSelect  = document.getElementById('employeeSelect');

  if (deptSelect && empSelect) {
    deptSelect.addEventListener('change', function () {
      const deptId = this.value;
      empSelect.innerHTML = '<option value="">Select Employee</option>';

      if (!deptId) return;

      ALL_EMPLOYEES
        .filter(e => String(e.department_id) === String(deptId))
        .forEach(e => {
          const opt = document.createElement('option');
          opt.value = e.id;
          opt.textContent = e.name;
          empSelect.appendChild(opt);
        });
    });
  }

  // Mark Attendance By: Date / Multiple / Month
  const markByRadios = document.querySelectorAll('input[name="mark_by"]');
  const singleDateWrapper = document.getElementById('singleDateWrapper');
  const multiDatesWrapper = document.getElementById('multiDatesWrapper');
  const attendanceDateInput = document.getElementById('attendanceDate');
  const multiDatesList = document.getElementById('multiDatesList');
  const addDateRowBtn = document.getElementById('addDateRow');

  function createDateRow() {
    const row = document.createElement('div');
    row.className = 'd-flex align-items-center gap-2 date-row';
    row.innerHTML = `
      <input type="date" class="form-control" name="dates[]">
      <button type="button" class="btn btn-outline-danger btn-sm remove-date-row">&times;</button>
    `;
    return row;
  }

  function updateMarkByUI(mode) {
    if (!singleDateWrapper || !multiDatesWrapper || !attendanceDateInput) return;

    if (mode === 'multiple') {
      singleDateWrapper.classList.add('d-none');
      multiDatesWrapper.classList.remove('d-none');
      attendanceDateInput.required = false;

      // Ensure at least one row exists and mark them required
      if (multiDatesList && multiDatesList.children.length === 0) {
        multiDatesList.appendChild(createDateRow());
      }
      if (multiDatesList) {
        multiDatesList.querySelectorAll('input[type="date"]').forEach(inp => {
          inp.required = true;
        });
      }
    } else {
      // 'date' or 'month' -> use single date field
      singleDateWrapper.classList.remove('d-none');
      multiDatesWrapper.classList.add('d-none');
      attendanceDateInput.required = true;
      if (multiDatesList) {
        multiDatesList.querySelectorAll('input[type="date"]').forEach(inp => {
          inp.required = false;
        });
      }
    }
  }

  if (markByRadios && markByRadios.length) {
    markByRadios.forEach(r => {
      r.addEventListener('change', function () {
        updateMarkByUI(this.value);
      });
    });
    // Initialize based on default checked radio
    const checked = Array.from(markByRadios).find(r => r.checked);
    if (checked) updateMarkByUI(checked.value);
  }

  if (addDateRowBtn && multiDatesList) {
    addDateRowBtn.addEventListener('click', function () {
      multiDatesList.appendChild(createDateRow());
    });

    multiDatesList.addEventListener('click', function (e) {
      if (e.target.classList.contains('remove-date-row')) {
        const row = e.target.closest('.date-row');
        if (row && multiDatesList.children.length > 1) {
          row.remove();
        }
      }
    });
  }

    // Save attendance button -> REAL AJAX SAVE
  const saveBtn = document.getElementById('saveAttendanceBtn');
  const markForm = document.getElementById('markAttendanceForm');

  if (saveBtn && markForm) {
    saveBtn.addEventListener('click', function () {
      // HTML5 validation
      if (!markForm.reportValidity()) return;

      const formData = new FormData(markForm);

      showLoader();

      fetch('save_admin_attendance.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          // modal close
          const modalEl = document.getElementById('markAttendanceModal');
          const modal   = bootstrap.Modal.getInstance(modalEl);
          modal.hide();

          showStatus(data.message || 'Attendance saved successfully.', 'success');

          // OPTIONAL: agar attendance tab open hai to refresh kara sakte ho
          // const tabBtn = document.querySelector('[data-page^="attendance_tab.php"]');
          // if (tabBtn && tabBtn.classList.contains('active')) {
          //   loadPage('attendance_tab.php?ajax=1', tabBtn);
          // }

        } else {
          showStatus(data.message || 'Failed to save attendance.', 'danger');
        }
      })
      .catch(() => {
        showStatus('Error while saving attendance. Please try again.', 'danger');
      })
      .finally(() => {
        hideLoader();
      });
    });
  }
});

document.addEventListener("submit", function (e) {
    const form = e.target;

    // Only intercept Department AJAX form
    if (form.closest("#contentArea") && form.action.includes("departments.php")) {
        e.preventDefault();

        const formData = new FormData(form);
        const url = form.action + "?ajax=1";

        showLoader();

        fetch(url, {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadPage(data.reload, document.querySelector('[data-page="departments.php?ajax=1"]'));
                showStatus("Department saved", "success");
            }
        })
        .catch(() => showStatus("Error saving department", "danger"))
        .finally(hideLoader);

    }
});

document.addEventListener("click", function (e) {
    if (e.target.classList.contains("dept-edit")) {
        const id = e.target.dataset.editId;

        loadPage("departments.php?ajax=1&edit=" + id,
                 document.querySelector('[data-page="departments.php?ajax=1"]'));
    }
});

// Designation Edit (AJAX load)
document.addEventListener("click", function (e) {
  const editLink = e.target.closest(".desig-edit");
  if (editLink) {
    e.preventDefault();
    const id = editLink.dataset.editId;
    const tabBtn = document.querySelector('[data-page="designations.php?ajax=1"]');
    loadPage("designations.php?ajax=1&edit=" + encodeURIComponent(id), tabBtn);
  }
});

// Holiday Edit (AJAX load)
document.addEventListener("click", function (e) {
  const editLink = e.target.closest(".holiday-edit");
  if (editLink) {
    e.preventDefault();
    const id = editLink.dataset.editId;
    const tabBtn = document.querySelector('[data-page="holidays.php?ajax=1"]');
    loadPage("holidays.php?ajax=1&edit=" + encodeURIComponent(id), tabBtn);
  }
});

document.addEventListener("submit", function (e) {
  const form = e.target;

  // SPA ke contentArea ke andar ka form hi intercept karna hai
  if (!form.closest("#contentArea")) return;

  // DEPARTMENT form
  if (form.action.includes("departments.php")) {
    e.preventDefault();

    const formData = new FormData(form);
    const url = "departments.php?ajax=1";

    showLoader();
    fetch(url, {
      method: "POST",
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const tabBtn = document.querySelector('[data-page="departments.php?ajax=1"]');
        loadPage(data.reload, tabBtn);
        showStatus("Department saved successfully.", "success");
      }
    })
    .catch(() => showStatus("Error saving department", "danger"))
    .finally(hideLoader);

    return;
  }

  // DESIGNATION form
  if (form.action.includes("designations.php")) {
    e.preventDefault();

    const formData = new FormData(form);
    const url = "designations.php?ajax=1";

    showLoader();
    fetch(url, {
      method: "POST",
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const tabBtn = document.querySelector('[data-page="designations.php?ajax=1"]');
        loadPage(data.reload, tabBtn);
        showStatus("Designation saved successfully.", "success");
      }
    })
    .catch(() => showStatus("Error saving designation", "danger"))
    .finally(hideLoader);

    return;
  }
});

document.addEventListener("submit", function (e) {
  const form = e.target;

  // sirf SPA contentArea ke andar wale form intercept karo
  if (!form.closest("#contentArea")) return;

  // SHIFTS form
  if (form.action.includes("shifts.php")) {
    e.preventDefault();

    const formData = new FormData(form);
    const url = "shifts.php?ajax=1";

    showLoader();
    fetch(url, {
      method: "POST",
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const tabBtn = document.querySelector('[data-page="shifts.php?ajax=1"]');
        loadPage(data.reload, tabBtn);
        showStatus("Shift saved successfully.", "success");
      } else if (data.errors) {
        showStatus(data.errors.join(" "), "danger");
      }
    })
    .catch(() => showStatus("Error saving shift", "danger"))
    .finally(hideLoader);

    return;
  }
});

document.addEventListener("click", function (e) {
  const editBtn = e.target.closest(".shift-edit");
  if (editBtn) {
    e.preventDefault();
    const id = editBtn.dataset.editId;
    const tabBtn = document.querySelector('[data-page="shifts.php?ajax=1"]');
    loadPage("shifts.php?ajax=1&edit=" + encodeURIComponent(id), tabBtn);
    return;
  }

  const delBtn = e.target.closest(".shift-delete");
  if (delBtn) {
    e.preventDefault();
    const id = delBtn.dataset.delId;
    if (!confirm("Delete this shift?")) return;

    showLoader();
    fetch("shifts.php?ajax=1&delete=" + encodeURIComponent(id))
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          const tabBtn = document.querySelector('[data-page="shifts.php?ajax=1"]');
          loadPage(data.reload, tabBtn);
          showStatus("Shift deleted successfully.", "success");
        } else {
          showStatus("Failed to delete shift.", "danger");
        }
      })
      .catch(() => showStatus("Failed to delete shift.", "danger"))
      .finally(hideLoader);
  }
});

// Attendance filter (attendance_tab.php ke andar ka form)
document.addEventListener("submit", function (e) {
  const form = e.target;

  // 1) Attendance FILTER form (AJAX)
  if (form.id === "attendanceFilterForm") {
    e.preventDefault();

    const month = form.month.value;
    const year  = form.year.value;

    const tabBtn = document.querySelector('[data-page^="attendance_tab.php"]');
    const url    = "attendance_tab.php?ajax=1"
                 + "&month=" + encodeURIComponent(month)
                 + "&year="  + encodeURIComponent(year);

    loadPage(url, tabBtn);  // same animation + loader use hoga
    return;
  }
});

</script>

<script>
// Attendance Details Modal Script (works with AJAX loaded content)
(function() {
    let attendanceModal = null;
    
    function initModal() {
        const modalEl = document.getElementById('attendanceDetailsModal');
        if (modalEl && !attendanceModal) {
            attendanceModal = new bootstrap.Modal(modalEl);
        }
    }
    
    function populateModal(data) {
        const logs = data.logs || [];
        const activities = [];
        
        logs.forEach(log => {
            activities.push({
                type: log.type,
                time: log.time,
                working_from: log.working_from || '',
                reason: log.reason || 'normal'
            });
        });
        
        activities.sort((a, b) => new Date(a.time) - new Date(b.time));
        
        // Show clock in if available
        const firstIn = activities.find(a => a.type === 'in');
        const clockInBox = document.getElementById('clockInBox');
        const clockInTime = document.getElementById('clockInTime');
        const clockInGreeting = document.getElementById('clockInGreeting');
        
        if (firstIn && clockInBox && clockInTime && clockInGreeting) {
            const clockInTimeObj = new Date(firstIn.time);
            const timeStr = clockInTimeObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
            const hour = clockInTimeObj.getHours();
            let greeting = 'Good morning! 👋';
            if (hour >= 12 && hour < 17) greeting = 'Good afternoon! ☀️';
            else if (hour >= 17) greeting = 'Good evening! 🌙';
            
            clockInTime.textContent = timeStr;
            clockInGreeting.textContent = greeting;
            clockInBox.style.display = 'block';
        } else {
            if (clockInBox) clockInBox.style.display = 'none';
        }
        
        // Show clock out if available
        const lastOut = activities.filter(a => a.type === 'out').pop();
        const clockOutBox = document.getElementById('clockOutBox');
        const clockOutTime = document.getElementById('clockOutTime');
        const clockOutGreeting = document.getElementById('clockOutGreeting');
        
        if (lastOut && clockOutBox && clockOutTime && clockOutGreeting) {
            const clockOutTimeObj = new Date(lastOut.time);
            const timeStr = clockOutTimeObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
            const hour = clockOutTimeObj.getHours();
            let greeting = 'Have a great day! 👋';
            if (hour >= 12 && hour < 17) greeting = 'Have a wonderful afternoon! ☀️';
            else if (hour >= 17) greeting = 'Have a great evening! 🌙';
            
            clockOutTime.textContent = timeStr;
            clockOutGreeting.textContent = greeting;
            clockOutBox.style.display = 'block';
        } else {
            if (clockOutBox) clockOutBox.style.display = 'none';
        }
        
        // Calculate total work time with breaks
        const inLogs = activities.filter(a => a.type === 'in');
        const outLogs = activities.filter(a => a.type === 'out');
        const totalWorkBox = document.getElementById('totalWorkBox');
        const totalWorkTime = document.getElementById('totalWorkTime');
        const grossWorkTime = document.getElementById('grossWorkTime');
        const breakTime = document.getElementById('breakTime');
        const breakTooltip = document.getElementById('breakTooltip');
        const lateTooltip = document.getElementById('lateTooltip');
        const workProgressCircle = document.getElementById('workProgressCircle');
        const breakProgressCircle = document.getElementById('breakProgressCircle');
        const lateProgressCircle = document.getElementById('lateProgressCircle');
        
        if (inLogs.length > 0 && outLogs.length > 0 && totalWorkBox && totalWorkTime && workProgressCircle) {
          const firstInTime = new Date(inLogs[0].time);
          const lastOut = new Date(outLogs[outLogs.length - 1].time);
            
            // Calculate gross hours (total time from first in to last out)
            const grossMs = lastOut - firstInTime;
            const grossHours = Math.floor(grossMs / (1000 * 60 * 60));
            const grossMinutes = Math.floor((grossMs % (1000 * 60 * 60)) / (1000 * 60));
            
            // Calculate break time (time between out with lunch/tea reason and next in)
            let totalBreakMs = 0;
            let firstBreakOffsetMs = null; // offset from first IN to first break start
            for (let i = 0; i < activities.length - 1; i++) {
                const current = activities[i];
                const next = activities[i + 1];
                
                // If current is 'out' with lunch/tea reason and next is 'in', calculate break
                if (current.type === 'out' && 
                    (current.reason === 'lunch' || current.reason === 'tea') && 
                    next && next.type === 'in') {
                    const breakStart = new Date(current.time);
                    const breakEnd = new Date(next.time);
                    totalBreakMs += (breakEnd - breakStart);

                    if (firstBreakOffsetMs === null) {
                      firstBreakOffsetMs = breakStart - firstInTime;
                    }
                }
            }

            // Calculate late time based on shift start (if available)
            let lateMs = 0;
            if (data.shift && data.shift.start_time) {
              try {
                const shiftStart = new Date(data.date + 'T' + data.shift.start_time + ':00');
                if (firstInTime > shiftStart) {
                  lateMs = firstInTime - shiftStart;
                }
              } catch (e) {
                lateMs = 0;
              }
            }
            
            const breakHours = Math.floor(totalBreakMs / (1000 * 60 * 60));
            const breakMinutes = Math.floor((totalBreakMs % (1000 * 60 * 60)) / (1000 * 60));
            
            // Calculate effective hours (gross - breaks)
            const effectiveMs = grossMs - totalBreakMs;
            const effectiveHours = Math.floor(effectiveMs / (1000 * 60 * 60));
            const effectiveMinutes = Math.floor((effectiveMs % (1000 * 60 * 60)) / (1000 * 60));
            
            // Split effective work into before/after first break
            let workBeforeMs = 0;
            let workAfterMs = 0;
            if (firstBreakOffsetMs !== null && totalBreakMs > 0 && firstBreakOffsetMs > 0) {
              workBeforeMs = Math.min(firstBreakOffsetMs, effectiveMs);
              if (workBeforeMs < 0) workBeforeMs = 0;
              workAfterMs = Math.max(effectiveMs - workBeforeMs, 0);
            } else {
              workBeforeMs = effectiveMs;
              workAfterMs = 0;
            }

            const totalSpanMs = lateMs + workBeforeMs + totalBreakMs + workAfterMs;
            let lateFraction = 0, workBeforeFraction = 0, lunchFraction = 0, workAfterFraction = 0;
            if (totalSpanMs > 0) {
              lateFraction = lateMs / totalSpanMs;
              workBeforeFraction = workBeforeMs / totalSpanMs;
              lunchFraction = totalBreakMs / totalSpanMs;
              workAfterFraction = workAfterMs / totalSpanMs;
            }

            // Display effective work time
            totalWorkTime.textContent = effectiveHours + 'hr ' + effectiveMinutes + 'min';
            
            // Display gross work time if breaks exist
            if (totalBreakMs > 0 && grossWorkTime) {
                grossWorkTime.textContent = 'Gross: ' + grossHours + 'hr ' + grossMinutes + 'min';
                grossWorkTime.style.display = 'block';
            } else {
                if (grossWorkTime) grossWorkTime.style.display = 'none';
            }
            
            // Set break tooltip text if breaks exist
            if (totalBreakMs > 0 && breakTooltip) {
              breakTooltip.textContent = 'Break: ' + breakHours + 'hr ' + breakMinutes + 'min';
            } else if (breakTooltip) {
              breakTooltip.textContent = '';
            }
            
            // Base circle metrics
            const circumference = 2 * Math.PI * 70;

            // Always show blue work ring when there is any gross time
            const hasWork = grossMs > 0;
            const workOffset = hasWork ? 0 : circumference;
            workProgressCircle.style.strokeDasharray = `${circumference}`;
            workProgressCircle.style.strokeDashoffset = workOffset;
            
            // Show break segment in pie chart if breaks exist (hover tooltip only)
            if (totalBreakMs > 0 && breakProgressCircle && totalSpanMs > 0) {
              const grossArcLength = circumference; // blue ring is full

              // Break arc based on lunch fraction and position after late + work-before-lunch
              const breakArcLength = grossArcLength * lunchFraction;
              const breakStartFraction = lateFraction + workBeforeFraction;
              const breakStartWithinGross = breakStartFraction * grossArcLength;

              const breakDashOffset = circumference - (breakStartWithinGross + breakArcLength);
              breakProgressCircle.style.strokeDasharray = `${breakArcLength} ${circumference}`;
              breakProgressCircle.style.strokeDashoffset = breakDashOffset;
              breakProgressCircle.style.display = 'block';
            } else {
              if (breakProgressCircle) breakProgressCircle.style.display = 'none';
              if (breakTime) {
                breakTime.style.display = 'none';
                breakTime.textContent = '';
              }
            }

            // --- Late segment (before first IN, based on shift start) ---
            if (lateProgressCircle && lateTooltip && lateMs > 0 && totalSpanMs > 0) {
              const lateArcLength = lateFraction * circumference;
              const lateDashOffset = circumference - lateArcLength; // starts at top

              lateProgressCircle.style.strokeDasharray = `${lateArcLength} ${circumference}`;
              lateProgressCircle.style.strokeDashoffset = lateDashOffset;
              lateProgressCircle.style.display = 'block';

              const lateHours = Math.floor(lateMs / (1000 * 60 * 60));
              const lateMinutes = Math.floor((lateMs % (1000 * 60 * 60)) / (1000 * 60));
              let lateLabel = 'Late: ';
              if (lateHours > 0) lateLabel += `${lateHours}hr `;
              lateLabel += `${lateMinutes}min`;
              lateTooltip.textContent = lateLabel;
            } else if (lateProgressCircle && lateTooltip) {
              lateProgressCircle.style.display = 'none';
              lateTooltip.textContent = '';
            }
            
            totalWorkBox.style.display = 'block';
        }
        
        // Populate activity timeline
        const activityTimeline = document.getElementById('activityTimeline');
        const activityCount = document.getElementById('activityCount');
        let timelineHTML = '';
        
        if (activities.length > 0 && activityCount) {
            activityCount.textContent = activities.length + ' activities today';
            
            activities.forEach((activity) => {
                const activityTime = new Date(activity.time);
                const timeStr = activityTime.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
                const icon = activity.type === 'in' ? '⏰' : '⬜';
                const label = activity.type === 'in' ? 'Clock In' : 'Clock Out';
                const workingFrom = activity.working_from ? ' · ' + activity.working_from.charAt(0).toUpperCase() + activity.working_from.slice(1) : '';
                
                timelineHTML += `
                    <div class="d-flex align-items-start mb-3">
                        <div class="me-3 mt-1">
                            <div class="rounded-circle bg-light" style="width:8px; height:8px;"></div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center">
                                <span class="me-2">${icon}</span>
                                <span class="fw-bold">${label}</span>
                            </div>
                            <div class="text-muted small">${timeStr}${workingFrom}</div>
                        </div>
                    </div>
                `;
            });
        } else {
            if (activityCount) activityCount.textContent = '0 activities today';
            timelineHTML = '<div class="text-center py-3 text-muted">No activities recorded</div>';
        }
        
        if (activityTimeline) activityTimeline.innerHTML = timelineHTML;
    }
    
    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }
    
    // Event delegation for dynamically loaded content
    document.addEventListener('click', function(e) {
      const cell = e.target.closest('.att-clickable');
      if (!cell) return;

      const status = (cell.getAttribute('data-status') || '').toUpperCase();
      const empId = cell.getAttribute('data-emp-id');
      const empName = cell.getAttribute('data-emp-name');
      const empRole = cell.getAttribute('data-emp-role');
      const date = cell.getAttribute('data-date');

      if (!empId || !date) return;

      // If status is Absent (A) -> open Mark Attendance modal prefilled
      if (status === 'A') {
        const deptSelect = document.getElementById('departmentSelect');
        const empSelect  = document.getElementById('employeeSelect');
        const dateInput  = document.getElementById('attendanceDate');

        if (deptSelect && empSelect && dateInput) {
          // Find employee in ALL_EMPLOYEES to get department
          const empObj = ALL_EMPLOYEES.find(e => String(e.id) === String(empId));

          // Reset form basic fields
          document.getElementById('markAttendanceForm')?.reset();

          if (empObj && empObj.department_id) {
            deptSelect.value = String(empObj.department_id);

            // Trigger change to populate employee list for that department
            const changeEvent = new Event('change');
            deptSelect.dispatchEvent(changeEvent);

            // After population, select this employee
            Array.from(empSelect.options).forEach(opt => {
              if (String(opt.value) === String(empId)) {
                opt.selected = true;
              }
            });
          }

          // Single date mode
          const markByDateRadio = document.getElementById('markByDate');
          if (markByDateRadio) {
            markByDateRadio.checked = true;
          }
          if (typeof updateMarkByUI === 'function') {
            updateMarkByUI('date');
          }

          // Set selected date
          dateInput.value = date;

          // Default Reason to Shift End (common for full-day manual)
          const reasonSelect = document.getElementById('attendanceReason');
          if (reasonSelect) {
            reasonSelect.value = 'shift_end';
          }

          // Open Mark Attendance modal
          const markModalEl = document.getElementById('markAttendanceModal');
          if (markModalEl && typeof bootstrap !== 'undefined') {
            const markModal = bootstrap.Modal.getOrCreateInstance(markModalEl);
            markModal.show();
          }
        }

        return; // Skip details modal for absent days
      }

      initModal();
      if (!attendanceModal) {
        console.error('Modal not initialized');
        return;
      }

      // Set employee info for details modal
      const modalEmpName = document.getElementById('modalEmpName');
      const modalEmpRole = document.getElementById('modalEmpRole');
      const modalEmpAvatar = document.getElementById('modalEmpAvatar');
      const modalDate = document.getElementById('modalDate');

      if (modalEmpName) modalEmpName.textContent = empName;
      if (modalEmpRole) modalEmpRole.textContent = empRole;
      if (modalEmpAvatar) modalEmpAvatar.textContent = empName.charAt(0).toUpperCase();
      if (modalDate) modalDate.textContent = formatDate(date);

      // Show loading
      const clockInBox = document.getElementById('clockInBox');
      const clockOutBox = document.getElementById('clockOutBox');
      const totalWorkBox = document.getElementById('totalWorkBox');
      const activityTimeline = document.getElementById('activityTimeline');

      if (clockInBox) clockInBox.style.display = 'none';
      if (clockOutBox) clockOutBox.style.display = 'none';
      if (totalWorkBox) totalWorkBox.style.display = 'none';
      if (activityTimeline) activityTimeline.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm" role="status"></div></div>';

      // Fetch attendance details
      fetch('get_attendance_details.php?emp_id=' + empId + '&date=' + date)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateModal(data);
                } else {
                    if (activityTimeline) activityTimeline.innerHTML = '<div class="text-center py-3 text-muted">No attendance data found</div>';
                }
                // Ensure modal is initialized and show as popup
                if (!attendanceModal) {
                    initModal();
                }
                if (attendanceModal && typeof attendanceModal.show === 'function') {
                    attendanceModal.show();
                } else {
                    console.error('Modal not properly initialized');
                    // Fallback: manually show modal
                    const modalEl = document.getElementById('attendanceDetailsModal');
                    if (modalEl) {
                        modalEl.style.display = 'block';
                        modalEl.classList.add('show');
                        document.body.classList.add('modal-open');
                        const backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade show';
                        backdrop.id = 'attendanceModalBackdrop';
                        document.body.appendChild(backdrop);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (activityTimeline) activityTimeline.innerHTML = '<div class="text-center py-3 text-danger">Error loading data</div>';
                // Ensure modal is initialized and show as popup
                if (!attendanceModal) {
                    initModal();
                }
                if (attendanceModal && typeof attendanceModal.show === 'function') {
                    attendanceModal.show();
                } else {
                    // Fallback: manually show modal with proper Bootstrap structure
                    const modalEl = document.getElementById('attendanceDetailsModal');
                    if (modalEl) {
                        modalEl.classList.add('show');
                        modalEl.style.display = 'block';
                        modalEl.setAttribute('aria-hidden', 'false');
                        modalEl.setAttribute('aria-modal', 'true');
                        document.body.classList.add('modal-open');
                        document.body.style.overflow = 'hidden';
                        document.body.style.paddingRight = '0px';
                        
                        // Create backdrop
                        let backdrop = document.getElementById('attendanceModalBackdrop');
                        if (!backdrop) {
                            backdrop = document.createElement('div');
                            backdrop.className = 'modal-backdrop fade show';
                            backdrop.id = 'attendanceModalBackdrop';
                            document.body.appendChild(backdrop);
                        }
                    }
                }
            });
    });
    
    // Initialize on page load (after Bootstrap is loaded)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for Bootstrap to be available
            if (typeof bootstrap !== 'undefined') {
                initModal();
            } else {
                setTimeout(initModal, 100);
            }
        });
    } else {
        if (typeof bootstrap !== 'undefined') {
            initModal();
        } else {
            setTimeout(initModal, 100);
        }
    }
})();
</script>

  </div>
</body>
</html>
