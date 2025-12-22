<?php
// Admin page to process leave requests
include 'db.php';

// Fetch all pending leave requests
$sql = "SELECT la.id, la.employee_id, e.name AS employee_name, lt.name AS leave_type, la.from_date, la.to_date, la.reason, la.status, la.created_at
        FROM leave_applications la
        JOIN employees e ON la.employee_id = e.id
        JOIN leave_types lt ON la.leave_type_id = lt.id
        WHERE la.status = 'pending' ORDER BY la.created_at DESC";
$result = $con->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Process Leave Requests</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2>Pending Leave Requests</h2>
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Employee</th>
                <th>Leave Type</th>
                <th>From</th>
                <th>To</th>
                <th>Reason</th>
                <th>Applied On</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['employee_name']) ?></td>
                <td><?= htmlspecialchars($row['leave_type']) ?></td>
                <td><?= htmlspecialchars($row['from_date']) ?></td>
                <td><?= htmlspecialchars($row['to_date']) ?></td>
                <td><?= htmlspecialchars($row['reason']) ?></td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
                <td>
                    <form method="post" action="process_leave.php" style="display:inline-block">
                        <input type="hidden" name="leave_id" value="<?= $row['id'] ?>">
                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                    </form>
                    <form method="post" action="process_leave.php" style="display:inline-block">
                        <input type="hidden" name="leave_id" value="<?= $row['id'] ?>">
                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                    </form>
                    <form method="post" action="delete_leave.php" style="display:inline-block" onsubmit="return confirm('Delete this leave and its attendance logs?');">
                        <input type="hidden" name="leave_id" value="<?= $row['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $con->close(); ?>
