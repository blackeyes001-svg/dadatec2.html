
<?php
session_start();
require_once "../includes/db.php";

// Protect admin page
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

// Update order status
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_status'])) {

    $orderId = (int) ($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowedStatuses = [
        'pending',
        'processing',
        'shipped',
        'delivered',
        'cancelled'
    ];

    if ($orderId <= 0 || !in_array($status, $allowedStatuses, true)) {
        $error = "Invalid order or status selected.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "UPDATE orders SET status = ? WHERE id = ?"
            );

            $stmt->execute([$status, $orderId]);

            $message = "Order status updated successfully.";

        } catch (PDOException $e) {
            $error = "Unable to update order status.";
        }
    }
}

// Get all orders and customer information
$stmt = $pdo->query("
    SELECT
        orders.*,
        users.full_name,
        users.email
    FROM orders
    LEFT JOIN users ON orders.user_id = users.id
    ORDER BY orders.created_at DESC
");

$orders = $stmt->fetchAll();

// Get order items
$itemStmt = $pdo->prepare("
    SELECT
        order_items.*,
        products.name AS product_name
    FROM order_items
    LEFT JOIN products ON order_items.product_id = products.id
    WHERE order_items.order_id = ?
");

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Orders | Global Zone Sports</title>

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
            min-height: 100vh;
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

        .heading {
            margin-bottom: 25px;
        }

        .orders-container {
            background: #10233d;
            padding: 25px;
            border-radius: 10px;
        }

        .order-card {
            background: #071426;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #334155;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .order-header h3 {
            color: #39FF14;
        }

        .details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .details p {
            color: #cbd5e1;
            line-height: 1.6;
        }

        .items {
            border-top: 1px solid #334155;
            border-bottom: 1px solid #334155;
            padding: 15px 0;
            margin-bottom: 18px;
        }

        .items h4 {
            margin-bottom: 12px;
            color: #39FF14;
        }

        .items p {
            margin-bottom: 8px;
            color: #cbd5e1;
        }

        .total {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 18px;
        }

        .status-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        select {
            padding: 11px;
            border-radius: 5px;
            border: 1px solid #334155;
            background: #071426;
            color: white;
        }

        button {
            background: #2563EB;
            color: white;
            padding: 11px 17px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .message {
            background: #14532d;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .error {
            background: #7f1d1d;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .empty {
            text-align: center;
            padding: 35px;
            color: #aaa;
        }

        @media(max-width: 700px) {
            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
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
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">

    <h2>GLOBAL ZONE</h2>

    <a href="dashboard.php">Dashboard</a>
    <a href="products.php">Manage Products</a>
    <a href="orders.php" class="active">Manage Orders</a>
    <a href="../index.php">View Website</a>
    <a href="logout.php">Logout</a>

</aside>

<main class="main">

    <div class="heading">
        <h1>Manage Orders</h1>
        <p>View customer orders and manage delivery progress.</p>
    </div>

    <?php if ($message): ?>
        <div class="message"><?= h($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <section class="orders-container">

        <h2 style="margin-bottom: 20px;">
            All Orders (<?= count($orders) ?>)
        </h2>

        <?php if (empty($orders)): ?>

            <div class="empty">
                <h3>No orders yet</h3>
                <p>Customer orders will appear here.</p>
            </div>

        <?php else: ?>

            <?php foreach ($orders as $order): ?>

                <div class="order-card">

                    <div class="order-header">

                        <h3>
                            Order #<?= (int)$order['id'] ?>
                        </h3>

                        <span>
                            <?= h(ucfirst($order['status'])) ?>
                        </span>

                    </div>

                    <div class="details">

                        <div>
                            <p><strong>Customer:</strong></p>
                            <p><?= h($order['full_name']) ?></p>
                        </div>

                        <div>
                            <p><strong>Email:</strong></p>
                            <p><?= h($order['email']) ?></p>
                        </div>

                        <div>
                            <p><strong>Phone:</strong></p>
                            <p><?= h($order['phone']) ?></p>
                        </div>

                        <div>
                            <p><strong>Order Date:</strong></p>
                            <p><?= h($order['created_at']) ?></p>
                        </div>

                        <div>
                            <p><strong>Payment Method:</strong></p>
                            <p><?= h($order['payment_method']) ?></p>
                        </div>

                        <div>
                            <p><strong>Payment Status:</strong></p>
                            <p><?= h($order['payment_status']) ?></p>
                        </div>

                        <div>
                            <p><strong>Delivery Address:</strong></p>
                            <p><?= h($order['delivery_address']) ?></p>
                        </div>

                    </div>

                    <div class="items">

                        <h4>Products Ordered</h4>

                        <?php
                        $itemStmt->execute([$order['id']]);
                        $items = $itemStmt->fetchAll();
                        ?>

                        <?php foreach ($items as $item): ?>

                            <p>
                                <?= h($item['product_name'] ?? 'Product unavailable') ?>
                                —
                                Quantity: <?= (int)$item['quantity'] ?>
                                —
                                ₦<?= number_format((float)$item['price'], 2) ?>
                                <?php if (!empty($item['selected_size'])): ?>
                                    — Size: <?= h($item['selected_size']) ?>
                                <?php endif; ?>
                                <?php if (!empty($item['selected_color'])): ?>
                                    — Colour: <?= h($item['selected_color']) ?>
                                <?php endif; ?>
                            </p>

                        <?php endforeach; ?>

                    </div>

                    <div class="total">
                        Total: ₦<?= number_format((float)$order['total_amount'], 2) ?>
                    </div>

                    <form method="POST" class="status-form">

                        <input type="hidden" name="order_id"
                               value="<?= (int)$order['id'] ?>">

                        <select name="status" required>

                            <?php
                            $statuses = [
                                'pending',
                                'processing',
                                'shipped',
                                'delivered',
                                'cancelled'
                            ];
                            ?>

                            <?php foreach ($statuses as $status): ?>

                                <option value="<?= h($status) ?>"
                                    <?= strtolower($order['status']) === $status ? 'selected' : '' ?>>

                                    <?= h(ucfirst($status)) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <button type="submit" name="update_status">
                            Update Order Status
                        </button>

                    </form>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</main>

</body>
</html>

