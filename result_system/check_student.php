<?php
require_once 'config.php';

$school     = '';
$department = '';
$matric     = '';
$error      = '';
$found      = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $school     = trim($_POST['school'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $matric     = strtoupper(trim($_POST['matric_no'] ?? ''));

    if ($school === '' || $department === '' || $matric === '') {
        $error = 'Enter the school name, department and matric number.';
    } else {
        // All three details must match the same student
        $stmt = $pdo->prepare(
            'SELECT u.full_name, s.school, s.department, s.matric_no
             FROM students s JOIN users u ON u.id = s.user_id
             WHERE s.school = ? AND s.department = ? AND s.matric_no = ?'
        );
        $stmt->execute([$school, $department, $matric]);
        $found = $stmt->fetch();

        if (!$found) {
            $error = 'No student found with those details. Check the spelling and try again.';
        }
    }
}

$pageTitle = 'Check Student';
require 'header.php';
?>

<div class="card narrow">
    <h1>Check Student</h1>
    <p class="muted">Enter the school name, department and matric number to see the student's name.</p>

    <?php if ($error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="check_student.php">
        <?= csrf_field() ?>

        <label for="school">School Name</label>
        <input type="text" id="school" name="school" value="<?= e($school) ?>" required>

        <label for="department">Department</label>
        <input type="text" id="department" name="department" value="<?= e($department) ?>" required>

        <label for="matric_no">Matric Number</label>
        <input type="text" id="matric_no" name="matric_no" value="<?= e($matric) ?>" required>

        <button type="submit" class="btn">Check</button>
    </form>
</div>

<?php if ($found): ?>
<div class="card narrow">
    <p class="muted">Student found</p>
    <h2 style="margin:0;color:var(--primary);"><?= e($found['full_name']) ?></h2>
    <p class="muted"><?= e($found['matric_no']) ?> | <?= e($found['department']) ?> | <?= e($found['school']) ?></p>
</div>
<?php endif; ?>

<?php require 'footer.php'; ?>
