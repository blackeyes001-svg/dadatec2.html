<?php
require_once 'config.php';

if (is_logged_in()) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php'));
} else {
    header('Location: login.php');
}
exit;
