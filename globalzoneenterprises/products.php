```php
<?php
session_start();

require_once __DIR__ . '/includes/db.php';

// Get the selected product ID from the URL
$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    http_response_code(400);
    exit('Invalid product ID. Please return to the shop.');
}

// Fetch the selected product from the database
try {
    $stmt = $pdo->prepare(
        "SELECT id, name, category, description, price,
                image, sizes, colors, stock
         FROM products
         WHERE id = :id
         LIMIT 1"
    );

    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();

} catch (PDOException $e) {
    error_log('Product details error: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to load this product. Please try again later.');
}

// Check whether the product exists
if (!$product) {
    http_response_code(404);
    exit('Product not found. Please return to the shop.');
}

// Safely display text
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Convert comma-separated sizes and colours into lists
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

    <title><?= e($product['name']) ?> | Global Zone Sport Enterprise</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        .details-container {
            max-width: 1150px;
            margin: 40px auto;
            padding: 20px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            color: #2563EB;
            text-decoration: none;
            font-weight: bold;
        }

        .details-card {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 35px;
            padding: 30px;
            background: #fff;
            color: #222;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .details-image {
            min-height: 350px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            background: #f3f4f6;
            border-radius: 10px;
        }

        .details-image img {
            width: 100%;
            max-height: 450px;
            object-fit: contain;
        }

        .image-placeholder {
            padding: 25px;
            text-align: center;
            color: #666;
        }

        .details-info h1 {
            margin: 15px 0;
            font-size: 30px;
            line-height: 1.3;
        }

        .category {
            color: #2563EB;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }

        .price {
            margin: 20px 0;
            color: #2563EB;
            font-size: 28px;
            font-weight: bold;
        }

        .description {
            margin: 20px 0;
            line-height: 1.8;
            color: #555;
        }

        .option-section {
            margin: 20px 0;
        }

        .option-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .option-item {
            padding: 7px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9fafb;
        }

        .in-stock {
            color: #15803d;
            font-weight: bold;
        }

        .out-stock {
            color: #dc2626;
            font-weight: bold;
        }

        .quantity-input {
            display: block;
            width: 100px;
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .button-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 25px;
        }

        .btn {
            display: inline-block;
            padding: 13px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            font-size: 15px;
        }

        .btn-cart {
            background: #2563EB;
            color: white;
        }

        .btn-cart:hover {
            background: #1748b5;
        }

        .btn-back {
            background: #e5e7eb;
            color: #222;
        }

        @media (max-width: 750px) {
            .details-card {
                grid-template-columns: 1fr;
                padding: 18px;
            }

            .details-image {
                min-height: 250px;
            }

            .details-info h1 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<header style="padding:18px 5%; background:#111; color:white;">
    <a href="index.php" style="color:white; margin-right:20px; text-decoration:none;">
        Home
    </a>

    <a href="products.php" style="color:white; margin-right:20px; text-decoration:none;">
        Shop Products
    </a>

    <a href="cart.php" style="color:white; text-decoration:none;">
        Shopping Cart
    </a>
</header>

<main class="details-container">

    <a href="products.php" class="back-link">
        &larr; Back to Products
    </a>

    <section class="details-card">

        <!-- Product Image -->
        <div class="details-image">

            <?php if (!empty($product['image'])): ?>

                <img
                    src="images/<?= e(basename($product['image'])) ?>"
                    alt="<?= e($product['name']) ?>"
                >

            <?php else: ?>

                <div class="image-placeholder">
                    <h2>Product Image</h2>
                    <p>Product picture coming soon.</p>
                </div>

            <?php endif; ?>

        </div>

        <!-- Product Information -->
        <div class="details-info">

            <span class="category">
                <?= e($product['category']) ?>
            </span>

            <h1><?= e($product['name']) ?></h1>

            <div class="price">
                ₦<?= number_format((float) $product['price'], 2) ?>
            </div>

            <div class="description">
                <?= nl2br(e(
                    $product['description']
                    ?: 'Discover quality sports equipment from Global Zone Sport Enterprise.'
                )) ?>
            </div>

            <!-- Available Sizes -->
            <?php if (!empty($sizes)): ?>
                <div class="option-section">
                    <strong>Available Sizes</strong>

                    <div class="option-list">
                        <?php foreach ($sizes as $size): ?>
                            <span class="option-item"><?= e($size) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Available Colours -->
            <?php if (!empty($colors)): ?>
                <div class="option-section">
                    <strong>Available Colours</strong>

                    <div class="option-list">
                        <?php foreach ($colors as $color): ?>
                            <span class="option-item"><?= e($color) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Stock Availability -->
            <p>
                <strong>Availability:</strong>

                <?php if ($stock > 0): ?>
                    <span class="in-stock">
                        In Stock (<?= $stock ?> available)
                    </span>
                <?php else: ?>
                    <span class="out-stock">Out of Stock</span>
                <?php endif; ?>
            </p>

            <!-- Quantity and Add to Cart -->
            <?php if ($stock > 0): ?>

                <form action="cart.php" method="POST">

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= (int) $product['id'] ?>"
                    >

                    <label for="quantity">
                        <strong>Quantity</strong>
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

                    <div class="button-row">

                        <button
                            type="submit"
                            name="add_to_cart"
                            value="1"
                            class="btn btn-cart"
                        >
                            Add to Cart
                        </button>

                        <a href="products.php" class="btn btn-back">
                            Continue Shopping
                        </a>

                    </div>

                </form>

            <?php else: ?>

                <p class="out-stock">
                    This product is currently unavailable.
                </p>

                <div class="button-row">
                    <a href="products.php" class="btn btn-back">
                        Continue Shopping
                    </a>
                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

</body>
</html>
