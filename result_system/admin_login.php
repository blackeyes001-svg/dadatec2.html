<?php
require_once 'config.php';

// Already logged in? Go to the right place.
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $error = attempt_login($pdo, 'admin');
}

$pageTitle = 'Admin Login';
require 'header.php';
?>

<div class="card narrow">
    <h1>Admin Login</h1>
    <p class="muted">For school staff who manage students, courses and results.</p>

    <?php if ($error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="admin_login.php">
        <?= csrf_field() ?>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit" class="btn">Login</button>
    </form>

    <p class="muted" style="margin-top:16px;"><a href="student_login.php">Are you a student? Login here</a></p>
</div>

<?php require 'footer.php'; ?>
