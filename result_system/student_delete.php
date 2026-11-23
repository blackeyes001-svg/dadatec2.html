<?php
require_once 'config.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: students.php');
    exit;
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);

// Find the login account of this student, then delete it.
// ON DELETE CASCADE removes the student row and their results automatically.
$stmt = $pdo->prepare('SELECT user_id FROM students WHERE id = ?');
$stmt->execute([$id]);
$userId = $stmt->fetchColumn();

if ($userId) {
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
    set_flash('Student deleted.');
} else {
    set_flash('Student not found.', 'error');
}

header('Location: students.php');
exit;
