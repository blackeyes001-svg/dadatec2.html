<?php
require_once __DIR__ . '/../includes/session.php';
require_once '../includes/db.php';

// Protect admin page
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Escape output safely
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Search customers
$search = trim($_GET['search'] ?? '');
$selectedCustomer = isset($_GET['customer']) ? (int) $_GET['customer'] : 0;

// Fetch customers
$sql = "
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.created_at,
        (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS total_orders,
        (SELECT COALESCE(SUM(o.total_amount), 0)
         FROM orders o WHERE o.user_id = u.id) AS total_spent
    FROM users u
    WHERE u.role = 'customer'
";

$params = [];

if ($search !== '') {
    $sql .= " AND (u.full_name LIKE :search_name OR u.email LIKE :search_email)";
    
    $params['search_name'] = "%$search%";
    $params['search_email'] = "%$search%";
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch selected customer's details and order history
$customerDetails = null;
$customerOrders = [];

if ($selectedCustomer > 0) {
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, created_at
        FROM users
        WHERE id = ? AND role = 'customer'
    ");

    $stmt->execute([$selectedCustomer]);
    $customerDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customerDetails) {
        $stmt = $pdo->prepare("
            SELECT id, total_amount, status, payment_method,
                   payment_status, created_at
            FROM orders
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");

        $stmt->execute([$selectedCustomer]);
        $customerOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Customers | Bayogoal Enterprises</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #071426;
            color: #ffffff;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background: #10233d;
            position: fixed;
            top: 0;
            left: 0;
            padding: 25px 15px;
            overflow-y: auto;
            border-right: 1px solid #203b5c;
        }

        .brand {
            color: #39FF14;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .brand-subtitle {
            color: #aab8ca;
            font-size: 12px;
            margin-bottom: 35px;
        }

        .sidebar a {
            display: block;
            color: #e5e7eb;
            text-decoration: none;
            padding: 14px;
            margin-bottom: 8px;
            border-radius: 7px;
            transition: 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #2563EB;
            color: #ffffff;
        }

        .main {
            margin-left: 250px;
            padding: 35px;
            min-width: 0;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #aab8ca;
            line-height: 1.6;
        }

        .search-box {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            max-width: 600px;
        }

        input {
            width: 100%;
            min-width: 0;
            padding: 14px;
            border: 1px solid #304968;
            border-radius: 7px;
            background: #10233d;
            color: #ffffff;
            outline: none;
            font-size: 15px;
        }

        input:focus {
            border-color: #39FF14;
        }

        button,
        .btn {
            background: #2563EB;
            color: #ffffff;
            border: none;
            padding: 12px 18px;
            border-radius: 7px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            white-space: nowrap;
            font-size: 14px;
            transition: 0.3s;
        }

        button:hover,
        .btn:hover {
            background: #1d4ed8;
        }

        .card {
            background: #10233d;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #203b5c;
            overflow: hidden;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th,
        td {
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

        .view-btn {
            background: #15803d;
            padding: 9px 13px;
        }

        .view-btn:hover {
            background: #166534;
        }

        .back-btn {
            background: #475569;
            margin-bottom: 22px;
        }

        .customer-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 22px;
        }

        .info-box {
            background: #071426;
            padding: 20px;
            border-radius: 9px;
            border: 1px solid #203b5c;
            min-width: 0;
        }

        .info-box small {
            color: #aab8ca;
            display: block;
            margin-bottom: 10px;
        }

        .info-box strong {
            color: #39FF14;
            overflow-wrap: anywhere;
            font-size: 15px;
        }

        .status {
            text-transform: capitalize;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 35px 15px;
            color: #aab8ca;
        }

        .section-title {
            margin-bottom: 20px;
            font-size: 19px;
        }

        .count {
            color: #39FF14;
        }

        .footer {
            color: #8292a8;
            text-align: center;
            padding: 25px 10px;
            font-size: 13px;
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
                padding: 25px;
            }
        }

        @media (max-width: 700px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 18px;
            }

            .brand {
                font-size: 20px;
            }

            .brand-subtitle {
                margin-bottom: 15px;
            }

            .sidebar a {
                display: inline-block;
                padding: 10px 12px;
                margin: 3px;
                font-size: 13px;
            }

            .main {
                margin-left: 0;
                padding: 20px 15px;
            }

            h1 {
                font-size: 24px;
            }

            .search-box {
                flex-direction: column;
            }

            .search-box button {
                width: 100%;
            }

            .card {
                padding: 17px;
            }
        }

        @media (max-width: 400px) {
            .sidebar a {
                font-size: 12px;
                padding: 9px;
            }

            .customer-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">

    <div class="brand">BAYOGOAL</div>
    <div class="brand-subtitle">ENTERPRISES ADMIN PANEL</div>

    <a href="dashboard.php">Dashboard</a>
    <a href="products.php">Manage Products</a>
    <a href="orders.php">Manage Orders</a>
    <a href="customers.php" class="active">Manage Customers</a>
    <a href="../index.php" target="_blank">View Website</a>
    <a href="logout.php">Logout</a>

</aside>

<main class="main">

<?php if ($selectedCustomer > 0 && $customerDetails): ?>

    <a href="customers.php" class="btn back-btn">
        ← Back to Customers
    </a>

    <div class="page-heading">
        <h1>Customer Details</h1>
        <p class="subtitle">
            View customer information and shopping history.
        </p>
    </div>

    <section class="card">

        <h2><?= e($customerDetails['full_name']) ?></h2>

        <div class="customer-info">

            <div class="info-box">
                <small>Customer ID</small>
                <strong>#<?= (int) $customerDetails['id'] ?></strong>
            </div>

            <div class="info-box">
                <small>Email Address</small>
                <strong><?= e($customerDetails['email']) ?></strong>
            </div>

            <div class="info-box">
                <small>Registration Date</small>
                <strong>
                    <?= date('d M Y', strtotime($customerDetails['created_at'])) ?>
                </strong>
            </div>

            <div class="info-box">
                <small>Total Orders</small>
                <strong><?= count($customerOrders) ?></strong>
            </div>

        </div>

    </section>

    <section class="card">

        <h2 class="section-title">Order History</h2>

        <?php if ($customerOrders): ?>

            <div class="table-wrapper">

                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Amount</th>
                            <th>Order Status</th>
                            <th>Payment Method</th>
                            <th>Payment Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($customerOrders as $order): ?>

                            <tr>
                                <td>#<?= (int) $order['id'] ?></td>

                                <td>
                                    ₦<?= number_format((float) $order['total_amount'], 2) ?>
                                </td>

                                <td class="status">
                                    <?= e($order['status']) ?>
                                </td>

                                <td>
                                    <?= e($order['payment_method']) ?>
                                </td>

                                <td class="status">
                                    <?= e($order['payment_status']) ?>
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

            <p class="empty">
                This customer has not placed any orders yet.
            </p>

        <?php endif; ?>

    </section>

<?php elseif ($selectedCustomer > 0): ?>

    <div class="page-heading">
        <h1>Customer Not Found</h1>

        <p class="subtitle">
            This customer does not exist or is not a registered customer.
        </p>
    </div>

    <a href="customers.php" class="btn">
        Back to Customers
    </a>

<?php else: ?>

    <div class="page-heading">
        <h1>Manage Customers</h1>

        <p class="subtitle">
            View registered customers and their shopping activities.
        </p>
    </div>

    <form method="GET" class="search-box">

        <input
            type="text"
            name="search"
            placeholder="Search by customer name or email..."
            value="<?= e($search) ?>"
        >

        <button type="submit">Search Customers</button>

    </form>

    <section class="card">

        <h2 class="section-title">
            Registered Customers
            <span class="count">(<?= count($customers) ?>)</span>
        </h2>

        <?php if ($customers): ?>

            <div class="table-wrapper">

                <table>

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Total Orders</th>
                            <th>Total Order Value</th>
                            <th>Joined</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($customers as $customer): ?>

                            <tr>

                                <td>
                                    #<?= (int) $customer['id'] ?>
                                </td>

                                <td>
                                    <?= e($customer['full_name']) ?>
                                </td>

                                <td>
                                    <?= e($customer['email']) ?>
                                </td>

                                <td>
                                    <?= (int) $customer['total_orders'] ?>
                                </td>

                                <td>
                                    ₦<?= number_format((float) $customer['total_spent'], 2) ?>
                                </td>

                                <td>
                                    <?= date('d M Y', strtotime($customer['created_at'])) ?>
                                </td>

                                <td>
                                    <a
                                        class="btn view-btn"
                                        href="customers.php?customer=<?= (int) $customer['id'] ?>"
                                    >
                                        View Details
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <p class="empty">
                No customers found.
            </p>

        <?php endif; ?>

    </section>

<?php endif; ?>

<footer class="footer">
    &copy; <?= date('Y') ?> Bayogoal Enterprises. All Rights Reserved.
</footer>

</main>

</body>
</html>