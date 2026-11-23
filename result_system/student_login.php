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
    $error = attempt_login($pdo, 'student');
}

$pageTitle = 'Student Login';
require 'header.php';
?>

<div class="card narrow">
    <h1>Student Login</h1>
    <p class="muted">Sign in to view your results and GPA.</p>

    <?php if ($error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="student_login.php">
        <?= csrf_field() ?>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit" class="btn">Login</button>
    </form>

    <p class="muted" style="margin-top:16px;"><a href="admin_login.php">Are you an admin? Login here</a></p>
</div>

<?php require 'footer.php'; ?>
