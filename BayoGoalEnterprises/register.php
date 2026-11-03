
<?php
session_start();

require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';
$full_name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (
        $full_name === '' ||
        $email === '' ||
        $password === '' ||
        $confirm_password === ''
    ) {
        $error = 'Please fill in all fields.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';

    } elseif (strlen($password) < 8) {
        $error = 'Your password must contain at least 8 characters.';

    } elseif ($password !== $confirm_password) {
        $error = 'Your passwords do not match.';

    } else {
        try {
            // Check whether the email is already registered.
            $check = $pdo->prepare(
                'SELECT id FROM users WHERE email = ? LIMIT 1'
            );

            $check->execute([$email]);

            if ($check->fetch()) {
                $error = 'This email is already registered. Please log in.';

            } else {
                // Securely hash the password.
                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Create the customer account.
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
            }

        } catch (PDOException $e) {
            error_log('Registration error: ' . $e->getMessage());
            $error = 'Registration failed. Please check your database setup.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account | Global Zone Sport Enterprise</title>

    <style>
        /* =========================
           GENERAL STYLES
        ========================= */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #080d18;
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 25px 15px;
        }

        /* =========================
           REGISTRATION CONTAINER
        ========================= */

        .register-container {
            width: 100%;
            max-width: 460px;
            background: #111827;
            border: 1px solid #263449;
            border-radius: 18px;
            padding: 35px;
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.4);
        }

        /* =========================
           BRAND
        ========================= */

        .brand {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-icon {
            width: 65px;
            height: 65px;
            margin: 0 auto 15px;
            border-radius: 15px;
            background: #2563eb;
            color: #39ff14;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            font-weight: bold;
        }

        .brand h1 {
            font-size: 22px;
            margin-bottom: 8px;
        }

        .brand p {
            color: #a8b3c7;
            font-size: 13px;
        }

        /* =========================
           FORM HEADING
        ========================= */

        .form-heading {
            margin-bottom: 24px;
        }

        .form-heading h2 {
            font-size: 23px;
            margin-bottom: 8px;
        }

        .form-heading p {
            font-size: 14px;
            color: #a8b3c7;
            line-height: 1.5;
        }

        /* =========================
           FORM FIELDS
        ========================= */

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #35445b;
            border-radius: 9px;
            background: #0b1220;
            color: #ffffff;
            font-size: 14px;
            outline: none;
        }

        input:focus {
            border-color: #39ff14;
            box-shadow: 0 0 0 3px rgba(57, 255, 20, 0.1);
        }

        input::placeholder {
            color: #7f8ba0;
        }

        /* =========================
           PASSWORD VISIBILITY
        ========================= */

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 70px;
        }

        .show-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #39ff14;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }

        /* =========================
           SUBMIT BUTTON
        ========================= */

        .register-button {
            width: 100%;
            padding: 14px;
            margin-top: 5px;
            border: none;
            border-radius: 9px;
            background: #2563eb;
            color: #ffffff;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }

        .register-button:hover {
            background: #1d4ed8;
        }

        /* =========================
           SUCCESS AND ERROR MESSAGES
        ========================= */

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 14px;
            line-height: 1.5;
        }

        .error {
            background: #451a1a;
            color: #fecaca;
            border: 1px solid #7f1d1d;
        }

        .success {
            background: #12351b;
            color: #b7ffb0;
            border: 1px solid #226b30;
        }

        /* =========================
           PAGE LINKS
        ========================= */

        .login-link {
            text-align: center;
            margin-top: 23px;
            color: #a8b3c7;
            font-size: 14px;
        }

        .login-link a {
            color: #39ff14;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .home-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #a8b3c7;
            font-size: 13px;
            text-decoration: none;
        }

        .home-link:hover {
            color: #ffffff;
        }

        /* =========================
           MOBILE RESPONSIVENESS
        ========================= */

        @media (max-width: 480px) {
            .register-container {
                padding: 25px 20px;
            }

            .brand h1 {
                font-size: 19px;
            }
        }
    </style>
</head>

<body>

    <main class="register-container">

        <div class="brand">
            <div class="brand-icon">GZ</div>

            <h1>GLOBAL ZONE SPORT</h1>

            <p>Gear Up. Play Hard. Win Big.</p>
        </div>

        <div class="form-heading">
            <h2>Create Your Account</h2>

            <p>
                Join Global Zone Sport Enterprise and get ready for the game.
            </p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="message error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="message success">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                <br><br>
                <a href="login.php" style="color: #39ff14;">
                    Continue to Login
                </a>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">

            <div class="form-group">
                <label for="full_name">Full Name</label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    placeholder="Enter your full name"
                    value="<?= htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="name"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email address"
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>

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
                        onclick="togglePassword('password', this)"
                    >
                        Show
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>

                <div class="password-wrapper">
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Enter your password again"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="button"
                        class="show-password"
                        onclick="togglePassword('confirm_password', this)"
                    >
                        Show
                    </button>
                </div>
            </div>

            <button type="submit" class="register-button">
                Create Account
            </button>

        </form>

        <p class="login-link">
            Already have an account?
            <a href="login.php">Log In</a>
        </p>

        <a href="index.php" class="home-link">
            &larr; Back to Home Page
        </a>

    </main>

    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);

            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = 'Hide';
            } else {
                input.type = 'password';
                button.textContent = 'Show';
            }
        }
    </script>

</body>
</html>
```