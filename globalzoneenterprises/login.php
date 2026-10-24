<?php
session_start();

require_once __DIR__ . '/includes/db.php';

// Redirect an already signed-in customer to the homepage.
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $error = 'Your session has expired. Refresh the page and try again.';
    } else {

        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($email) > 150 ||
            !is_string($password) ||
            $password === '' ||
            strlen($password) > 4096
        ) {
            $error = 'Invalid email or password.';
        } else {

            $stmt = $pdo->prepare(
                'SELECT id, full_name, email, password, role
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );

            $stmt->execute(['email' => $email]);

            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {

                // Replace the session ID to help prevent session fixation.
                session_regenerate_id(true);

                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // Generate a fresh CSRF token for the authenticated session.
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                // Upgrade older password hashes when necessary.
                if (password_needs_rehash(
                    $user['password'],
                    PASSWORD_DEFAULT
                )) {
                    $newHash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $update = $pdo->prepare(
                        'UPDATE users SET password = :password WHERE id = :id'
                    );

                    $update->execute([
                        'password' => $newHash,
                        'id' => $user['id']
                    ]);
                }

                header('Location: index.php');
                exit;

            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign In | Global Zone Sport Enterprise</title>

    <style>
        :root {
            --blue: #2563EB;
            --green: #39FF14;
            --dark: #080B12;
            --white: #FFFFFF;
            --muted: #667085;
            --background: #F4F7FC;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: var(--background);
            color: #172033;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: var(--dark);
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .brand {
            color: var(--white);
            text-decoration: none;
            font-size: 20px;
            font-weight: 800;
        }

        .brand span {
            color: var(--green);
        }

        header a.back {
            color: var(--white);
            text-decoration: none;
        }

        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 16px;
        }

        .auth-card {
            background: var(--white);
            width: 100%;
            max-width: 440px;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
        }

        .auth-card h1 {
            font-size: 29px;
            margin-bottom: 10px;
        }

        .intro {
            color: var(--muted);
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .field {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #D0D5DD;
            border-radius: 8px;
            font-size: 15px;
        }

        input:focus {
            outline: 2px solid var(--blue);
            border-color: var(--blue);
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: var(--blue);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }

        .submit-btn:hover {
            background: #1D4ED8;
        }

        .error {
            background: #FEF2F2;
            color: #991B1B;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success {
            background: #ECFDF3;
            color: #166534;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .switch {
            text-align: center;
            margin-top: 22px;
            line-height: 1.7;
        }

        .switch a {
            color: var(--blue);
            font-weight: 700;
        }

        footer {
            background: var(--dark);
            color: #D1D5DB;
            text-align: center;
            padding: 18px;
            font-size: 14px;
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: 24px 20px;
            }

            .auth-card h1 {
                font-size: 25px;
            }
        }
    </style>
</head>

<body>

<header>
    <a class="brand" href="index.php">
        GLOBAL <span>ZONE</span>
    </a>

    <a class="back" href="index.php">← Back to Home</a>
</header>

<main>
    <section class="auth-card">

        <h1>Welcome Back!</h1>

        <p class="intro">
            Sign in to your Global Zone Sport Enterprise account.
        </p>

        <?php if (isset($_GET['registered'])): ?>
            <div class="success" role="status">
                Account created successfully! You can now sign in.
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="error" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token']) ?>"
            >

            <div class="field">
                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="150"
                    autocomplete="email"
                    value="<?= e($email) ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button class="submit-btn" type="submit">
                Sign In
            </button>

        </form>

        <p class="switch">
            Don't have an account?
            <a href="register.php">Create Account</a>
        </p>

    </section>
</main>

<footer>
    &copy; <?= date('Y') ?> Global Zone Sport Enterprise.
    Gear Up. Play Hard. Win Big.
</footer>

</body>
</html>