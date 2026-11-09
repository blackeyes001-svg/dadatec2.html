<?php
require_once __DIR__ . '/includes/session.php';

require_once __DIR__ . '/includes/db.php';

// Initialise cart
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';


// IMAGE PATH HANDLER
function getProductImage($image)
{
    $image = trim((string)($image ?? ''));

    if ($image === '') {
        return '';
    }

    // Support online image URLs
    $scheme = strtolower((string)parse_url($image, PHP_URL_SCHEME));

    if (
        in_array($scheme, ['http', 'https'], true) &&
        filter_var($image, FILTER_VALIDATE_URL)
    ) {
        return $image;
    }

    // Support local category image paths
    $image = ltrim($image, '/');

    if (strpos($image, 'images/') !== 0) {
        $image = 'images/' . $image;
    }

    return $image;
}


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
             FROM products
             WHERE id = ?
             LIMIT 1"
        );

        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {

            $message = 'Product not found.';

        } elseif ((int)$product['stock'] < 1) {

            $message = 'This product is currently out of stock.';

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

        if (!isset($_SESSION['cart'][$key])) {
            continue;
        }

        $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

        if (!$quantity || $quantity < 1) {
            unset($_SESSION['cart'][$key]);
            continue;
        }

        $productId = (int)$_SESSION['cart'][$key]['product_id'];

        $stmt = $pdo->prepare(
            "SELECT stock FROM products WHERE id = ? LIMIT 1"
        );

        $stmt->execute([$productId]);

        $stock = (int)$stmt->fetchColumn();

        if ($stock < 1) {
            unset($_SESSION['cart'][$key]);
            continue;
        }

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
$totalQuantity = 0;

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
    $stock = (int)$product['stock'];

    if ($stock < 1) {
        unset($_SESSION['cart'][$key]);
        continue;
    }

    if ($quantity > $stock) {
        $quantity = $stock;
        $_SESSION['cart'][$key]['quantity'] = $quantity;
    }

    $subtotal = (float)$product['price'] * $quantity;

    $product['quantity'] = $quantity;
    $product['size'] = $item['size'] ?? '';
    $product['color'] = $item['color'] ?? '';
    $product['key'] = $key;
    $product['subtotal'] = $subtotal;

    // Correct image path
    $product['image'] = getProductImage($product['image']);

    $cartItems[] = $product;

    $grandTotal += $subtotal;
    $totalQuantity += $quantity;
}


// ESCAPE OUTPUT
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Shopping Cart | Bayogoal Enterprises</title>

    <meta name="description" content="Review your shopping cart and purchase quality sports equipment from Bayogoal Enterprises.">

    <link rel="stylesheet" href="css/style.css">

    <style>

        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h1 {
            font-size: 30px;
            margin-bottom: 8px;
            color: #0b1b33;
            font-weight: 800;
        }

        .page-heading p {
            color: #475569;
        }

        .message {
            background: #183d36;
            color: #39FF14;
            padding: 15px;
            border: 1px solid #39FF14;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .cart-card {
            display: flex;
            align-items: center;
            gap: 22px;
            background: #10233d;
            border: 1px solid #263f60;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 18px;
            transition: 0.3s;
        }

        .cart-card:hover {
            border-color: #2563EB;
        }

        /* PRODUCT IMAGE */

        .cart-image {
            width: 125px;
            height: 125px;
            flex-shrink: 0;
            background: #172e4b;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: #aabbd0;
            text-align: center;
            padding: 10px;
        }

        .cart-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .cart-image-placeholder {
            color: #aabbd0;
            font-size: 13px;
            text-align: center;
        }

        /* PRODUCT INFORMATION */

        .cart-info {
            flex: 1;
            min-width: 0;
            color: #ffffff;
        }

        .cart-info h3 {
            font-size: 19px;
            margin-bottom: 8px;
            color: #ffffff;
            font-weight: 800;
        }

        .cart-info label,
        .subtotal {
            color: #ffffff;
            font-weight: 600;
        }

        .price {
            color: #39FF14;
            font-weight: bold;
        }

        .product-details {
            color: #dbe5f1;
            font-size: 14px;
            margin: 5px 0;
        }

        .quantity-input {
            width: 85px;
            padding: 10px;
            background: #071426;
            color: white;
            border: 1px solid #456080;
            border-radius: 5px;
            font-size: 16px;
            margin-left: 5px;
        }

        .quantity-input:focus {
            outline: 2px solid #39FF14;
        }

        .subtotal {
            margin: 12px 0;
        }

        /* REMOVE */

        .remove {
            display: inline-block;
            color: #ff6464;
            text-decoration: none;
            font-weight: bold;
            margin-top: 5px;
        }

        .remove:hover {
            text-decoration: underline;
        }

        /* SUMMARY */

        .summary {
            background: #10233d;
            border: 1px solid #263f60;
            border-radius: 12px;
            padding: 28px;
            margin-top: 30px;
            color: #ffffff;
        }

        .summary h2 {
            margin-bottom: 18px;
            color: #ffffff;
            font-weight: 800;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin: 12px 0;
            color: #dbe5f1;
        }

        .summary-row strong {
            color: #ffffff;
            font-weight: 700;
        }

        .total {
            color: #39FF14;
            font-size: 27px;
            font-weight: bold;
        }

        .summary-divider {
            border: none;
            border-top: 1px solid #456080;
            margin: 18px 0;
        }

        /* BUTTONS */

        .cart-btn {
            display: inline-block;
            padding: 13px 20px;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            margin: 8px 5px 0 0;
            font-size: 15px;
            transition: 0.3s;
        }

        .btn-blue {
            background: #2563EB;
            color: white;
        }

        .btn-blue:hover {
            background: #1748b5;
        }

        .btn-green {
            background: #39FF14;
            color: #071426;
        }

        .btn-green:hover {
            background: #2bd900;
            transform: translateY(-2px);
        }

        .btn-back {
            background: #263f60;
            color: white;
        }

        .btn-back:hover {
            background: #34577e;
        }

        /* EMPTY CART */

        .empty-cart {
            background: #10233d;
            border: 1px solid #263f60;
            padding: 50px 20px;
            text-align: center;
            border-radius: 12px;
            color: #ffffff;
        }

        .empty-cart h2 {
            margin-bottom: 10px;
            color: #ffffff;
            font-weight: 800;
        }

        .empty-cart p {
            color: #c5d2e2;
            margin-bottom: 15px;
        }

        /* MOBILE */

        @media (max-width: 650px) {

            .container {
                margin: 20px auto;
                padding: 15px;
            }

            .page-heading h1 {
                font-size: 25px;
            }

            .cart-card {
                align-items: flex-start;
                flex-wrap: wrap;
                padding: 16px;
                gap: 15px;
            }

            .cart-image {
                width: 90px;
                height: 90px;
            }

            .cart-info {
                width: 100%;
            }

            .cart-info h3 {
                font-size: 17px;
            }

            .summary {
                padding: 20px;
            }

            .summary-row {
                flex-wrap: wrap;
            }

            .total {
                font-size: 23px;
            }

            .cart-btn {
                text-align: center;
            }

        }

    </style>

