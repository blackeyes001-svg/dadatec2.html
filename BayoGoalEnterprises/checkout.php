<?php
session_start();
require_once 'includes/db.php';

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

// A customer must be logged in before placing an order.
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$cartItems = $_SESSION['cart'];
$products = [];
$subtotal = 0;
$deliveryFee = 0;
$total = 0;
$message = '';

foreach ($cartItems as $item) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$item['product_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $quantity = (int)$item['quantity'];
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
}

$total = $subtotal + $deliveryFee;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['full_name'] ?? '');
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
        $name === '' ||
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

            // Create the main order.
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

            // Save each product belonging to the order.
            foreach ($products as $item) {

                $productId = (int)$item['product']['id'];
                $quantity = (int)$item['quantity'];

                // Check stock and reduce it safely.
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
                        "Insufficient stock for " .
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

            $pdo->commit();

            // Empty the cart after successful order placement.
            unset($_SESSION['cart']);

            header("Location: orders.php?success=1");
            exit;

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $message = "Order could not be placed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Checkout | Global Zone Sport Enterprise</title>

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
    max-width: 1150px;
    margin: 40px auto;
}

h1 {
    margin-bottom: 10px;
}

.subtitle {
    color: #aaa;
    margin-bottom: 30px;
}

.checkout-layout {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 25px;
}

.box {
    background: #10233d;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.box h2 {
    margin-bottom: 20px;
    color: #39FF14;
    font-size: 21px;
}

label {
    display: block;
    margin: 14px 0 7px;
}

input, textarea {
    width: 100%;
    padding: 13px;
    border: 1px solid #294568;
    border-radius: 6px;
    background: #071426;
    color: white;
    outline: none;
}

.payment-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px;
    border: 1px solid #294568;
    border-radius: 7px;
    margin-bottom: 12px;
    cursor: pointer;
}

.payment-option input {
    width: auto;
}

.payment-option:hover {
    border-color: #39FF14;
}

.order-item {
    padding: 15px 0;
    border-bottom: 1px solid #294568;
}

.order-item p {
    color: #bbb;
    margin: 6px 0;
}

.price {
    color: #39FF14;
    font-weight: bold;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin: 17px 0;
}

.grand-total {
    border-top: 1px solid #294568;
    padding-top: 18px;
    font-size: 20px;
    font-weight: bold;
}

button {
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
}

button:hover {
    background: #39FF14;
    color: #071426;
}

.message {
    background: #183b2b;
    border: 1px solid #39FF14;
    padding: 15px;
    border-radius: 7px;
    margin-bottom: 20px;
}

.back {
    display: inline-block;
    color: #39FF14;
    text-decoration: none;
    margin-bottom: 25px;
}

@media(max-width: 800px) {
    .checkout-layout {
        grid-template-columns: 1fr;
    }

    .container {
        width: 94%;
    }
}
</style>
</head>

<body>

<header>
    <h2>GLOBAL ZONE SPORT ENTERPRISE</h2>
</header>

<div class="container">

<a href="cart.php" class="back">← Back to Cart</a>

<h1>Checkout</h1>
<p class="subtitle">Complete your delivery and payment information.</p>

<?php if ($message): ?>
<div class="message">
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<div class="checkout-layout">

<div>

<form method="POST">

<div class="box">
    <h2>Customer Information</h2>

    <label>Full Name</label>
    <input type="text" name="full_name" required>

    <label>Email Address</label>
    <input type="email" name="email" required>

    <label>Phone Number</label>
    <input type="tel" name="phone" required>
</div>

<div class="box">
    <h2>Delivery Information</h2>

    <label>Delivery Address</label>
    <textarea name="address" rows="3" required></textarea>

    <label>City</label>
    <input type="text" name="city" required>

    <label>State</label>
    <input type="text" name="state" required>
</div>

<div class="box">
    <h2>Payment Method</h2>

    <label class="payment-option">
        <input type="radio" name="payment_method"
               value="bank_transfer" required>
        Bank Transfer
    </label>

    <label class="payment-option">
        <input type="radio" name="payment_method"
               value="card">
        Debit / Credit Card
    </label>

    <label class="payment-option">
        <input type="radio" name="payment_method"
               value="cash_on_delivery">
        Pay on Delivery
    </label>

    <button type="submit">Place Order</button>
</div>

</form>

</div>

<div>

<div class="box">
    <h2>Your Order</h2>

    <?php foreach ($products as $item): ?>

    <div class="order-item">

        <h3>
            <?= htmlspecialchars($item['product']['name']) ?>
        </h3>

        <p>Size: <?= htmlspecialchars($item['size'] ?? 'N/A') ?></p>

        <p>Colour: <?= htmlspecialchars($item['color'] ?? 'N/A') ?></p>

        <p>Quantity: <?= $item['quantity'] ?></p>

        <p class="price">
            ₦<?= number_format($item['total'], 2) ?>
        </p>

    </div>

    <?php endforeach; ?>

    <div class="summary-row">
        <span>Subtotal</span>
        <span>₦<?= number_format($subtotal, 2) ?></span>
    </div>

    <div class="summary-row">
        <span>Delivery Fee</span>
        <span>₦<?= number_format($deliveryFee, 2) ?></span>
    </div>

    <div class="summary-row grand-total">
        <span>Total</span>
        <span class="price">
            ₦<?= number_format($total, 2) ?>
        </span>
    </div>

</div>

</div>
</div>
</div>

</body>
</html>