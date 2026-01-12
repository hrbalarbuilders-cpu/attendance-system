<?php
// settings.php
date_default_timezone_set('Asia/Kolkata');
include '../config/db.php';

// -------------- FETCH CURRENT LOCATION SETTINGS (default) --------------
$location = [
  'location_name' => 'Office',
  'latitude'      => '',
  'longitude'     => '',
  'radius_meters' => 100
];

$res = $con->query("SELECT * FROM geo_settings WHERE id = 1 LIMIT 1");
if ($res && $res->num_rows > 0) {
  $location = $res->fetch_assoc();
}

$successMsg = '';
$errorMsg   = '';

// -------------- HANDLE LOCATION FORM SUBMIT --------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['location_form'])) {
  $location_name = trim($_POST['location_name'] ?? 'Office');
  $latitude      = trim($_POST['latitude'] ?? '');
  $longitude     = trim($_POST['longitude'] ?? '');
  $radius        = (int)($_POST['radius_meters'] ?? 0);

  // Basic validation
  if ($latitude === '' || $longitude === '' || $radius <= 0) {
    $errorMsg = "Please enter valid latitude, longitude and radius.";
  } else {
    // Check if row exists
    $resCheck = $con->query("SELECT id FROM geo_settings WHERE id = 1 LIMIT 1");
    if ($resCheck && $resCheck->num_rows > 0) {
      // UPDATE
      $stmt = $con->prepare("
        UPDATE geo_settings
        SET location_name = ?, latitude = ?, longitude = ?, radius_meters = ?
        WHERE id = 1
      ");
      $stmt->bind_param("sssi", $location_name, $latitude, $longitude, $radius);
      if ($stmt->execute()) {
        $successMsg = "Location settings updated successfully.";
      } else {
        $errorMsg = "Failed to update location settings. DB error.";
      }
    }
    else {
      // INSERT
      $stmt = $con->prepare("
        INSERT INTO geo_settings (id, location_name, latitude, longitude, radius_meters)
        VALUES (1, ?, ?, ?, ?)
      ");
      $stmt->bind_param("sssi", $location_name, $latitude, $longitude, $radius);
      if ($stmt->execute()) {
        $successMsg = "Location settings saved successfully.";
      } else {
        $errorMsg = "Failed to save location settings. DB error.";
      }
    }

    // PHP side me bhi latest values set karo
    $location['location_name'] = $location_name;
    $location['latitude']      = $latitude;
    $location['longitude']     = $longitude;
    $location['radius_meters'] = $radius;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Settings</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f3f5fb;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    .settings-wrapper { max-width: 1200px; }
    .section-title { font-size: 1.8rem; font-weight: 700; letter-spacing: 0.02em; }
    .settings-card { border-radius: 16px; border: 1px solid #e3e3e3; box-shadow: 0 6px 18px rgba(15,23,42,0.06); }
    .main-content-scroll {
      margin-top: 72px;
      /* Increased to clear fixed header and prevent top content/button from being cut off */
    }
    /* Top nav tab styles live in admin/includes/top-nav-styles.php */
  </style>
</head>
<body>

  <?php include_once __DIR__ . '/../includes/header.php'; ?>
<div class="main-content-scroll container py-4 d-flex justify-content-center">
  <div class="settings-wrapper w-100">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="section-title mb-0">Settings</h1>
        <small class="text-muted">Configure shifts, geo-fence and other masters</small>
      </div>
      <div>
        <a href="employees.php" class="btn btn-outline-secondary">← Back to HR</a>
      </div>
    </div>
    <div class="mb-4">
      <?php include_once __DIR__ . '/../includes/navbar-settings.php'; ?>
    </div>
    <div id="settingsContentArea" class="row g-3"></div>
  </div> <!-- settings-wrapper -->
</div> <!-- container -->
<script>
// Tab switching logic for Settings page
function loadSettingsTab(page, button) {
  const contentArea = document.getElementById("settingsContentArea");
  document.querySelectorAll('.top-nav-pill').forEach(btn => btn.classList.remove('active'));
  if (button) button.classList.add('active');
  contentArea.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
  fetch(page)
    .then(response => response.text())
    .then(html => {
      contentArea.innerHTML = html;

      // If Shift Master is loaded inside Settings, bind events (pagination/edit/delete/save)
      if (String(page || '').startsWith('shifts.php')) {
        try { initSettingsShiftsTabEvents(); } catch (e) {}
        try { initShiftTimePicker(); } catch (e) {}
      }
      // If Leave Types is loaded inside Settings, bind events
      if (String(page || '').startsWith('leave_settings.php')) {
        try { initSettingsLeaveTypesTabEvents(); } catch (e) {}
      }
      // If Working From is loaded inside Settings, bind events
      if (String(page || '').startsWith('working_from_settings.php')) {
        try { initSettingsWorkingFromTabEvents(); } catch (e) {}
      }
      // If Location is loaded inside Settings, bind events
      if (String(page || '').startsWith('location_settings.php')) {
        try { initSettingsLocationTabEvents(); } catch (e) {}
      }
    })
    .catch(() => {
      contentArea.innerHTML = '<div class="alert alert-danger m-3">Failed to load tab content.</div>';
    });
}

// Leave Types tab event handling
function initSettingsLeaveTypesTabEvents() {
  var modal = document.getElementById('leaveTypeModal');
  if (!modal) return;

  // Open Add modal button
  var openBtn = document.getElementById('openLeaveTypeModal');
  if (openBtn) {
    openBtn.addEventListener('click', function() {
      // Reset form for Add mode
      modal.querySelector('[name="id"]').value = '0';
      modal.querySelector('[name="code"]').value = '';
      modal.querySelector('[name="name"]').value = '';
      modal.querySelector('[name="yearly_quota"]').value = '0';
      modal.querySelector('[name="monthly_limit"]').value = '';
      modal.querySelector('[name="color_hex"]').value = '#111827';
      modal.querySelector('[name="unused_action"]').value = 'lapse';
      modal.querySelector('.modal-title').textContent = 'Add Leave Type';
      modal.querySelector('button[type="submit"]').textContent = 'Add Leave Type';
      var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
      bsModal.show();
    });
  }

  // Edit button click: populate modal and open
  document.querySelectorAll('.edit-leave-type-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      modal.querySelector('[name="id"]').value = btn.dataset.id;
      modal.querySelector('[name="code"]').value = btn.dataset.code;
      modal.querySelector('[name="name"]').value = btn.dataset.name;
      modal.querySelector('[name="yearly_quota"]').value = btn.dataset.yearly;
      modal.querySelector('[name="monthly_limit"]').value = btn.dataset.monthly;
      modal.querySelector('[name="color_hex"]').value = btn.dataset.color;
      modal.querySelector('[name="unused_action"]').value = btn.dataset.unused;
      modal.querySelector('.modal-title').textContent = 'Edit Leave Type';
      modal.querySelector('button[type="submit"]').textContent = 'Update Leave Type';
      var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
      bsModal.show();
    });
  });

  // Toggle button
  document.querySelectorAll('.leave-type-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var id = btn.dataset.id;
      fetch('leave_settings.php?ajax=1&toggle=' + encodeURIComponent(id))
        .then(function() {
          loadSettingsTab('leave_settings.php?ajax=1', document.querySelector('[data-page^="leave_settings.php"]'));
        });
    });
  });

  // Delete button
  document.querySelectorAll('.leave-type-delete').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (!confirm('Delete this leave type?')) return;
      var id = btn.dataset.id;
      fetch('leave_settings.php?ajax=1&delete=' + encodeURIComponent(id))
        .then(function() {
          loadSettingsTab('leave_settings.php?ajax=1', document.querySelector('[data-page^="leave_settings.php"]'));
        });
    });
  });

  // Form submit handler
  var form = document.getElementById('leaveTypeForm');
  if (form && !form.dataset.bound) {
    form.dataset.bound = '1';
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      var formData = new FormData(form);
      fetch('leave_settings.php?ajax=1', {
        method: 'POST',
        body: formData
      })
      .then(function() {
        var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
        bsModal.hide();
        loadSettingsTab('leave_settings.php?ajax=1', document.querySelector('[data-page^="leave_settings.php"]'));
      })
      .catch(function() {
        alert('Error saving leave type.');
      });
    });
  }
}

