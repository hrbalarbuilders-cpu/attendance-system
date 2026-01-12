<?php
// Reusable Location Create/Edit Modal Include
// Supports location_group for grouping multiple coordinates
?>

<style>
#locationModal .modal-dialog {
  margin-top: 80px;
}
</style>

<div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Point</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" id="locationForm">
        <div class="modal-body">
          <input type="hidden" name="id" value="0">
          <input type="hidden" name="location_form" value="1">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-semibold">Location Group <span class="text-danger">*</span></label>
              <input type="text" name="location_group" id="locationGroupInput" class="form-control" list="locationGroupList" placeholder="e.g. Company ABC" required>
              <datalist id="locationGroupList"></datalist>
              <div class="form-text small">Group name for this location. Points with same group = 1 location for clock in/out.</div>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">Point Name <span class="text-danger">*</span></label>
              <input type="text" name="location_name" class="form-control" placeholder="e.g. Branch A, HQ, Warehouse" required>
              <div class="form-text small">Specific point/branch name within the location group.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Latitude <span class="text-danger">*</span></label>
              <input type="text" name="latitude" class="form-control" placeholder="e.g. 19.0760000" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Longitude <span class="text-danger">*</span></label>
              <input type="text" name="longitude" class="form-control" placeholder="e.g. 72.8777000" required>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">Radius (meters)</label>
              <input type="number" name="radius_meters" class="form-control" min="10" value="100" required>
              <div class="form-text small">Get coordinates: Google Maps → Right click → What's here?</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark">Add Point</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Populate datalist with existing location groups
document.addEventListener('DOMContentLoaded', function() {
  var datalist = document.getElementById('locationGroupList');
  if (datalist && window.existingLocationGroups) {
    window.existingLocationGroups.forEach(function(group) {
      var option = document.createElement('option');
      option.value = group;
      datalist.appendChild(option);
    });
  }
});
</script>
