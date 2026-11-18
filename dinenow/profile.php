
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/includes/db.php";

// Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

$error = "";
$success = "";

// CSRF token
if (empty($_SESSION['profile_token'])) {
    $_SESSION['profile_token'] = bin2hex(random_bytes(32));
}

// Fetch customer details
$stmt = $pdo->prepare("
    SELECT id, full_name, email, phone, password
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['profile_token'] ?? '';

    if (!hash_equals($_SESSION['profile_token'], $token)) {
        $error = "Invalid request. Please refresh the page.";
    } else {

        $action = $_POST['action'] ?? '';

        // UPDATE PROFILE
        if ($action === 'update_profile') {

            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if ($fullName === '' || $email === '') {
                $error = "Full name and email address are required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Please enter a valid email address.";
            } elseif (strlen($fullName) > 150 || strlen($email) > 150 || strlen($phone) > 30) {
                $error = "One or more fields exceed the allowed length.";
            } else {

                $check = $pdo->prepare("
                    SELECT id FROM users
                    WHERE email = :email AND id != :id
                    LIMIT 1
                ");

                $check->execute([
                    'email' => $email,
                    'id' => $userId
                ]);

                if ($check->fetch()) {
                    $error = "This email address is already being used by another account.";
                } else {

                    $update = $pdo->prepare("
                        UPDATE users
                        SET full_name = :full_name,
                            email = :email,
                            phone = :phone
                        WHERE id = :id
                    ");

                    $update->execute([
                        'full_name' => $fullName,
                        'email' => $email,
                        'phone' => $phone,
                        'id' => $userId
                    ]);

                    $_SESSION['full_name'] = $fullName;
                    $_SESSION['email'] = $email;

                    $success = "Your profile has been updated successfully.";
                }
            }
        }

        // CHANGE PASSWORD
        elseif ($action === 'change_password') {

            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                $error = "Please complete all password fields.";
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $error = "Your current password is incorrect.";
            } elseif (strlen($newPassword) < 8) {
                $error = "Your new password must contain at least 8 characters.";
            } elseif ($newPassword !== $confirmPassword) {
                $error = "Your new passwords do not match.";
            } else {

                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                $updatePassword = $pdo->prepare("
                    UPDATE users
                    SET password = :password
                    WHERE id = :id
                ");

                $updatePassword->execute([
                    'password' => $hashedPassword,
                    'id' => $userId
                ]);

                $success = "Your password has been changed successfully.";
            }
        }

        else {
            $error = "Invalid form request.";
        }

        // Refresh customer information
        $stmt = $pdo->prepare("
            SELECT id, full_name, email, phone, password
            FROM users
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        $_SESSION['profile_token'] = bin2hex(random_bytes(32));
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile | DineNow</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #101010;
            color: white;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 950px;
            margin: auto;
        }

        .brand {
            text-align: center;
            font-size: 30px;
            font-weight: bold;
            margin-bottom: 35px;
        }

        .brand span {
            color: #ff7628;
        }

        .heading {
            margin-bottom: 30px;
        }

        .heading h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .heading p {
            color: #aaa;
        }

        .profile-card {
            background: #191919;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
        }

        .profile-card h2 {
            color: #ff7628;
            font-size: 22px;
            margin-bottom: 25px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 9px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 14px;
            background: #101010;
            border: 1px solid #444;
            border-radius: 7px;
            color: white;
            font-size: 15px;
            outline: none;
        }

        input:focus {
            border-color: #ff7628;
        }

        button {
            background: #ff7628;
            color: #101010;
            border: none;
            padding: 14px 25px;
            border-radius: 7px;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
            transition: .3s;
        }

        button:hover {
            background: white;
        }

        .message {
            padding: 15px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .success {
            background: #173c25;
            color: #a5f0b9;
            border: 1px solid #287a43;
        }

        .error {
            background: #421b1b;
            color: #ffaaaa;
            border: 1px solid #8b3333;
        }

        .back-link {
            display: inline-block;
            margin-top: 10px;
            color: #ff7628;
            text-decoration: none;
        }

        @media(max-width: 600px) {

            body {
                padding: 25px 13px;
            }

            .profile-card {
                padding: 22px 17px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .heading h1 {
                font-size: 27px;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <div class="brand">
        Dine<span>Now.</span>
    </div>

    <div class="heading">
        <h1>My Profile</h1>
        <p>Manage your personal information and account security.</p>
    </div>

    <?php if ($success): ?>
        <div class="message success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>


    <!-- PERSONAL INFORMATION -->

    <div class="profile-card">

        <h2>Personal Information</h2>

        <form method="POST">

            <input type="hidden" name="action" value="update_profile">

            <input type="hidden"
                   name="profile_token"
                   value="<?= htmlspecialchars($_SESSION['profile_token']) ?>">

            <div class="form-grid">

                <div class="form-group">
                    <label>Full Name</label>

                    <input type="text"
                           name="full_name"
                           value="<?= htmlspecialchars($user['full_name']) ?>"
                           maxlength="150"
                           required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>

                    <input type="email"
                           name="email"
                           value="<?= htmlspecialchars($user['email']) ?>"
                           maxlength="150"
                           required>
                </div>

                <div class="form-group full">
                    <label>Phone Number</label>

                    <input type="text"
                           name="phone"
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                           maxlength="30">
                </div>

            </div>

            <button type="submit">
                Save Changes
            </button>

        </form>

    </div>


    <!-- CHANGE PASSWORD -->

    <div class="profile-card">

        <h2>Change Password</h2>

        <form method="POST">

            <input type="hidden" name="action" value="change_password">

            <input type="hidden"
                   name="profile_token"
                   value="<?= htmlspecialchars($_SESSION['profile_token']) ?>">

            <div class="form-group">
                <label>Current Password</label>

                <input type="password"
                       name="current_password"
                       autocomplete="current-password"
                       required>
            </div>

            <div class="form-group">
                <label>New Password</label>

                <input type="password"
                       name="new_password"
                       minlength="8"
                       autocomplete="new-password"
                       required>
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>

                <input type="password"
                       name="confirm_password"
                       minlength="8"
                       autocomplete="new-password"
                       required>
            </div>

            <button type="submit">
                Update Password
            </button>

        </form>

    </div>


    <a href="index.php" class="back-link">
        ← Back to Home
    </a>

</div>

</body>
</html>
