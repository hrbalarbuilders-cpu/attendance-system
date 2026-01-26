<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database host - Alwaysdata
$host = "mysql-demoserver.alwaysdata.net";

// Alwaysdata database username
$user = "demoserver";

// Alwaysdata database password
$pass = "Manyooooh199";

// Alwaysdata database name
$db = "demoserver_attendance";

$con = new mysqli($host, $user, $pass, $db);

// Set charset to UTF-8
$con->set_charset("utf8mb4");

if ($con->connect_error) {
    die("DB Error: " . $con->connect_error);
}
