<?php
session_start();

require_once "../includes/db.php";

// Protect the dashboard
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Get dashboard statistics
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

$totalCustomers = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'customer'"
)->fetchColumn();

$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

$totalRevenue = $pdo->query(
    "SELECT COALESCE(SUM(total_amount), 0) FROM orders"
)->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Global Zone Sports</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #071426;
            color: white;
        }

        .sidebar {
            width: 240px;
            height: 100vh;
            background: #10233d;
            position: fixed;
            padding: 25px 15px;
        }

        .sidebar h2 {
            color: #39FF14;
            text-align: center;
            margin-bottom: 35px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 14px;
            margin-bottom: 10px;
            border-radius: 6px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #2563EB;
        }

        .main {
            margin-left: 240px;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .logout {
            background: #dc2626;
            color: white;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 6px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .card {
            background: #10233d;
            padding: 25px;
            border-radius: 10px;
            border-left: 4px solid #2563EB;
        }

        .card h3 {
            color: #aaa;
            font-size: 15px;
            margin-bottom: 15px;
        }

        .card p {
            font-size: 28px;
            font-weight: bold;
            color: #39FF14;
        }

        @media(max-width: 650px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .sidebar h2 {
                margin-bottom: 15px;
            }

            .sidebar a {
                display: inline-block;
                padding: 10px;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <h2>GLOBAL ZONE</h2>

        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="products.php">Manage Products</a>
        <a href="orders.php">Manage Orders</a>
        <a href="../index.php">View Website</a>
    </aside>

    <main class="main">

        <div class="topbar">
            <div>
                <h1>Admin Dashboard</h1>
                <p>Welcome, <?= htmlspecialchars($_SESSION['admin_name']) ?>!</p>
            </div>

            <a href="logout.php" class="logout">Logout</a>
        </div>

        <div class="cards">

            <div class="card">
                <h3>Total Products</h3>
                <p><?= number_format((int)$totalProducts) ?></p>
            </div>

            <div class="card">
                <h3>Total Customers</h3>
                <p><?= number_format((int)$totalCustomers) ?></p>
            </div>

            <div class="card">
                <h3>Total Orders</h3>
                <p><?= number_format((int)$totalOrders) ?></p>
            </div>

            <div class="card">
                <h3>Total Revenue</h3>
                <p>₦<?= number_format((float)$totalRevenue, 2) ?></p>
            </div>

        </div>

    </main>

</body>
</html>