<?php
session_start();
require_once __DIR__ . "/includes/db.php";

// Redirect logged-in users
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$email = "";

// Login form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid request. Please refresh the page and try again.";
    } else {

        $email = trim($_POST['email'] ?? "");
        $password = $_POST['password'] ?? "";

        if (empty($email) || empty($password)) {
            $error = "Please enter your email and password.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {

            $stmt = $pdo->prepare(
                "SELECT * FROM users WHERE email = ? LIMIT 1"
            );
            $stmt->execute([$email]);
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
                $error = "Incorrect email or password.";
            }
        }
    }
}

$registered = isset($_GET['registered']) && $_GET['registered'] == 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | DineNow</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background: #101010;
            color: #fff;
        }

        .page-container {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* LEFT IMAGE SECTION */

        .image-section {
            position: relative;
            min-height: 100vh;
            background:
                linear-gradient(
                    180deg,
                    rgba(0,0,0,0.12),
                    rgba(0,0,0,0.85)
                ),
                url("https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1200&q=85");

            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
            padding: 65px;
        }

        .image-content {
            max-width: 500px;
            position: relative;
            z-index: 2;
        }

        .brand {
            position: absolute;
            top: 35px;
            left: 50px;
            font-size: 27px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .brand span {
            color: #FF7628;
        }

        .image-content .tag {
            color: #FF7628;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 3px;
            margin-bottom: 15px;
        }

        .image-content h1 {
            font-size: clamp(35px, 4vw, 55px);
            line-height: 1.15;
            margin-bottom: 18px;
        }

        .image-content h1 span {
            color: #FF7628;
        }

        .image-content p {
            color: #dedede;
            line-height: 1.8;
            font-size: 14px;
            max-width: 420px;
        }

        /* RIGHT SECTION */

        .form-section {
            min-height: 100vh;
            padding: 35px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;

            background:
                radial-gradient(
                    circle at 15% 20%,
                    rgba(255,118,40,0.18),
                    transparent 35%
                ),
                radial-gradient(
                    circle at 90% 85%,
                    rgba(255,118,40,0.12),
                    transparent 35%
                ),
                #111111;
        }

        .glow {
            position: absolute;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: rgba(255,118,40,0.16);
            filter: blur(90px);
            pointer-events: none;
        }

        .glow.one {
            top: 5%;
            right: 5%;
        }

        .glow.two {
            bottom: 5%;
            left: 5%;
        }

        /* GLASS CARD */

        .login-card {
            width: 100%;
            max-width: 440px;
            padding: 42px 38px;
            position: relative;
            z-index: 2;

            background: rgba(255,255,255,0.075);
            border: 1px solid rgba(255,255,255,0.17);
            border-radius: 22px;

            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);

            box-shadow:
                0 20px 60px rgba(0,0,0,0.4),
                inset 0 1px 1px rgba(255,255,255,0.12);
        }

        .card-brand {
            display: none;
            font-size: 25px;
            font-weight: 800;
            margin-bottom: 30px;
        }

        .card-brand span {
            color: #FF7628;
        }

        .login-card h2 {
            font-size: 30px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #bcbcbc;
            font-size: 13px;
            margin-bottom: 30px;
        }

        .message {
            padding: 12px 14px;
            border-radius: 9px;
            font-size: 12px;
            margin-bottom: 18px;
        }

        .error-message {
            background: rgba(255,60,60,0.12);
            border: 1px solid rgba(255,80,80,0.3);
            color: #ff9999;
        }

        .success-message {
            background: rgba(50,200,100,0.12);
            border: 1px solid rgba(50,200,100,0.3);
            color: #9df0b8;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 9px;
            color: #eeeeee;
        }

        .input-box {
            position: relative;
        }

        .input-box input {
            width: 100%;
            height: 52px;
            padding: 0 45px 0 15px;

            background: rgba(0,0,0,0.25);
            border: 1px solid rgba(255,255,255,0.16);
            border-radius: 10px;

            outline: none;
            color: #fff;
            font-size: 13px;
            transition: 0.3s;
        }

        .input-box input::placeholder {
            color: #888;
        }

        .input-box input:focus {
            border-color: #FF7628;
            box-shadow: 0 0 0 3px rgba(255,118,40,0.12);
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #aaa;
            cursor: pointer;
            font-size: 11px;
        }

        .toggle-password:hover {
            color: #FF7628;
        }

        .form-options {
            display: flex;
            justify-content: flex-end;
            margin-top: -5px;
            margin-bottom: 24px;
        }

        .forgot-link {
            color: #FF7628;
            text-decoration: none;
            font-size: 12px;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 10px;

            background: #FF7628;
            color: #fff;

            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }

        .login-btn:hover {
            background: #e85e12;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255,118,40,0.25);
        }

        .register-text {
            text-align: center;
            margin-top: 25px;
            color: #aaa;
            font-size: 12px;
        }

        .register-text a {
            color: #FF7628;
            font-weight: 600;
            text-decoration: none;
        }

        .register-text a:hover {
            text-decoration: underline;
        }

        .back-home {
            display: block;
            text-align: center;
            margin-top: 22px;
            color: #aaa;
            font-size: 12px;
            text-decoration: none;
        }

        .back-home:hover {
            color: #FF7628;
        }

        /* MOBILE RESPONSIVENESS */

        @media (max-width: 850px) {
            .page-container {
                grid-template-columns: 1fr;
            }

            .image-section {
                min-height: 290px;
                padding: 35px 25px;
                background-position: center 55%;
            }

            .brand {
                top: 22px;
                left: 25px;
                font-size: 23px;
            }

            .image-content h1 {
                font-size: 32px;
                margin-bottom: 10px;
            }

            .image-content p {
                font-size: 12px;
            }

            .image-content .tag {
                font-size: 10px;
                margin-bottom: 8px;
            }

            .form-section {
                min-height: auto;
                padding: 35px 20px 50px;
            }

            .login-card {
                max-width: 480px;
            }
        }

        @media (max-width: 480px) {
            .image-section {
                min-height: 245px;
                padding: 25px 22px;
            }

            .brand {
                top: 18px;
                left: 22px;
            }

            .image-content h1 {
                font-size: 27px;
            }

            .login-card {
                padding: 32px 23px;
                border-radius: 18px;
            }

            .login-card h2 {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>

<div class="page-container">

    <!-- LEFT IMAGE SECTION -->
    <section class="image-section">

        <div class="brand">
            Dine<span>Now.</span>
        </div>

        <div class="image-content">
            <div class="tag">BOOK. DINE. ENJOY.</div>

            <h1>
                Great food.<br>
                Beautiful moments.<br>
                <span>One reservation away.</span>
            </h1>

            <p>
                Discover amazing restaurants, reserve your table
                and enjoy unforgettable dining experiences with DineNow.
            </p>
        </div>

    </section>

    <!-- RIGHT LOGIN SECTION -->
    <section class="form-section">

        <div class="glow one"></div>
        <div class="glow two"></div>

        <div class="login-card">

            <div class="card-brand">
                Dine<span>Now.</span>
            </div>

            <h2>Welcome Back!</h2>

            <p class="subtitle">
                Sign in to continue your dining experience.
            </p>

            <?php if ($registered): ?>
                <div class="message success-message">
                    Your account has been created successfully. Please log in.
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="message error-message">
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
                    <label for="email">Email Address</label>

                    <div class="input-box">
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email address"
                            value="<?= htmlspecialchars($email) ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>

                    <div class="input-box">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword()"
                            id="toggleText"
                        >
                            Show
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <a href="#" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" class="login-btn">
                    Sign In
                </button>

            </form>

            <div class="register-text">
                Don't have an account?
                <a href="register.php">Create Account</a>
            </div>

            <a href="index.php" class="back-home">
                ← Back to Homepage
            </a>

        </div>

    </section>

</div>

<script>
function togglePassword() {
    const password = document.getElementById("password");
    const toggleText = document.getElementById("toggleText");

    if (password.type === "password") {
        password.type = "text";
        toggleText.textContent = "Hide";
    } else {
        password.type = "password";
        toggleText.textContent = "Show";
    }
}
</script>

</body>
</html>