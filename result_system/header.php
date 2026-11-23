<?php
// Include config.php BEFORE this file in every page.
$pageTitle = $pageTitle ?? 'Result Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Result System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <a href="index.php" class="logo">Result<span>System</span></a>
    <nav>
        <?php if (is_logged_in()): ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="students.php">Students</a>
                <a href="courses.php">Courses</a>
                <a href="results.php">Results</a>
            <?php else: ?>
                <a href="student_dashboard.php">My Results</a>
            <?php endif; ?>
            <a href="logout.php" class="logout">Logout (<?= e($_SESSION['full_name']) ?>)</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </nav>
</header>

<main class="container">
<?= show_flash() ?>
