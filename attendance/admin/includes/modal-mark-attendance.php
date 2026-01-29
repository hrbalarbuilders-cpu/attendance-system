<?php
// Extracted Mark Attendance Modal
// Requires $deptRes, $workingFromOptions, etc. to be defined in the parent page
// or fetching them here if not available.
// Ideally logic provided by parent page.
?>
<!-- Mark Attendance Modal -->
<div class="modal fade" id="markAttendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <!-- Header -->
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" style="font-size: 22px;">Mark Attendance</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body pt-2">
                <form id="markAttendanceForm">
                    <div class="row g-4">
                        <!-- Department -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select class="form-select" id="departmentSelect" name="department_id" required>
                                    <option value="">Select Department...</option>
                                    <?php
                                    if (isset($deptRes) && $deptRes->num_rows > 0) {
                                        mysqli_data_seek($deptRes, 0);
                                        while ($d = $deptRes->fetch_assoc()) {
                                            ?>
                                            <option value="<?php echo $d['id']; ?>">
                                                <?php echo htmlspecialchars($d['department_name']); ?>
                                            </option>
                                            <?php
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Employee -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select class="form-select" id="employeeSelect" name="employee_id" required>
                                    <option value="">Select Employee</option>
                                    <!-- JS se dept wise fill hoga -->
                                </select>
                            </div>
                        </div>

                        <!-- Late -->
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-semibold">Late</label>
                            <div class="d-flex align-items-center gap-3 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="late" id="lateYes" value="1">
                                    <label class="form-check-label" for="lateYes">Yes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="late" id="lateNo" value="0"
                                        checked>
                                    <label class="form-check-label" for="lateNo">No</label>
                                </div>
                            </div>
                        </div>

                        <!-- Half Day -->
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-semibold">Half Day</label>
                            <div class="d-flex align-items-center gap-3 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="half_day" id="halfYes" value="1">
                                    <label class="form-check-label" for="halfYes">Yes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="half_day" id="halfNo" value="0"
                                        checked>
                                    <label class="form-check-label" for="halfNo">No</label>
                                </div>
                            </div>
                        </div>

                        <!-- Mark Attendance By -->
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Mark Attendance By</label>
                            <div class="d-flex align-items-center gap-4 mt-1 flex-wrap">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mark_by" id="markByDate"
                                        value="date" checked>
                                    <label class="form-check-label" for="markByDate">Date</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mark_by" id="markByMultiple"
                                        value="multiple">
                                    <label class="form-check-label" for="markByMultiple">Multiple</label>
                                </div>
                            </div>
                        </div>

                        <!-- Select Date (single) -->
                        <div class="col-md-6 col-lg-3" id="singleDateWrapper">
                            <label class="form-label fw-semibold">Select Date <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">📅</span>
                                <input type="date" class="form-control" name="date" id="attendanceDate" required>
                            </div>
                        </div>

                        <!-- Select Multiple Dates -->
                        <div class="col-12 d-none" id="multiDatesWrapper">
                            <label class="form-label fw-semibold">Select Dates <span
                                    class="text-danger">*</span></label>
                            <div id="multiDatesList" class="d-flex flex-column gap-2"></div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addDateRow">+ Add
                                Date</button>
                            <small class="text-muted d-block mt-1">Same Clock In/Out will be applied to all selected
                                dates.</small>
                        </div>

                        <!-- Clock In -->
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-semibold">Clock In <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">⏰</span>
                                <input type="text" class="form-control time-input" name="clock_in" id="clockIn"
                                    placeholder="09:00 AM" readonly required>
                            </div>
                        </div>

                        <!-- Clock Out -->
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-semibold">Clock Out <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">⏱</span>
                                <input type="text" class="form-control time-input" name="clock_out" id="clockOut"
                                    placeholder="06:30 PM" readonly required>
                            </div>
                        </div>

                        <!-- Working From -->
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-semibold">Working From <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <select class="form-select" name="working_from" id="workingFrom" required>
                                    <option value="">Select Working From...</option>
                                    <?php if (isset($workingFromOptions)): ?>
                                        <?php foreach ($workingFromOptions as $wf) { ?>
                                            <option value="<?php echo htmlspecialchars($wf['code']); ?>">
                                                <?php echo htmlspecialchars($wf['label']); ?>
                                            </option>
                                        <?php } ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Reason / Break Type -->
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-semibold">Reason / Break Type</label>
                            <select class="form-select" name="reason" id="attendanceReason" required>
                                <option value="">Select</option>
                                <option value="lunch">Lunch Break</option>
                                <option value="shift_end">Shift End</option>
                            </select>
                        </div>

                        <!-- Attendance Overwrite -->
                        <div class="col-12">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1" id="overwrite"
                                    name="overwrite" checked>
                                <label class="form-check-label fw-semibold" for="overwrite">Attendance Overwrite</label>
                            </div>
                        </div>

                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="modal-footer border-0 pt-0 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark" id="saveAttendanceBtn">Apply</button>
            </div>
        </div>
    </div>
</div>