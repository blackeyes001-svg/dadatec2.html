<?php
// Secure session configuration
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

$isSecure = (
    isset($_SERVER['HTTPS']) &&
    $_SERVER['HTTPS'] !== 'off'
);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

require_once __DIR__ . '/includes/db.php';

// Redirect already logged-in users
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Generate CSRF token
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

            try {

                $stmt = $pdo->prepare(
                    'SELECT id, full_name, email, password, role
                     FROM users
                     WHERE email = :email
                     LIMIT 1'
                );

                $stmt->execute(['email' => $email]);

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (
                    $user &&
                    password_verify($password, $user['password'])
                ) {

                    // Regenerate session ID
                    session_regenerate_id(true);

                    // Store user information
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];

                    // Generate fresh CSRF token
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    // Upgrade old password hashes
                    if (
                        password_needs_rehash(
                            $user['password'],
                            PASSWORD_DEFAULT
                        )
                    ) {

                        $newHash = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        $update = $pdo->prepare(
                            'UPDATE users
                             SET password = :password
                             WHERE id = :id'
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

            } catch (PDOException $e) {

                error_log('Login error: ' . $e->getMessage());

                $error = 'Unable to sign in right now. Please try again later.';

            }
        }
    }
}

// Escape output
function e($value) {
    return htmlspecialchars(
        (string)$value,
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

<title>Sign In | Bayogoal Enterprises</title>

<meta name="description" content="Sign in to your Bayogoal Enterprises account.">

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
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: var(--dark);
    color: var(--white);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* HEADER */

.login-header {
    background: var(--panel);
    padding: 20px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    border-bottom: 2px solid var(--blue);
}

.brand {
    color: var(--white);
    text-decoration: none;
    font-size: 24px;
    font-weight: 900;
}

.brand span {
    color: var(--green);
}

.brand small {
    display: block;
    font-size: 10px;
    letter-spacing: 4px;
    color: var(--muted);
    text-align: center;
}

.back {
    color: var(--white);
    text-decoration: none;
    font-weight: bold;
}

.back:hover {
    color: var(--green);
}

/* MAIN */

.login-main {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 45px 16px;
}

/* LOGIN CARD */

.auth-card {
    background: var(--panel);
    width: 100%;
    max-width: 450px;
    padding: 38px;
    border-radius: 16px;
    border: 1px solid var(--border);
    box-shadow: 0 15px 45px rgba(0, 0, 0, 0.25);
}

.logo-mark {
    width: 65px;
    height: 65px;
    border-radius: 15px;
    background: var(--dark);
    border: 1px solid var(--green);
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--green);
    font-size: 25px;
    font-weight: 900;
    margin-bottom: 22px;
}

.auth-card h1 {
    font-size: 29px;
    margin-bottom: 10px;
}

.intro {
    color: var(--muted);
    line-height: 1.7;
    margin-bottom: 28px;
}

/* FORM */

.field {
    margin-bottom: 21px;
}

.field label {
    display: block;
    font-weight: bold;
    margin-bottom: 9px;
}

.field input {
    width: 100%;
    padding: 14px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--dark);
    color: var(--white);
    font-size: 15px;
}

.field input:focus {
    outline: 2px solid var(--green);
    border-color: var(--green);
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
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: var(--green);
    cursor: pointer;
    font-weight: bold;
    font-size: 12px;
    padding: 8px;
}

/* BUTTON */

.submit-btn {
    width: 100%;
    padding: 15px;
    background: var(--blue);
    color: var(--white);
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
    margin-top: 8px;
}

.submit-btn:hover {
    background: var(--green);
    color: var(--dark);
}

/* MESSAGES */

.error {
    background: #451b25;
    border: 1px solid #ef4444;
    color: #fecaca;
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 20px;
    line-height: 1.5;
}

.success {
    background: #183b2b;
    border: 1px solid var(--green);
    color: #bbf7d0;
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 20px;
    line-height: 1.5;
}

/* REGISTER LINK */

.switch {
    text-align: center;
    margin-top: 25px;
    line-height: 1.8;
    color: var(--muted);
}

.switch a {
    color: var(--green);
    font-weight: bold;
    text-decoration: none;
}

.switch a:hover {
    text-decoration: underline;
}

/* FOOTER */

.login-footer {
    background: var(--panel);
    border-top: 1px solid var(--border);
    color: var(--muted);
    text-align: center;
    padding: 20px;
    font-size: 14px;
}

.login-footer span {
    color: var(--green);
    font-weight: bold;
}

/* MOBILE */

@media (max-width: 500px) {

    .login-header {
        padding: 17px 5%;
    }

    .brand {
        font-size: 19px;
    }

    .back {
        font-size: 13px;
    }

    .login-main {
        padding: 30px 14px;
    }

    .auth-card {
        padding: 27px 21px;
    }

    .auth-card h1 {
        font-size: 25px;
    }

}

</style>

</head>

<body>

<header class="login-header">

    <a class="brand" href="index.php">
        BAYO<span>GOAL</span>
        <small>ENTERPRISES</small>
    </a>

    <a class="back" href="index.php">
        &larr; Back to Home
    </a>

</header>

<main class="login-main">

    <section class="auth-card">

        <div class="logo-mark">BG</div>

        <h1>Welcome Back!</h1>

        <p class="intro">
            Sign in to your Bayogoal Enterprises account
            and continue shopping for your favourite sports equipment.
        </p>

        <?php if (isset($_GET['registered'])): ?>

            <div class="success" role="status">
                Account created successfully!
                You can now sign in.
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
                    placeholder="Enter your email address"
                    required
                >

            </div>

            <div class="field">

                <label for="password">Password</label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        id="togglePassword"
                        aria-label="Show password"
                    >
                        SHOW
                    </button>

                </div>

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

<footer class="login-footer">

    &copy; <?= date('Y') ?>

    <span>Bayogoal Enterprises</span>.

    Gear Up. Play Hard. Win Big.

</footer>

<script>

const passwordInput = document.getElementById('password');

const togglePassword = document.getElementById('togglePassword');

togglePassword.addEventListener('click', function () {

    if (passwordInput.type === 'password') {

        passwordInput.type = 'text';

        togglePassword.textContent = 'HIDE';

        togglePassword.setAttribute('aria-label', 'Hide password');

    } else {

        passwordInput.type = 'password';

        togglePassword.textContent = 'SHOW';

        togglePassword.setAttribute('aria-label', 'Show password');

    }

});

</script>

</body>
</html>