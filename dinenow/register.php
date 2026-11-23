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

$full_name = "";
$email = "";
$phone = "";

// Registration form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid request. Please refresh the page and try again.";

    } else {

        $full_name = trim($_POST['full_name'] ?? "");
        $email = trim($_POST['email'] ?? "");
        $phone = trim($_POST['phone'] ?? "");
        $password = $_POST['password'] ?? "";
        $confirm_password = $_POST['confirm_password'] ?? "";

        if (
            empty($full_name) ||
            empty($email) ||
            empty($phone) ||
            empty($password) ||
            empty($confirm_password)
        ) {
            $error = "Please fill in all fields.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";

        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";

        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";

        } else {

            // Check whether email already exists
            $checkEmail = $pdo->prepare(
                "SELECT id FROM users WHERE email = ? LIMIT 1"
            );

            $checkEmail->execute([$email]);

            if ($checkEmail->fetch()) {
                $error = "An account with this email already exists.";

            } else {

                // Hash password
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Insert new customer
                $insertUser = $pdo->prepare(
                    "INSERT INTO users
                    (full_name, email, phone, password)
                    VALUES (?, ?, ?, ?)"
                );

                $insertUser->execute([
                    $full_name,
                    $email,
                    $phone,
                    $hashedPassword
                ]);

                // Redirect to login
                header("Location: login.php?registered=1");
                exit;
            }
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

        .image-content {
            max-width: 500px;
            position: relative;
            z-index: 2;
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

        .register-card {
            width: 100%;
            max-width: 460px;

            padding: 35px 38px;

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
            margin-bottom: 25px;
        }

        .card-brand span {
            color: #FF7628;
        }

        .register-card h2 {
            font-size: 29px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .subtitle {
            color: #bcbcbc;
            font-size: 12px;
            margin-bottom: 24px;
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

        /* FORM */

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #eeeeee;
        }

        .input-box {
            position: relative;
        }

        .input-box input {
            width: 100%;
            height: 48px;

            padding: 0 45px 0 15px;

            background: rgba(0,0,0,0.25);
            border: 1px solid rgba(255,255,255,0.16);
            border-radius: 10px;

            outline: none;
            color: #fff;
            font-size: 12px;

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

        /* BUTTON */

        .register-btn {
            width: 100%;
            height: 51px;

            margin-top: 8px;

            border: none;
            border-radius: 10px;

            background: #FF7628;
            color: #fff;

            font-size: 14px;
            font-weight: 700;

            cursor: pointer;
            transition: 0.3s;
        }

        .register-btn:hover {
            background: #e85e12;
            transform: translateY(-2px);

            box-shadow:
                0 8px 25px rgba(255,118,40,0.25);
        }

        .login-text {
            text-align: center;
            margin-top: 22px;

            color: #aaa;
            font-size: 12px;
        }

        .login-text a {
            color: #FF7628;
            font-weight: 600;
            text-decoration: none;
        }

        .login-text a:hover {
            text-decoration: underline;
        }

        .back-home {
            display: block;
            text-align: center;

            margin-top: 17px;

            color: #aaa;
            font-size: 12px;
            text-decoration: none;
        }

        .back-home:hover {
            color: #FF7628;
        }

        /* TABLET AND MOBILE */

        @media (max-width: 850px) {

            .page-container {
                grid-template-columns: 1fr;
            }

            .image-section {
                min-height: 270px;
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

            .register-card {
                max-width: 480px;
            }
        }

        @media (max-width: 480px) {

            .image-section {
                min-height: 230px;
                padding: 25px 22px;
            }

            .brand {
                top: 18px;
                left: 22px;
            }

            .image-content h1 {
                font-size: 27px;
            }

            .register-card {
                padding: 30px 23px;
                border-radius: 18px;
            }

            .register-card h2 {
                font-size: 25px;
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

            <div class="tag">
                BOOK. DINE. ENJOY.
            </div>

            <h1>
                Your next<br>
                favourite meal<br>
                <span>starts here.</span>
            </h1>

            <p>
                Join DineNow today. Explore wonderful restaurants,
                discover delicious meals and reserve your perfect table.
            </p>

        </div>

    </section>


    <!-- RIGHT REGISTRATION SECTION -->

    <section class="form-section">

        <div class="glow one"></div>
        <div class="glow two"></div>

        <div class="register-card">

            <div class="card-brand">
                Dine<span>Now.</span>
            </div>

            <h2>Create Account</h2>

            <p class="subtitle">
                Join DineNow and start enjoying better dining experiences.
            </p>

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

                    <label for="full_name">Full Name</label>

                    <div class="input-box">

                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            placeholder="Enter your full name"
                            value="<?= htmlspecialchars($full_name) ?>"
                            required
                        >

                    </div>

                </div>


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

                    <label for="phone">Phone Number</label>

                    <div class="input-box">

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            placeholder="Enter your phone number"
                            value="<?= htmlspecialchars($phone) ?>"
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
                            placeholder="Create a password"
                            minlength="8"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword('password', 'togglePasswordText')"
                            id="togglePasswordText"
                        >
                            Show
                        </button>

                    </div>

                </div>


                <div class="form-group">

                    <label for="confirm_password">Confirm Password</label>

                    <div class="input-box">

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Confirm your password"
                            minlength="8"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword('confirm_password', 'toggleConfirmText')"
                            id="toggleConfirmText"
                        >
                            Show
                        </button>

                    </div>

                </div>


                <button type="submit" class="register-btn">
                    Create My Account
                </button>

            </form>


            <div class="login-text">

                Already have an account?

                <a href="login.php">Sign In</a>

            </div>


            <a href="index.php" class="back-home">
                ← Back to Homepage
            </a>

        </div>

    </section>

</div>


<script>

function togglePassword(inputId, buttonId) {

    const password = document.getElementById(inputId);
    const button = document.getElementById(buttonId);

    if (password.type === "password") {

        password.type = "text";
        button.textContent = "Hide";

    } else {

        password.type = "password";
        button.textContent = "Show";

    }

}

</script>

</body>
</html>