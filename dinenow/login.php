<?php
session_start();

require_once __DIR__ . "/includes/db.php";

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$email = "";
$registered = isset($_GET['registered']);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? "");
    $password = $_POST['password'] ?? "";
    $csrf_token = $_POST['csrf_token'] ?? "";

    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $error = "Invalid request. Please refresh the page.";

    } elseif (empty($email) || empty($password)) {
        $error = "Please enter your email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, full_name, email, password, role
                 FROM users
                 WHERE email = :email
                 LIMIT 1"
            );

            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header("Location: index.php");
                exit;

            } else {
                $error = "Incorrect email address or password.";
            }

        } catch (PDOException $e) {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | DineNow</title>

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
        radial-gradient(circle at 15% 20%, rgba(255,118,40,.22), transparent 35%),
        radial-gradient(circle at 85% 80%, rgba(255,118,40,.15), transparent 35%),
        linear-gradient(135deg, #080808, #191919, #090909);
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 25px;
    color: white;
}

.auth-container {
    width: 100%;
    max-width: 440px;
}

.auth-box {
    padding: 42px 35px;
    background: rgba(255,255,255,.07);
    backdrop-filter: blur(22px);
    -webkit-backdrop-filter: blur(22px);
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 22px;
    box-shadow: 0 8px 40px rgba(0,0,0,.5),
                0 0 25px rgba(255,118,40,.08);
    text-align: center;
}

.logo {
    font-size: 34px;
    font-weight: 800;
    color: white;
    text-decoration: none;
}

.logo span {
    color: #FF7628;
}

.tagline {
    color: #FF7628;
    font-size: 11px;
    letter-spacing: 4px;
    margin: 7px 0 32px;
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
    margin-bottom: 28px;
}

.form-group {
    text-align: left;
    margin-bottom: 21px;
}

.form-group label {
    display: block;
    font-size: 13px;
    margin-bottom: 9px;
    color: #eee;
}

.form-group input {
    width: 100%;
    height: 50px;
    padding: 0 15px;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.16);
    border-radius: 10px;
    outline: none;
    color: white;
    font-size: 14px;
    transition: .3s;
}

.form-group input::placeholder {
    color: #999;
}

.form-group input:focus {
    border-color: #FF7628;
    box-shadow: 0 0 12px rgba(255,118,40,.2);
}

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
}

.toggle-password:hover {
    color: white;
}

.auth-button {
    width: 100%;
    height: 51px;
    border: none;
    border-radius: 10px;
    background: #FF7628;
    color: white;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
    transition: .3s;
    margin-top: 8px;
}

.auth-button:hover {
    background: #ff8d4d;
    box-shadow: 0 0 22px rgba(255,118,40,.4);
    transform: translateY(-2px);
}

.error-message {
    background: rgba(255,60,60,.13);
    color: #ff9292;
    border: 1px solid rgba(255,60,60,.3);
    padding: 12px;
    border-radius: 9px;
    margin-bottom: 20px;
    font-size: 13px;
}

.success-message {
    background: rgba(30,150,70,.15);
    color: #8be0a5;
    padding: 12px;
    border-radius: 9px;
    margin-bottom: 20px;
    font-size: 13px;
}

.auth-switch {
    margin-top: 25px;
    font-size: 13px;
    color: #bbb;
}

.auth-switch a {
    color: #FF7628;
    text-decoration: none;
    font-weight: bold;
}

.back-home {
    display: inline-block;
    margin-top: 25px;
    color: #aaa;
    text-decoration: none;
    font-size: 13px;
}

.back-home:hover {
    color: #FF7628;
}

@media(max-width: 480px) {
    body {
        padding: 16px;
    }

    .auth-box {
        padding: 35px 23px;
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

        <h1>Welcome Back</h1>

        <p class="subtitle">
            Login to your DineNow account and continue exploring.
        </p>

        <?php if ($registered): ?>
            <div class="success-message">
                Registration successful! You can now log in.
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="form-group">
                <label>Email Address</label>
                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email address"
                    value="<?= htmlspecialchars($email) ?>"
                    autocomplete="email"
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
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword('password', this)"
                    >Show</button>
                </div>
            </div>

            <button type="submit" class="auth-button">
                Login to Account
            </button>

        </form>

        <p class="auth-switch">
            Don't have an account?
            <a href="register.php">Create Account</a>
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