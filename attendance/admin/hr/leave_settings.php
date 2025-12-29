<?php
// leave_settings.php
date_default_timezone_set('Asia/Kolkata');
include '../config/db.php';

// Ensure leave_types table exists
$tableCheck = $con->query("SHOW TABLES LIKE 'leave_types'");
if (!$tableCheck || $tableCheck->num_rows == 0) {
		$createSql = "
				CREATE TABLE `leave_types` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`code` varchar(50) NOT NULL,
					`name` varchar(100) NOT NULL,
					`yearly_quota` int(11) NOT NULL DEFAULT 0,
					`monthly_limit` int(11) DEFAULT NULL,
					`color_hex` varchar(20) DEFAULT '#111827',
					`unused_action` varchar(20) NOT NULL DEFAULT 'lapse',
					`applicability` varchar(255) DEFAULT NULL,
					`is_active` tinyint(1) NOT NULL DEFAULT 1,
					`created_at` timestamp NOT NULL DEFAULT current_timestamp(),
					`updated_at` timestamp NULL DEFAULT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `code` (`code`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
		";
		$con->query($createSql);
}

$successMsg = '';
$errorMsg   = '';

// Handle add / update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$id            = (int)($_POST['id'] ?? 0);
		$code          = trim($_POST['code'] ?? '');
		$name          = trim($_POST['name'] ?? '');
		$yearly_quota  = (int)($_POST['yearly_quota'] ?? 0);
		$monthly_limit = $_POST['monthly_limit'] === '' ? null : (int)$_POST['monthly_limit'];
		$color_hex     = trim($_POST['color_hex'] ?? '#111827');
		$unused_action = trim($_POST['unused_action'] ?? 'lapse');
		$applicability = ''; // applicability no longer used

		if ($code === '' || $name === '') {
				$errorMsg = 'Please enter leave code and name.';
		} else {
				if ($id > 0) {
						$stmt = $con->prepare("UPDATE leave_types SET code=?, name=?, yearly_quota=?, monthly_limit=?, color_hex=?, unused_action=?, applicability='', updated_at=NOW() WHERE id=?");
						$stmt->bind_param("ssisssi", $code, $name, $yearly_quota, $monthly_limit, $color_hex, $unused_action, $id);
						if ($stmt->execute()) {
								$successMsg = 'Leave type updated successfully.';
						} else {
								$errorMsg = 'Failed to update leave type.';
						}
				} else {
						$stmt = $con->prepare("INSERT INTO leave_types (code, name, yearly_quota, monthly_limit, color_hex, unused_action, applicability) VALUES (?,?,?,?,?,?, '')");
						$stmt->bind_param("ssisss", $code, $name, $yearly_quota, $monthly_limit, $color_hex, $unused_action);
						if ($stmt->execute()) {
								$successMsg = 'Leave type added successfully.';
						} else {
								$errorMsg = 'Failed to add leave type. Maybe code already exists.';
						}
				}
		}
}

// Toggle active
if (isset($_GET['toggle'])) {
		$id = (int)$_GET['toggle'];
		$con->query("UPDATE leave_types SET is_active = 1 - is_active WHERE id = " . $id);
		header('Location: leave_settings.php');
		exit;
}

// Delete
if (isset($_GET['delete'])) {
		$id = (int)$_GET['delete'];
		$con->query("DELETE FROM leave_types WHERE id = " . $id);
		header('Location: leave_settings.php');
		exit;
}

// Edit row
$editRow = null;
if (isset($_GET['edit'])) {
		$id = (int)$_GET['edit'];
		$resE = $con->query("SELECT * FROM leave_types WHERE id = " . $id . " LIMIT 1");
		$editRow = $resE ? $resE->fetch_assoc() : null;
}

