<?php include '../includes/auth_check.php'; ?>
// Prevent direct access to the HR folder
header("Location: employees.php");
exit;
?>