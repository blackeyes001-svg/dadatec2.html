<?php
// Run this ONCE in the browser to create the first admin account.
// Then DELETE this file.
require_once 'config.php';

$count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

if ($count === 0) {
    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)');
    $stmt->execute(['System Admin', 'admin@school.com', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
    $message = 'Admin created. Email: admin@school.com | Password: admin123';
} else {
    $message = 'An admin account already exists. Nothing was changed.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container narrow">
    <div class="card">
        <h1>Setup</h1>
        <p><?= e($message) ?></p>
        <p><strong>Now delete setup.php</strong> from the project folder, then <a href="admin_login.php">go to admin login</a>.</p>
    </div>
</main>
</body>
</html>
