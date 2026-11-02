<?php
session_start();
require_once "../includes/db.php";

$error = "";

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1"
        );

        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {

            session_regenerate_id(true);

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];

            header("Location: dashboard.php");
            exit;

        } else {
            $error = "Invalid admin email or password.";
        }

    } catch (PDOException $e) {
        $error = "Something went wrong. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Global Zone Sports</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #071426;
            color: white;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-box {
            background: #10233d;
            width: 100%;
            max-width: 420px;
            padding: 35px;
            border-radius: 12px;
            border: 1px solid #2563EB;
        }

        h2 {
            text-align: center;
            margin-bottom: 10px;
        }

        .subtitle {
            text-align: center;
            color: #aaa;
            margin-bottom: 25px;
        }

        input {
            width: 100%;
            padding: 14px;
            margin-bottom: 18px;
            border: 1px solid #334155;
            border-radius: 6px;
            background: #071426;
            color: white;
        }

        button {
            width: 100%;
            padding: 14px;
            background: #2563EB;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .error {
            background: #7f1d1d;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 18px;
            text-align: center;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #39FF14;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="login-box">

        <h2>GLOBAL ZONE</h2>
        <p class="subtitle">Administrator Login</p>

        <?php if (!empty($error)): ?>
            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <input
                type="email"
                name="email"
                placeholder="Admin Email"
                required
            >

            <input
                type="password"
                name="password"
                placeholder="Admin Password"
                required
            >

            <button type="submit">Login to Dashboard</button>

        </form>

        <a href="../index.php" class="back">← Back to Website</a>

    </div>

</body>
</html>