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

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    min-height: 100vh;
    font-family: Arial, sans-serif;
    background:
        radial-gradient(circle at 15% 20%, rgba(255,118,40,0.22), transparent 35%),
        radial-gradient(circle at 85% 80%, rgba(255,118,40,0.15), transparent 35%),
        linear-gradient(135deg, #080808, #191919, #090909);
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 30px 18px;
    color: #fff;
}

.auth-container {
    width: 100%;
    max-width: 470px;
}

.auth-box {
    width: 100%;
    padding: 40px 35px;
    background: rgba(255,255,255,0.07);
    backdrop-filter: blur(22px);
    -webkit-backdrop-filter: blur(22px);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 22px;
    box-shadow:
        0 8px 40px rgba(0,0,0,0.5),
        0 0 25px rgba(255,118,40,0.08);
    text-align: center;
}

.logo {
    display: inline-block;
    font-size: 34px;
    font-weight: 800;
    color: #fff;
    text-decoration: none;
    letter-spacing: -1px;
}

.logo span {
    color: #FF7628;
}

.tagline {
    color: #FF7628;
    font-size: 11px;
    letter-spacing: 4px;
    margin-top: 7px;
    margin-bottom: 27px;
    font-weight: bold;
}

.auth-box h1 {
    font-size: 29px;
    margin-bottom: 10px;
}

.subtitle {
    color: #bdbdbd;
    font-size: 13px;
    line-height: 1.6;
    margin-bottom: 25px;
}

.form-group {
    text-align: left;
    margin-bottom: 17px;
}

.form-group label {
    display: block;
    font-size: 13px;
    margin-bottom: 8px;
    color: #eee;
}

.form-group input {
    width: 100%;
    height: 49px;
    padding: 0 15px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.16);
    border-radius: 10px;
    outline: none;
    color: #fff;
    font-size: 14px;
    transition: 0.3s;
}

.form-group input::placeholder {
    color: #999;
}

.form-group input:focus {
    border-color: #FF7628;
    box-shadow: 0 0 12px rgba(255,118,40,0.2);
    background: rgba(255,255,255,0.11);
}

/* Password show and hide */

.password-wrapper {
    position: relative;
}

.password-wrapper input {
    padding-right: 75px;
}

.toggle-password {
    position: absolute;
    right: 13px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #FF7628;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    padding: 8px 3px;
}

.toggle-password:hover {
    color: #fff;
}

.auth-button {
    width: 100%;
    height: 51px;
    border: none;
    border-radius: 10px;
    background: #FF7628;
    color: #fff;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
    margin-top: 9px;
}

.auth-button:hover {
    background: #ff8d4d;
    box-shadow: 0 0 22px rgba(255,118,40,0.4);
    transform: translateY(-2px);
}

.error-message {
    background: rgba(255,60,60,0.13);
    color: #ff9292;
    border: 1px solid rgba(255,60,60,0.3);
    padding: 12px;
    border-radius: 9px;
    margin-bottom: 20px;
    font-size: 13px;
}

.auth-switch {
    margin-top: 24px;
    font-size: 13px;
    color: #bbb;
}

.auth-switch a {
    color: #FF7628;
    text-decoration: none;
    font-weight: bold;
}

.auth-switch a:hover {
    text-decoration: underline;
}

.back-home {
    display: inline-block;
    margin-top: 23px;
    color: #aaa;
    text-decoration: none;
    font-size: 13px;
    transition: 0.3s;
}

.back-home:hover {
    color: #FF7628;
}

@media(max-width: 480px) {

    body {
        padding: 18px 13px;
    }

    .auth-box {
        padding: 32px 22px;
    }

    .auth-box h1 {
        font-size: 26px;
    }

}

</style>

</head>

<body>

<div class="auth-container">

    <div class="auth-box">

        <a href="index.php" class="logo">
            Dine<span>Now.</span>
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
                    autocomplete="name"
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
                    autocomplete="email"
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
                    autocomplete="tel"
                    required
                >

            </div>

            <div class="form-group">

                <label>Password</label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimum 8 characters"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword('password', this)"
                    >
                        Show
                    </button>

                </div>

            </div>

            <div class="form-group">

                <label>Confirm Password</label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm your password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword('confirm_password', this)"
                    >
                        Show
                    </button>

                </div>

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

<script>

function togglePassword(inputId, button) {

    const passwordInput = document.getElementById(inputId);

    if (passwordInput.type === "password") {

        passwordInput.type = "text";
        button.textContent = "Hide";

    } else {

        passwordInput.type = "password";
        button.textContent = "Show";

    }

}

</script>

</body>
</html>