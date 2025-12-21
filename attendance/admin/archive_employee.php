<?php
// archive_employee.php
include 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: employees_list.php?error=invalid');
    exit;
}

// Set status to 0 (archived/inactive)
$stmt = $con->prepare("UPDATE employees SET status = 0 WHERE id = ?");
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    header('Location: employees_list.php?archived=1');
    exit;
} else {
    echo '<div class="alert alert-danger m-4">Failed to archive employee. Please try again.</div>';
}
?>
