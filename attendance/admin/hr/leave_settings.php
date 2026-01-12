<?php
// leave_settings.php
date_default_timezone_set('Asia/Kolkata');
include '../config/db.php';

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

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

// --- AJAX mode: output only the content, no HTML shell ---
if ($isAjax) {
?>
<style>
	.color-dot { width: 18px; height: 18px; border-radius: 999px; border:1px solid #e5e7eb; display:inline-block; }
	.actions-cell { display: flex; flex-wrap: wrap; justify-content: center; gap: 0.25rem; }
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
	<div>
		<h3 class="mb-0">Leave Types</h3>
		<div class="text-muted small">Create and manage leave types</div>
	</div>
	<button type="button" class="btn btn-dark btn-sm" id="openLeaveTypeModal">+ Add Leave Type</button>
</div>
<?php if ($successMsg): ?>
	<div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
	<div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div>
<?php endif; ?>
<div class="card settings-card shadow-sm border-0" style="border-radius:18px;">
	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-hover table-borderless align-middle mb-0 w-100" style="font-size: 0.97rem;">
				<thead class="table-light" style="font-size:0.93em;">
					<tr>
						<th class="text-center px-2" style="width:32px;">#</th>
						<th class="text-start px-2">Code</th>
						<th class="text-start px-2">Name</th>
						<th class="text-center px-2">Yearly</th>
						<th class="text-center px-2">Monthly</th>
						<th class="text-center px-2">Color</th>
						<th class="text-center px-2">Unused</th>
						<th class="text-center px-2">Status</th>
						<th class="text-center px-2">Actions</th>
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
							<td class="text-center"><span class="color-dot" style="background: <?php echo htmlspecialchars($row['color_hex'] ?: '#111827'); ?>;"></span></td>
							<td class="text-center"><?php
								$labelMap = ['carry_forward' => 'Carry Forward', 'encash' => 'Encash', 'lapse' => 'Lapsed'];
								echo htmlspecialchars($labelMap[$row['unused_action']] ?? 'Lapsed');
							?></td>
							<td class="text-center">
								<?php if ((int)$row['is_active'] === 1): ?>
									<span class="badge bg-success">Active</span>
								<?php else: ?>
									<span class="badge bg-secondary">Inactive</span>
								<?php endif; ?>
							</td>
							<td class="text-center actions-cell">
								<button type="button" class="btn btn-sm btn-outline-primary edit-leave-type-btn"
									data-id="<?php echo (int)$row['id']; ?>"
									data-code="<?php echo htmlspecialchars($row['code']); ?>"
									data-name="<?php echo htmlspecialchars($row['name']); ?>"
									data-yearly="<?php echo (int)$row['yearly_quota']; ?>"
									data-monthly="<?php echo $row['monthly_limit'] !== null ? (int)$row['monthly_limit'] : ''; ?>"
									data-color="<?php echo htmlspecialchars($row['color_hex'] ?: '#111827'); ?>"
									data-unused="<?php echo htmlspecialchars($row['unused_action'] ?? 'lapse'); ?>"
								>Edit</button>
								<a href="leave_assign.php?leave_id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-secondary">Assign</a>
								<button type="button" class="btn btn-sm btn-outline-warning leave-type-toggle" data-id="<?php echo (int)$row['id']; ?>">Toggle</button>
								<button type="button" class="btn btn-sm btn-outline-danger leave-type-delete" data-id="<?php echo (int)$row['id']; ?>">Delete</button>
							</td>
						</tr>
					<?php endwhile; ?>
				<?php else: ?>
					<tr><td colspan="9" class="text-muted text-center py-3">No leave types defined yet.</td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php include '../includes/modal-leave-type.php'; ?>
<?php
	exit;
}
// --- END AJAX mode ---
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
			   <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#leaveTypeModal">
				   <?php echo $editRow ? 'Edit Leave Type' : 'Add Leave Type'; ?>
			   </button>
		   </div>

		<?php if ($successMsg): ?>
			<div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
		<?php endif; ?>
		<?php if ($errorMsg): ?>
			<div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div>
		<?php endif; ?>

		<div class="row g-3">
			<div class="col-12">
				<div class="card settings-card h-100 w-100 shadow-sm border-0" style="width:100%; border-radius:18px;">
					<div class="card-body" style="width:100%;">
						<h5 class="card-title mb-3">Existing Leave Types</h5>
						<div class="table-responsive" style="width:100%;">
							<table class="table table-hover table-borderless align-middle mb-0 w-100" style="font-size: 0.97rem; border-radius:14px; overflow:hidden; background:#fff; width: 100%;">
							   <thead class="table-light" style="font-size:0.93em;">
								   <tr style="border-top:none;">
									   <th class="text-center px-2" style="width:32px;">#</th>
									   <th class="text-start px-2" style="width:60px;">Code</th>
									   <th class="text-start px-2" style="min-width:120px;">Name</th>
									   <th class="text-center px-2" style="width:60px;">Yearly</th>
									   <th class="text-center px-2" style="width:60px;">Monthly</th>
									   <th class="text-center px-2" style="width:50px;">Color</th>
									   <th class="text-center px-2" style="width:90px;">Unused</th>
									   <th class="text-center px-2" style="width:60px;">Status</th>
									   <th class="text-center px-2" style="width:140px;">Actions</th>
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
														<button type="button" class="btn btn-sm btn-outline-primary edit-leave-type-btn"
															data-id="<?php echo (int)$row['id']; ?>"
															data-code="<?php echo htmlspecialchars($row['code']); ?>"
															data-name="<?php echo htmlspecialchars($row['name']); ?>"
															data-yearly="<?php echo (int)$row['yearly_quota']; ?>"
															data-monthly="<?php echo $row['monthly_limit'] !== null ? (int)$row['monthly_limit'] : ''; ?>"
															data-color="<?php echo htmlspecialchars($row['color_hex'] ?: '#111827'); ?>"
															data-unused="<?php echo htmlspecialchars($row['unused_action'] ?? 'lapse'); ?>"
														>Edit</button>
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

<?php include '../includes/modal-leave-type.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var modal = document.getElementById('leaveTypeModal');
  var bsModal = new bootstrap.Modal(modal);

  // Edit button click: populate modal and open
  document.querySelectorAll('.edit-leave-type-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      modal.querySelector('[name="id"]').value = btn.dataset.id;
      modal.querySelector('[name="code"]').value = btn.dataset.code;
      modal.querySelector('[name="name"]').value = btn.dataset.name;
      modal.querySelector('[name="yearly_quota"]').value = btn.dataset.yearly;
      modal.querySelector('[name="monthly_limit"]').value = btn.dataset.monthly;
      modal.querySelector('[name="color_hex"]').value = btn.dataset.color;
      modal.querySelector('[name="unused_action"]').value = btn.dataset.unused;
      modal.querySelector('.modal-title').textContent = 'Edit Leave Type';
      modal.querySelector('button[type="submit"]').textContent = 'Update Leave Type';
      bsModal.show();
    });
  });

  // Reset modal on close (for Add mode)
  modal.addEventListener('hidden.bs.modal', function() {
    modal.querySelector('[name="id"]').value = '0';
    modal.querySelector('[name="code"]').value = '';
    modal.querySelector('[name="name"]').value = '';
    modal.querySelector('[name="yearly_quota"]').value = '0';
    modal.querySelector('[name="monthly_limit"]').value = '';
    modal.querySelector('[name="color_hex"]').value = '#111827';
    modal.querySelector('[name="unused_action"]').value = 'lapse';
    modal.querySelector('.modal-title').textContent = 'Add Leave Type';
    modal.querySelector('button[type="submit"]').textContent = 'Add Leave Type';
  });
});
</script>

</body>
</html>
