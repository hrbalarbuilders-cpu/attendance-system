<?php
// Reusable Shift Create/Edit Modal Include (used in Settings -> Shifts tab)
//
// Expected variables (set by the caller):
// $modalIsEdit, $modalTitle, $modalSubmitText, $modalId,
// $modalShiftName, $modalShiftColor, $modalStart, $modalEnd,
// $modalLunchStart, $modalLunchEnd, $modalEarly, $modalLate,
// $modalHalf, $modalPunches

$modalIsEdit = isset($modalIsEdit) ? (bool)$modalIsEdit : false;
$modalTitle = isset($modalTitle) ? (string)$modalTitle : 'Create Shift';
$modalSubmitText = isset($modalSubmitText) ? (string)$modalSubmitText : 'Save Shift';
$modalId = isset($modalId) ? (int)$modalId : 0;
$modalShiftName = isset($modalShiftName) ? (string)$modalShiftName : '';
$modalShiftColor = isset($modalShiftColor) ? (string)$modalShiftColor : '#0d6efd';
$modalStart = isset($modalStart) ? (string)$modalStart : '';
$modalEnd = isset($modalEnd) ? (string)$modalEnd : '';
$modalLunchStart = isset($modalLunchStart) ? (string)$modalLunchStart : '';
$modalLunchEnd = isset($modalLunchEnd) ? (string)$modalLunchEnd : '';
$modalHalf = isset($modalHalf) ? (string)$modalHalf : '';
$modalEarly = isset($modalEarly) ? (int)$modalEarly : 0;
$modalLate = isset($modalLate) ? (int)$modalLate : 10;
$modalPunches = isset($modalPunches) ? (int)$modalPunches : 4;
?>

<?php if ($modalIsEdit): ?>
  <div id="shiftModalMeta" data-open="1" style="display:none;"></div>
<?php endif; ?>

<style>
  /* Settings page has a fixed header; keep modal below it */
  #createShiftModal .modal-dialog { margin-top: 96px; }
</style>

<!-- Create/Edit Shift Modal (Settings) -->
<div class="modal fade" id="createShiftModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo htmlspecialchars($modalTitle); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" id="createShiftErrors"></div>

        <form method="POST" action="shifts.php" id="createShiftForm">
          <input type="hidden" name="id" value="<?php echo (int)$modalId; ?>">

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Shift Name</label>
              <input type="text" name="shift_name" class="form-control" required value="<?php echo htmlspecialchars($modalShiftName); ?>">
            </div>

            <div class="col-md-2">
              <label class="form-label">Shift Color</label>
              <input type="color" name="shift_color" class="form-control form-control-color" style="width: 100%;" value="<?php echo htmlspecialchars($modalShiftColor); ?>">
            </div>

            <div class="col-md-3">
              <label class="form-label">Start Time</label>
              <input type="text" name="start_time" id="start_time" class="form-control time-input" placeholder="09:00 AM" readonly required value="<?php echo htmlspecialchars($modalStart); ?>">
            </div>

            <div class="col-md-3">
              <label class="form-label">End Time</label>
              <input type="text" name="end_time" id="end_time" class="form-control time-input" placeholder="06:30 PM" readonly required value="<?php echo htmlspecialchars($modalEnd); ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label">Lunch Start (optional)</label>
              <input type="text" name="lunch_start" id="lunch_start" class="form-control time-input" placeholder="01:00 PM" readonly value="<?php echo htmlspecialchars($modalLunchStart); ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label">Lunch End (optional)</label>
              <input type="text" name="lunch_end" id="lunch_end" class="form-control time-input" placeholder="01:30 PM" readonly value="<?php echo htmlspecialchars($modalLunchEnd); ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label">Early Clock-In</label>
              <input type="number" name="early_clock_in_before" class="form-control" min="0" value="<?php echo (int)$modalEarly; ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label">Late Mark After</label>
              <input type="number" name="late_mark_after" class="form-control" min="1" required value="<?php echo (int)$modalLate; ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label">Half Time After</label>
              <input type="text" name="half_day_time" id="half_day_time" class="form-control time-input" placeholder="Auto / 02:30 PM" readonly value="<?php echo htmlspecialchars($modalHalf); ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label">Total Punches Per Day</label>
              <input type="number" name="total_punches" class="form-control" min="1" required value="<?php echo (int)$modalPunches; ?>">
            </div>
          </div>

          <div class="mt-4 d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark"><?php echo htmlspecialchars($modalSubmitText); ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
