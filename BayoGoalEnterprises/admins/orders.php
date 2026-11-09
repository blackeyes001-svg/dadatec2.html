<?php
require_once __DIR__ . '/../includes/session.php';
require_once "../includes/db.php";

// Protect admin page
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Generate CSRF token
if (empty($_SESSION['admin_orders_token'])) {
    $_SESSION['admin_orders_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['admin_orders_token'];

$message = "";
$error = "";

// Allowed values
$allowedStatuses = [
    'pending',
    'processing',
    'shipped',
    'delivered',
    'cancelled'
];

$allowedPaymentStatuses = [
    'unpaid',
    'paid',
    'failed',
    'refunded'
];

// Handle order and payment updates
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {

        $error = "Invalid request. Please refresh the page and try again.";

    } else {

        $orderId = (int)($_POST['order_id'] ?? 0);

        try {

            if (isset($_POST['update_status'])) {

                $status = $_POST['status'] ?? '';

                if ($orderId <= 0 || !in_array($status, $allowedStatuses, true)) {

                    $error = "Invalid order or status selected.";

                } else {

                    $stmt = $pdo->prepare(
                        "UPDATE orders SET status = ? WHERE id = ?"
                    );

                    $stmt->execute([$status, $orderId]);

                    $message = $stmt->rowCount() > 0
                        ? "Order status updated successfully."
                        : "Order status is unchanged or the order was not found.";
                }

            } elseif (isset($_POST['update_payment'])) {

                $paymentStatus = $_POST['payment_status'] ?? '';

                if ($orderId <= 0 || !in_array($paymentStatus, $allowedPaymentStatuses, true)) {

                    $error = "Invalid order or payment status selected.";

                } else {

                    $stmt = $pdo->prepare(
                        "UPDATE orders SET payment_status = ? WHERE id = ?"
                    );

                    $stmt->execute([$paymentStatus, $orderId]);

                    $message = $stmt->rowCount() > 0
                        ? "Payment status updated successfully."
                        : "Payment status is unchanged or the order was not found.";
                }
            }

        } catch (PDOException $e) {

            error_log("Order update error: " . $e->getMessage());

            $error = "Unable to update order. Please try again.";
        }
    }
}

// Search and filter values
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$paymentFilter = $_GET['payment'] ?? '';

// Build orders query
$sql = "
    SELECT
        orders.*,
        users.full_name,
        users.email
    FROM orders
    LEFT JOIN users ON orders.user_id = users.id
    WHERE 1=1
";

$params = [];

// Search by order ID, customer name or email
if ($search !== '') {

    if (ctype_digit($search)) {

        $sql .= " AND (
            orders.id = :order_id
            OR users.full_name LIKE :customer_name
            OR users.email LIKE :customer_email
        )";

        $params['order_id'] = (int)$search;

    } else {

        $sql .= " AND (
            users.full_name LIKE :customer_name
            OR users.email LIKE :customer_email
        )";
    }

    $params['customer_name'] = "%$search%";
    $params['customer_email'] = "%$search%";
}

// Filter order status
if (in_array($statusFilter, $allowedStatuses, true)) {

    $sql .= " AND orders.status = :status";

    $params['status'] = $statusFilter;
}

// Filter payment status
if (in_array($paymentFilter, $allowedPaymentStatuses, true)) {

    $sql .= " AND orders.payment_status = :payment_status";

    $params['payment_status'] = $paymentFilter;
}

