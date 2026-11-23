<?php
require_once 'config.php';
require_login('admin');

$courses = $pdo->query('SELECT id, course_code, course_title, unit FROM courses ORDER BY course_code')->fetchAll();

$pageTitle = 'Courses';
require 'header.php';
?>

<div class="page-head">
    <h1>Courses</h1>
    <a class="btn" href="course_form.php">+ Add Course</a>
</div>

<div class="table-wrap">
<table>
    <thead>
        <tr><th>Code</th><th>Title</th><th>Unit</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if (!$courses): ?>
        <tr><td colspan="4" class="empty">No courses yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($courses as $c): ?>
        <tr>
            <td><?= e($c['course_code']) ?></td>
            <td><?= e($c['course_title']) ?></td>
            <td><?= e($c['unit']) ?></td>
            <td class="row-actions">
                <a class="btn small" href="course_form.php?id=<?= (int)$c['id'] ?>">Edit</a>
                <form method="POST" action="course_delete.php"
                      onsubmit="return confirm('Delete this course and all results recorded for it?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                    <button type="submit" class="btn small danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require 'footer.php'; ?>
