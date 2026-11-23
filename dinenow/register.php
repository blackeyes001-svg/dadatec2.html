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

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>

:root {
    --orange: #FF7628;
    --orange-light: #ff914f;
    --black: #101010;
    --dark: #191919;
    --white: #ffffff;
    --grey: #777777;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    min-height: 100vh;
    font-family: 'DM Sans', Arial, sans-serif;
    background: #f5f5f5;
    color: var(--black);
}

a {
    text-decoration: none;
}

/* MAIN LAYOUT */

.auth-layout {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr 1fr;
}

/* LEFT VISUAL PANEL */

.visual-panel {
    position: relative;
    min-height: 100vh;

    background:
        linear-gradient(
            90deg,
            rgba(10,10,10,.85),
            rgba(10,10,10,.48)
        ),
        url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1800&q=85');

    background-size: cover;
    background-position: center;

    display: flex;
    flex-direction: column;
    justify-content: space-between;

    padding: 45px 55px;

    overflow: hidden;
    color: white;
}

.visual-panel::after {
    content: "";
    position: absolute;
    inset: 0;

    background: linear-gradient(
        135deg,
        rgba(255,118,40,.15),
        transparent 60%
    );

    pointer-events: none;
}

.visual-logo,
.visual-content,
.visual-footer {
    position: relative;
    z-index: 2;
}

.visual-logo {
    font-size: 30px;
    font-weight: 800;
    color: white;
}

.visual-logo span {
    color: var(--orange);
}

.visual-content {
    max-width: 600px;
    margin-top: 50px;
    margin-bottom: 50px;
}

.visual-content .small-heading {
    color: var(--orange);
    font-size: 14px;
    font-weight: 800;
    letter-spacing: 4px;
    margin-bottom: 20px;
    text-transform: uppercase;
}

.visual-content h1 {
    font-size: clamp(45px, 5vw, 76px);
    line-height: 1.05;
    font-weight: 800;
    margin-bottom: 25px;
}

.visual-content h1 span {
    color: var(--orange);
}

.visual-content p {
    font-size: 17px;
    line-height: 1.8;
    color: #e2e2e2;
    max-width: 480px;
    margin-bottom: 35px;
}

.explore-button {
    display: inline-flex;
    align-items: center;
    gap: 14px;

    background: var(--orange);
    color: white;

    padding: 17px 27px;
    border-radius: 50px;

    font-weight: 700;
    transition: .3s;
}

.explore-button:hover {
    background: var(--orange-light);
    transform: translateY(-3px);
}

.visual-footer {
    color: #c5c5c5;
    font-size: 12px;
    letter-spacing: 1px;
}

/* RIGHT FORM PANEL */

.form-panel {
    background: white;
    min-height: 100vh;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 35px 8%;
}

.form-content {
    width: 100%;
    max-width: 460px;
}

.home-link {
    display: inline-block;
    color: var(--orange);
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 28px;
    transition: .3s;
}

.home-link:hover {
    transform: translateX(-4px);
}

/* WELCOME HEADING */

.welcome-heading {
    text-align: center;
    margin-bottom: 30px;
}

.welcome-heading .welcome-small {
    color: var(--grey);
    font-size: 14px;
    margin-bottom: 8px;
}

.welcome-heading h2 {
    font-size: 38px;
    font-weight: 800;
    color: var(--black);
    margin-bottom: 10px;
}

.welcome-heading h2 span {
    color: var(--orange);
}

.welcome-heading p {
    color: #777;
    font-size: 14px;
    line-height: 1.7;
}

/* ERROR MESSAGE */

.error-message {
    background: #fff0ef;
    color: #c0392b;
    border: 1px solid #ffd2cd;
    padding: 13px;
    border-radius: 9px;
    margin-bottom: 18px;
    font-size: 13px;
}

/* FORM GROUPS */

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 700;
    color: #292929;
    margin-bottom: 8px;
}

.form-group input {
    width: 100%;
    height: 51px;

    padding: 0 16px;

    border: 1px solid #dedede;
    border-radius: 10px;

    background: #fafafa;
    color: #222;

    font-size: 14px;
    outline: none;

    transition: .3s;
}

.form-group input::placeholder {
    color: #999;
}

.form-group input:focus {
    border-color: var(--orange);
    background: white;

    box-shadow: 0 0 0 4px rgba(255,118,40,.10);
}

/* PASSWORD */

.password-wrapper {
    position: relative;
}

.password-wrapper input {
    padding-right: 75px;
}

.toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;

    transform: translateY(-50%);

    border: none;
    background: transparent;

    color: var(--orange);
    font-size: 12px;
    font-weight: 800;

    cursor: pointer;
}

/* REGISTER BUTTON */

.auth-button {
    width: 100%;
    height: 54px;

    border: none;
    border-radius: 10px;

    background: var(--orange);
    color: white;

    font-size: 15px;
    font-weight: 800;

    cursor: pointer;
    transition: .3s;

    margin-top: 8px;
}

