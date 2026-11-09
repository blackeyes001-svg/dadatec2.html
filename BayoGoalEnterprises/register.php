<?php
require_once __DIR__ . '/includes/session.php';

require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';

$full_name = '';
$email = '';

// Generate CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $error = 'Your session has expired. Refresh the page and try again.';

    } else {

        $full_name = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (
            $full_name === '' ||
            $email === '' ||
            $password === '' ||
            $confirm_password === ''
        ) {
            $error = 'Please fill in all fields.';

        } elseif (strlen($full_name) > 150) {
            $error = 'Your name is too long.';

        } elseif (
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($email) > 150
        ) {
            $error = 'Please enter a valid email address.';

        } elseif (!is_string($password) || strlen($password) < 8) {
            $error = 'Your password must contain at least 8 characters.';

        } elseif (strlen($password) > 4096) {
            $error = 'Your password is too long.';

        } elseif ($password !== $confirm_password) {
            $error = 'Your passwords do not match.';

        } else {

            try {

                // Check whether email already exists.
                $check = $pdo->prepare(
                    'SELECT id FROM users WHERE email = ? LIMIT 1'
                );

                $check->execute([$email]);

                if ($check->fetch()) {

                    $error = 'This email is already registered. Please log in.';

                } else {

                    // Securely hash password.
                    $hashed_password = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    // Create customer account.
                    $insert = $pdo->prepare(
                        'INSERT INTO users (full_name, email, password, role)
                         VALUES (?, ?, ?, ?)'
                    );

                    $insert->execute([
                        $full_name,
                        $email,
                        $hashed_password,
                        'customer'
                    ]);

                    $success = 'Account created successfully! You can now log in.';

                    $full_name = '';
                    $email = '';

                    // Generate a fresh CSRF token.
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                }

            } catch (PDOException $e) {

                error_log('Bayogoal registration error: ' . $e->getMessage());

                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

// Escape output.
function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account | Bayogoal Enterprises</title>

    <meta name="description" content="Create your Bayogoal Enterprises customer account.">

    <style>

        :root {
            --blue: #2563EB;
            --green: #39FF14;
            --dark: #071426;
            --panel: #10233d;
            --white: #ffffff;
            --muted: #aabbd0;
            --border: #294568;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: var(--dark);
            color: var(--white);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 15px;
        }

        /* REGISTRATION CONTAINER */

        .register-container {
            width: 100%;
            max-width: 470px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.3);
        }

        /* BRAND */

        .brand {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-icon {
            width: 65px;
            height: 65px;
            margin: 0 auto 15px;
            border-radius: 15px;
            background: var(--dark);
            border: 1px solid var(--green);
            color: var(--green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            font-weight: bold;
        }

        .brand h1 {
            font-size: 23px;
            margin-bottom: 8px;
        }

        .brand h1 span {
            color: var(--green);
        }

        .brand p {
            color: var(--muted);
            font-size: 13px;
        }

        /* FORM HEADING */

        .form-heading {
            margin-bottom: 25px;
        }

        .form-heading h2 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .form-heading p {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.6;
        }

        /* FORM FIELDS */

        .form-group {
            margin-bottom: 19px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--dark);
            color: var(--white);
            font-size: 15px;
        }

        .form-group input:focus {
            outline: 2px solid var(--green);
            border-color: var(--green);
        }

        .form-group input::placeholder {
            color: #8190a6;
        }

        /* PASSWORD VISIBILITY */

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 75px;
        }

        .show-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: var(--green);
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            padding: 8px;
        }

        /* REGISTER BUTTON */

        .register-button {
            width: 100%;
            padding: 15px;
            margin-top: 5px;
            border: none;
            border-radius: 8px;
            background: var(--blue);
            color: var(--white);
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .register-button:hover {
            background: var(--green);
            color: var(--dark);
        }

        /* MESSAGES */

        .message {
            padding: 13px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }

        .error {
            background: #451b25;
            color: #fecaca;
            border: 1px solid #ef4444;
        }

        .success {
            background: #183b2b;
            color: #bbf7d0;
            border: 1px solid var(--green);
        }

        .success a {
            color: var(--green);
            font-weight: bold;
            text-decoration: none;
        }

        /* LOGIN LINK */

        .login-link {
            text-align: center;
            margin-top: 24px;
            color: var(--muted);
            font-size: 14px;
        }

        .login-link a {
            color: var(--green);
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* HOME LINK */

        .home-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--muted);
            font-size: 13px;
            text-decoration: none;
        }

        .home-link:hover {
            color: var(--green);
        }

        /* MOBILE */

        @media (max-width: 480px) {

            .register-container {
                padding: 27px 20px;
            }

            .brand h1 {
                font-size: 20px;
            }

            .form-heading h2 {
                font-size: 22px;
            }

        }

    </style>

</head>

<body>

<main class="register-container">

    <!-- BRAND -->

    <div class="brand">

        <div class="brand-icon">
            BG
        </div>

        <h1>
            BAYO<span>GOAL</span> ENTERPRISES
        </h1>

        <p>
            Gear Up. Play Hard. Win Big.
        </p>

    </div>

    <!-- FORM HEADING -->

    <div class="form-heading">

        <h2>Create Your Account</h2>

        <p>
            Join Bayogoal Enterprises and get ready
            for your favourite sports activities.
        </p>

    </div>

    <!-- ERROR MESSAGE -->

    <?php if ($error !== ''): ?>

        <div class="message error" role="alert">
            <?= e($error) ?>
        </div>

    <?php endif; ?>

    <!-- SUCCESS MESSAGE -->

    <?php if ($success !== ''): ?>

        <div class="message success" role="status">

            <?= e($success) ?>

            <br><br>

            <a href="login.php?registered=1">
                Continue to Login &rarr;
            </a>

        </div>

    <?php endif; ?>

    <!-- REGISTRATION FORM -->

    <form action="register.php" method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($_SESSION['csrf_token']) ?>"
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
                value="<?= e($full_name) ?>"
                maxlength="150"
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
                value="<?= e($email) ?>"
                maxlength="150"
                autocomplete="email"
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
                    placeholder="At least 8 characters"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >

                <button
                    type="button"
                    class="show-password"
                    data-target="password"
                    aria-label="Show password"
                >
                    SHOW
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
                    placeholder="Enter your password again"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >

                <button
                    type="button"
                    class="show-password"
                    data-target="confirm_password"
                    aria-label="Show confirm password"
                >
                    SHOW
                </button>

            </div>

        </div>

        <button type="submit" class="register-button">
            Create Account
        </button>

    </form>

    <!-- LOGIN LINK -->

    <p class="login-link">

        Already have an account?

        <a href="login.php">
            Log In
        </a>

    </p>

    <!-- HOME LINK -->

    <a href="index.php" class="home-link">
        &larr; Back to Home Page
    </a>

</main>

<script>

document.querySelectorAll('.show-password').forEach(function(button) {

    button.addEventListener('click', function() {

        const input = document.getElementById(
            button.dataset.target
        );

        if (input.type === 'password') {

            input.type = 'text';
            button.textContent = 'HIDE';
            button.setAttribute('aria-label', 'Hide password');

        } else {

            input.type = 'password';
            button.textContent = 'SHOW';
            button.setAttribute('aria-label', 'Show password');

        }

    });

});

</script>

</body>
</html>