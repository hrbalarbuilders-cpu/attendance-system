<?php
// CRITICAL: Authentication required for security
include '../includes/auth_check.php';
include '../config/db.php';

if (!isset($_GET['id'])) {
    header("Location: employees.php");
    exit;
}

$id = intval($_GET['id']);

$stmt = $con->prepare("DELETE FROM employees WHERE user_id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: employees.php?deleted=1&message=Employee+deleted+successfully");
} else {
    error_log("Failed to delete employee $id: " . $stmt->error);
    header("Location: employees.php?error=1&message=Failed+to+delete+employee");
}
exit;
