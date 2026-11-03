<?php
session_start();
require_once "../includes/db.php";

$error = "";

// Redirect logged-in administrators
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Generate CSRF token
if (empty($_SESSION['admin_login_token'])) {
    $_SESSION['admin_login_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = strtolower(trim($_POST['email'] ?? ""));
    $password = $_POST['password'] ?? "";
    $csrfToken = $_POST['csrf_token'] ?? "";

    if (
        empty($csrfToken) ||
        !hash_equals($_SESSION['admin_login_token'], $csrfToken)
    ) {
        $error = "Invalid request. Please refresh the page and try again.";

    } elseif (empty($email) || empty($password)) {
        $error = "Please enter your email and password.";

    } else {

        try {
            $stmt = $pdo->prepare(
                "SELECT id, full_name, email, password
                 FROM users
                 WHERE email = ? AND role = 'admin'
                 LIMIT 1"
            );

            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password'])) {

                session_regenerate_id(true);

                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];

                // Refresh CSRF token
                $_SESSION['admin_login_token'] = bin2hex(random_bytes(32));

                header("Location: dashboard.php");
                exit;

            } else {
                $error = "Invalid admin email or password.";
            }

        } catch (PDOException $e) {
            error_log("Admin login error: " . $e->getMessage());
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

    <title>Admin Login | Bayogoal Enterprises</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            padding: 20px;

            display: flex;
            align-items: center;
            justify-content: center;

            background:
                radial-gradient(
                    circle at top right,
                    rgba(37, 99, 235, 0.18),
                    transparent 40%
                ),
                #071426;

            color: #ffffff;
            font-family: Arial, Helvetica, sans-serif;
        }

        .login-box {
            width: 100%;
            max-width: 430px;
            padding: 38px;

            background: #10233d;

            border: 1px solid rgba(37, 99, 235, 0.65);
            border-radius: 14px;

            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.35);
        }

        .brand {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-icon {
            width: 65px;
            height: 65px;
            margin: 0 auto 15px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #2563EB;
            color: #ffffff;

            border: 2px solid #39FF14;
            border-radius: 14px;

            font-size: 23px;
            font-weight: 900;
        }

        .brand h1 {
            font-size: 25px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .brand h1 span {
            color: #39FF14;
        }

        .subtitle {
            margin-top: 9px;
            color: #aebbd0;
            font-size: 13px;
        }

        .login-heading {
            margin-bottom: 22px;
            text-align: center;
        }

        .login-heading h2 {
            font-size: 20px;
            margin-bottom: 7px;
        }

        .login-heading p {
            color: #aebbd0;
            font-size: 12px;
        }

        .form-group {
            margin-bottom: 19px;
        }

        label {
            display: block;
            margin-bottom: 8px;

            color: #e2e8f0;
            font-size: 12px;
            font-weight: bold;
        }

        input {
            width: 100%;
            min-height: 48px;
            padding: 13px 14px;

            background: #071426;
            color: #ffffff;

            border: 1px solid #334155;
            border-radius: 6px;

            outline: none;
            font-size: 14px;
        }

        input:focus {
            border-color: #39FF14;
            box-shadow: 0 0 0 3px rgba(57, 255, 20, 0.1);
        }

        input::placeholder {
            color: #8290a5;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 65px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);

            background: transparent;
            border: none;

            color: #39FF14;
            font-size: 11px;
            font-weight: bold;

            cursor: pointer;
        }

        .login-button {
            width: 100%;
            min-height: 49px;
            margin-top: 8px;

            background: #2563EB;
            color: #ffffff;

            border: none;
            border-radius: 6px;

            font-size: 13px;
            font-weight: 900;

            cursor: pointer;
            transition: 0.3s;
        }

        .login-button:hover {
            background: #39FF14;
            color: #071426;
        }

        .error {
            margin-bottom: 20px;
            padding: 13px;

            background: rgba(220, 38, 38, 0.18);
            color: #fca5a5;

            border: 1px solid #991b1b;
            border-radius: 6px;

            text-align: center;
            font-size: 12px;
        }

        .back-link {
            display: block;
            margin-top: 25px;

            color: #39FF14;
            text-align: center;
            text-decoration: none;

            font-size: 12px;
            font-weight: bold;
        }

        .back-link:hover {
            color: #ffffff;
        }

        .security-note {
            margin-top: 25px;
            padding-top: 18px;

            border-top: 1px solid #334155;

            color: #8290a5;
            text-align: center;
            font-size: 10px;
            line-height: 1.7;
        }

        @media (max-width: 480px) {
            .login-box {
                padding: 27px 21px;
            }

            .brand h1 {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

    <div class="login-box">

        <div class="brand">

            <div class="brand-icon">BG</div>

            <h1>BAYO<span>GOAL</span></h1>

            <p class="subtitle">ENTERPRISES</p>

        </div>

        <div class="login-heading">

            <h2>Administrator Login</h2>

            <p>Sign in to manage your sports store.</p>

        </div>

        <?php if (!empty($error)): ?>

            <div class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['admin_login_token'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <div class="form-group">

                <label for="email">Admin Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your admin email"
                    autocomplete="username"
                    value="<?= htmlspecialchars(
                        $_POST['email'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">Admin Password</label>

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
                        id="togglePassword"
                    >
                        SHOW
                    </button>

                </div>

            </div>

            <button type="submit" class="login-button">
                LOGIN TO DASHBOARD →
            </button>

        </form>

        <a href="../index.php" class="back-link">
            ← Back to Bayogoal Website
        </a>

        <div class="security-note">
            Restricted access. Only authorized administrators can enter
            this dashboard.
            <br>
            &copy; <?= date("Y") ?> Bayogoal Enterprises.
        </div>

    </div>

    <script>
        const passwordInput = document.getElementById("password");
        const togglePassword = document.getElementById("togglePassword");

        togglePassword.addEventListener("click", function () {

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                togglePassword.textContent = "HIDE";
            } else {
                passwordInput.type = "password";
                togglePassword.textContent = "SHOW";
            }

        });
    </script>

</body>
</html>