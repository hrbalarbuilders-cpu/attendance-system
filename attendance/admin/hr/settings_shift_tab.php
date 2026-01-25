</tbody>
</table>
</div>
</div>

</div>



<?php

// Shift Master form and list

// Employee Shift Roster Section
include '../config/db.php';
$sql = "
SELECT e.*, d.department_name, s.shift_name, s.start_time, s.end_time
FROM employees e
LEFT JOIN departments d ON d.id = e.department_id
LEFT JOIN shifts s      ON s.id = e.shift_id
ORDER BY e.user_id DESC
";
$result = $con->query($sql);
?>
<div class="card mt-4">
	<div class="card-header d-flex justify-content-between align-items-center">
		<span class="fw-semibold">Employee Shift Roster</span>
		<small class="text-muted">Total: <?php echo $result ? $result->num_rows : 0; ?></small>
	</div>
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="table table-hover align-middle mb-0">
				<thead>
					<tr class="text-nowrap">
						<th>#</th>
						<th>Emp Code</th>
						<th>Name</th>
						<th>Department</th>
						<th>Shift</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$i = 1;
					if ($result && $result->num_rows > 0) {
						while ($row = $result->fetch_assoc()) {
							$department = $row["department_name"] ?? "-";
							$shiftName = "-";
							if (!empty($row['shift_name'])) {
								$startDisp = $row['start_time'] ? date('h:i A', strtotime($row['start_time'])) : "";
								$endDisp = $row['end_time'] ? date('h:i A', strtotime($row['end_time'])) : "";
								$shiftName = $row['shift_name'] .
									(($startDisp && $endDisp) ? " ($startDisp – $endDisp)" : "");
							}
							$isActive = (int) $row['status'] === 1;
							$updatedTs = $row['updated_at'] ?: $row['created_at'];
							$updated = $updatedTs ? date('d M Y, h:i A', strtotime($updatedTs)) : '-';
							?>
							<tr class="text-nowrap">
								<td><?php echo $i++; ?></td>
								<td><?php echo htmlspecialchars($row['emp_code'] ?? ''); ?></td>
								<td>
									<div class="fw-semibold"><?php echo htmlspecialchars($row['name']); ?></div>
									<div class="small text-muted">Updated: <?php echo $updated; ?></div>
								</td>
								<td><?php echo htmlspecialchars($department); ?></td>
								<td><?php echo htmlspecialchars($shiftName); ?></td>
								<td>
									<span class="badge bg-<?php echo $isActive ? 'success' : 'secondary'; ?>">
										<?php echo $isActive ? 'Active' : 'Inactive'; ?>
									</span>
								</td>
							</tr>
							<?php
						}
					} else {
						?>
						<tr>
							<td colspan="6" class="text-center py-4 text-muted">
								No employees found. Please add one.
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>

</div>

<!-- Shared Time Picker Modal for Shift Master (copied from shifts.php) -->
<div class="modal fade" id="shiftTimePickerModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content p-3">
			<div class="modal-body text-center">
				<div class="fs-3 fw-semibold mb-3" id="shiftTpDisplay">09:00 AM</div>
				<div class="row g-3 justify-content-center mb-3">
					<div class="col-4">
						<label class="form-label mb-1 small">Hour</label>
						<input type="number" min="1" max="12" class="form-control text-center" id="shiftTpHour"
							value="9">
					</div>
					<div class="col-4">
						<label class="form-label mb-1 small">Min</label>
						<input type="number" min="0" max="59" class="form-control text-center" id="shiftTpMinute"
							value="0">
					</div>
					<div class="col-4">
						<label class="form-label mb-1 small">Period</label>
						<div class="btn-group w-100" role="group">
							<button type="button" class="btn btn-outline-dark active" id="shiftTpAm">AM</button>
							<button type="button" class="btn btn-outline-dark" id="shiftTpPm">PM</button>
						</div>
					</div>
				</div>
				<div class="d-flex justify-content-between mt-2">
					<button type="button" class="btn btn-outline-secondary" id="shiftTpCancel"
						data-bs-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-dark" id="shiftTpApply">Apply</button>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
// Backward-compatible wrapper: Shift Master is now served by shifts.php.
// Kept so old links still work.

include __DIR__ . '/shifts.php';