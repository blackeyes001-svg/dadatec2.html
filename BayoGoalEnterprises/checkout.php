
<?php
require_once __DIR__ . '/includes/session.php';

require_once __DIR__ . '/includes/db.php';

// CUSTOMER LOGIN CHECK
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// CART CHECK
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$products = [];
$subtotal = 0;
$deliveryFee = 0;
$total = 0;
$message = '';

$fullName = '';
$email = '';
$phone = '';
$address = '';
$city = '';
$state = '';
$payment = '';

// FETCH CART PRODUCTS
foreach ($_SESSION['cart'] as $key => $item) {

    $stmt = $pdo->prepare(
        "SELECT id, name, price, image, stock
         FROM products
         WHERE id = ?
         LIMIT 1"
    );

    $stmt->execute([(int)$item['product_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        unset($_SESSION['cart'][$key]);
        continue;
    }

    $quantity = (int)$item['quantity'];

    if ($quantity < 1) {
        unset($_SESSION['cart'][$key]);
        continue;
    }

    $lineTotal = (float)$product['price'] * $quantity;

    $subtotal += $lineTotal;

    $products[] = [
        'product' => $product,
        'quantity' => $quantity,
        'size' => $item['size'] ?? null,
        'color' => $item['color'] ?? null,
        'total' => $lineTotal
    ];
}

$total = $subtotal + $deliveryFee;

// CHECK CART AGAIN
if (empty($products)) {
    header("Location: cart.php");
    exit;
}

// HANDLE ORDER SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $payment = $_POST['payment_method'] ?? '';

    $allowedPayments = [
        'bank_transfer',
        'card',
        'cash_on_delivery'
    ];

    if (
        $fullName === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $phone === '' ||
        $address === '' ||
        $city === '' ||
        $state === '' ||
        !in_array($payment, $allowedPayments, true)
    ) {

        $message = "Please complete all required fields correctly.";

    } elseif (empty($products)) {

        $message = "Your cart does not contain valid products.";

    } else {

        try {

            $pdo->beginTransaction();

            $userId = (int)$_SESSION['user_id'];

            $deliveryAddress = $address . ', ' . $city . ', ' . $state;

            /*
             * LOCK PRODUCTS AND VERIFY STOCK.
             * Recalculate prices using current database values.
             */

            $verifiedProducts = [];
            $subtotal = 0;

            foreach ($_SESSION['cart'] as $item) {

                $productId = (int)$item['product_id'];
                $quantity = (int)$item['quantity'];

                $stmt = $pdo->prepare(
                    "SELECT id, name, price, stock
                     FROM products
                     WHERE id = ?
                     FOR UPDATE"
                );

                $stmt->execute([$productId]);

                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    throw new Exception("A product in your cart no longer exists.");
                }

                if ($quantity < 1 || $quantity > (int)$product['stock']) {
                    throw new Exception(
                        "Insufficient stock for " . $product['name'] .
                        ". Please update your cart."
                    );
                }

                $lineTotal = (float)$product['price'] * $quantity;

                $subtotal += $lineTotal;

                $verifiedProducts[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'size' => $item['size'] ?? null,
                    'color' => $item['color'] ?? null,
                    'total' => $lineTotal
                ];
            }

            $total = $subtotal + $deliveryFee;

            // CREATE MAIN ORDER

            $stmt = $pdo->prepare("
                INSERT INTO orders
                (
                    user_id,
                    total_amount,
                    status,
                    delivery_address,
                    phone,
                    payment_method,
                    payment_status
                )
                VALUES (?, ?, 'pending', ?, ?, ?, 'unpaid')
            ");

            $stmt->execute([
                $userId,
                $total,
                $deliveryAddress,
                $phone,
                $payment
            ]);

            $orderId = $pdo->lastInsertId();

            // SAVE ORDER ITEMS AND REDUCE STOCK

            foreach ($verifiedProducts as $item) {

                $productId = (int)$item['product']['id'];
                $quantity = (int)$item['quantity'];

                $stockStmt = $pdo->prepare("
                    UPDATE products
                    SET stock = stock - ?
                    WHERE id = ? AND stock >= ?
                ");

                $stockStmt->execute([
                    $quantity,
                    $productId,
                    $quantity
                ]);

                if ($stockStmt->rowCount() !== 1) {
                    throw new Exception(
                        "Unable to update stock for " .
                        $item['product']['name']
                    );
                }

                $itemStmt = $pdo->prepare("
                    INSERT INTO order_items
                    (
                        order_id,
                        product_id,
                        quantity,
                        price,
                        selected_size,
                        selected_color
                    )
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $itemStmt->execute([
                    $orderId,
                    $productId,
                    $quantity,
                    $item['product']['price'],
                    $item['size'],
                    $item['color']
                ]);
            }

            // COMPLETE TRANSACTION

            $pdo->commit();

            // CLEAR CART AFTER SUCCESSFUL ORDER

            unset($_SESSION['cart']);

            header("Location: orders.php?success=1");
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Bayogoal checkout error: ' . $e->getMessage());

            $message = "Your order could not be placed. " .
                       "Please check your cart and try again.";
        }
    }
}

// ESCAPE OUTPUT
function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout | Bayogoal Enterprises</title>

    <meta name="description" content="Complete your sports equipment purchase at Bayogoal Enterprises.">

    <link rel="stylesheet" href="css/style.css">

    <style>

        /* CHECKOUT PAGE */

        .container {
            width: 90%;
            max-width: 1150px;
            margin: 40px auto;
        }

        .back {
            display: inline-block;
            color: #39FF14;
            text-decoration: none;
            margin-bottom: 25px;
            font-weight: bold;
        }

        .back:hover {
            text-decoration: underline;
        }

        .container h1 {
            margin-bottom: 8px;
            font-size: 32px;
        }

        .subtitle {
            color: #aabbd0;
            margin-bottom: 30px;
        }

        /* CHECKOUT LAYOUT */

        .checkout-layout {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 25px;
            align-items: start;
        }

        .box {
            background: #10233d;
            border: 1px solid #263f60;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .box h2 {
            margin-bottom: 20px;
            color: #39FF14;
            font-size: 21px;
        }

        /* FORM */

        .checkout-form label {
            display: block;
            margin: 14px 0 7px;
            font-weight: bold;
        }

        .checkout-form input,
        .checkout-form textarea {
            width: 100%;
            padding: 13px;

            border: 1px solid #294568;
            border-radius: 6px;

            background: #071426;
            color: white;

            font-size: 15px;
            font-family: inherit;
        }

        .checkout-form input:focus,
        .checkout-form textarea:focus {
            outline: 2px solid #39FF14;
            border-color: #39FF14;
        }

        .checkout-form textarea {
            resize: vertical;
        }

        /* PAYMENT OPTIONS */

        .payment-option {
            display: flex !important;
            align-items: center;
            gap: 12px;

            padding: 14px;

            border: 1px solid #294568;
            border-radius: 7px;

            margin-bottom: 12px !important;
            cursor: pointer;
            transition: 0.3s;
        }

        .payment-option input {
            width: 18px !important;
            height: 18px;
            accent-color: #39FF14;
        }

        .payment-option:hover {
            border-color: #39FF14;
            background: #172e4b;
        }

        /* ORDER ITEMS */

        .order-item {
            padding: 15px 0;
            border-bottom: 1px solid #294568;
        }

        .order-item h3 {
            font-size: 17px;
            margin-bottom: 8px;
        }

        .order-item p {
            color: #bbb;
            margin: 6px 0;
        }

        .price {
            color: #39FF14;
            font-weight: bold;
        }

        /* SUMMARY */

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin: 17px 0;
        }

        .grand-total {
            border-top: 1px solid #294568;
            padding-top: 18px;
            font-size: 20px;
            font-weight: bold;
        }

        .order-summary {
            position: sticky;
            top: 20px;
        }

        /* BUTTON */

        .place-order-btn {
            width: 100%;
            padding: 15px;

            background: #2563EB;
            border: none;
            color: white;

            font-size: 16px;
            font-weight: bold;

            border-radius: 7px;
            cursor: pointer;

            margin-top: 20px;
            transition: 0.3s;
        }

        .place-order-btn:hover {
            background: #39FF14;
            color: #071426;
        }

        /* MESSAGE */

        .message {
            background: #183b2b;
            border: 1px solid #39FF14;
            color: white;
            padding: 15px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        /* MOBILE RESPONSIVENESS */

        @media (max-width: 800px) {

            .checkout-layout {
                grid-template-columns: 1fr;
            }

            .container {
                width: 94%;
                margin: 25px auto;
            }

            .order-summary {
                position: static;
            }

            .container h1 {
                font-size: 27px;
            }
        }

        @media (max-width: 480px) {

            .box {
                padding: 18px;
            }

            .summary-row {
                flex-wrap: wrap;
            }

        }

    </style>

</head>

<body>

    <!-- SHARED HEADER -->
    <?php include 'includes/header.php'; ?>


    <main class="container">

        <a href="cart.php" class="back">
            &larr; Back to Cart
        </a>

        <h1>Checkout</h1>

        <p class="subtitle">
            Complete your delivery and payment information.
        </p>


        <?php if ($message): ?>

            <div class="message">
                <?= e($message) ?>
            </div>

        <?php endif; ?>


        <div class="checkout-layout">


            <!-- CUSTOMER INFORMATION -->

            <div>

                <form method="POST" action="checkout.php" class="checkout-form">

                    <div class="box">

                        <h2>Customer Information</h2>

                        <label for="full_name">Full Name</label>

                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            value="<?= e($fullName) ?>"
                            autocomplete="name"
                            required
                        >

                        <label for="email">Email Address</label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= e($email) ?>"
                            autocomplete="email"
                            required
                        >

                        <label for="phone">Phone Number</label>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="<?= e($phone) ?>"
                            autocomplete="tel"
                            required
                        >

                    </div>


                    <!-- DELIVERY INFORMATION -->

                    <div class="box">

                        <h2>Delivery Information</h2>

                        <label for="address">Delivery Address</label>

                        <textarea
                            id="address"
                            name="address"
                            rows="3"
                            autocomplete="street-address"
                            required
                        ><?= e($address) ?></textarea>

                        <label for="city">City</label>

                        <input
                            type="text"
                            id="city"
                            name="city"
                            value="<?= e($city) ?>"
                            autocomplete="address-level2"
                            required
                        >

                        <label for="state">State</label>

                        <input
                            type="text"
                            id="state"
                            name="state"
                            value="<?= e($state) ?>"
                            autocomplete="address-level1"
                            required
                        >

                    </div>


                    <!-- PAYMENT INFORMATION -->

                    <div class="box">

                        <h2>Payment Method</h2>

                        <label class="payment-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="bank_transfer"
                                <?= $payment === 'bank_transfer' ? 'checked' : '' ?>
                                required
                            >

                            Bank Transfer

                        </label>


                        <label class="payment-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="card"
                                <?= $payment === 'card' ? 'checked' : '' ?>
                            >

                            Debit / Credit Card

                        </label>


                        <label class="payment-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="cash_on_delivery"
                                <?= $payment === 'cash_on_delivery' ? 'checked' : '' ?>
                            >

                            Cash on Delivery

                        </label>


                        <button type="submit" class="place-order-btn">
                            Place Order
                        </button>

                    </div>

                </form>

            </div>


            <!-- ORDER SUMMARY -->

            <div class="box order-summary">

                <h2>Your Order</h2>

                <?php foreach ($products as $item): ?>

                    <div class="order-item">

                        <h3>
                            <?= e($item['product']['name']) ?>
                        </h3>

                        <p>
                            Size:
                            <?= e($item['size'] ?? 'N/A') ?>
                        </p>

                        <p>
                            Colour:
                            <?= e($item['color'] ?? 'N/A') ?>
                        </p>

                        <p>
                            Quantity:
                            <?= (int)$item['quantity'] ?>
                        </p>

                        <p class="price">
                            ₦<?= number_format($item['total'], 2) ?>
                        </p>

                    </div>

                <?php endforeach; ?>


                <div class="summary-row">

                    <span>Subtotal</span>

                    <span>
                        ₦<?= number_format($subtotal, 2) ?>
                    </span>

                </div>


                <div class="summary-row">

                    <span>Delivery Fee</span>

                    <span>
                        ₦<?= number_format($deliveryFee, 2) ?>
                    </span>

                </div>


                <div class="summary-row grand-total">

                    <span>Total</span>

                    <span class="price">
                        ₦<?= number_format($total, 2) ?>
                    </span>

                </div>

                <p style="color:#aabbd0; font-size:13px;">
                    Your order will be saved after successful submission.
                </p>

            </div>

        </div>

    </main>


    <!-- SHARED FOOTER -->
    <?php include 'includes/footer.php'; ?>


    <script src="js/script.js"></script>

</body>

</html>
