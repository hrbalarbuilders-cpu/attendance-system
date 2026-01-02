<?php
// Modal for Add / Edit Source (moved to includes for reuse)
?>
<div class="modal fade" id="sourceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="sourceForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add / Edit Source</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="sourceId" name="id">
        <div class="mb-2">
          <label class="form-label">Name</label>
          <input name="name" id="sourceName" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Description</label>
          <textarea name="description" id="sourceDescription" class="form-control" rows="3"></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label">Status</label>
          <select name="status" id="sourceStatus" class="form-select">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>
