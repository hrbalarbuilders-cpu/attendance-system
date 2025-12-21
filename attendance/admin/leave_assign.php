<?php
// leave_assign.php - map leave types to specific employees
date_default_timezone_set('Asia/Kolkata');
include 'db.php';

$leaveId = isset($_GET['leave_id']) ? (int)$_GET['leave_id'] : 0;
if ($leaveId <= 0) {
		header('Location: leave_settings.php');
		exit;
}

// Ensure mapping table exists (safety for older DBs)
$tableCheck = $con->query("SHOW TABLES LIKE 'leave_type_employees'");
if (!$tableCheck || $tableCheck->num_rows == 0) {
		$con->query("CREATE TABLE IF NOT EXISTS `leave_type_employees` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`leave_type_id` int(11) NOT NULL,
				`employee_id` int(11) NOT NULL,
				`created_at` timestamp NOT NULL DEFAULT current_timestamp(),
				PRIMARY KEY (`id`),
				UNIQUE KEY `uniq_leave_emp` (`leave_type_id`,`employee_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
}

// Fetch leave type
$resLeave = $con->query("SELECT * FROM leave_types WHERE id = " . $leaveId . " LIMIT 1");
if (!$resLeave || $resLeave->num_rows === 0) {
		header('Location: leave_settings.php');
		exit;
}
$leaveType = $resLeave->fetch_assoc();

// Handle POST save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$selected = isset($_POST['employee_ids']) && is_array($_POST['employee_ids'])
				? array_map('intval', $_POST['employee_ids'])
				: [];

		// Clear existing mappings
		$con->query("DELETE FROM leave_type_employees WHERE leave_type_id = " . $leaveId);

		if (!empty($selected)) {
				$stmt = $con->prepare("INSERT INTO leave_type_employees (leave_type_id, employee_id) VALUES (?, ?)");
				foreach ($selected as $empId) {
						$stmt->bind_param('ii', $leaveId, $empId);
						$stmt->execute();
				}
		}

		header('Location: leave_settings.php');
		exit;
}

// Fetch all employees
$empRes = $con->query("SELECT id, emp_code, name, department_id FROM employees ORDER BY name ASC");

// Fetch departments for labels
$deptMap = [];
$deptRes = $con->query("SELECT id, department_name FROM departments");
if ($deptRes) {
		while ($d = $deptRes->fetch_assoc()) {
				$deptMap[$d['id']] = $d['department_name'];
		}
}

// Fetch current mappings
$assigned = [];
$mapRes = $con->query("SELECT employee_id FROM leave_type_employees WHERE leave_type_id = " . $leaveId);
if ($mapRes) {
		while ($m = $mapRes->fetch_assoc()) {
				$assigned[] = (int)$m['employee_id'];
		}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Assign Employees - <?php echo htmlspecialchars($leaveType['name']); ?></title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
		body { background: #f3f5fb; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
		.page-wrapper { max-width: 1100px; }
		.section-title { font-size: 1.8rem; font-weight: 700; letter-spacing: 0.02em; }
	</style>
</head>
<body>

<div class="container py-4 d-flex justify-content-center">
	<div class="page-wrapper w-100">

		<div class="d-flex justify-content-between align-items-center mb-3">
			<div>
				<h1 class="section-title mb-0">Assign Employees</h1>
				<small class="text-muted">Leave type: <?php echo htmlspecialchars($leaveType['name']); ?> (<?php echo htmlspecialchars($leaveType['code']); ?>)</small>
			</div>
			<a href="leave_settings.php" class="btn btn-outline-secondary">← Back to Leave Settings</a>
		</div>

		<form method="POST">
			<div class="card">
				<div class="card-body">
					<div class="d-flex justify-content-between align-items-center mb-2">
						<h5 class="mb-0">Select applicable employees</h5>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" id="checkAll">
							<label class="form-check-label" for="checkAll">Select All</label>
						</div>
					</div>

					<div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
						<table class="table table-sm align-middle mb-0">
							<thead class="table-light">
								<tr>
									<th style="width:40px;"></th>
									<th>Emp Code</th>
									<th>Name</th>
									<th>Department</th>
								</tr>
							</thead>
							<tbody>
							<?php if ($empRes && $empRes->num_rows > 0): ?>
								<?php while ($e = $empRes->fetch_assoc()):
									$isChecked = in_array((int)$e['id'], $assigned, true);
									$deptName = $deptMap[$e['department_id']] ?? '-';
								?>
									<tr>
										<td>
											<input type="checkbox" class="form-check-input emp-check" name="employee_ids[]" value="<?php echo (int)$e['id']; ?>" <?php echo $isChecked ? 'checked' : ''; ?>>
										</td>
										<td><?php echo htmlspecialchars($e['emp_code']); ?></td>
										<td><?php echo htmlspecialchars($e['name']); ?></td>
										<td><?php echo htmlspecialchars($deptName); ?></td>
									</tr>
								<?php endwhile; ?>
							<?php else: ?>
								<tr>
									<td colspan="4" class="text-muted">No employees found.</td>
								</tr>
							<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="card-footer d-flex justify-content-end gap-2">
					<a href="leave_settings.php" class="btn btn-light border">Cancel</a>
					<button type="submit" class="btn btn-dark">Save Applicability</button>
				</div>
			</div>
		</form>

	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	const checkAll = document.getElementById('checkAll');
	const checks = document.querySelectorAll('.emp-check');
	if (checkAll) {
		checkAll.addEventListener('change', function () {
			checks.forEach(c => c.checked = checkAll.checked);
		});
	}
});
</script>

</body>
</html>
