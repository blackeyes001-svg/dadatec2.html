<?php
require_once 'config.php';
require_login('admin');

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $id > 0;
$errors  = [];

$data = ['course_code' => '', 'course_title' => '', 'unit' => 3];

if ($editing) {
    $stmt = $pdo->prepare('SELECT course_code, course_title, unit FROM courses WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();

    if (!$found) {
        set_flash('Course not found.', 'error');
        header('Location: courses.php');
        exit;
    }
    $data = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $data['course_code']  = strtoupper(trim($_POST['course_code'] ?? ''));
    $data['course_title'] = trim($_POST['course_title'] ?? '');
    $data['unit']         = (int)($_POST['unit'] ?? 0);

    if ($data['course_code'] === '' || strlen($data['course_code']) > 15) {
        $errors[] = 'Enter the course code (maximum 15 characters).';
    }
    if ($data['course_title'] === '' || strlen($data['course_title']) > 150) {
        $errors[] = 'Enter the course title (maximum 150 characters).';
    }
    if ($data['unit'] < 1 || $data['unit'] > 6) {
        $errors[] = 'Unit must be between 1 and 6.';
    }

    if (!$errors) {
        try {
            if ($editing) {
                $pdo->prepare('UPDATE courses SET course_code = ?, course_title = ?, unit = ? WHERE id = ?')
                    ->execute([$data['course_code'], $data['course_title'], $data['unit'], $id]);
                set_flash('Course updated.');
            } else {
                $pdo->prepare('INSERT INTO courses (course_code, course_title, unit) VALUES (?, ?, ?)')
                    ->execute([$data['course_code'], $data['course_title'], $data['unit']]);
                set_flash('Course added.');
            }
            header('Location: courses.php');
            exit;
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                $errors[] = 'That course code already exists.';
            } else {
                $errors[] = 'Database error. Please try again.';
            }
        }
    }
}

$pageTitle = $editing ? 'Edit Course' : 'Add Course';
require 'header.php';
?>

<div class="card narrow">
    <h1><?= e($pageTitle) ?></h1>

    <?php foreach ($errors as $err): ?>
        <div class="alert error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" action="course_form.php<?= $editing ? '?id=' . $id : '' ?>">
        <?= csrf_field() ?>

        <label for="course_code">Course Code (e.g. CSC101)</label>
        <input type="text" id="course_code" name="course_code" value="<?= e($data['course_code']) ?>" required>

        <label for="course_title">Course Title</label>
        <input type="text" id="course_title" name="course_title" value="<?= e($data['course_title']) ?>" required>

        <label for="unit">Credit Unit (1 to 6)</label>
        <input type="number" id="unit" name="unit" min="1" max="6" value="<?= e($data['unit']) ?>" required>

        <div class="actions">
            <button type="submit" class="btn"><?= $editing ? 'Save Changes' : 'Add Course' ?></button>
            <a class="btn secondary" href="courses.php">Cancel</a>
        </div>
    </form>
</div>

<?php require 'footer.php'; ?>
