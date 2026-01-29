<?php
include 'includes/auth_check.php';

// If we reach here, user is logged in
header("Location: dashboard/index.php");
exit;
