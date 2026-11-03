<?php
session_start();
require_once 'includes/db.php';

// Customers must be logged in.
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];

// Fetch this customer's orders.
$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch products belonging to each order.
$itemStmt = $pdo->prepare("
    SELECT 
        order_items.*,
        products.name AS product_name
    FROM order_items
    INNER JOIN products
        ON order_items.product_id = products.id
    WHERE order_items.order_id = ?
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Orders | Global Zone Sport Enterprise</title>

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

header {
    background: #10233d;
    padding: 22px 7%;
    border-bottom: 2px solid #2563EB;
}

header h2 {
    color: #39FF14;
}

.container {
    width: 90%;
    max-width: 1100px;
    margin: 40px auto;
}

h1 {
    margin-bottom: 10px;
}

.subtitle {
    color: #aaa;
    margin-bottom: 30px;
}

.success {
    background: #183b2b;
    border: 1px solid #39FF14;
    padding: 15px;
    border-radius: 7px;
    margin-bottom: 25px;
}

.order-card {
    background: #10233d;
    border: 1px solid #294568;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
}

.order-header {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
    border-bottom: 1px solid #294568;
    padding-bottom: 18px;
    margin-bottom: 18px;
}

.order-header h3 {
    color: #39FF14;
    margin-bottom: 8px;
}

.order-header p {
    color: #aaa;
    margin-top: 5px;
}

.status {
    background: #2563EB;
    padding: 8px 13px;
    border-radius: 20px;
    height: fit-content;
    text-transform: capitalize;
}

.product {
    padding: 13px 0;
    border-bottom: 1px solid #294568;
}

.product h4 {
    margin-bottom: 8px;
}

.product p {
    color: #bbb;
    margin: 5px 0;
    font-size: 14px;
}

.total {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
    font-size: 19px;
    font-weight: bold;
}

.total span:last-child {
    color: #39FF14;
}

.payment {
    margin-top: 15px;
    color: #bbb;
}

.empty {
    background: #10233d;
    padding: 35px;
    border-radius: 12px;
    text-align: center;
}

.empty p {
    color: #aaa;
    margin: 15px 0;
}

button {
    background: #2563EB;
    border: none;
    padding: 12px 20px;
    border-radius: 6px;
    color: white;
    cursor: pointer;
    margin-top: 15px;
}

button:hover {
    background: #39FF14;
    color: #071426;
}

a {
    text-decoration: none;
}

@media(max-width: 600px) {
    .container {
        width: 94%;
    }

    .order-card {
        padding: 18px;
    }
}
</style>
</head>

<body>

<header>
    <h2>GLOBAL ZONE SPORT ENTERPRISE</h2>
</header>

<div class="container">

<h1>My Orders</h1>

<p class="subtitle">
    View and track your purchases.
</p>

<?php if (isset($_GET['success'])): ?>
    <div class="success">
        Your order has been placed successfully!
    </div>
<?php endif; ?>

<?php if (empty($orders)): ?>

    <div class="empty">
        <h2>No Orders Yet</h2>
        <p>You haven't placed any orders yet.</p>

        <a href="products.php">
            <button>Start Shopping</button>
        </a>
    </div>

<?php else: ?>

    <?php foreach ($orders as $order): ?>

        <div class="order-card">

            <div class="order-header">

                <div>
                    <h3>
                        Order #<?= (int)$order['id'] ?>
                    </h3>

                    <p>
                        Date:
                        <?= htmlspecialchars($order['created_at']) ?>
                    </p>
                </div>

                <span class="status">
                    <?= htmlspecialchars($order['status']) ?>
                </span>

            </div>

            <h3>Products Ordered</h3>

            <?php
            $itemStmt->execute([$order['id']]);
            $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <?php foreach ($items as $item): ?>

                <div class="product">

                    <h4>
                        <?= htmlspecialchars($item['product_name']) ?>
                    </h4>

                    <p>
                        Quantity:
                        <?= (int)$item['quantity'] ?>
                    </p>

                    <p>
                        Size:
                        <?= htmlspecialchars($item['selected_size'] ?? 'N/A') ?>
                    </p>

                    <p>
                        Colour:
                        <?= htmlspecialchars($item['selected_color'] ?? 'N/A') ?>
                    </p>

                    <p>
                        Price:
                        ₦<?= number_format(
                            (float)$item['price'] * (int)$item['quantity'],
                            2
                        ) ?>
                    </p>

                </div>

            <?php endforeach; ?>

            <div class="total">
                <span>Total Amount</span>

                <span>
                    ₦<?= number_format((float)$order['total_amount'], 2) ?>
                </span>
            </div>

            <p class="payment">
                Payment Method:
                <?= htmlspecialchars($order['payment_method']) ?>
            </p>

            <p class="payment">
                Payment Status:
                <?= htmlspecialchars($order['payment_status']) ?>
            </p>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</div>

</body>
</html>