<?php
require_once 'config.php';
require_login('admin');

$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    $stmt = $pdo->prepare(
        'SELECT s.id, s.matric_no, s.department, s.level, u.full_name, u.email
         FROM students s JOIN users u ON u.id = s.user_id
         WHERE u.full_name LIKE ? OR s.matric_no LIKE ?
         ORDER BY u.full_name'
    );
    $stmt->execute(['%' . $q . '%', '%' . $q . '%']);
} else {
    $stmt = $pdo->query(
        'SELECT s.id, s.matric_no, s.department, s.level, u.full_name, u.email
         FROM students s JOIN users u ON u.id = s.user_id
         ORDER BY u.full_name'
    );
}
$students = $stmt->fetchAll();

$pageTitle = 'Students';
require 'header.php';
?>

<div class="page-head">
    <h1>Students</h1>
    <a class="btn" href="student_form.php">+ Add Student</a>
</div>

<form method="GET" action="students.php" class="search-row">
    <input type="text" name="q" placeholder="Search by name or matric no" value="<?= e($q) ?>">
    <button type="submit" class="btn">Search</button>
</form>

<div class="table-wrap">
<table>
    <thead>
        <tr><th>Name</th><th>Matric No</th><th>Email</th><th>Department</th><th>Level</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if (!$students): ?>
        <tr><td colspan="6" class="empty">No students found.</td></tr>
    <?php endif; ?>
    <?php foreach ($students as $s): ?>
        <tr>
            <td><?= e($s['full_name']) ?></td>
            <td><?= e($s['matric_no']) ?></td>
            <td><?= e($s['email']) ?></td>
            <td><?= e($s['department']) ?></td>
            <td><?= e($s['level']) ?></td>
            <td class="row-actions">
                <a class="btn small" href="student_form.php?id=<?= (int)$s['id'] ?>">Edit</a>
                <form method="POST" action="student_delete.php"
                      onsubmit="return confirm('Delete this student and all their results?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <button type="submit" class="btn small danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require 'footer.php'; ?>
