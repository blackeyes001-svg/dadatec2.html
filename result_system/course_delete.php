<?php
require_once 'config.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: courses.php');
    exit;
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare('DELETE FROM courses WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    set_flash('Course deleted.');
} else {
    set_flash('Course not found.', 'error');
}

header('Location: courses.php');
exit;
