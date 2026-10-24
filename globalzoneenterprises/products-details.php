```php
<?php
session_start();

require_once __DIR__ . '/includes/db.php';

// Get the product ID from the URL
$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Stop if the product ID is missing or invalid
if (!$productId || $productId < 1) {
    http_response_code(400);
    exit('Invalid product ID. Please return to the products page.');
}

// Retrieve the selected product
try {
    $stmt = $pdo->prepare(
        "SELECT id, name, category, description, price, image,
                sizes, colors, stock
         FROM products
         WHERE id = :id
         LIMIT 1"
    );

    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();

} catch (PDOException $e) {
    error_log('Product details error: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to load this product right now. Please try again later.');
}

// Show a message if the product does not exist
if (!$product) {
    http_response_code(404);
    exit('Product not found. Please return to the products page.');
}

// Safely display text
function escapeDetail($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Prepare size and colour options
$sizes = array_filter(
    array_map('trim', explode(',', (string) ($product['sizes'] ?? '')))
);

$colors = array_filter(
    array_map('trim', explode(',', (string) ($product['colors'] ?? '')))
);

$stock = (int) $product['stock'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= escapeDetail($product['name']) ?> |
        Global Zone Sport Enterprise
    </title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        .details-container {
            max-width: 1100px;
            margin: 45px auto;
            padding: 20px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            color: #2563EB;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .details-card {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 35px;
            background: #fff;
            color: #222;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .details-image {
            min-height: 350px;
            background: #f3f4f6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .details-image img {
            display: block;
            width: 100%;
            max-height: 450px;
            object-fit: contain;
        }

        .image-placeholder {
            padding: 30px;
            text-align: center;
            color: #666;
        }

        .details-info {
            padding: 10px;
        }

        .details-category {
            display: inline-block;
            color: #2563EB;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .details-info h1 {
            font-size: 30px;
            margin: 15px 0;
            line-height: 1.3;
        }

        .details-price {
            color: #2563EB;
            font-size: 28px;
            font-weight: bold;
            margin: 20px 0;
        }

        .details-description {
            color: #555;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .detail-option {
            margin: 18px 0;
        }

        .detail-option strong {
            display: block;
            margin-bottom: 8px;
        }

        .option-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .option-item {
            padding: 7px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9fafb;
        }

        .stock-available {
            color: #15803d;
            font-weight: bold;
        }

        .stock-unavailable {
            color: #dc2626;
            font-weight: bold;
        }

        .quantity-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .quantity-input {
            width: 100px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        .details-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 25px;
        }

        .details-button {
            display: inline-block;
            padding: 13px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .cart-button {
            background: #2563EB;
            color: #fff;
        }

        .cart-button:hover {
            background: #1748b5;
        }

        .back-button {
            background: #e5e7eb;
            color: #222;
        }

        .back-button:hover {
            background: #d1d5db;
        }

        @media (max-width: 750px) {
            .details-card {
                grid-template-columns: 1fr;
                gap: 20px;
                padding: 15px;
            }

            .details-image {
                min-height: 250px;
            }

            .details-info h1 {
                font-size: 24px;
            }

            .details-price {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<header style="padding:18px 5%; background:#111; color:white;">
    <a href="index.php"
       style="color:white; text-decoration:none; margin-right:20px;">
        Home
    </a>

    <a href="products.php"
       style="color:white; text-decoration:none; margin-right:20px;">
        Shop Products
    </a>

    <a href="cart.php"
       style="color:white; text-decoration:none;">
        Shopping Cart
    </a>
</header>

<main class="details-container">

    <a href="products.php" class="back-link">
        &larr; Back to Products
    </a>

    <section class="details-card">

        <div class="details-image">

            <?php if (!empty($product['image'])): ?>

                <img
                    src="images/<?= escapeDetail(basename($product['image'])) ?>"
                    alt="<?= escapeDetail($product['name']) ?>"
                >

            <?php else: ?>

                <div class="image-placeholder">
                    <h2>Product Image</h2>
                    <p>We will add the product picture here.</p>
                </div>

            <?php endif; ?>

        </div>

        <div class="details-info">

            <span class="details-category">
                <?= escapeDetail($product['category']) ?>
            </span>

            <h1><?= escapeDetail($product['name']) ?></h1>

            <div class="details-price">
                ₦<?= number_format((float) $product['price'], 2) ?>
            </div>

            <div class="details-description">
                <?= nl2br(escapeDetail(
                    $product['description'] ?: 'Quality sports product from Global Zone Sport Enterprise.'
                )) ?>
            </div>

            <?php if ($sizes): ?>
                <div class="detail-option">
                    <strong>Available Sizes</strong>

                    <div class="option-list">
                        <?php foreach ($sizes as $size): ?>
                            <span class="option-item">
                                <?= escapeDetail($size) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($colors): ?>
                <div class="detail-option">
                    <strong>Available Colours</strong>

                    <div class="option-list">
                        <?php foreach ($colors as $color): ?>
                            <span class="option-item">
                                <?= escapeDetail($color) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <p>
                <strong>Availability:</strong>

                <?php if ($stock > 0): ?>
                    <span class="stock-available">
                        In Stock (<?= $stock ?> available)
                    </span>
                <?php else: ?>
                    <span class="stock-unavailable">
                        Out of Stock
                    </span>
                <?php endif; ?>
            </p>

            <?php if ($stock > 0): ?>

                <form action="cart.php" method="POST">

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= (int) $product['id'] ?>"
                    >

                    <label for="quantity" class="quantity-label">
                        Select Quantity
                    </label>

                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        class="quantity-input"
                        value="1"
                        min="1"
                        max="<?= $stock ?>"
                        required
                    >

                    <div class="details-buttons">

                        <button
                            type="submit"
                            name="add_to_cart"
                            value="1"
                            class="details-button cart-button"
                        >
                            Add to Cart
                        </button>

                        <a
                            href="products.php"
                            class="details-button back-button"
                        >
                            Continue Shopping
                        </a>

                    </div>

                </form>

            <?php else: ?>

                <div class="details-buttons">
                    <a href="products.php"
                       class="details-button back-button">
                        Continue Shopping
                    </a>
                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

</body>
</html>
```
