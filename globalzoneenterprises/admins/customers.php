```php
<?php
session_start();
require_once '../includes/db.php';

// Protect admin page
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
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
        (SELECT COALESCE(SUM(o.total_amount), 0) FROM orders o WHERE o.user_id = u.id) AS total_spent
    FROM users u
    WHERE u.role = 'customer'
";

$params = [];

if ($search !== '') {
    $sql .= " AND (u.full_name LIKE :search OR u.email LIKE :search)";
    $params['search'] = "%$search%";
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
    <title>Manage Customers | Global Zone Sports Enterprise</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
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
            margin-bottom: 35px;
            font-size: 20px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 14px;
            margin-bottom: 8px;
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

        h1 {
            margin-bottom: 10px;
        }

        .subtitle {
            color: #aab8ca;
            margin-bottom: 25px;
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            max-width: 550px;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #304968;
            border-radius: 6px;
            background: #10233d;
            color: white;
            outline: none;
        }

        button, .btn {
            background: #2563EB;
            color: white;
            border: none;
            padding: 12px 17px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            white-space: nowrap;
        }

        button:hover, .btn:hover {
            background: #1d4ed8;
        }

        .card {
            background: #10233d;
            padding: 22px;
            border-radius: 10px;
            margin-bottom: 25px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #304968;
        }

        th {
            color: #39FF14;
        }

        td {
            color: #e5e7eb;
        }

        .view-btn {
            background: #16a34a;
            padding: 9px 13px;
        }

        .back-btn {
            background: #475569;
            margin-bottom: 20px;
        }

        .customer-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .info-box {
            background: #071426;
            padding: 18px;
            border-radius: 8px;
        }

        .info-box small {
            color: #aab8ca;
            display: block;
            margin-bottom: 8px;
        }

        .info-box strong {
            color: #39FF14;
            overflow-wrap: anywhere;
        }

        .status {
            color: #39FF14;
            text-transform: capitalize;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #aab8ca;
        }

        @media (max-width: 750px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 15px;
            }

            .sidebar h2 {
                margin-bottom: 12px;
            }

            .sidebar a {
                display: inline-block;
                padding: 10px;
                margin: 3px;
            }

            .main {
                margin-left: 0;
                padding: 18px;
            }

            .search-box {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">
    <h2>GLOBAL ZONE</h2>

    <a href="dashboard.php">Dashboard</a>
    <a href="products.php">Manage Products</a>
    <a href="orders.php">Manage Orders</a>
    <a href="customers.php" class="active">Manage Customers</a>
    <a href="../index.php" target="_blank">View Website</a>
    <a href="logout.php">Logout</a>
</aside>

<main class="main">

<?php if ($selectedCustomer > 0 && $customerDetails): ?>

    <a href="customers.php" class="btn back-btn">← Back to Customers</a>

    <h1>Customer Details</h1>
    <p class="subtitle">Customer profile and order history.</p>

    <section class="card">
        <h2><?= htmlspecialchars($customerDetails['full_name']) ?></h2>

        <div class="customer-info">
            <div class="info-box">
                <small>Customer ID</small>
                <strong>#<?= (int) $customerDetails['id'] ?></strong>
            </div>

            <div class="info-box">
                <small>Email Address</small>
                <strong><?= htmlspecialchars($customerDetails['email']) ?></strong>
            </div>

            <div class="info-box">
                <small>Registration Date</small>
                <strong><?= date('d M Y', strtotime($customerDetails['created_at'])) ?></strong>
            </div>

            <div class="info-box">
                <small>Total Orders</small>
                <strong><?= count($customerOrders) ?></strong>
            </div>
        </div>
    </section>

    <section class="card">
        <h2 style="margin-bottom: 20px;">Order History</h2>

        <?php if ($customerOrders): ?>

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
                                <?= htmlspecialchars($order['status']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($order['payment_method']) ?>
                            </td>

                            <td class="status">
                                <?= htmlspecialchars($order['payment_status']) ?>
                            </td>

                            <td>
                                <?= date('d M Y', strtotime($order['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else: ?>

            <p class="empty">This customer has not placed any orders yet.</p>

        <?php endif; ?>
    </section>

<?php elseif ($selectedCustomer > 0): ?>

    <h1>Customer Not Found</h1>
    <p class="subtitle">This customer does not exist or is not a registered customer.</p>
    <a href="customers.php" class="btn">Back to Customers</a>

<?php else: ?>

    <h1>Manage Customers</h1>
    <p class="subtitle">View registered customers and their shopping activities.</p>

    <form method="GET" class="search-box">
        <input
            type="text"
            name="search"
            placeholder="Search by customer name or email..."
            value="<?= htmlspecialchars($search) ?>"
        >
        <button type="submit">Search</button>
    </form>

    <section class="card">

        <h2 style="margin-bottom: 20px;">
            Registered Customers (<?= count($customers) ?>)
        </h2>

        <?php if ($customers): ?>

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
                            <td>#<?= (int) $customer['id'] ?></td>

                            <td>
                                <?= htmlspecialchars($customer['full_name']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($customer['email']) ?>
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

        <?php else: ?>

            <p class="empty">No customers found.</p>

        <?php endif; ?>

    </section>

<?php endif; ?>

</main>

</body>
</html>
```
