<?php
require_once 'config.php';

// Logged in: go straight to the right dashboard
if (is_logged_in()) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php'));
    exit;
}

$pageTitle = 'Welcome';
require 'header.php';
?>

<div class="card narrow" style="text-align:center;">
    <h1>Student Result Management System</h1>
    <p class="muted">Choose how you want to sign in.</p>

    <div class="actions" style="justify-content:center;">
        <a class="btn" href="admin_login.php">Admin Login</a>
        <a class="btn secondary" href="student_login.php">Student Login</a>
        <a class="btn secondary" href="check_student.php">Check Student</a>
    </div>
</div>

<?php require 'footer.php'; ?>
