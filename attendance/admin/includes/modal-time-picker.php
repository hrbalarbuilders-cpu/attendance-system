<?php
// Shared Time Picker Modal for Shift Master and Mark Attendance
?>
<div class="modal fade" id="shiftTimePickerModal" tabindex="-1" aria-hidden="true" style="z-index: 3300 !important;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3 border-0 shadow-lg rounded-4">
            <div class="modal-body text-center">
                <div class="fs-2 fw-bold mb-4 text-primary" id="shiftTpDisplay">09:00 AM</div>
                <div class="row g-3 justify-content-center mb-4">
                    <div class="col-4">
                        <label class="form-label mb-2 small fw-semibold text-muted">Hour</label>
                        <input type="number" min="1" max="12"
                            class="form-control form-control-lg text-center fw-bold border-2" id="shiftTpHour"
                            value="9">
                    </div>
                    <div class="col-4">
                        <label class="form-label mb-2 small fw-semibold text-muted">Min</label>
                        <input type="number" min="0" max="59"
                            class="form-control form-control-lg text-center fw-bold border-2" id="shiftTpMinute"
                            value="0">
                    </div>
                    <div class="col-4">
                        <label class="form-label mb-2 small fw-semibold text-muted">Period</label>
                        <div class="btn-group btn-group-lg w-100 shadow-sm" role="group">
                            <button type="button" class="btn btn-outline-primary fw-bold active"
                                id="shiftTpAm">AM</button>
                            <button type="button" class="btn btn-outline-primary fw-bold" id="shiftTpPm">PM</button>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between gap-3 mt-2">
                    <button type="button" class="btn btn-light btn-lg px-4 fw-semibold border w-100" id="shiftTpCancel"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-lg px-4 fw-bold w-100 shadow-sm"
                        id="shiftTpApply">Apply</button>
                </div>
            </div>
        </div>
    </div>
</div>