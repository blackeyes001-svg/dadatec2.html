<?php
session_start();

// Remember which login page to return to
$role = $_SESSION['role'] ?? 'admin';

$_SESSION = [];
session_destroy();

header('Location: ' . ($role === 'student' ? 'student_login.php' : 'admin_login.php'));
exit;
