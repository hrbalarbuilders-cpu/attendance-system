<?php
// Reusable Working From Create/Edit Modal Include
// Expects: (optional) $editRow for pre-populating edit mode
$modalIsEdit = isset($editRow) && $editRow ? true : false;
$modalTitle = $modalIsEdit ? 'Edit Working From' : 'Add Working From';
$modalSubmitText = $modalIsEdit ? 'Update' : 'Add';
$modalId = $modalIsEdit ? (int)($editRow['id'] ?? 0) : 0;
$modalCode = $modalIsEdit ? htmlspecialchars($editRow['code'] ?? '') : '';
$modalLabel = $modalIsEdit ? htmlspecialchars($editRow['label'] ?? '') : '';
?>

<div class="modal fade" id="workingFromModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo $modalTitle; ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" id="workingFromForm">
        <div class="modal-body">
          <input type="hidden" name="id" value="<?php echo $modalId; ?>">
          <div class="row g-2">
            <div class="col-12 col-md-6">
              <label class="form-label small">Code</label>
              <input type="text" name="code" class="form-control form-control-sm" placeholder="office, home, client" required value="<?php echo $modalCode; ?>">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label small">Label</label>
              <input type="text" name="label" class="form-control form-control-sm" placeholder="Office, Home, Client Site" required value="<?php echo $modalLabel; ?>">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark"><?php echo $modalSubmitText; ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
