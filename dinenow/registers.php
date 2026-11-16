<?php

session_start();

require_once __DIR__ . "/includes/db.php";

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";
$full_name = "";
$email = "";
$phone = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST['full_name'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $phone = trim($_POST['phone'] ?? "");
    $password = $_POST['password'] ?? "";
    $confirm_password = $_POST['confirm_password'] ?? "";
    $csrf_token = $_POST['csrf_token'] ?? "";

    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {

        $error = "Invalid request. Please refresh the page.";

    } elseif (
        empty($full_name) ||
        empty($email) ||
        empty($phone) ||
        empty($password) ||
        empty($confirm_password)
    ) {

        $error = "Please complete all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 8) {

        $error = "Password must contain at least 8 characters.";

    } elseif ($password !== $confirm_password) {

        $error = "Your passwords do not match.";

    } else {

        try {

            $check = $pdo->prepare(
                "SELECT id FROM users WHERE email = :email"
            );

            $check->execute([
                ':email' => $email
            ]);

            if ($check->fetch()) {

                $error = "This email address is already registered.";

            } else {

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $insert = $pdo->prepare("
                    INSERT INTO users
                    (full_name, email, phone, password)
                    VALUES
                    (:full_name, :email, :phone, :password)
                ");

                $insert->execute([
                    ':full_name' => $full_name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':password' => $hashed_password
                ]);

                header("Location: login.php?registered=1");
                exit;

            }

        } catch (PDOException $e) {

            $error = "Registration failed. Please try again.";

        }

    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account | DineNow</title>

    <link rel="stylesheet" href="css/auth.css">

</head>

<body>

<div class="auth-container">

    <div class="auth-box">

        <a href="index.php" class="logo">
            Dine<span>Now</span>
        </a>

        <p class="tagline">BOOK. DINE. ENJOY.</p>

        <h1>Create Account</h1>

        <p class="subtitle">
            Join DineNow and discover amazing restaurants.
        </p>

        <?php if (!empty($error)): ?>

            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
            >

            <div class="form-group">

                <label>Full Name</label>

                <input
                    type="text"
                    name="full_name"
                    placeholder="Enter your full name"
                    value="<?= htmlspecialchars($full_name) ?>"
                    maxlength="100"
                    required
                >

            </div>

            <div class="form-group">

                <label>Email Address</label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    value="<?= htmlspecialchars($email) ?>"
                    maxlength="150"
                    required
                >

            </div>

            <div class="form-group">

                <label>Phone Number</label>

                <input
                    type="tel"
                    name="phone"
                    placeholder="Enter your phone number"
                    value="<?= htmlspecialchars($phone) ?>"
                    maxlength="20"
                    required
                >

            </div>

            <div class="form-group">

                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    placeholder="Minimum 8 characters"
                    minlength="8"
                    required
                >

            </div>

            <div class="form-group">

                <label>Confirm Password</label>

                <input
                    type="password"
                    name="confirm_password"
                    placeholder="Confirm your password"
                    minlength="8"
                    required
                >

            </div>

            <button type="submit" class="auth-button">
                Create Account
            </button>

        </form>

        <p class="auth-switch">
            Already have an account?
            <a href="login.php">Login</a>
        </p>

        <a href="index.php" class="back-home">
            ← Back to Homepage
        </a>

    </div>

</div>

</body>
</html>