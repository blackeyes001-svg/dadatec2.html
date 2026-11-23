<?php
require_once 'config.php';
require_login('admin');

$studentFilter = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

$students = $pdo->query(
    'SELECT s.id, s.matric_no, u.full_name
     FROM students s JOIN users u ON u.id = s.user_id
     ORDER BY u.full_name'
)->fetchAll();

$sql = 'SELECT r.id, u.full_name, s.matric_no, c.course_code, c.course_title, c.unit, r.score, r.grade
        FROM results r
        JOIN students s ON s.id = r.student_id
        JOIN users u ON u.id = s.user_id
        JOIN courses c ON c.id = r.course_id';

if ($studentFilter > 0) {
    $stmt = $pdo->prepare($sql . ' WHERE r.student_id = ? ORDER BY c.course_code');
    $stmt->execute([$studentFilter]);
} else {
    $stmt = $pdo->query($sql . ' ORDER BY u.full_name, c.course_code');
}
$results = $stmt->fetchAll();

$pageTitle = 'Results';
require 'header.php';
?>

<div class="page-head">
    <h1>Results</h1>
    <a class="btn" href="result_form.php">+ Add Result</a>
</div>

<form method="GET" action="results.php" class="search-row">
    <select name="student_id">
        <option value="0">All students</option>
        <?php foreach ($students as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= ($studentFilter === (int)$s['id']) ? 'selected' : '' ?>>
                <?= e($s['full_name']) ?> (<?= e($s['matric_no']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Filter</button>
</form>

<div class="table-wrap">
<table>
    <thead>
        <tr><th>Student</th><th>Matric No</th><th>Course</th><th>Unit</th><th>Score</th><th>Grade</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if (!$results): ?>
        <tr><td colspan="7" class="empty">No results found.</td></tr>
    <?php endif; ?>
    <?php foreach ($results as $r): ?>
        <tr>
            <td><?= e($r['full_name']) ?></td>
            <td><?= e($r['matric_no']) ?></td>
            <td><?= e($r['course_code']) ?> - <?= e($r['course_title']) ?></td>
            <td><?= e($r['unit']) ?></td>
            <td><?= e($r['score']) ?></td>
            <td><span class="grade grade-<?= e($r['grade']) ?>"><?= e($r['grade']) ?></span></td>
            <td class="row-actions">
                <a class="btn small" href="result_form.php?id=<?= (int)$r['id'] ?>">Edit</a>
                <form method="POST" action="result_delete.php"
                      onsubmit="return confirm('Delete this result?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button type="submit" class="btn small danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require 'footer.php'; ?>
