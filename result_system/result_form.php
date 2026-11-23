<?php
require_once 'config.php';
require_login('admin');

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $id > 0;
$errors  = [];

$studentId = 0;
$courseId  = 0;
$score     = '';
$current   = null;

if ($editing) {
    $stmt = $pdo->prepare(
        'SELECT r.id, r.student_id, r.course_id, r.score, u.full_name, s.matric_no, c.course_code, c.course_title
         FROM results r
         JOIN students s ON s.id = r.student_id
         JOIN users u ON u.id = s.user_id
         JOIN courses c ON c.id = r.course_id
         WHERE r.id = ?'
    );
    $stmt->execute([$id]);
    $current = $stmt->fetch();

    if (!$current) {
        set_flash('Result not found.', 'error');
        header('Location: results.php');
        exit;
    }
    $studentId = (int)$current['student_id'];
    $courseId  = (int)$current['course_id'];
    $score     = $current['score'];
}

// Dropdown lists (used when adding a new result)
$students = $pdo->query(
    'SELECT s.id, s.matric_no, u.full_name
     FROM students s JOIN users u ON u.id = s.user_id
     ORDER BY u.full_name'
)->fetchAll();
$courses = $pdo->query('SELECT id, course_code, course_title FROM courses ORDER BY course_code')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $score = trim($_POST['score'] ?? '');
    $valid = filter_var($score, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);

    if ($valid === false) {
        $errors[] = 'Score must be a whole number from 0 to 100.';
    }

    if (!$editing) {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $courseId  = (int)($_POST['course_id'] ?? 0);

        if ($studentId <= 0 || $courseId <= 0) {
            $errors[] = 'Choose a student and a course.';
        } else {
            $check = $pdo->prepare('SELECT id FROM results WHERE student_id = ? AND course_id = ?');
            $check->execute([$studentId, $courseId]);
            if ($check->fetch()) {
                $errors[] = 'This student already has a result for that course. Edit the existing result instead.';
            }
        }
    }

    if (!$errors) {
        list($grade, $points) = grade_from_score($valid);

        try {
            if ($editing) {
                $pdo->prepare('UPDATE results SET score = ?, grade = ? WHERE id = ?')
                    ->execute([$valid, $grade, $id]);
                set_flash('Result updated.');
            } else {
                $pdo->prepare('INSERT INTO results (student_id, course_id, score, grade) VALUES (?, ?, ?, ?)')
                    ->execute([$studentId, $courseId, $valid, $grade]);
                set_flash('Result added.');
            }
            header('Location: results.php');
            exit;
        } catch (PDOException $ex) {
            $errors[] = 'Could not save the result. Check the student and course and try again.';
        }
    }
}

$pageTitle = $editing ? 'Edit Result' : 'Add Result';
require 'header.php';
?>

<div class="card narrow">
    <h1><?= e($pageTitle) ?></h1>

    <?php foreach ($errors as $err): ?>
        <div class="alert error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" action="result_form.php<?= $editing ? '?id=' . $id : '' ?>">
        <?= csrf_field() ?>

        <?php if ($editing): ?>
            <p><strong>Student:</strong> <?= e($current['full_name']) ?> (<?= e($current['matric_no']) ?>)</p>
            <p><strong>Course:</strong> <?= e($current['course_code']) ?> - <?= e($current['course_title']) ?></p>
        <?php else: ?>
            <label for="student_id">Student</label>
            <select id="student_id" name="student_id" required>
                <option value="">-- Select student --</option>
                <?php foreach ($students as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= ($studentId === (int)$s['id']) ? 'selected' : '' ?>>
                        <?= e($s['full_name']) ?> (<?= e($s['matric_no']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="course_id">Course</label>
            <select id="course_id" name="course_id" required>
                <option value="">-- Select course --</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ($courseId === (int)$c['id']) ? 'selected' : '' ?>>
                        <?= e($c['course_code']) ?> - <?= e($c['course_title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <label for="score">Score (0 to 100)</label>
        <input type="number" id="score" name="score" min="0" max="100" value="<?= e($score) ?>" required>

        <p class="muted">The grade is worked out automatically: A 70+, B 60-69, C 50-59, D 45-49, E 40-44, F below 40.</p>

        <div class="actions">
            <button type="submit" class="btn"><?= $editing ? 'Save Changes' : 'Add Result' ?></button>
            <a class="btn secondary" href="results.php">Cancel</a>
        </div>
    </form>
</div>

<?php require 'footer.php'; ?>
