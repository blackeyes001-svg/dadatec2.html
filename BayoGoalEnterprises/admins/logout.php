<?php
session_start();

// Clear administrator session
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);

// Regenerate session ID
session_regenerate_id(true);

// Return to admin login
header("Location: login.php");
exit;
?>s