<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "attendance_db"; 

$con = new mysqli($host, $user, $pass, $db);

if ($con->connect_error) {
    die("DB Error: " . $con->connect_error);
}
?>
