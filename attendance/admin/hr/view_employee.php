<?php
// view_employee.php
include '../config/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$employee = null;
if ($id > 0) {
    $sql = "SELECT e.*, d.department_name, desig.designation_name, s.shift_name, s.start_time, s.end_time
            FROM employees e
            LEFT JOIN departments d ON d.id = e.department_id
            LEFT JOIN designations desig ON desig.id = e.designation_id
            LEFT JOIN shifts s ON s.id = e.shift_id
            WHERE e.user_id = $id LIMIT 1";
    $res = $con->query($sql);
    if ($res && $res->num_rows > 0) {
        $employee = $res->fetch_assoc();
    }
}
if (!$employee) {
    echo '<div class="alert alert-danger m-4">Employee not found.</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="background:#f6f8fb;">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1 fw-bold">Employee Profile</h2>
                <div class="text-muted">Detailed employee information</div>
            </div>
            <a href="employees.php" class="btn btn-outline-secondary">&larr; Back to HR</a>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <div class="row align-items-center mb-4">
                            <div class="col-auto">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                    style="width:64px;height:64px;font-size:2rem;">
                                    <?php echo strtoupper(substr($employee['name'] ?? '?', 0, 1)); ?>
                                </div>
                            </div>
                            <div class="col ps-0">
                                <h3 class="mb-0 fw-semibold"><?php echo htmlspecialchars($employee['name'] ?? '-'); ?>
                                </h3>
                                <div class="text-muted small">Employee Code:
                                    <?php echo htmlspecialchars($employee['emp_code'] ?? '-'); ?></div>
                            </div>
                            <div class="col-auto">
                                <span
                                    class="badge bg-<?php echo ((int) ($employee['status'] ?? 1) === 1) ? 'success' : 'secondary'; ?> px-3 py-2"
                                    style="font-size:1rem;">
                                    <?php echo ((int) ($employee['status'] ?? 1) === 1) ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <div class="mb-2 text-muted small">Department</div>
                                <div class="fw-semibold">
                                    <?php echo htmlspecialchars($employee['department_name'] ?? '-'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2 text-muted small">Designation</div>
                                <div class="fw-semibold">
                                    <?php echo htmlspecialchars($employee['designation_name'] ?? '-'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2 text-muted small">Mobile</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($employee['mobile'] ?? '-'); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2 text-muted small">Email</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($employee['email'] ?? '-'); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2 text-muted small">Date of Birth</div>
                                <div class="fw-semibold">
                                    <?php echo !empty($employee['dob']) ? date('d M Y', strtotime($employee['dob'])) : '-'; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2 text-muted small">Joining Date</div>
                                <div class="fw-semibold">
                                    <?php echo !empty($employee['joining_date']) ? date('d M Y', strtotime($employee['joining_date'])) : '-'; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2 text-muted small">Shift</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($employee['shift_name'] ?? '-'); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2 text-muted small">Shift Timing</div>
                                <div class="fw-semibold">
                                    <?php
                                    if (!empty($employee['start_time']) && !empty($employee['end_time'])) {
                                        echo date('h:i A', strtotime($employee['start_time'])) . ' – ' . date('h:i A', strtotime($employee['end_time']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2 text-muted small">Working From</div>
                                <div class="fw-semibold">
                                    <?php echo htmlspecialchars($employee['default_working_from'] ?? '-'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2 text-muted small">Week Off Days</div>
                                <div class="fw-semibold">
                                    <?php echo !empty($employee['weekoff_days']) ? htmlspecialchars($employee['weekoff_days']) : '-'; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2 text-muted small">Device ID</div>
                                <div class="fw-semibold">
                                    <?php echo $employee['device_id'] ? htmlspecialchars($employee['device_id']) : 'Not registered'; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2 text-muted small">Status</div>
                                <div class="fw-semibold">
                                    <span
                                        class="badge bg-<?php echo ((int) ($employee['status'] ?? 1) === 1) ? 'success' : 'secondary'; ?> px-3 py-2"
                                        style="font-size:1rem;">
                                        <?php echo ((int) ($employee['status'] ?? 1) === 1) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2 text-muted small">Created At</div>
                                <div class="fw-semibold">
                                    <?php echo !empty($employee['created_at']) ? date('d M Y, h:i A', strtotime($employee['created_at'])) : '-'; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2 text-muted small">Updated At</div>
                                <div class="fw-semibold">
                                    <?php echo !empty($employee['updated_at']) ? date('d M Y, h:i A', strtotime($employee['updated_at'])) : '-'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>