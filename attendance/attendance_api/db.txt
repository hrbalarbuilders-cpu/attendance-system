<?php
// Start output buffering to prevent any accidental output
ob_start();

// ========================================
// DATABASE CONFIGURATION - LOCAL WAMP
// ========================================

// Database host - Alwaysdata
$host = "mysql-demoserver.alwaysdata.net";

// Alwaysdata database username
// ⚠️ CHECK "USERS" TAB IN ALWAYSDATA DASHBOARD FOR THIS
$user = "demoserver";

// Alwaysdata database password
// ⚠️ THE PASSWORD YOU SET FOR THE DB USER
$pass = "Manyooooh199";

// Alwaysdata database name
$db = "demoserver_attendance";

// ========================================
// CREATE DATABASE CONNECTION
// ========================================
$con = new mysqli($host, $user, $pass, $db);

// Set charset to UTF-8 to prevent encoding issues
$con->set_charset("utf8mb4");

// Check connection
if ($con->connect_error) {
    // Return JSON error instead of plain text
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "status" => "error",
        "msg" => "Database connection failed: " . $con->connect_error
    ]);
    exit;
}

// NO CLOSING PHP TAG - This prevents BOM and whitespace issues!