.auth-button:hover {
    background: #e9651d;

    box-shadow: 0 8px 22px rgba(255,118,40,.25);

    transform: translateY(-2px);
}

/* DIVIDER */

.divider {
    display: flex;
    align-items: center;
    gap: 15px;

    margin: 24px 0;

    color: #999;
    font-size: 11px;
    text-align: center;
}

.divider::before,
.divider::after {
    content: "";
    flex: 1;
    height: 1px;
    background: #e7e7e7;
}

/* ACCOUNT SWITCH */

.auth-switch {
    text-align: center;
    color: #777;
    font-size: 14px;
    line-height: 1.8;
}

.auth-switch a {
    color: var(--orange);
    font-weight: 800;
    margin-left: 5px;
}

.auth-switch a:hover {
    text-decoration: underline;
}

.back-home-bottom {
    display: block;
    text-align: center;
    margin-top: 24px;
    color: #999;
    font-size: 12px;
}

.back-home-bottom:hover {
    color: var(--orange);
}

/* TABLETS */

@media(max-width: 950px) {

    .auth-layout {
        grid-template-columns: 42% 58%;
    }

    .visual-panel {
        padding: 35px;
    }

    .visual-content h1 {
        font-size: 48px;
    }

    .form-panel {
        padding: 30px 7%;
    }

}

/* MOBILE */

@media(max-width: 700px) {

    .auth-layout {
        display: flex;
        flex-direction: column;
    }

    .visual-panel {
        min-height: 340px;
        padding: 28px 25px;
        background-position: center;
    }

    .visual-logo {
        font-size: 25px;
    }

    .visual-content {
        margin: 45px 0 30px;
    }

    .visual-content .small-heading {
        font-size: 11px;
        letter-spacing: 3px;
        margin-bottom: 12px;
    }

    .visual-content h1 {
        font-size: 43px;
        margin-bottom: 15px;
    }

    .visual-content p {
        font-size: 14px;
        margin-bottom: 22px;
    }

    .explore-button {
        padding: 13px 21px;
        font-size: 13px;
    }

    .visual-footer {
        font-size: 10px;
    }

    .form-panel {
        min-height: auto;
        padding: 40px 24px;
    }

    .form-content {
        max-width: 480px;
    }

    .home-link {
        margin-bottom: 22px;
    }

    .welcome-heading h2 {
        font-size: 32px;
    }

}

@media(max-width: 380px) {

    .visual-content h1 {
        font-size: 36px;
    }

    .form-panel {
        padding: 35px 18px;
    }

}

</style>

</head>

<body>

<div class="auth-layout">

    <!-- LEFT VISUAL SECTION -->

    <section class="visual-panel">

        <a href="index.php" class="visual-logo">
            Dine<span>Now.</span>
        </a>

        <div class="visual-content">

            <div class="small-heading">
                BOOK. DINE. ENJOY.
            </div>

            <h1>
                Welcome to<br>
                Dine<span>Now</span>
            </h1>

            <p>
                Discover and book the best restaurants,
                enjoy delicious meals, and create
                unforgettable dining experiences.
            </p>

            <a href="restaurants.php" class="explore-button">
                Explore Restaurants <span>→</span>
            </a>

        </div>

        <div class="visual-footer">
            GOOD FOOD. GREAT MOMENTS. ONLY ON DINENOW.
        </div>

    </section>


    <!-- RIGHT REGISTRATION SECTION -->

    <section class="form-panel">

        <div class="form-content">

            <a href="index.php" class="home-link">
                ← Back to Homepage
            </a>

            <div class="welcome-heading">

                <p class="welcome-small">
                    Join the DineNow experience
                </p>

                <h2>
                    Create Your <span>Account.</span>
                </h2>

                <p>
                    Sign up today and discover amazing
                    restaurants and unforgettable meals.
                </p>

            </div>


            <?php if (!empty($error)): ?>

                <div class="error-message">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>

            <?php endif; ?>


            <form method="POST" action="">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                >


                <div class="form-group">

                    <label for="full_name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        placeholder="Enter your full name"
                        value="<?= htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') ?>"
                        maxlength="100"
                        autocomplete="name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email address"
                        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                        maxlength="150"
                        autocomplete="email"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="phone">
                        Phone Number
                    </label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        placeholder="Enter your phone number"
                        value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
                        maxlength="20"
                        autocomplete="tel"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

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

                    <label for="confirm_password">
                        Confirm Password
                    </label>

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
                    Create My Account →
                </button>

            </form>


            <div class="divider">
                YOUR DINING EXPERIENCE STARTS HERE
            </div>


            <p class="auth-switch">
                Already have an account?
                <a href="login.php">Login</a>
            </p>


            <a href="index.php" class="back-home-bottom">
                © <?= date('Y') ?> DineNow. All Rights Reserved.
            </a>

        </div>

    </section>

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