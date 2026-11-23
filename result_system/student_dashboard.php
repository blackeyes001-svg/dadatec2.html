<?php
require_once 'config.php';
require_login('student');

// Only this logged-in student's own record
$stmt = $pdo->prepare(
    'SELECT s.id, s.matric_no, s.department, s.level, u.full_name, u.email
     FROM students s JOIN users u ON u.id = s.user_id
     WHERE s.user_id = ?'
);
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    die('Student record not found. Please contact the admin.');
}

$stmt = $pdo->prepare(
    'SELECT c.course_code, c.course_title, c.unit, r.score, r.grade
     FROM results r JOIN courses c ON c.id = r.course_id
     WHERE r.student_id = ?
     ORDER BY c.course_code'
);
$stmt->execute([$student['id']]);
$results = $stmt->fetchAll();

// GPA = sum(grade point x unit) / sum(unit)
$totalUnits  = 0;
$totalPoints = 0;
foreach ($results as $r) {
    list($letter, $points) = grade_from_score($r['score']);
    $totalUnits  += (int)$r['unit'];
    $totalPoints += $points * (int)$r['unit'];
}
$gpa = $totalUnits > 0 ? number_format($totalPoints / $totalUnits, 2) : '0.00';

$pageTitle = 'My Results';
require 'header.php';
?>

<h1>My Results</h1>

<div class="card">
    <p><strong>Name:</strong> <?= e($student['full_name']) ?></p>
    <p><strong>Matric No:</strong> <?= e($student['matric_no']) ?></p>
    <p><strong>Department:</strong> <?= e($student['department']) ?></p>
    <p><strong>Level:</strong> <?= e($student['level']) ?></p>
</div>

<div class="table-wrap">
<table>
    <thead>
        <tr><th>Course Code</th><th>Course Title</th><th>Unit</th><th>Score</th><th>Grade</th></tr>
    </thead>
    <tbody>
    <?php if (!$results): ?>
        <tr><td colspan="5" class="empty">No results have been recorded for you yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($results as $r): ?>
        <tr>
            <td><?= e($r['course_code']) ?></td>
            <td><?= e($r['course_title']) ?></td>
            <td><?= e($r['unit']) ?></td>
            <td><?= e($r['score']) ?></td>
            <td><span class="grade grade-<?= e($r['grade']) ?>"><?= e($r['grade']) ?></span></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<div class="stats">
    <div class="stat"><h2><?= $totalUnits ?></h2><p>Total Units</p></div>
    <div class="stat"><h2><?= e($gpa) ?></h2><p>GPA (5.0 scale)</p></div>
</div>

<div class="actions">
    <button type="button" class="btn" onclick="window.print()">Print Result</button>
</div>

<?php require 'footer.php'; ?>
