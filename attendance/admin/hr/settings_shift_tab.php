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
ORDER BY e.id DESC
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
										$endDisp   = $row['end_time']   ? date('h:i A', strtotime($row['end_time']))   : "";
										$shiftName = $row['shift_name'] .
																 (($startDisp && $endDisp) ? " ($startDisp – $endDisp)" : "");
								}
								$isActive  = (int)$row['status'] === 1;
								$updatedTs = $row['updated_at'] ?: $row['created_at'];
								$updated   = $updatedTs ? date('d M Y, h:i A', strtotime($updatedTs)) : '-';
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
						<input type="number" min="1" max="12" class="form-control text-center" id="shiftTpHour" value="9">
					</div>
					<div class="col-4">
						<label class="form-label mb-1 small">Min</label>
						<input type="number" min="0" max="59" class="form-control text-center" id="shiftTpMinute" value="0">
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
					<button type="button" class="btn btn-outline-secondary" id="shiftTpCancel" data-bs-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-dark" id="shiftTpApply">Apply</button>
				</div>
			</div>
		</div>
	</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Custom time picker logic for Shift Master (from shifts.php)
function initShiftTimePicker() {
	const modalEl = document.getElementById('shiftTimePickerModal');
	if (!modalEl || typeof bootstrap === 'undefined') return;

	const modal = new bootstrap.Modal(modalEl);
	const displayEl = document.getElementById('shiftTpDisplay');
	const hourEl = document.getElementById('shiftTpHour');
	const minuteEl = document.getElementById('shiftTpMinute');
	const amBtn = document.getElementById('shiftTpAm');
	const pmBtn = document.getElementById('shiftTpPm');
	const applyBtn = document.getElementById('shiftTpApply');
	const cancelBtn = document.getElementById('shiftTpCancel');

	if (!displayEl || !hourEl || !minuteEl || !amBtn || !pmBtn || !applyBtn) return;

	let currentTargetInput = null;

	function parseToMinutes(value) {
		if (!value) return null;
		const match = value.trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
		if (!match) return null;
		let h = parseInt(match[1], 10);
		const m = parseInt(match[2], 10);
		const period = match[3].toUpperCase();
		if (isNaN(h) || isNaN(m)) return null;
		if (h === 12) h = 0;
		if (period === 'PM') h += 12;
		return h * 60 + m;
	}

	function formatFromMinutes(totalMinutes) {
		totalMinutes = ((totalMinutes % (24 * 60)) + (24 * 60)) % (24 * 60);
		let h24 = Math.floor(totalMinutes / 60);
		const m = totalMinutes % 60;
		let period = 'AM';
		if (h24 >= 12) {
			period = 'PM';
			if (h24 > 12) h24 -= 12;
		}
		if (h24 === 0) h24 = 12;
		const hStr = h24.toString().padStart(2, '0');
		const mStr = m.toString().padStart(2, '0');
		return `${hStr}:${mStr} ${period}`;
	}

	function updateDisplay() {
		let h = parseInt(hourEl.value || '0', 10);
		let m = parseInt(minuteEl.value || '0', 10);
		if (isNaN(h) || h < 1) h = 1;
		if (h > 12) h = 12;
		if (isNaN(m) || m < 0) m = 0;
		if (m > 59) m = 59;
		hourEl.value = h;
		minuteEl.value = m;
		const period = amBtn.classList.contains('active') ? 'AM' : 'PM';
		const hStr = h.toString().padStart(2, '0');
		const mStr = m.toString().padStart(2, '0');
		displayEl.textContent = `${hStr}:${mStr} ${period}`;
	}

	function parseExisting(value) {
		if (!value) return;
		const match = value.trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
		if (!match) return;
		const h = parseInt(match[1], 10);
		const m = parseInt(match[2], 10);
		const period = match[3].toUpperCase();
		if (!isNaN(h)) hourEl.value = h;
		if (!isNaN(m)) minuteEl.value = m;
		if (period === 'PM') {
			pmBtn.classList.add('active');
			amBtn.classList.remove('active');
		} else {
			amBtn.classList.add('active');
			pmBtn.classList.remove('active');
		}
		updateDisplay();
	}

	function openPickerForInput(input) {
		currentTargetInput = input;
		if (!currentTargetInput) return;

		hourEl.value = 9;
		minuteEl.value = 0;
		amBtn.classList.add('active');
		pmBtn.classList.remove('active');

		parseExisting(currentTargetInput.value);
		updateDisplay();
		modal.show();
	}

	document.querySelectorAll('.time-input').forEach(input => {
		if (input.dataset.tpBound === '1') return;
		input.dataset.tpBound = '1';
		input.addEventListener('click', function() {
			openPickerForInput(this);
		});
	});

	hourEl.addEventListener('input', updateDisplay);
	minuteEl.addEventListener('input', updateDisplay);
	amBtn.addEventListener('click', function() {
		amBtn.classList.add('active');
		pmBtn.classList.remove('active');
		updateDisplay();
	});
	pmBtn.addEventListener('click', function() {
		pmBtn.classList.add('active');
		amBtn.classList.remove('active');
		updateDisplay();
	});

	if (cancelBtn) {
		cancelBtn.addEventListener('click', function() {
			currentTargetInput = null;
			modal.hide();
		});
	}

	applyBtn.addEventListener('click', function() {
		if (!currentTargetInput) return;
		updateDisplay();
		currentTargetInput.value = displayEl.textContent;

		// Auto-calculate Half Time when Start or End time is set
		if (currentTargetInput.id === 'start_time' || currentTargetInput.id === 'end_time') {
			const startInput = document.getElementById('start_time');
			const endInput = document.getElementById('end_time');
			const halfInput = document.getElementById('half_day_time');
			if (startInput && endInput && halfInput) {
				const sMin = parseToMinutes(startInput.value);
				const eMinRaw = parseToMinutes(endInput.value);
				if (sMin !== null && eMinRaw !== null) {
					let eMin = eMinRaw;
					if (eMin <= sMin) {
						// Overnight shift: end is next day
						eMin += 24 * 60;
					}
					const halfMin = Math.round(sMin + (eMin - sMin) / 2);
					halfInput.value = formatFromMinutes(halfMin);
				}
			}
		}
		modal.hide();
	});
}

// Initialize on load (for full page context)
document.addEventListener('DOMContentLoaded', function () {
	initShiftTimePicker();
});
</script>