// Working From tab event handling
function initSettingsWorkingFromTabEvents() {
  var modal = document.getElementById('workingFromModal');
  if (!modal) return;

  // Open Add modal button
  var openBtn = document.getElementById('openWorkingFromModal');
  if (openBtn) {
    openBtn.addEventListener('click', function() {
      modal.querySelector('[name="id"]').value = '0';
      modal.querySelector('[name="code"]').value = '';
      modal.querySelector('[name="label"]').value = '';
      modal.querySelector('.modal-title').textContent = 'Add Working From';
      modal.querySelector('button[type="submit"]').textContent = 'Add';
      var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
      bsModal.show();
    });
  }

  // Edit button click
  document.querySelectorAll('.edit-working-from-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      modal.querySelector('[name="id"]').value = btn.dataset.id;
      modal.querySelector('[name="code"]').value = btn.dataset.code;
      modal.querySelector('[name="label"]').value = btn.dataset.label;
      modal.querySelector('.modal-title').textContent = 'Edit Working From';
      modal.querySelector('button[type="submit"]').textContent = 'Update';
      var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
      bsModal.show();
    });
  });

  // Toggle button
  document.querySelectorAll('.working-from-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var id = btn.dataset.id;
      fetch('working_from_settings.php?ajax=1&toggle=' + encodeURIComponent(id))
        .then(function() {
          loadSettingsTab('working_from_settings.php?ajax=1', document.querySelector('[data-page^="working_from_settings.php"]'));
        });
    });
  });

  // Delete button
  document.querySelectorAll('.working-from-delete').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (!confirm('Delete this option?')) return;
      var id = btn.dataset.id;
      fetch('working_from_settings.php?ajax=1&delete=' + encodeURIComponent(id))
        .then(function() {
          loadSettingsTab('working_from_settings.php?ajax=1', document.querySelector('[data-page^="working_from_settings.php"]'));
        });
    });
  });

  // Form submit handler
  var form = document.getElementById('workingFromForm');
  if (form && !form.dataset.bound) {
    form.dataset.bound = '1';
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      var formData = new FormData(form);
      fetch('working_from_settings.php?ajax=1', {
        method: 'POST',
        body: formData
      })
      .then(function() {
        var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
        bsModal.hide();
        loadSettingsTab('working_from_settings.php?ajax=1', document.querySelector('[data-page^="working_from_settings.php"]'));
      })
      .catch(function() {
        alert('Error saving working from option.');
      });
    });
  }
}

