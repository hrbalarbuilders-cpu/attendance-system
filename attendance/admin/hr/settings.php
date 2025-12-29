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
    /* Top nav inside Settings (same style as Employees tabs) */
    .top-nav-wrapper {
      background: #ffffff;
      border-radius: 8px;
      padding: 6px 10px;
      box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
      display: inline-flex;
      gap: 16px;
      align-items: center;
    }
    .main-content-scroll {
      margin-top: 72px;
      /* Increased to clear fixed header and prevent top content/button from being cut off */
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
        <a href="employees.php" class="btn btn-outline-secondary">← Back to Employees</a>
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
    })
    .catch(() => {
      contentArea.innerHTML = '<div class="alert alert-danger m-3">Failed to load tab content.</div>';
    });
}
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.top-nav-pill').forEach(btn => {
    btn.addEventListener('click', function () {
      const page = btn.dataset.page;
      if (page) loadSettingsTab(page, btn);
    });
  });
  // Load default tab
  const defaultBtn = document.querySelector('.top-nav-pill.active');
  if (defaultBtn) loadSettingsTab(defaultBtn.dataset.page, defaultBtn);
});
</script>
</body>
</html>