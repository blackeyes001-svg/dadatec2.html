<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/db.php";

// Redirect logged-in admins to dashboard
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin') {
    header("Location: dashboard.php");
    exit;
}

if (empty($_SESSION['admin_login_token'])) {
    $_SESSION['admin_login_token'] = bin2hex(random_bytes(32));
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST['csrf_token'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!hash_equals($_SESSION['admin_login_token'], $token)) {
        $error = "Invalid request. Please refresh the page and try again.";
    } elseif (empty($email) || empty($password)) {
        $error = "Please enter your email and password.";
    } else {

        $stmt = $pdo->prepare("
            SELECT id, full_name, email, password, role
            FROM users
            WHERE email = :email AND role = 'admin'
            LIMIT 1
        ");

        $stmt->execute(['email' => $email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['full_name'] = $admin['full_name'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['role'] = $admin['role'];

            unset($_SESSION['admin_login_token']);

            header("Location: dashboard.php");
            exit;

        } else {
            $error = "Invalid admin email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DineNow | Admin Login</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            background: #101010;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: #fff;
        }

        .login-container {
            width: 100%;
            max-width: 430px;
            background: #191919;
            padding: 40px 35px;
            border-radius: 14px;
            border: 1px solid #333;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.4);
        }

        .logo {
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .logo span {
            color: #FF7628;
        }

        .subtitle {
            text-align: center;
            color: #aaa;
            font-size: 14px;
            margin-bottom: 30px;
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 22px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #ddd;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 13px;
            background: #101010;
            border: 1px solid #444;
            border-radius: 7px;
            color: #fff;
            outline: none;
            font-size: 15px;
        }

        input:focus {
            border-color: #FF7628;
        }

        button {
            width: 100%;
            padding: 14px;
            background: #FF7628;
            color: #fff;
            border: none;
            border-radius: 7px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #e65f12;
        }

        .error {
            background: #3a1717;
            color: #ff7777;
            border: 1px solid #773333;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .back-home {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: #FF7628;
            text-decoration: none;
            font-size: 14px;
        }

        .back-home:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 22px;
            }
        }
    </style>
</head>

<body>

<div class="login-container">

    <div class="logo">Dine<span>Now.</span></div>

    <p class="subtitle">Restaurant Management System</p>

    <h2>Admin Login</h2>

    <?php if (!empty($error)): ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION['admin_login_token']); ?>"
        >

        <div class="form-group">
            <label for="email">Admin Email</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter admin email"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter admin password"
                required
            >
        </div>

        <button type="submit">Login to Dashboard</button>

    </form>

    <a href="../index.php" class="back-home">← Back to Homepage</a>

</div>

</body>
</html>