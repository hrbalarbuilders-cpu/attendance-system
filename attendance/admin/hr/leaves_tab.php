<?php
// Employee Leaves Tab - List and process leave requests for employees
include '../config/db.php';

$isAjax = isset($_GET['ajax']) && $_GET['ajax'];

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 10;
if ($per_page > 100)
    $per_page = 100;
$offset = ($page - 1) * $per_page;

// Total count
$countSql = "SELECT COUNT(*) AS c
    FROM leave_applications la
    JOIN employees e ON la.user_id = e.user_id
    JOIN leave_types lt ON la.leave_type_id = lt.id";
$totalCount = 0;
$countRes = $con->query($countSql);
if ($countRes && $countRes->num_rows) {
    $r = $countRes->fetch_assoc();
    $totalCount = (int) ($r['c'] ?? 0);
}

// Fetch paginated leave requests
$sql = "SELECT la.id, la.user_id, e.name AS employee_name, lt.name AS leave_type, la.from_date, la.to_date, la.reason, la.status, la.created_at
        FROM leave_applications la
        JOIN employees e ON la.user_id = e.user_id
        JOIN leave_types lt ON la.leave_type_id = lt.id
        ORDER BY la.created_at DESC
        LIMIT " . (int) $offset . "," . (int) $per_page;
$result = $con->query($sql);

// Preload dropdown data for Apply Leave modal
// Preload dropdown data for Apply Leave modal
$employeesList = [];
$leaveTypesList = [];

if (!$isAjax) {
    // ONLY FETCH FULL LISTS FOR MODAL IF NOT AJAX (Full Page Load)
    $empRes = $con->query("SELECT user_id, emp_code, name FROM employees ORDER BY name ASC");
    if ($empRes && $empRes->num_rows) {
        while ($e = $empRes->fetch_assoc()) {
            $employeesList[] = $e;
        }
    }

    $ltRes = $con->query("SELECT id, code, name FROM leave_types ORDER BY name ASC");
    if ($ltRes && $ltRes->num_rows) {
        while ($lt = $ltRes->fetch_assoc()) {
            $leaveTypesList[] = $lt;
        }
    }
}


