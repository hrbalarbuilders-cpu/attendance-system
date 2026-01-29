<?php
// CRITICAL: Authentication required for security
include '../includes/auth_check.php';
include '../config/db.php';

// Clean output buffer for proper JSON response
if (ob_get_level())
    ob_clean();
header('Content-Type: application/json');

if (!isset($_POST['id'], $_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$id = intval($_POST['id']);
$status = intval($_POST['status']) === 1 ? 1 : 0;

$stmt = $con->prepare("UPDATE employees SET status = ?, updated_at = NOW() WHERE user_id = ?");
$stmt->bind_param("ii", $status, $id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => $status ? 'Account activated successfully.' : 'Account deactivated successfully.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
