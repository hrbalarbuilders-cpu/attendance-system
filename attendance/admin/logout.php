<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
echo "<script>
    localStorage.removeItem('admin_logged_in');
    localStorage.setItem('admin_logged_out', Date.now());
    window.location.href = 'login.php';
</script>";
exit;
?>