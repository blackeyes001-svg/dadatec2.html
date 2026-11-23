<?php
require_once 'config.php';
require_login('admin');

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $id > 0;
$errors  = [];
$row     = null;

$data = [
    'full_name'  => '',
    'email'      => '',
    'matric_no'  => '',
    'department' => '',
    'level'      => 100,
];

if ($editing) {
    $stmt = $pdo->prepare(
        'SELECT s.id, s.user_id, s.matric_no, s.department, s.level, u.full_name, u.email
         FROM students s JOIN users u ON u.id = s.user_id
         WHERE s.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        set_flash('Student not found.', 'error');
        header('Location: students.php');
        exit;
    }
    $data = [
        'full_name'  => $row['full_name'],
        'email'      => $row['email'],
        'matric_no'  => $row['matric_no'],
        'department' => $row['department'],
        'level'      => $row['level'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $data['full_name']  = trim($_POST['full_name'] ?? '');
    $data['email']      = trim($_POST['email'] ?? '');
    $data['matric_no']  = strtoupper(trim($_POST['matric_no'] ?? ''));
    $data['department'] = trim($_POST['department'] ?? '');
    $data['level']      = (int)($_POST['level'] ?? 0);
    $password           = $_POST['password'] ?? '';

    if ($data['full_name'] === '' || strlen($data['full_name']) > 100) {
        $errors[] = 'Enter the full name (maximum 100 characters).';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($data['matric_no'] === '' || strlen($data['matric_no']) > 30) {
        $errors[] = 'Enter the matric number (maximum 30 characters).';
    }
    if ($data['department'] === '') {
        $errors[] = 'Enter the department.';
    }
    if (!in_array($data['level'], [100, 200, 300, 400, 500], true)) {
        $errors[] = 'Choose a valid level.';
    }
    if (!$editing && strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($editing && $password !== '' && strlen($password) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            if ($editing) {
                $pdo->prepare('UPDATE users SET full_name = ?, email = ? WHERE id = ?')
                    ->execute([$data['full_name'], $data['email'], $row['user_id']]);

                if ($password !== '') {
                    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                        ->execute([password_hash($password, PASSWORD_DEFAULT), $row['user_id']]);
                }

                $pdo->prepare('UPDATE students SET matric_no = ?, department = ?, level = ? WHERE id = ?')
                    ->execute([$data['matric_no'], $data['department'], $data['level'], $id]);

                $message = 'Student updated.';
            } else {
                $pdo->prepare('INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)')
                    ->execute([$data['full_name'], $data['email'], password_hash($password, PASSWORD_DEFAULT), 'student']);
                $userId = $pdo->lastInsertId();

                $pdo->prepare('INSERT INTO students (user_id, matric_no, department, level) VALUES (?, ?, ?, ?)')
                    ->execute([$userId, $data['matric_no'], $data['department'], $data['level']]);

                $message = 'Student added.';
            }

            $pdo->commit();
            set_flash($message);
            header('Location: students.php');
            exit;
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($ex->getCode() === '23000') {
                $errors[] = 'That email or matric number is already in use.';
            } else {
                $errors[] = 'Database error. Please try again.';
            }
        }
    }
}

$pageTitle = $editing ? 'Edit Student' : 'Add Student';
require 'header.php';
?>

<div class="card narrow">
    <h1><?= e($pageTitle) ?></h1>

    <?php foreach ($errors as $err): ?>
        <div class="alert error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" action="student_form.php<?= $editing ? '?id=' . $id : '' ?>">
        <?= csrf_field() ?>

        <label for="full_name">Full Name</label>
        <input type="text" id="full_name" name="full_name" value="<?= e($data['full_name']) ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($data['email']) ?>" required>

        <label for="matric_no">Matric Number</label>
        <input type="text" id="matric_no" name="matric_no" value="<?= e($data['matric_no']) ?>" required>

        <label for="department">Department</label>
        <input type="text" id="department" name="department" value="<?= e($data['department']) ?>" required>

        <label for="level">Level</label>
        <select id="level" name="level">
            <?php foreach ([100, 200, 300, 400, 500] as $lvl): ?>
                <option value="<?= $lvl ?>" <?= ((int)$data['level'] === $lvl) ? 'selected' : '' ?>><?= $lvl ?></option>
            <?php endforeach; ?>
        </select>

        <label for="password">Password <?= $editing ? '(leave blank to keep the current one)' : '' ?></label>
        <input type="password" id="password" name="password" <?= $editing ? '' : 'required' ?>>

        <div class="actions">
            <button type="submit" class="btn"><?= $editing ? 'Save Changes' : 'Add Student' ?></button>
            <a class="btn secondary" href="students.php">Cancel</a>
        </div>
    </form>
</div>

<?php require 'footer.php'; ?>
