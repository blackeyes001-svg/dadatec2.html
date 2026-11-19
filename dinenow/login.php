\<?php

session_start();

require_once \_\_DIR\_\_ . "/includes/db.php";

// Redirect logged-in users.
if (isset($\_SESSION['user_id'])) {
&#x20;   header("Location: index.php");
&#x20;   exit;
}

// Generate CSRF token.
if (empty($\_SESSION['csrf_token'])) {
&#x20;   $\_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$email = "";

$registered = isset($\_GET['registered']);

if ($\_SERVER["REQUEST_METHOD"] === "POST") {

&#x20;   $email = trim($\_POST['email'] ?? "");
&#x20;   $password = $\_POST['password'] ?? "";
&#x20;   $csrf_token = $\_POST['csrf_token'] ?? "";

&#x20;   if (!hash_equals($\_SESSION['csrf_token'], $csrf_token)) {

&#x20;       $error = "Invalid request. Please refresh the page.";

&#x20;   } elseif (empty($email) || empty($password)) {

&#x20;       $error = "Please enter your email and password.";

&#x20;   } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

&#x20;       $error = "Please enter a valid email address.";

&#x20;   } else {

&#x20;       try {

&#x20;           $stmt = $pdo->prepare(
&#x20;               "SELECT id, full_name, email, password, role
&#x20;                FROM users
&#x20;                WHERE email = \:email
&#x20;                LIMIT 1"
&#x20;           );

&#x20;           $stmt->execute(['\:email' => $email]);

&#x20;           $user = $stmt->fetch();

&#x20;           if ($user && password_verify($password, $user['password'])) {

&#x20;               session_regenerate_id(true);

&#x20;               $\_SESSION['user_id'] = $user['id'];
&#x20;               $\_SESSION['full_name'] = $user['full_name'];
&#x20;               $\_SESSION['email'] = $user['email'];
&#x20;               $\_SESSION['role'] = $user['role'];

&#x20;               $\_SESSION['csrf_token'] = bin2hex(random_bytes(32));

&#x20;               header("Location: index.php");
&#x20;               exit;

&#x20;           } else {

&#x20;               $error = "Incorrect email address or password.";

&#x20;           }

&#x20;       } catch (PDOException $e) {

&#x20;           $error = "Something went wrong. Please try again.";

&#x20;       }
&#x20;   }
}

?>

\<!DOCTYPE html>

\<html lang="en">

\<head>

\<meta charset="UTF-8">
\<meta name="viewport" content="width=device-width, initial-scale=1.0">

\<title>Login | DineNow\</title>

\<link rel="stylesheet" href="css/auth.css">

\<style>

\* {
&#x20;   margin: 0;
&#x20;   padding: 0;
&#x20;   box-sizing: border-box;
}

body {
&#x20;   min-height: 100vh;
&#x20;   font-family: Arial, sans-serif;
&#x20;   background:
&#x20;       radial-gradient(circle at 15% 20%, rgba(255,118,40,0.22), transparent 35%),
&#x20;       radial-gradient(circle at 85% 80%, rgba(255,118,40,0.15), transparent 35%),
&#x20;       linear-gradient(135deg, #080808, #191919, #090909);
&#x20;   display: flex;
&#x20;   justify-content: center;
&#x20;   align-items: center;
&#x20;   padding: 25px;
&#x20;   color: #fff;
&#x20;   overflow-x: hidden;
}

.auth-container {
&#x20;   width: 100%;
&#x20;   max-width: 440px;
}

.auth-box {
&#x20;   width: 100%;
&#x20;   padding: 42px 35px;
&#x20;   background: rgba(255,255,255,0.07);
&#x20;   backdrop-filter: blur(22px);
&#x20;   -webkit-backdrop-filter: blur(22px);
&#x20;   border: 1px solid rgba(255,255,255,0.18);
&#x20;   border-radius: 22px;
&#x20;   box-shadow:
&#x20;       0 8px 40px rgba(0,0,0,0.5),
&#x20;       0 0 25px rgba(255,118,40,0.08);
&#x20;   text-align: center;
}

.logo {
&#x20;   display: inline-block;
&#x20;   font-size: 34px;
&#x20;   font-weight: 800;
&#x20;   color: #fff;
&#x20;   text-decoration: none;
&#x20;   letter-spacing: -1px;
}

.logo span {
&#x20;   color: #FF7628;
}

.tagline {
&#x20;   color: #FF7628;
&#x20;   font-size: 11px;
&#x20;   letter-spacing: 4px;
&#x20;   margin-top: 7px;
&#x20;   margin-bottom: 32px;
&#x20;   font-weight: bold;
}

.auth-box h1 {
&#x20;   font-size: 29px;
&#x20;   margin-bottom: 10px;
}

.subtitle {
&#x20;   color: #bdbdbd;
&#x20;   font-size: 13px;
&#x20;   line-height: 1.6;
&#x20;   margin-bottom: 28px;
}

.form-group {
&#x20;   text-align: left;
&#x20;   margin-bottom: 21px;
}

.form-group label {
&#x20;   display: block;
&#x20;   font-size: 13px;
&#x20;   margin-bottom: 9px;
&#x20;   color: #eee;
}

.form-group input {
&#x20;   width: 100%;
&#x20;   height: 50px;
&#x20;   padding: 0 15px;
&#x20;   background: rgba(255,255,255,0.08);
&#x20;   border: 1px solid rgba(255,255,255,0.16);
&#x20;   border-radius: 10px;
&#x20;   outline: none;
&#x20;   color: #fff;
&#x20;   font-size: 14px;
&#x20;   transition: 0.3s;
}

.form-group input::placeholder {
&#x20;   color: #999;
}

.form-group input\:focus {
&#x20;   border-color: #FF7628;
&#x20;   box-shadow: 0 0 12px rgba(255,118,40,0.2);
&#x20;   background: rgba(255,255,255,0.11);
}

/\* Password field and toggle \*/

.password-wrapper {
&#x20;   position: relative;
}

.password-wrapper input {
&#x20;   padding-right: 75px;
}

.toggle-password {
&#x20;   position: absolute;
&#x20;   right: 13px;
&#x20;   top: 50%;
&#x20;   transform: translateY(-50%);
&#x20;   background: none;
&#x20;   border: none;
&#x20;   color: #FF7628;
&#x20;   font-size: 12px;
&#x20;   font-weight: bold;
&#x20;   cursor: pointer;
&#x20;   padding: 8px 3px;
}

.toggle-password\:hover {
&#x20;   color: #fff;
}

.auth-button {
&#x20;   width: 100%;
&#x20;   height: 51px;
&#x20;   border: none;
&#x20;   border-radius: 10px;
&#x20;   background: #FF7628;
&#x20;   color: #fff;
&#x20;   font-size: 15px;
&#x20;   font-weight: bold;
&#x20;   cursor: pointer;
&#x20;   transition: 0.3s;
&#x20;   margin-top: 8px;
}

.auth-button\:hover {
&#x20;   background: #ff8d4d;
&#x20;   box-shadow: 0 0 22px rgba(255,118,40,0.4);
&#x20;   transform: translateY(-2px);
}

.error-message {
&#x20;   background: rgba(255,60,60,0.13);
&#x20;   color: #ff9292;
&#x20;   border: 1px solid rgba(255,60,60,0.3);
&#x20;   padding: 12px;
&#x20;   border-radius: 9px;
&#x20;   margin-bottom: 20px;
&#x20;   font-size: 13px;
}

.success-message {
&#x20;   background: rgba(30,150,70,0.15);
&#x20;   color: #8be0a5;
&#x20;   border: 1px solid rgba(100,220,130,0.25);
&#x20;   padding: 12px;
&#x20;   border-radius: 9px;
&#x20;   margin-bottom: 20px;
&#x20;   font-size: 13px;
}

.auth-switch {
&#x20;   margin-top: 25px;
&#x20;   font-size: 13px;
&#x20;   color: #bbb;
}

.auth-switch a {
&#x20;   color: #FF7628;
&#x20;   text-decoration: none;
&#x20;   font-weight: bold;
}

.auth-switch a\:hover {
&#x20;   text-decoration: underline;
}

.back-home {
&#x20;   display: inline-block;
&#x20;   margin-top: 25px;
&#x20;   color: #aaa;
&#x20;   text-decoration: none;
&#x20;   font-size: 13px;
&#x20;   transition: 0.3s;
}

.back-home\:hover {
&#x20;   color: #FF7628;
}

@media(max-width: 480px) {

&#x20;   body {
&#x20;       padding: 16px;
&#x20;   }

&#x20;   .auth-box {
&#x20;       padding: 35px 23px;
&#x20;   }

&#x20;   .auth-box h1 {
&#x20;       font-size: 26px;
&#x20;   }

}

\</style>

\</head>

\<body>

\<div class="auth-container">

```
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

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
        >

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
                >
                    Show
                </button>

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
```

\</div>

\<script>

function togglePassword(inputId, button) {

&#x20;   const passwordInput = document.getElementById(inputId);

&#x20;   if (passwordInput.type === "password") {

&#x20;       passwordInput.type = "text";
&#x20;       button.textContent = "Hide";

&#x20;   } else {

&#x20;       passwordInput.type = "password";
&#x20;       button.textContent = "Show";

&#x20;   }

}

\</script>

\</body>
\</html>
