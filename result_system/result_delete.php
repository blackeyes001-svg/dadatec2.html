<?php
require_once 'config.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: results.php');
    exit;
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare('DELETE FROM results WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    set_flash('Result deleted.');
} else {
    set_flash('Result not found.', 'error');
}

header('Location: results.php');
exit;
