<?php
// archive_employee.php
// CRITICAL: Authentication required for security
include '../includes/auth_check.php';
include '../config/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: employees.php?error=1&message=Invalid+employee+ID');
    exit;
}

// Set status to 0 (archived/inactive)
$stmt = $con->prepare("UPDATE employees SET status = 0 WHERE user_id = ?");
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    header('Location: employees.php?success=1&message=Employee+archived+successfully');
    exit;
} else {
    error_log("Failed to archive employee $id: " . $stmt->error);
    header('Location: employees.php?error=1&message=Failed+to+archive+employee');
    exit;
}
?>