// List
$list = $con->query("SELECT * FROM leave_types ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Leave Settings</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
		body {
			background: #f3f5fb;
			font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
		}
		.page-wrapper { max-width: 1100px; }
		.section-title { font-size: 1.8rem; font-weight: 700; letter-spacing: 0.02em; }
		.settings-card { border-radius: 16px; border: 1px solid #e3e3e3; box-shadow: 0 6px 18px rgba(15,23,42,0.06); }
		.color-dot { width: 18px; height: 18px; border-radius: 999px; border:1px solid #e5e7eb; display:inline-block; }
		.actions-cell {
			display: flex;
			flex-wrap: wrap;
			justify-content: center;
			gap: 0.25rem;
		}
	</style>
</head>
<body>

<div class="container py-4 d-flex justify-content-center">
	<div class="page-wrapper w-100">

		<div class="d-flex justify-content-between align-items-center mb-3">
			<h1 class="section-title mb-0">Leave Settings</h1>
			<a href="settings.php" class="btn btn-outline-secondary">← Back to Settings</a>
		</div>

		<?php if ($successMsg): ?>
			<div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
		<?php endif; ?>
		<?php if ($errorMsg): ?>
			<div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div>
		<?php endif; ?>

		<div class="row g-3">
			<div class="col-md-4">
				<div class="card settings-card h-100">
					<div class="card-body">
						<h5 class="card-title mb-3"><?php echo $editRow ? 'Edit Leave Type' : 'Add Leave Type'; ?></h5>
						<form method="POST">
							<input type="hidden" name="id" value="<?php echo $editRow['id'] ?? 0; ?>">

							<div class="mb-2">
								<label class="form-label small">Code</label>
								<input type="text" name="code" class="form-control form-control-sm" placeholder="CL, PL, SL" required
											 value="<?php echo htmlspecialchars($editRow['code'] ?? ''); ?>">
							</div>

							<div class="mb-2">
								<label class="form-label small">Name</label>
								<input type="text" name="name" class="form-control form-control-sm" placeholder="Casual Leave" required
											 value="<?php echo htmlspecialchars($editRow['name'] ?? ''); ?>">
							</div>

							<div class="mb-2">
								<label class="form-label small">Yearly Quota (days)</label>
								<input type="number" name="yearly_quota" class="form-control form-control-sm" min="0"
											 value="<?php echo htmlspecialchars($editRow['yearly_quota'] ?? 0); ?>">
							</div>

							<div class="mb-2">
								<label class="form-label small">Monthly Limit (optional)</label>
								<input type="number" name="monthly_limit" class="form-control form-control-sm" min="0"
											 value="<?php echo isset($editRow['monthly_limit']) ? htmlspecialchars($editRow['monthly_limit']) : ''; ?>">
								<div class="form-text small">Leave days allowed per month (leave blank for no limit).</div>
							</div>

							<div class="mb-2">
								<label class="form-label small">Color</label>
								<input type="color" name="color_hex" class="form-control form-control-sm form-control-color"
											 value="<?php echo htmlspecialchars($editRow['color_hex'] ?? '#111827'); ?>">
							</div>

							<div class="mb-2">
								<label class="form-label small">Unused Leave Action</label>
								<select name="unused_action" class="form-select form-select-sm">
									<?php
									$ua = $editRow['unused_action'] ?? 'lapse';
									$options = [
										'carry_forward' => 'Carry Forward',
										'encash'        => 'Encash',
										'lapse'         => 'Lapsed',
									];
									foreach ($options as $val => $label) {
											$sel = ($ua === $val) ? 'selected' : '';
											echo '<option value="'.$val.'" '.$sel.'>'.$label.'</option>';
									}
									?>
								</select>
							</div>

							<button type="submit" class="btn btn-sm btn-dark w-100">
								<?php echo $editRow ? 'Update Leave Type' : 'Save Leave Type'; ?>
							</button>
						</form>
					</div>
				</div>
			</div>

			<div class="col-md-8">
				<div class="card settings-card h-100">
					<div class="card-body">
						<h5 class="card-title mb-3">Existing Leave Types</h5>
						<div class="table-responsive">
							<table class="table table-sm align-middle mb-0">
								<thead>
									<tr>
											<th class="text-center" style="width:40px;">#</th>
											<th class="text-start">Code</th>
											<th class="text-start">Name</th>
											<th class="text-center">Yearly</th>
											<th class="text-center">Monthly</th>
											<th class="text-center" style="width:70px;">Color</th>
											<th class="text-center">Unused Action</th>
											<th class="text-center" style="width:80px;">Status</th>
											<th class="text-center" style="width:180px;">Actions</th>
									</tr>
								</thead>
								<tbody>
								<?php if ($list && $list->num_rows > 0): ?>
									<?php $i = 1; while ($row = $list->fetch_assoc()): ?>
										<tr>
											<td class="text-center"><?php echo $i++; ?></td>
											<td class="text-start"><?php echo htmlspecialchars($row['code']); ?></td>
											<td class="text-start"><?php echo htmlspecialchars($row['name']); ?></td>
											<td class="text-center"><?php echo (int)$row['yearly_quota']; ?></td>
											<td class="text-center"><?php echo $row['monthly_limit'] !== null ? (int)$row['monthly_limit'] : '-'; ?></td>
											<td class="text-center">
												<span class="color-dot" style="background: <?php echo htmlspecialchars($row['color_hex'] ?: '#111827'); ?>;"></span>
											</td>
											<td class="text-center">
												<?php
													$labelMap = [
														'carry_forward' => 'Carry Forward',
														'encash'        => 'Encash',
														'lapse'         => 'Lapsed',
													];
													echo htmlspecialchars($labelMap[$row['unused_action']] ?? 'Lapsed');
												?>
											</td>
											<td class="text-center">
												<?php if ((int)$row['is_active'] === 1): ?>
													<span class="badge bg-success">Active</span>
												<?php else: ?>
													<span class="badge bg-secondary">Inactive</span>
												<?php endif; ?>
											</td>
											<td class="text-center actions-cell">
												<a href="leave_settings.php?edit=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
												<a href="leave_assign.php?leave_id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-secondary">Assign</a>
												<a href="leave_settings.php?toggle=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-warning">Toggle</a>
												<a href="leave_settings.php?delete=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-danger"
													onclick="return confirm('Delete this leave type?');">Delete</a>
											</td>
										</tr>
									<?php endwhile; ?>
								<?php else: ?>
									<tr>
										<td colspan="10" class="text-muted">No leave types defined yet.</td>
									</tr>
								<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>

	</div>
</div>

</body>
</html>
