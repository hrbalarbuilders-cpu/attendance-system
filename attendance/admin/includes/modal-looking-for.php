<?php /** Modal for creating/editing Looking For items */ ?>
<style>
  /* Ensure modal appears above the fixed header */
  #lookingForModal.modal {
    z-index: 4000;
  }
  .modal-backdrop.show {
    z-index: 3900;
  }
</style>
<div class="modal fade" id="lookingForModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" id="lookingForForm">
      <div class="modal-header">
        <h5 class="modal-title">Looking For</h5>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="lookingForId" name="id" value="">
        <div class="mb-2">
          <label class="form-label">Name</label>
          <input id="lookingForName" name="name" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Types</label>
          <div id="lfTypes" class="mb-2">
            <!-- dynamic type groups inserted here -->
          </div>
          <div class="d-flex gap-2">
            <button id="btnAddLfType" type="button" class="btn btn-sm btn-outline-secondary">Add Type</button>
            <small class="text-muted">Add type groups (e.g. Villa) and subtypes (3 BHK, 4 BHK)</small>
          </div>
          <input type="hidden" id="typesJson" name="types_json" value="">
        </div>
        <!-- Project Type removed per request -->
        <div class="mb-2">
          <label class="form-label">Status</label>
          <select id="lookingForStatus" name="status" class="form-select">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
        <button class="btn btn-primary" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>
