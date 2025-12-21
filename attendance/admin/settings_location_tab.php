<?php
// Location tab content moved from settings.php
include 'db.php';

// Fetch current location settings
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['location_form'])) {
  $location_name = trim($_POST['location_name'] ?? 'Office');
  $latitude      = trim($_POST['latitude'] ?? '');
  $longitude     = trim($_POST['longitude'] ?? '');
  $radius        = (int)($_POST['radius_meters'] ?? 0);
  if ($latitude === '' || $longitude === '' || $radius <= 0) {
    $errorMsg = "Please enter valid latitude, longitude and radius.";
  } else {
    $resCheck = $con->query("SELECT id FROM geo_settings WHERE id = 1 LIMIT 1");
    if ($resCheck && $resCheck->num_rows > 0) {
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
    } else {
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
    $location['location_name'] = $location_name;
    $location['latitude']      = $latitude;
    $location['longitude']     = $longitude;
    $location['radius_meters'] = $radius;
  }
}
?>
<div class="col-md-12">
  <div class="card settings-card h-100">
    <div class="card-body d-flex flex-column">
      <h5 class="card-title mb-1">Location (Geo-fence)</h5>
      <p class="text-muted small mb-2">Set office latitude, longitude and radius (meters) for geo-fencing attendance.</p>
      <?php if ($successMsg): ?>
        <div class="alert alert-success py-2"> <?php echo $successMsg; ?> </div>
      <?php elseif ($errorMsg): ?>
        <div class="alert alert-danger py-2"> <?php echo $errorMsg; ?> </div>
      <?php endif; ?>
      <form method="POST" class="mt-2">
        <input type="hidden" name="location_form" value="1">
        <div class="mb-2">
          <label class="form-label small mb-1">Location Name</label>
          <input type="text" name="location_name" class="form-control form-control-sm" value="<?php echo htmlspecialchars($location['location_name'] ?? 'Office'); ?>">
        </div>
        <div class="mb-2">
          <label class="form-label small mb-1">Latitude</label>
          <input type="text" name="latitude" class="form-control form-control-sm" placeholder="e.g. 19.0760000" value="<?php echo htmlspecialchars($location['latitude'] ?? ''); ?>">
          <div class="form-text small">Copy from Google Maps (Right click → What's here?).</div>
        </div>
        <div class="mb-2">
          <label class="form-label small mb-1">Longitude</label>
          <input type="text" name="longitude" class="form-control form-control-sm" placeholder="e.g. 72.8777000" value="<?php echo htmlspecialchars($location['longitude'] ?? ''); ?>">
        </div>
        <div class="mb-3">
          <label class="form-label small mb-1">Radius (in meters)</label>
          <input type="number" name="radius_meters" class="form-control form-control-sm" min="10" value="<?php echo htmlspecialchars($location['radius_meters'] ?? 100); ?>">
          <div class="form-text small">Common values: 50, 100, 200 meters.</div>
        </div>
        <button type="submit" class="btn btn-sm btn-success w-100">Save Location</button>
      </form>
    </div>
  </div>
</div>
