<?php
require_once __DIR__ . '/includes/session.php';

require_once __DIR__ . '/includes/db.php';

// Customers must be logged in.
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

// Safely display database values.
function e($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

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

    <title>My Orders | Bayogoal Enterprises</title>

    <meta name="description" content="View your Bayogoal Enterprises orders, purchases and payment information.">

    <link rel="stylesheet" href="css/style.css">

    <style>

        /* ORDERS PAGE */

        .orders-page {
            background: #071426;
            color: #ffffff;
            min-height: 70vh;
            padding: 45px 5%;
        }

        .orders-container {
            width: 100%;
            max-width: 1100px;
            margin: auto;
        }

        .orders-heading {
            margin-bottom: 30px;
        }

        .orders-heading h1 {
            font-size: 32px;
            margin-bottom: 10px;
            color: #ffffff;
        }

        .orders-heading p {
            color: #aabbd0;
            line-height: 1.7;
        }

        /* SUCCESS MESSAGE */

        .order-success {
            background: #183b2b;
            border: 1px solid #39FF14;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 25px;
            color: #ffffff;
            line-height: 1.6;
        }

        /* ORDER CARD */

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
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 15px;
            border-bottom: 1px solid #294568;
            padding-bottom: 20px;
            margin-bottom: 22px;
        }

        .order-header h3 {
            color: #39FF14;
            margin-bottom: 9px;
            font-size: 20px;
        }

        .order-header p {
            color: #aabbd0;
            font-size: 13px;
            margin-top: 5px;
        }

        /* ORDER STATUS */

        .order-status {
            background: #2563EB;
            color: #ffffff;
            padding: 9px 15px;
            border-radius: 20px;
            font-size: 13px;
            text-transform: capitalize;
        }

        .section-title {
            font-size: 18px;
            margin-bottom: 12px;
            color: #ffffff;
        }

        /* PRODUCTS */

        .order-product {
            padding: 17px 0;
            border-bottom: 1px solid #294568;
        }

        .order-product:last-child {
            border-bottom: none;
        }

        .order-product h4 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #ffffff;
        }

        .order-product p {
            color: #aabbd0;
            margin: 7px 0;
            font-size: 14px;
        }

        .order-product .product-price {
            color: #39FF14;
            font-weight: bold;
        }

        /* TOTAL */

        .order-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            border-top: 1px solid #294568;
            padding-top: 20px;
            margin-top: 15px;
            font-size: 19px;
            font-weight: bold;
        }

        .order-total span:last-child {
            color: #39FF14;
        }

        /* PAYMENT DETAILS */

        .payment-details {
            margin-top: 20px;
            padding: 15px;
            background: #071426;
            border-radius: 8px;
        }

        .payment-details p {
            color: #aabbd0;
            font-size: 14px;
            margin: 8px 0;
            line-height: 1.6;
        }

        .payment-details strong {
            color: #ffffff;
        }

        /* EMPTY ORDERS */

        .empty-orders {
            background: #10233d;
            border: 1px solid #294568;
            padding: 45px 20px;
            border-radius: 12px;
            text-align: center;
        }

        .empty-orders h2 {
            color: #39FF14;
            margin-bottom: 12px;
        }

        .empty-orders p {
            color: #aabbd0;
            margin-bottom: 20px;
            line-height: 1.7;
        }

        .orders-shop-btn {
            display: inline-block;
            background: #2563EB;
            color: #ffffff;
            text-decoration: none;
            padding: 13px 22px;
            border-radius: 7px;
            font-weight: bold;
            transition: 0.3s;
        }

        .orders-shop-btn:hover {
            background: #39FF14;
            color: #071426;
        }

        /* MOBILE RESPONSIVENESS */

        @media (max-width: 650px) {

            .orders-page {
                padding: 30px 4%;
            }

            .orders-heading h1 {
                font-size: 26px;
            }

            .order-card {
                padding: 18px;
            }

            .order-total {
                font-size: 16px;
            }

        }

        @media (max-width: 380px) {

            .order-total {
                font-size: 14px;
            }

            .order-status {
                font-size: 12px;
            }

        }

    </style>

</head>

<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="orders-page">

    <div class="orders-container">

        <div class="orders-heading">

            <h1>My Orders</h1>

            <p>
                View your purchases, order details and payment information.
            </p>

        </div>

        <?php if (isset($_GET['success'])): ?>

            <div class="order-success" role="status">

                Your order has been placed successfully!
                Thank you for shopping with Bayogoal Enterprises.

            </div>

        <?php endif; ?>

        <?php if (empty($orders)): ?>

            <div class="empty-orders">

                <h2>No Orders Yet</h2>

                <p>
                    You haven't placed any orders yet.
                    Visit our products page to start shopping.
                </p>

                <a href="products.php" class="orders-shop-btn">
                    Start Shopping
                </a>

            </div>

        <?php else: ?>

            <?php foreach ($orders as $order): ?>

                <div class="order-card">

                    <div class="order-header">

                        <div>

                            <h3>
                                Order #<?= (int) $order['id'] ?>
                            </h3>

                            <p>
                                Order Date:
                                <?= e($order['created_at']) ?>
                            </p>

                        </div>

                        <span class="order-status">
                            <?= e($order['status']) ?>
                        </span>

                    </div>

                    <h3 class="section-title">
                        Products Ordered
                    </h3>

                    <?php

                    $itemStmt->execute([$order['id']]);

                    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

                    ?>

                    <?php if (empty($items)): ?>

                        <p>
                            No product details are available for this order.
                        </p>

                    <?php else: ?>

                        <?php foreach ($items as $item): ?>

                            <div class="order-product">

                                <h4>
                                    <?= e($item['product_name']) ?>
                                </h4>

                                <p>
                                    Quantity:
                                    <?= (int) $item['quantity'] ?>
                                </p>

                                <p>
                                    Size:
                                    <?= e($item['selected_size'] ?? 'N/A') ?>
                                </p>

                                <p>
                                    Colour:
                                    <?= e($item['selected_color'] ?? 'N/A') ?>
                                </p>

                                <p class="product-price">

                                    Price: ₦<?= number_format(
                                        (float) $item['price'] *
                                        (int) $item['quantity'],
                                        2
                                    ) ?>

                                </p>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    <div class="order-total">

                        <span>Total Amount</span>

                        <span>
                            ₦<?= number_format(
                                (float) $order['total_amount'],
                                2
                            ) ?>
                        </span>

                    </div>

                    <div class="payment-details">

                        <p>
                            <strong>Payment Method:</strong>

                            <?= e($order['payment_method']) ?>
                        </p>

                        <p>
                            <strong>Payment Status:</strong>

                            <?= e($order['payment_status']) ?>
                        </p>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="js/script.js"></script>

</body>
</html>