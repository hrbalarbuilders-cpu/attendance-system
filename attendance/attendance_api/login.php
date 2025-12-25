<?php
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');

include "db.php";

// Debug logging removed in cleanup


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status" => "error",
        "msg" => "Invalid request method"
    ]);
    exit;
}


$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// Default password is 123456
$defaultPassword = '123456';

if (empty($email) || empty($password)) {
    echo json_encode([
        "status" => "error",
        "msg" => "Email and password are required"
    ]);
    exit;
}

// Check if employee exists with this email
$stmt = $con->prepare("
    SELECT id, emp_code, name, email, status, shift_id
    FROM employees 
    WHERE email = ? AND status = 1
    LIMIT 1
");

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "msg" => "Database error: " . $con->error
    ]);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $employee = $result->fetch_assoc();
    
    // Double-check: Ensure employee status is active (1)
    if ((int)$employee['status'] !== 1) {
        echo json_encode([
            "status" => "error",
            "msg" => "Account is inactive. Please contact administrator."
        ]);
        $stmt->close();
        exit;
    }
    
    // Check password (default password is 123456)
    if ($password === $defaultPassword) {
        // Get numeric user_id from emp_code (same logic as clock.php)
        $user_id = (int) filter_var($employee['emp_code'], FILTER_SANITIZE_NUMBER_INT);
        if ($user_id <= 0) {
            $user_id = (int)$employee['id'];
        }
        
        echo json_encode([
            "status" => "success",
            "user_id" => $user_id,
            "employee_id" => (int)$employee['id'],
            "emp_code" => $employee['emp_code'],
            "name" => $employee['name'],
            "email" => $employee['email'],
            "shift_id" => $employee['shift_id']
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "msg" => "Invalid password"
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "msg" => "Invalid email or account is inactive"
    ]);
}

$stmt->close();
?>

