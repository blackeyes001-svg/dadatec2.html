<?php
session_start();

require_once __DIR__ . '/includes/db.php';

// Initialise cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';

// ADD PRODUCT TO CART
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {

    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $size = trim($_POST['size'] ?? '');
    $color = trim($_POST['color'] ?? '');

    if (!$productId || !$quantity || $quantity < 1) {
        $message = 'Invalid product or quantity.';
    } else {

        $stmt = $pdo->prepare(
            "SELECT id, name, price, image, sizes, colors, stock
             FROM products WHERE id = ? LIMIT 1"
        );

        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $message = 'Product not found.';
        } elseif ($quantity > (int)$product['stock']) {
            $message = 'Requested quantity is not available.';
        } else {

            $key = $productId . '|' . $size . '|' . $color;

            $existingQuantity = $_SESSION['cart'][$key]['quantity'] ?? 0;

            if ($existingQuantity + $quantity > (int)$product['stock']) {
                $message = 'You cannot add more than the available stock.';
            } else {

                $_SESSION['cart'][$key] = [
                    'product_id' => $productId,
                    'quantity' => $existingQuantity + $quantity,
                    'size' => $size,
                    'color' => $color
                ];

                $message = 'Product added to your shopping cart!';
            }
        }
    }
}

// UPDATE QUANTITY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {

    foreach ($_POST['quantities'] ?? [] as $key => $quantity) {

        $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

        if (!isset($_SESSION['cart'][$key])) {
            continue;
        }

        if (!$quantity || $quantity < 1) {
            unset($_SESSION['cart'][$key]);
            continue;
        }

        $productId = (int)$_SESSION['cart'][$key]['product_id'];

        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $stock = (int)$stmt->fetchColumn();

        $_SESSION['cart'][$key]['quantity'] = min($quantity, $stock);
    }

    $message = 'Shopping cart updated.';
}

// REMOVE ITEM
if (isset($_GET['remove'])) {

    $key = (string)$_GET['remove'];

    if (isset($_SESSION['cart'][$key])) {
        unset($_SESSION['cart'][$key]);
    }

    header('Location: cart.php');
    exit;
}

// FETCH CART PRODUCTS
$cartItems = [];
$grandTotal = 0;

foreach ($_SESSION['cart'] as $key => $item) {

    $stmt = $pdo->prepare(
        "SELECT id, name, price, image, stock
         FROM products WHERE id = ? LIMIT 1"
    );

    $stmt->execute([$item['product_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        unset($_SESSION['cart'][$key]);
        continue;
    }

    $quantity = (int)$item['quantity'];
    $subtotal = (float)$product['price'] * $quantity;

    $product['quantity'] = $quantity;
    $product['size'] = $item['size'];
    $product['color'] = $item['color'];
    $product['key'] = $key;
    $product['subtotal'] = $subtotal;

    $cartItems[] = $product;
    $grandTotal += $subtotal;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Shopping Cart | Global Zone Sport Enterprise</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #071426;
            color: white;
        }

        header {
            background: #10233d;
            padding: 20px 5%;
            border-bottom: 2px solid #2563EB;
        }

        header a {
            color: white;
            text-decoration: none;
            margin-right: 25px;
            font-weight: bold;
        }

        header a:hover {
            color: #39FF14;
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }

        h1 {
            color: white;
        }

        .message {
            background: #183d36;
            color: #39FF14;
            padding: 15px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .cart-card {
            display: flex;
            align-items: center;
            gap: 20px;
            background: #10233d;
            border: 1px solid #263f60;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 18px;
        }

        .cart-image {
            width: 120px;
            height: 120px;
            background: #172e4b;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: #aabbd0;
            text-align: center;
        }

        .cart-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .cart-info {
            flex: 1;
            min-width: 0;
        }

        .cart-info h3 {
            margin-top: 0;
        }

        .price {
            color: #39FF14;
            font-weight: bold;
        }

        .quantity-input {
            width: 75px;
            padding: 9px;
            background: #071426;
            color: white;
            border: 1px solid #456080;
            border-radius: 5px;
        }

        .remove {
            color: #ff6464;
            text-decoration: none;
            font-weight: bold;
        }

        .summary {
            background: #10233d;
            border: 1px solid #263f60;
            border-radius: 10px;
            padding: 25px;
            margin-top: 25px;
        }

        .total {
            color: #39FF14;
            font-size: 27px;
            font-weight: bold;
        }

        .btn {
            display: inline-block;
            padding: 13px 20px;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            margin: 8px 5px 0 0;
        }

        .btn-blue {
            background: #2563EB;
            color: white;
        }

        .btn-green {
            background: #39FF14;
            color: #071426;
        }

        .btn-back {
            background: #263f60;
            color: white;
        }

        .empty-cart {
            background: #10233d;
            padding: 40px 20px;
            text-align: center;
            border-radius: 10px;
        }

        @media(max-width: 650px) {
            .cart-card {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .cart-image {
                width: 90px;
                height: 90px;
            }

            .cart-info {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<header>
    <a href="index.php">Home</a>
    <a href="products.php">Shop Products</a>
    <a href="cart.php">Shopping Cart</a>
</header>

<main class="container">

    <h1>🛒 My Shopping Cart</h1>

    <?php if ($message): ?>
        <div class="message"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>

        <div class="empty-cart">
            <h2>Your cart is empty</h2>
            <p>You haven't added any products yet.</p>

            <a href="products.php" class="btn btn-blue">
                Start Shopping
            </a>
        </div>

    <?php else: ?>

        <form method="POST" action="cart.php">

            <?php foreach ($cartItems as $item): ?>

                <div class="cart-card">

                    <div class="cart-image">

                        <?php if (!empty($item['image'])): ?>

                            <img
                                src="images/<?= e(basename($item['image'])) ?>"
                                alt="<?= e($item['name']) ?>"
                            >

                        <?php else: ?>

                            Product Image

                        <?php endif; ?>

                    </div>

                    <div class="cart-info">

                        <h3><?= e($item['name']) ?></h3>

                        <p class="price">
                            ₦<?= number_format($item['price'], 2) ?>
                        </p>

                        <p>Size: <?= e($item['size'] ?: 'Not specified') ?></p>

                        <p>Colour: <?= e($item['color'] ?: 'Not specified') ?></p>

                        <label>
                            Quantity:

                            <input
                                type="number"
                                class="quantity-input"
                                name="quantities[<?= e($item['key']) ?>]"
                                value="<?= $item['quantity'] ?>"
                                min="1"
                                max="<?= (int)$item['stock'] ?>"
                            >
                        </label>

                        <p>
                            Subtotal:
                            <span class="price">
                                ₦<?= number_format($item['subtotal'], 2) ?>
                            </span>
                        </p>

                        <a
                            class="remove"
                            href="cart.php?remove=<?= urlencode($item['key']) ?>"
                            onclick="return confirm('Remove this product from your cart?')"
                        >
                            Remove Item
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

            <button
                type="submit"
                name="update_cart"
                value="1"
                class="btn btn-blue"
            >
                Update Cart
            </button>

        </form>

        <div class="summary">

            <h2>Cart Summary</h2>

            <p>Total Items: <?= count($cartItems) ?></p>

            <p class="total">
                Total: ₦<?= number_format($grandTotal, 2) ?>
            </p>

            <a href="products.php" class="btn btn-back">
                Continue Shopping
            </a>

            <a href="checkout.php" class="btn btn-green">
                Proceed to Checkout
            </a>

        </div>

    <?php endif; ?>

</main>

</body>
</html>