// Location tab event handling
function initSettingsLocationTabEvents() {
  var modal = document.getElementById('locationModal');
  if (!modal) return;

  // Populate datalist with existing location groups
  var datalist = document.getElementById('locationGroupList');
  if (datalist && window.existingLocationGroups) {
    datalist.innerHTML = '';
    window.existingLocationGroups.forEach(function(group) {
      var option = document.createElement('option');
      option.value = group;
      datalist.appendChild(option);
    });
  }

  // Open Add modal button
  var openBtn = document.getElementById('openLocationModal');
  if (openBtn) {
    openBtn.addEventListener('click', function() {
      modal.querySelector('[name="id"]').value = '0';
      modal.querySelector('[name="location_group"]').value = '';
      modal.querySelector('[name="location_name"]').value = '';
      modal.querySelector('[name="latitude"]').value = '';
      modal.querySelector('[name="longitude"]').value = '';
      modal.querySelector('[name="radius_meters"]').value = '100';
      modal.querySelector('.modal-title').textContent = 'Add Point';
      modal.querySelector('button[type="submit"]').textContent = 'Add Point';
      var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
      bsModal.show();
    });
  }

  // Edit button click
  document.querySelectorAll('.edit-location-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      modal.querySelector('[name="id"]').value = btn.dataset.id;
      modal.querySelector('[name="location_group"]').value = btn.dataset.group || '';
      modal.querySelector('[name="location_name"]').value = btn.dataset.name;
      modal.querySelector('[name="latitude"]').value = btn.dataset.lat;
      modal.querySelector('[name="longitude"]').value = btn.dataset.lng;
      modal.querySelector('[name="radius_meters"]').value = btn.dataset.radius;
      modal.querySelector('.modal-title').textContent = 'Edit Point';
      modal.querySelector('button[type="submit"]').textContent = 'Update';
      var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
      bsModal.show();
    });
  });

  // Reset on close
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

  // Toggle button
  document.querySelectorAll('.location-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var id = btn.dataset.id;
      fetch('location_settings.php?ajax=1&toggle=' + encodeURIComponent(id))
        .then(function() {
          loadSettingsTab('location_settings.php?ajax=1', document.querySelector('[data-page^="location_settings.php"]'));
        });
    });
  });

  // Delete button
  document.querySelectorAll('.location-delete').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (!confirm('Delete this point?')) return;
      var id = btn.dataset.id;
      fetch('location_settings.php?ajax=1&delete=' + encodeURIComponent(id))
        .then(function() {
          loadSettingsTab('location_settings.php?ajax=1', document.querySelector('[data-page^="location_settings.php"]'));
        });
    });
  });

  // Form submit handler
  var form = document.getElementById('locationForm');
  if (form && !form.dataset.bound) {
    form.dataset.bound = '1';
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      var formData = new FormData(form);
      fetch('location_settings.php?ajax=1', {
        method: 'POST',
        body: formData
      })
      .then(function() {
        var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
        bsModal.hide();
        loadSettingsTab('location_settings.php?ajax=1', document.querySelector('[data-page^="location_settings.php"]'));
      })
      .catch(function() {
        alert('Error saving location.');
      });
    });
  }
}

