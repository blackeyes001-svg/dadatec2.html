<?php
require_once 'config.php';
require_login('admin');

$totalStudents = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$totalCourses  = (int)$pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();
$totalResults  = (int)$pdo->query('SELECT COUNT(*) FROM results')->fetchColumn();

$recent = $pdo->query(
    'SELECT u.full_name, s.matric_no, c.course_code, r.score, r.grade
     FROM results r
     JOIN students s ON s.id = r.student_id
     JOIN users u ON u.id = s.user_id
     JOIN courses c ON c.id = r.course_id
     ORDER BY r.id DESC LIMIT 5'
)->fetchAll();

$pageTitle = 'Admin Dashboard';
require 'header.php';
?>

<h1>Admin Dashboard</h1>

<div class="stats">
    <div class="stat"><h2><?= $totalStudents ?></h2><p>Students</p></div>
    <div class="stat"><h2><?= $totalCourses ?></h2><p>Courses</p></div>
    <div class="stat"><h2><?= $totalResults ?></h2><p>Results Recorded</p></div>
</div>

<div class="actions">
    <a class="btn" href="student_form.php">+ Add Student</a>
    <a class="btn" href="course_form.php">+ Add Course</a>
    <a class="btn" href="result_form.php">+ Add Result</a>
</div>

<h2>Recent Results</h2>
<div class="table-wrap">
<table>
    <thead>
        <tr><th>Student</th><th>Matric No</th><th>Course</th><th>Score</th><th>Grade</th></tr>
    </thead>
    <tbody>
    <?php if (!$recent): ?>
        <tr><td colspan="5" class="empty">No results yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($recent as $row): ?>
        <tr>
            <td><?= e($row['full_name']) ?></td>
            <td><?= e($row['matric_no']) ?></td>
            <td><?= e($row['course_code']) ?></td>
            <td><?= e($row['score']) ?></td>
            <td><?= e($row['grade']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require 'footer.php'; ?>
