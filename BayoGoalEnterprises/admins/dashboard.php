?variant="standard" id="82519" title="Upgraded Bayogoal Admin Dashboard"}

<?php
session_start();

require_once "../includes/db.php";

// Protect dashboard
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Dashboard statistics
$totalProducts = $pdo->query(
    "SELECT COUNT(*) FROM products"
)->fetchColumn();

$totalCustomers = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'customer'"
)->fetchColumn();

$totalOrders = $pdo->query(
    "SELECT COUNT(*) FROM orders"
)->fetchColumn();

$totalRevenue = $pdo->query(
    "SELECT COALESCE(SUM(total_amount), 0) FROM orders"
)->fetchColumn();

// Fetch latest five orders
$recentOrders = $pdo->query("
    SELECT
        o.id,
        o.total_amount,
        o.status,
        o.created_at,
        u.full_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$adminName = $_SESSION['admin_name'] ?? 'Administrator';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Dashboard | Bayogoal Enterprises</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #071426;
    color: #ffffff;
    min-height: 100vh;
}

.sidebar {
    width: 250px;
    min-height: 100vh;
    background: #10233d;
    position: fixed;
    top: 0;
    left: 0;
    padding: 30px 18px;
    border-right: 1px solid #203b5c;
}

.brand {
    text-align: center;
    margin-bottom: 40px;
}

.brand h2 {
    color: #ffffff;
    font-size: 25px;
    letter-spacing: 1px;
}

.brand span {
    color: #39FF14;
}

.brand p {
    color: #9ca3af;
    font-size: 11px;
    margin-top: 7px;
    letter-spacing: 2px;
}

.sidebar a {
    display: block;
    color: #d1d5db;
    text-decoration: none;
    padding: 14px 15px;
    margin-bottom: 10px;
    border-radius: 7px;
    transition: 0.3s;
    font-size: 14px;
}

.sidebar a:hover,
.sidebar a.active {
    background: #2563EB;
    color: #ffffff;
}

.sidebar .website-link {
    margin-top: 25px;
    border: 1px solid #334155;
}

.main {
    margin-left: 250px;
    padding: 35px;
    min-width: 0;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 35px;
    flex-wrap: wrap;
}

.welcome h1 {
    font-size: 28px;
    margin-bottom: 10px;
}

.welcome p {
    color: #9ca3af;
    font-size: 14px;
}

.welcome strong {
    color: #39FF14;
}

.logout {
    background: #dc2626;
    color: #ffffff;
    padding: 12px 22px;
    text-decoration: none;
    border-radius: 7px;
    font-size: 14px;
    transition: 0.3s;
}

.logout:hover {
    background: #b91c1c;
}

.section-heading {
    margin-bottom: 20px;
}

.section-heading h2 {
    font-size: 20px;
    margin-bottom: 7px;
}

.section-heading p {
    color: #9ca3af;
    font-size: 13px;
}

.cards {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 35px;
}

.card {
    background: #10233d;
    padding: 25px;
    border-radius: 12px;
    border-left: 4px solid #2563EB;
    transition: 0.3s;
    min-width: 0;
}

.card:hover {
    transform: translateY(-4px);
}

.card h3 {
    color: #9ca3af;
    font-size: 14px;
    font-weight: normal;
    margin-bottom: 18px;
}

.card p {
    font-size: 27px;
    font-weight: bold;
    color: #39FF14;
    overflow-wrap: anywhere;
}

.orders-card {
    background: #10233d;
    padding: 25px;
    border-radius: 12px;
    border: 1px solid #203b5c;
    margin-top: 25px;
}

.orders-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.orders-heading h2 {
    font-size: 20px;
}

.orders-heading a {
    color: #39FF14;
    text-decoration: none;
    font-size: 13px;
}

.orders-heading a:hover {
    text-decoration: underline;
}

.table-wrapper {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 650px;
}

th, td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #304968;
}

th {
    color: #39FF14;
    font-size: 13px;
    text-transform: uppercase;
}

td {
    color: #e5e7eb;
    font-size: 14px;
}

tr:hover td {
    background: rgba(37, 99, 235, 0.08);
}

.status {
    text-transform: capitalize;
    font-weight: bold;
}

.empty {
    text-align: center;
    padding: 30px;
    color: #9ca3af;
}

.dashboard-footer {
    margin-top: 45px;
    padding-top: 20px;
    border-top: 1px solid #24364d;
    color: #64748b;
    font-size: 13px;
    text-align: center;
}

@media (max-width: 1100px) {
    .cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .sidebar {
        width: 100%;
        min-height: auto;
        position: relative;
        padding: 20px 15px;
    }

    .brand {
        margin-bottom: 20px;
    }

    .sidebar a {
        display: inline-block;
        padding: 11px;
        margin: 4px 2px;
        font-size: 13px;
    }

    .sidebar .website-link {
        margin-top: 5px;
    }

    .main {
        margin-left: 0;
        padding: 22px 15px;
    }

    .welcome h1 {
        font-size: 23px;
    }
}

@media (max-width: 450px) {
    .cards {
        grid-template-columns: 1fr;
    }

    .card {
        padding: 22px;
    }

    .card p {
        font-size: 25px;
    }
}
</style>
</head>

<body>

<aside class="sidebar">

    <div class="brand">
        <h2>BAYO<span>GOAL</span></h2>
        <p>ENTERPRISES</p>
    </div>

    <nav>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="products.php">Manage Products</a>
        <a href="orders.php">Manage Orders</a>
        <a href="customers.php">Manage Customers</a>
        <a href="../index.php" class="website-link" target="_blank">View Website</a>
        <a href="logout.php">Logout</a>
    </nav>

</aside>

<main class="main">

    <div class="topbar">

        <div class="welcome">
            <h1>Admin Dashboard</h1>

            <p>
                Welcome back,
                <strong><?= e($adminName) ?></strong>!
            </p>
        </div>

        <a href="logout.php" class="logout">Logout</a>

    </div>

    <div class="section-heading">
        <h2>Store Overview</h2>
        <p>Here is a summary of your store's activity.</p>
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

    <section class="orders-card">

        <div class="orders-heading">
            <h2>Recent Orders</h2>
            <a href="orders.php">View All Orders →</a>
        </div>

        <?php if ($recentOrders): ?>

            <div class="table-wrapper">

                <table>

                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($recentOrders as $order): ?>

                            <tr>
                                <td>#<?= (int)$order['id'] ?></td>

                                <td>
                                    <?= e($order['full_name'] ?? 'Unknown Customer') ?>
                                </td>

                                <td>
                                    ₦<?= number_format((float)$order['total_amount'], 2) ?>
                                </td>

                                <td class="status">
                                    <?= e($order['status'] ?? 'Pending') ?>
                                </td>

                                <td>
                                    <?= date('d M Y', strtotime($order['created_at'])) ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <p class="empty">No orders have been placed yet.</p>

        <?php endif; ?>

    </section>

    <div class="dashboard-footer">
        &copy; <?= date('Y') ?> Bayogoal Enterprises.
        Admin Control Panel.
    </div>

</main>

</body>
</html>
