<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database host - Alwaysdata
$host = "localhost";

// Alwaysdata database username
$user = "root";

// Alwaysdata database password
$pass = "";

// Alwaysdata database name
$db = "attendance_db";

$con = new mysqli($host, $user, $pass, $db);

// Set charset to UTF-8
$con->set_charset("utf8mb4");

if ($con->connect_error) {
    die("DB Error: " . $con->connect_error);
}