function renderLeavesTable($result, $totalCount, $page, $per_page, $offset, $employeesList, $leaveTypesList)
{
    $totalPages = max(1, (int) ceil(($totalCount ?: 0) / max(1, $per_page)));
    $startRec = $totalCount > 0 ? ($offset + 1) : 0;
    $endRec = $totalCount > 0 ? ($offset + ($result ? $result->num_rows : 0)) : 0;
    ?>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h1 class="section-title mb-0">Employee Leave Requests</h1>
            <button type="button" class="btn btn-dark" id="openApplyLeaveModal">Apply Leave</button>
        </div>

        <div class="card card-main">
            <div class="card-header card-main-header d-flex justify-content-between align-items-center gap-2">
                <span class="fw-semibold">Leave Requests</span>
                <small class="text-muted">Total: <?php echo (int) $totalCount; ?></small>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-nowrap">
                                <th>#</th>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Applied On</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows): ?>
                                <?php
                                $i = $totalCount > 0 ? ($offset + 1) : 1;
                                while ($row = $result->fetch_assoc()):
                                    $status = strtolower((string) ($row['status'] ?? ''));
                                    $badgeClass = 'secondary';
                                    if ($status === 'pending')
                                        $badgeClass = 'warning';
                                    else if ($status === 'approved')
                                        $badgeClass = 'success';
                                    else if ($status === 'rejected')
                                        $badgeClass = 'danger';
                                    $createdAt = !empty($row['created_at']) ? date('d M Y, h:i A', strtotime($row['created_at'])) : '-';
                                    ?>
                                    <tr>
                                        <td class="text-nowrap"><?php echo (int) $i++; ?></td>
                                        <td class="text-nowrap"><?php echo htmlspecialchars($row['employee_name']); ?></td>
                                        <td class="text-nowrap"><?php echo htmlspecialchars($row['leave_type']); ?></td>
                                        <td class="text-nowrap"><?php echo htmlspecialchars($row['from_date']); ?></td>
                                        <td class="text-nowrap"><?php echo htmlspecialchars($row['to_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                        <td class="text-nowrap">
                                            <span
                                                class="badge bg-<?php echo $badgeClass; ?><?php echo $badgeClass === 'warning' ? ' text-dark' : ''; ?>">
                                                <?php echo htmlspecialchars(ucfirst($status ?: 'pending')); ?>
                                            </span>
                                        </td>
                                        <td class="text-nowrap"><?php echo htmlspecialchars($createdAt); ?></td>
                                        <td class="text-end text-nowrap">
                                            <?php if (($row['status'] ?? '') === 'pending'): ?>
                                                <div class="d-inline-flex gap-2">
                                                    <form method="post" action="process_leave.php" class="m-0">
                                                        <input type="hidden" name="leave_id" value="<?php echo (int) $row['id']; ?>">
                                                        <button type="submit" name="action" value="approve"
                                                            class="btn btn-success btn-sm">Approve</button>
                                                    </form>
                                                    <form method="post" action="process_leave.php" class="m-0">
                                                        <input type="hidden" name="leave_id" value="<?php echo (int) $row['id']; ?>">
                                                        <button type="submit" name="action" value="reject"
                                                            class="btn btn-danger btn-sm">Reject</button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No leave requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div id="leavesPagingMeta" data-page="<?php echo (int) $page; ?>"
                    data-per-page="<?php echo (int) $per_page; ?>" style="display:none;"></div>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-3 px-2">
                        <ul class="pagination mb-0">
                            <?php
                            $start = max(1, $page - 3);
                            $end = min($totalPages, $page + 3);
                            if ($page > 1)
                                echo '<li class="page-item"><a href="#" class="page-link leaves-page-link" data-page="' . ($page - 1) . '">Previous</a></li>';
                            if ($start > 1)
                                echo '<li class="page-item"><a href="#" class="page-link leaves-page-link" data-page="1">1</a></li>' . ($start > 2 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : '');
                            for ($p = $start; $p <= $end; $p++) {
                                $cls = $p == $page ? ' page-item active' : ' page-item';
                                echo '<li class="' . $cls . '"><a href="#" class="page-link leaves-page-link" data-page="' . $p . '">' . $p . '</a></li>';
                            }
                            if ($end < $totalPages)
                                echo ($end < $totalPages - 1 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : '') . '<li class="page-item"><a href="#" class="page-link leaves-page-link" data-page="' . $totalPages . '">' . $totalPages . '</a></li>';
                            if ($page < $totalPages)
                                echo '<li class="page-item"><a href="#" class="page-link leaves-page-link" data-page="' . ($page + 1) . '">Next</a></li>';
                            ?>
                        </ul>
                    </nav>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mt-2 px-2 pb-2">
                    <div class="small text-muted">Record <?php echo (int) $startRec; ?>–<?php echo (int) $endRec; ?> of
                        <?php echo (int) $totalCount; ?>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label class="small text-muted mb-0">Rows:</label>
                        <select id="leavesPerPageFooter" class="form-select form-select-sm" style="width:80px;">
                            <option value="10" <?php if ($per_page == 10)
                                echo 'selected'; ?>>10</option>
                            <option value="25" <?php if ($per_page == 25)
                                echo 'selected'; ?>>25</option>
                            <option value="50" <?php if ($per_page == 50)
                                echo 'selected'; ?>>50</option>
                            <option value="100" <?php if ($per_page == 100)
                                echo 'selected'; ?>>100</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Apply Leave Modal -->
    <div class="modal fade" id="applyLeaveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Apply Leave</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="applyLeaveForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Employee</label>
                                <select name="employee_id" class="form-select" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employeesList as $e): ?>
                                        <option value="<?php echo (int) $e['user_id']; ?>">
                                            <?php echo htmlspecialchars(trim(($e['emp_code'] ?? '') . ' - ' . ($e['name'] ?? ''))); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Leave Type</label>
                                <select name="leave_type_id" class="form-select" required>
                                    <option value="">Select Leave Type</option>
                                    <?php foreach ($leaveTypesList as $lt): ?>
                                        <option value="<?php echo (int) $lt['id']; ?>">
                                            <?php
                                            $label = ($lt['name'] ?? '');
                                            if (!empty($lt['code'])) {
                                                $label .= ' (' . $lt['code'] . ')';
                                            }
                                            echo htmlspecialchars($label);
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">From Date</label>
                                <input type="date" name="from_date" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">To Date</label>
                                <input type="date" name="to_date" class="form-control" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Reason</label>
                                <textarea name="reason" class="form-control" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark" id="applyLeaveSubmitBtn">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
}

if ($isAjax) {
    renderLeavesTable($result, $totalCount, $page, $per_page, $offset, $employeesList, $leaveTypesList);
    $con->close();
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Employee Leaves</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <?php renderLeavesTable($result, $totalCount, $page, $per_page, $offset, $employeesList, $leaveTypesList); ?>
</body>

</html>
<?php $con->close(); ?>