$sql .= " ORDER BY orders.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Order items query
$itemStmt = $pdo->prepare("
    SELECT
        order_items.*,
        products.name AS product_name
    FROM order_items
    LEFT JOIN products ON order_items.product_id = products.id
    WHERE order_items.order_id = ?
");

// Escape output
function h($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Order status CSS class
function statusClass($status) {

    $status = strtolower((string)$status);

    $allowed = [
        'pending',
        'processing',
        'shipped',
        'delivered',
        'cancelled'
    ];

    return in_array($status, $allowed, true)
        ? $status
        : 'pending';
}

// Payment status CSS class
function paymentClass($status) {

    $status = strtolower((string)$status);

    $allowed = [
        'unpaid',
        'paid',
        'failed',
        'refunded'
    ];

    return in_array($status, $allowed, true)
        ? 'payment-' . $status
        : 'payment-unpaid';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manage Orders | Bayogoal Enterprises</title>

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

/* SIDEBAR */

.sidebar {
    width: 250px;
    min-height: 100vh;
    background: #10233d;
    position: fixed;
    top: 0;
    left: 0;
    padding: 30px 18px;
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

/* MAIN */

.main {
    margin-left: 250px;
    padding: 35px;
    min-width: 0;
}

.heading {
    margin-bottom: 30px;
}

.heading h1 {
    font-size: 28px;
    margin-bottom: 10px;
}

.heading p {
    color: #9ca3af;
    font-size: 14px;
}

/* MESSAGES */

.message,
.error {
    padding: 14px;
    margin-bottom: 20px;
    border-radius: 7px;
    font-size: 14px;
}

.message {
    background: #14532d;
    border: 1px solid #166534;
    color: #dcfce7;
}

.error {
    background: #7f1d1d;
    border: 1px solid #991b1b;
    color: #fee2e2;
}

/* SEARCH AND FILTERS */

.filter-container {
    background: #10233d;
    padding: 22px;
    border-radius: 12px;
    margin-bottom: 25px;
}

.filter-container h3 {
    margin-bottom: 17px;
    font-size: 17px;
}

.filter-form {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto auto;
    gap: 12px;
}

input,
select {
    padding: 12px;
    border-radius: 6px;
    border: 1px solid #334155;
    background: #071426;
    color: #ffffff;
    font-size: 13px;
    min-width: 0;
    width: 100%;
}

input::placeholder {
    color: #94a3b8;
}

button {
    background: #2563EB;
    color: #ffffff;
    padding: 12px 18px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: bold;
    transition: 0.3s;
}

button:hover {
    background: #1d4ed8;
}

.reset-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 18px;
    border-radius: 6px;
    background: #334155;
    color: white;
    text-decoration: none;
    font-size: 13px;
}

/* ORDERS */

.orders-container {
    background: #10233d;
    padding: 25px;
    border-radius: 12px;
}

.orders-title {
    font-size: 20px;
    margin-bottom: 22px;
}

.order-card {
    background: #071426;
    padding: 22px;
    border-radius: 10px;
    margin-bottom: 22px;
    border: 1px solid #334155;
}

.order-card:last-child {
    margin-bottom: 0;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 22px;
}

.order-header h3 {
    color: #39FF14;
    font-size: 19px;
}

.status-badge {
    display: inline-block;
    padding: 7px 13px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-transform: capitalize;
}

.pending {
    background: #78350f;
    color: #fde68a;
}

.processing {
    background: #1e3a8a;
    color: #bfdbfe;
}

.shipped {
    background: #581c87;
    color: #e9d5ff;
}

.delivered {
    background: #14532d;
    color: #bbf7d0;
}

.cancelled {
    background: #7f1d1d;
    color: #fecaca;
}

/* PAYMENT BADGES */

.payment-unpaid {
    background: #78350f;
    color: #fde68a;
}

.payment-paid {
    background: #14532d;
    color: #bbf7d0;
}

.payment-failed {
    background: #7f1d1d;
    color: #fecaca;
}

.payment-refunded {
    background: #581c87;
    color: #e9d5ff;
}

/* DETAILS */

.details {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 22px;
}

.detail-item {
    min-width: 0;
    background: #10233d;
    padding: 13px;
    border-radius: 7px;
}

.detail-item strong {
    display: block;
    color: #39FF14;
    font-size: 12px;
    margin-bottom: 8px;
}

.detail-item p {
    color: #cbd5e1;
    font-size: 13px;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

/* PRODUCTS */

.items {
    border-top: 1px solid #334155;
    border-bottom: 1px solid #334155;
    padding: 18px 0;
    margin-bottom: 20px;
}

.items h4 {
    margin-bottom: 15px;
    color: #39FF14;
    font-size: 15px;
}

.product-item {
    color: #cbd5e1;
    margin-bottom: 10px;
    font-size: 13px;
    line-height: 1.7;
}

.product-item:last-child {
    margin-bottom: 0;
}

.total {
    font-size: 21px;
    font-weight: bold;
    color: #39FF14;
    margin-bottom: 22px;
}

/* UPDATE FORMS */

.update-section {
    border-top: 1px solid #334155;
    padding-top: 18px;
    margin-top: 18px;
}

.update-section h4 {
    color: #39FF14;
    margin-bottom: 12px;
    font-size: 14px;
}

.status-form {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}

.status-form select {
    min-width: 170px;
    width: auto;
}

/* EMPTY */

.empty {
    text-align: center;
    padding: 45px 15px;
    color: #9ca3af;
}

.empty h3 {
    color: #ffffff;
    margin-bottom: 10px;
}

/* FOOTER */

.footer {
    text-align: center;
    color: #64748b;
    font-size: 13px;
    padding: 25px 0;
}

/* RESPONSIVE */

@media(max-width: 1100px) {

    .details {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .filter-form {
        grid-template-columns: 1fr 1fr;
    }
}

@media(max-width: 700px) {

    .sidebar {
        position: relative;
        width: 100%;
        min-height: auto;
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
        padding: 20px 15px;
    }

    .heading h1 {
        font-size: 24px;
    }

    .orders-container {
        padding: 17px;
    }

    .order-card {
        padding: 16px;
    }

    .details {
        grid-template-columns: 1fr;
    }

    .filter-form {
        grid-template-columns: 1fr;
    }

    .status-form {
        align-items: stretch;
        flex-direction: column;
    }

    .status-form select,
    .status-form button {
        width: 100%;
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
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Manage Products</a>
        <a href="customers.php">Manage Customers</a>
        <a href="orders.php" class="active">Manage Orders</a>
        <a href="../index.php" class="website-link">View Website</a>
        <a href="logout.php">Logout</a>
    </nav>

</aside>

<main class="main">

    <div class="heading">
        <h1>Manage Orders</h1>
        <p>View customer orders, track payments and manage deliveries.</p>
    </div>

    <?php if ($message): ?>
        <div class="message">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <!-- SEARCH AND FILTER -->

    <section class="filter-container">

        <h3>Search and Filter Orders</h3>

        <form method="GET" class="filter-form">

            <input
                type="text"
                name="search"
                placeholder="Search order ID, customer name or email..."
                value="<?= h($search) ?>"
            >

            <select name="status">

                <option value="">All Order Statuses</option>

                <?php foreach ($allowedStatuses as $status): ?>

                    <option
                        value="<?= h($status) ?>"
                        <?= $statusFilter === $status ? 'selected' : '' ?>
                    >
                        <?= h(ucfirst($status)) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <select name="payment">

                <option value="">All Payment Statuses</option>

                <?php foreach ($allowedPaymentStatuses as $payment): ?>

                    <option
                        value="<?= h($payment) ?>"
                        <?= $paymentFilter === $payment ? 'selected' : '' ?>
                    >
                        <?= h(ucfirst($payment)) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <button type="submit">
                Search
            </button>

            <a href="orders.php" class="reset-btn">
                Reset
            </a>

        </form>

    </section>

    <!-- ORDERS LIST -->

    <section class="orders-container">

        <h2 class="orders-title">
            Orders Found (<?= count($orders) ?>)
        </h2>

        <?php if (empty($orders)): ?>

            <div class="empty">
                <h3>No orders found</h3>
                <p>Try changing your search or filter.</p>
            </div>

        <?php else: ?>

            <?php foreach ($orders as $order): ?>

                <div class="order-card">

                    <div class="order-header">

                        <h3>
                            Order #<?= (int)$order['id'] ?>
                        </h3>

                        <span class="status-badge <?= h(statusClass($order['status'])) ?>">
                            <?= h($order['status']) ?>
                        </span>

                    </div>

                    <!-- CUSTOMER DETAILS -->

                    <div class="details">

                        <div class="detail-item">
                            <strong>Customer</strong>
                            <p><?= h($order['full_name']) ?></p>
                        </div>

                        <div class="detail-item">
                            <strong>Email</strong>
                            <p><?= h($order['email']) ?></p>
                        </div>

                        <div class="detail-item">
                            <strong>Phone</strong>
                            <p><?= h($order['phone']) ?></p>
                        </div>

                        <div class="detail-item">
                            <strong>Order Date</strong>
                            <p><?= h($order['created_at']) ?></p>
                        </div>

                        <div class="detail-item">
                            <strong>Payment Method</strong>
                            <p><?= h($order['payment_method']) ?></p>
                        </div>

                        <div class="detail-item">
                            <strong>Payment Status</strong>
                            <p>
                                <span class="status-badge <?= h(paymentClass($order['payment_status'])) ?>">
                                    <?= h($order['payment_status']) ?>
                                </span>
                            </p>
                        </div>

                        <div class="detail-item">
                            <strong>Delivery Address</strong>
                            <p><?= h($order['delivery_address']) ?></p>
                        </div>

                    </div>

                    <!-- PRODUCTS ORDERED -->

                    <div class="items">

                        <h4>Products Ordered</h4>

                        <?php

                        $itemStmt->execute([$order['id']]);

                        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

                        ?>

                        <?php if (empty($items)): ?>

                            <p class="product-item">
                                No order items found.
                            </p>

                        <?php else: ?>

                            <?php foreach ($items as $item): ?>

                                <p class="product-item">

                                    <strong>
                                        <?= h($item['product_name'] ?? 'Product unavailable') ?>
                                    </strong>

                                    — Quantity: <?= (int)$item['quantity'] ?>

                                    — ₦<?= number_format((float)$item['price'], 2) ?>

                                    <?php if (!empty($item['selected_size'])): ?>
                                        — Size: <?= h($item['selected_size']) ?>
                                    <?php endif; ?>

                                    <?php if (!empty($item['selected_color'])): ?>
                                        — Colour: <?= h($item['selected_color']) ?>
                                    <?php endif; ?>

                                </p>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                    <!-- TOTAL -->

                    <div class="total">
                        Total: ₦<?= number_format((float)$order['total_amount'], 2) ?>
                    </div>

                    <!-- UPDATE ORDER STATUS -->

                    <div class="update-section">

                        <h4>Update Delivery Status</h4>

                        <form method="POST" class="status-form">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= h($csrfToken) ?>"
                            >

                            <input
                                type="hidden"
                                name="order_id"
                                value="<?= (int)$order['id'] ?>"
                            >

                            <select name="status" required>

                                <?php foreach ($allowedStatuses as $status): ?>

                                    <option
                                        value="<?= h($status) ?>"
                                        <?= strtolower((string)$order['status']) === $status ? 'selected' : '' ?>
                                    >
                                        <?= h(ucfirst($status)) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <button type="submit" name="update_status">
                                Update Order Status
                            </button>

                        </form>

                    </div>

                    <!-- UPDATE PAYMENT STATUS -->

                    <div class="update-section">

                        <h4>Update Payment Status</h4>

                        <form method="POST" class="status-form">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= h($csrfToken) ?>"
                            >

                            <input
                                type="hidden"
                                name="order_id"
                                value="<?= (int)$order['id'] ?>"
                            >

                            <select name="payment_status" required>

                                <?php foreach ($allowedPaymentStatuses as $payment): ?>

                                    <option
                                        value="<?= h($payment) ?>"
                                        <?= strtolower((string)$order['payment_status']) === $payment ? 'selected' : '' ?>
                                    >
                                        <?= h(ucfirst($payment)) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <button type="submit" name="update_payment">
                                Update Payment Status
                            </button>

                        </form>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

    <div class="footer">
        &copy; <?= date('Y') ?> Bayogoal Enterprises. Admin Control Panel.
    </div>

</main>

</body>
</html>