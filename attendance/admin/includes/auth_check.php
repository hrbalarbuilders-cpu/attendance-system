<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Compute web base path for admin folder
$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$adminPos = strpos($script, '/admin');
if ($adminPos !== false) {
    $base = substr($script, 0, $adminPos + strlen('/admin'));
} else {
    $base = rtrim(dirname($script), '/');
}

if (!isset($_SESSION['emp_id']) || $_SESSION['emp_id'] <= 0) {
    header("Location: " . $base . "/login.php");
    exit;
}
?>