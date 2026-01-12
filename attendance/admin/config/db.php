<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "attendance_db"; 

$con = new mysqli($host, $user, $pass, $db);

if ($con->connect_error) {
    die("DB Error: " . $con->connect_error);
}
