<?php
// Reusable Leave Type Create/Edit Modal Include
// Expects: $editRow (array|null)
$modalIsEdit = isset($editRow) && $editRow ? true : false;
$modalTitle = $modalIsEdit ? 'Edit Leave Type' : 'Add Leave Type';
$modalSubmitText = $modalIsEdit ? 'Update Leave Type' : 'Add Leave Type';
$modalId = $modalIsEdit ? (int)($editRow['id'] ?? 0) : 0;
$modalCode = $modalIsEdit ? htmlspecialchars($editRow['code'] ?? '') : '';
$modalName = $modalIsEdit ? htmlspecialchars($editRow['name'] ?? '') : '';
$modalYearly = $modalIsEdit ? htmlspecialchars($editRow['yearly_quota'] ?? 0) : 0;
$modalMonthly = $modalIsEdit ? (isset($editRow['monthly_limit']) ? htmlspecialchars($editRow['monthly_limit']) : '') : '';
$modalColor = $modalIsEdit ? htmlspecialchars($editRow['color_hex'] ?? '#111827') : '#111827';
$modalUnused = $modalIsEdit ? ($editRow['unused_action'] ?? 'lapse') : 'lapse';
?>

<div class="modal fade" id="leaveTypeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo $modalTitle; ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" id="leaveTypeForm">
        <div class="modal-body">
          <input type="hidden" name="id" value="<?php echo $modalId; ?>">
          <style>
            /* Compact + slightly narrower modal (no scrollbars) */
            #leaveTypeModal .modal-dialog { max-width: 620px; }
            #leaveTypeModal .modal-body { padding-top: 0.75rem; padding-bottom: 0.75rem; }
            #leaveTypeModal .form-label { margin-bottom: 0.25rem; }
          </style>

          <div class="row g-2">
            <div class="col-12 col-md-6">
              <label class="form-label small">Code</label>
              <input type="text" name="code" class="form-control form-control-sm" placeholder="CL, PL, SL" required value="<?php echo $modalCode; ?>">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label small">Name</label>
              <input type="text" name="name" class="form-control form-control-sm" placeholder="Casual Leave" required value="<?php echo $modalName; ?>">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label small">Yearly Quota (days)</label>
              <input type="number" name="yearly_quota" class="form-control form-control-sm" min="0" value="<?php echo $modalYearly; ?>">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label small">Monthly Limit (optional)</label>
              <input type="number" name="monthly_limit" class="form-control form-control-sm" min="0" placeholder="Leave blank for no limit" value="<?php echo $modalMonthly; ?>">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label small">Color</label>
              <input type="color" name="color_hex" class="form-control form-control-sm form-control-color" style="width: 100%;" value="<?php echo $modalColor; ?>">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label small">Unused Leave Action</label>
              <select name="unused_action" class="form-select form-select-sm">
                <?php
                $options = [
                  'carry_forward' => 'Carry Forward',
                  'encash'        => 'Encash',
                  'lapse'         => 'Lapsed',
                ];
                foreach ($options as $val => $label) {
                  $sel = $modalUnused === $val ? 'selected' : '';
                  echo "<option value=\"$val\" $sel>$label</option>";
                }
                ?>
              </select>
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