</head>

<body>

    <?php include 'includes/header.php'; ?>


    <main class="container">

        <div class="page-heading">

            <h1>🛒 My Shopping Cart</h1>

            <p>Review your selected sports equipment before checkout.</p>

        </div>


        <?php if ($message): ?>

            <div class="message">
                <?= e($message) ?>
            </div>

        <?php endif; ?>


        <?php if (empty($cartItems)): ?>

            <div class="empty-cart">

                <h2>Your Cart is Empty</h2>

                <p>
                    You haven't added any products to your shopping cart yet.
                </p>

                <a href="products.php" class="cart-btn btn-blue">
                    Start Shopping
                </a>

            </div>

        <?php else: ?>


            <!-- CART PRODUCTS -->

            <form method="POST" action="cart.php">

                <?php foreach ($cartItems as $item): ?>

                    <div class="cart-card">

                        <!-- PRODUCT IMAGE -->

                        <div class="cart-image">

                            <?php if (!empty($item['image'])): ?>

                                <img
                                    src="<?= e($item['image']) ?>"
                                    alt="<?= e($item['name']) ?>"
                                    onerror="
                                        this.style.display='none';
                                        this.nextElementSibling.style.display='block';
                                    "
                                >

                                <span
                                    class="cart-image-placeholder"
                                    style="display:none;"
                                >
                                    Image unavailable
                                </span>

                            <?php else: ?>

                                <span class="cart-image-placeholder">
                                    Product image coming soon
                                </span>

                            <?php endif; ?>

                        </div>


                        <!-- PRODUCT INFORMATION -->

                        <div class="cart-info">

                            <h3><?= e($item['name']) ?></h3>

                            <p class="price">
                                ₦<?= number_format((float)$item['price'], 2) ?>
                            </p>

                            <p class="product-details">
                                Size:
                                <?= e($item['size'] ?: 'Not specified') ?>
                            </p>

                            <p class="product-details">
                                Colour:
                                <?= e($item['color'] ?: 'Not specified') ?>
                            </p>

                            <p style="margin: 10px 0;">

                                <label>
                                    Quantity:

                                    <input
                                        type="number"
                                        class="quantity-input"
                                        name="quantities[<?= e($item['key']) ?>]"
                                        value="<?= $item['quantity'] ?>"
                                        min="1"
                                        max="<?= (int)$item['stock'] ?>"
                                        required
                                    >
                                </label>

                            </p>

                            <p class="subtotal">

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
                    class="cart-btn btn-blue"
                >
                    Update Cart
                </button>

            </form>


            <!-- CART SUMMARY -->

            <div class="summary">

                <h2>Cart Summary</h2>

                <div class="summary-row">

                    <span>Total Items:</span>

                    <strong><?= $totalQuantity ?></strong>

                </div>

                <hr class="summary-divider">

                <div class="summary-row">

                    <strong>Grand Total:</strong>

                    <span class="total">
                        ₦<?= number_format($grandTotal, 2) ?>
                    </span>

                </div>

                <p style="color:#c5d2e2; font-size:13px; margin-top:10px;">

                    Delivery charges, if applicable, will be shown during checkout.

                </p>

                <div style="margin-top:20px;">

                    <a href="products.php" class="cart-btn btn-back">
                        Continue Shopping
                    </a>

                    <a href="checkout.php" class="cart-btn btn-green">
                        Proceed to Checkout →
                    </a>

                </div>

            </div>

        <?php endif; ?>

    </main>


    <?php include 'includes/footer.php'; ?>

    <script src="js/script.js"></script>

</body>

</html>