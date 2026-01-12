<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['emp_id']) && $_SESSION['emp_id'] > 0) {
    header("Location: dashboard/index.php");
    exit;
}

// Otherwise, redirect to login
header("Location: login.php");
exit;
