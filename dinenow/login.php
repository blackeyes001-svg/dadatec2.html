<?php

session_start();

require_once __DIR__ . "/includes/db.php";

// Redirect logged-in customers to homepage.
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Generate CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$email = "";

// Check registration success.
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

            $stmt->execute([
                ':email' => $email
            ]);

            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {

                // Prevent session fixation.
                session_regenerate_id(true);

                // Store customer information.
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Generate a fresh CSRF token.
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

    <link rel="stylesheet" href="css/auth.css">

</head>

<body>

<div class="auth-container">

    <div class="auth-box">

        <a href="index.php" class="logo">
            Dine<span>Now</span>
        </a>

        <p class="tagline">BOOK. DINE. ENJOY.</p>

        <h1>Welcome Back</h1>

        <p class="subtitle">
            Login to your DineNow account and continue exploring.
        </p>

        <?php if ($registered): ?>

            <div style="background:#173d27;color:#8be0a5;padding:12px;border-radius:7px;margin-bottom:20px;font-size:13px;">
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
                    required
                >

            </div>

            <div class="form-group">

                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

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

</body>
</html>