function initSettingsShiftsTabEvents() {
  var meta = document.getElementById('shiftsPagingMeta');
  var perSel = document.getElementById('shiftsPerPageFooter');
  if (!meta || !perSel) return;

  var currentPage = parseInt(meta.getAttribute('data-page') || '1', 10) || 1;
  var currentPer = parseInt(meta.getAttribute('data-per-page') || '10', 10) || 10;
  perSel.value = String(currentPer);

  function loadShiftMaster(p, perPage) {
    var url = 'shifts.php?ajax=1'
      + '&page=' + encodeURIComponent(p)
      + '&per_page=' + encodeURIComponent(perPage);
    loadSettingsTab(url, document.querySelector('[data-page^="shifts.php"]'));
  }

  perSel.onchange = function(){
    var newPer = parseInt(this.value || '10', 10) || 10;
    loadShiftMaster(1, newPer);
  };

  document.querySelectorAll('.shifts-page-link').forEach(function(a){
    a.addEventListener('click', function(e){
      e.preventDefault();
      var p = parseInt(this.dataset.page || '1', 10) || 1;
      var per = parseInt(perSel.value || '10', 10) || 10;
      loadShiftMaster(p, per);
    });
  });

  // Edit
  document.querySelectorAll('.shift-edit').forEach(function(btn){
    btn.addEventListener('click', function(e){
      e.preventDefault();
      var id = this.dataset.editId;
      var per = parseInt(perSel.value || '10', 10) || 10;
      loadSettingsTab('shifts.php?ajax=1&edit=' + encodeURIComponent(id) + '&page=' + encodeURIComponent(currentPage) + '&per_page=' + encodeURIComponent(per),
        document.querySelector('[data-page^="shifts.php"]'));
    });
  });

  // Delete
  document.querySelectorAll('.shift-delete').forEach(function(btn){
    btn.addEventListener('click', function(e){
      e.preventDefault();
      var id = this.dataset.delId;
      if (!confirm('Delete this shift?')) return;
      fetch('shifts.php?ajax=1&delete=' + encodeURIComponent(id))
        .then(function(r){ return r.json(); })
        .then(function(data){
          if (data && data.success && data.reload) {
            loadSettingsTab(data.reload, document.querySelector('[data-page^="shifts.php"]'));
          } else {
            alert('Failed to delete shift.');
          }
        })
        .catch(function(){ alert('Failed to delete shift.'); });
    });
  });

  // Create Shift modal reset
  var openBtn = document.getElementById('openCreateShiftModal');
  var createForm = document.getElementById('createShiftForm');
  var createErr = document.getElementById('createShiftErrors');
  if (openBtn && createForm && !openBtn.dataset.bound) {
    openBtn.dataset.bound = '1';
    openBtn.addEventListener('click', function(){
      try { createForm.reset(); } catch (e) {}
      if (createErr) {
        createErr.classList.add('d-none');
        createErr.innerHTML = '';
      }
    });
  }

  // Auto-open modal when coming from Edit action
  var openMeta = document.getElementById('shiftModalMeta');
  var modalEl = document.getElementById('createShiftModal');
  if (openMeta && modalEl && openMeta.getAttribute('data-open') === '1' && typeof bootstrap !== 'undefined') {
    try {
      var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();
    } catch (e) {}
  }
}

