<?php
// Reusable Location Create/Edit Modal Include
// Supports location_group for grouping multiple coordinates
?>

<!-- modal-location.php -->
<style>
  #locationModal .modal-dialog {
    margin-top: 50px;
  }

  #admin_map {
    height: 400px;
    width: 100%;
    border-radius: 12px;
    border: 1px solid #ddd;
    margin-top: 10px;
    background: #eee;
  }

  .leaflet-draw-toolbar a {
    background-color: #fff !important;
  }
</style>

<div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Point</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" id="locationForm">
        <div class="modal-body">
          <input type="hidden" name="id" value="0">
          <input type="hidden" name="location_form" value="1">
          <input type="hidden" name="geofence_polygon" id="geofence_polygon_input">

          <!-- New Hybrid Fields for Auto-Attendance -->
          <div class="row g-2 mb-3 d-none" id="hybrid_geofence_fields">
            <div class="col-4">
              <label class="x-small fw-bold">Lat (Center)</label>
              <input type="text" name="latitude" id="lat_input" class="form-control form-control-sm" readonly
                placeholder="Auto">
            </div>
            <div class="col-4">
              <label class="x-small fw-bold">Lng (Center)</label>
              <input type="text" name="longitude" id="lng_input" class="form-control form-control-sm" readonly
                placeholder="Auto">
            </div>
            <div class="col-4">
              <label class="x-small fw-bold">Radius (m)</label>
              <input type="number" name="radius_meters" id="radius_input" class="form-control form-control-sm"
                value="150">
            </div>
          </div>

          <div class="row g-3">
            <div class="col-lg-4">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label small fw-bold">Location Group <span class="text-danger">*</span></label>
                  <input type="text" name="location_group" id="locationGroupInput" class="form-control bg-light"
                    list="locationGroupList" placeholder="e.g. Balar Builders" required>
                  <datalist id="locationGroupList"></datalist>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Office Name <span class="text-danger">*</span></label>
                  <input type="text" name="location_name" class="form-control" placeholder="e.g. Ville Flora Office"
                    required>
                </div>

                <div class="col-12 mt-2">
                  <div class="card border-primary-subtle shadow-sm">
                    <div class="card-body p-3">
                      <label class="form-label small fw-bold text-primary mb-1">
                        <i class="bi bi-clipboard-check me-1"></i> Paste from Google Maps
                      </label>
                      <textarea id="bulk_coords_input" class="form-control border-primary-subtle" rows="6"
                        placeholder="Paste Google Measured Coords here&#10;Ex:&#10;20.1234, 72.5678&#10;20.1245, 72.5689"></textarea>
                      <button type="button" class="btn btn-primary w-100 mt-2 fw-bold" onclick="applyBulkCoords()">
                        Process & Visualize
                      </button>
                    </div>
                  </div>
                </div>

                <div class="col-12">
                  <div class="alert alert-info py-2 small mb-0 border-0 shadow-sm" style="background-color: #f0f7ff;">
                    <i class="bi bi-lightning-charge-fill text-warning me-1"></i>
                    <strong>Fast Setup:</strong> Just paste the coordinates you measured in Google Maps and click
                    Process.
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-8">
              <div id="admin_map"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark">Save Location</button>
        </div>
      </form>
    </div>
  </div>
</div>

</div>
</div>
</div>