// Intercept Shift Master form submit when running inside Settings
document.addEventListener('submit', function(e){
  var form = e.target;
  if (!form.closest('#settingsContentArea')) return;
  if (!form.action || !String(form.action).includes('shifts.php')) return;
  e.preventDefault();

  var formData = new FormData(form);
  fetch('shifts.php?ajax=1', {
    method: 'POST',
    body: formData
  })
  .then(function(r){ return r.json(); })
  .then(function(data){
    if (data && data.success && data.reload) {
      // If coming from Create Shift modal, close it before reload
      var modalEl = document.getElementById('createShiftModal');
      if (modalEl && typeof bootstrap !== 'undefined') {
        try {
          var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
          modal.hide();
        } catch (e) {}
      }
      loadSettingsTab(data.reload, document.querySelector('[data-page^="shifts.php"]'));
    } else if (data && data.errors) {
      var errBox = document.getElementById('createShiftErrors');
      if (form && form.id === 'createShiftForm' && errBox) {
        errBox.classList.remove('d-none');
        errBox.innerHTML = '<ul class="mb-0">' + data.errors.map(function(m){
          return '<li>' + String(m) + '</li>';
        }).join('') + '</ul>';
      } else {
        alert(data.errors.join(' '));
      }
    } else {
      alert('Error saving shift.');
    }
  })
  .catch(function(){ alert('Error saving shift.'); });
});
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.top-nav-pill').forEach(btn => {
    btn.addEventListener('click', function () {
      const page = btn.dataset.page;
      if (page) {
        // Store active tab in URL hash for persistence on reload
        window.location.hash = encodeURIComponent(page);
        loadSettingsTab(page, btn);
      }
    });
  });

  // On load, check URL hash for active tab, else use default
  var hashPage = window.location.hash ? decodeURIComponent(window.location.hash.substring(1)) : null;
  var targetBtn = null;
  if (hashPage) {
    targetBtn = document.querySelector('.top-nav-pill[data-page="' + hashPage + '"]');
  }
  if (!targetBtn) {
    targetBtn = document.querySelector('.top-nav-pill.active') || document.querySelector('.top-nav-pill');
  }
  if (targetBtn) {
    loadSettingsTab(targetBtn.dataset.page, targetBtn);
  }
});
</script>

<!-- Bootstrap JS (needed for Shift time picker modal) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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

<script>
// Minimal time picker (same as Employees Shift Master)
function initShiftTimePicker() {
  const modalEl = document.getElementById('shiftTimePickerModal');
  if (!modalEl || typeof bootstrap === 'undefined') return;

  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  const displayEl = document.getElementById('shiftTpDisplay');
  const hourEl = document.getElementById('shiftTpHour');
  const minuteEl = document.getElementById('shiftTpMinute');
  const amBtn = document.getElementById('shiftTpAm');
  const pmBtn = document.getElementById('shiftTpPm');
  const applyBtn = document.getElementById('shiftTpApply');
  const cancelBtn = document.getElementById('shiftTpCancel');

  if (!displayEl || !hourEl || !minuteEl || !amBtn || !pmBtn || !applyBtn) return;

  // Persist target across tab reloads (apply button handler is bound once)
  if (!('tpTarget' in modalEl)) {
    modalEl.tpTarget = null;
  }

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
    modalEl.tpTarget = input;
    if (!modalEl.tpTarget) return;

    hourEl.value = 9;
    minuteEl.value = 0;
    amBtn.classList.add('active');
    pmBtn.classList.remove('active');

    parseExisting(modalEl.tpTarget.value);
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

  if (cancelBtn && !cancelBtn.dataset.bound) {
    cancelBtn.dataset.bound = '1';
    cancelBtn.addEventListener('click', function() {
      modalEl.tpTarget = null;
      modal.hide();
    });
  }

  if (!applyBtn.dataset.bound) {
    applyBtn.dataset.bound = '1';
    applyBtn.addEventListener('click', function() {
      const target = modalEl.tpTarget;
      if (!target) return;
      updateDisplay();
      target.value = displayEl.textContent;

      // Auto-calculate Half Time when Start or End time is set
      if (target.id === 'start_time' || target.id === 'end_time') {
        const scope = target.closest('form') || document;
        const startInput = scope.querySelector('#start_time');
        const endInput = scope.querySelector('#end_time');
        const halfInput = scope.querySelector('#half_day_time');
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
}
</script>
</body>
</html>