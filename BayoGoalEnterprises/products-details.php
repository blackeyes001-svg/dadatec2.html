<?php
session_start();

require_once __DIR__ . '/includes/db.php';

// Get product ID
$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    http_response_code(400);
    exit('Invalid product ID. Please return to the shop.');
}

// Fetch product
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
    error_log('Bayogoal product details error: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to load this product. Please try again later.');
}

// Check product
if (!$product) {
    http_response_code(404);
    exit('Product not found. Please return to the shop.');
}

// Escape output
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Available sizes and colours
$sizes = array_filter(
    array_map('trim', explode(',', (string) ($product['sizes'] ?? '')))
);

$colors = array_filter(
    array_map('trim', explode(',', (string) ($product['colors'] ?? '')))
);

$stock = (int) $product['stock'];

// PRODUCT IMAGE HANDLING
// Supports online URLs and local category image paths.

$image = trim((string) ($product['image'] ?? ''));

if ($image !== '') {

    // Allow only HTTP and HTTPS external image URLs.
    $scheme = strtolower((string) parse_url($image, PHP_URL_SCHEME));

    if ($scheme === 'http' || $scheme === 'https') {

        // Keep online image URL unchanged.

    } else {

        // Keep existing images/ paths.
        if (strpos($image, 'images/') !== 0) {
            $image = 'images/' . ltrim($image, '/');
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($product['name']) ?> | Bayogoal Enterprises</title>

    <meta name="description" content="Discover quality sports equipment at Bayogoal Enterprises.">

    <link rel="stylesheet" href="css/style.css">

    <style>

        /* PRODUCT DETAILS PAGE */

        .details-container {
            max-width: 1200px;
            margin: 45px auto;
            padding: 20px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            color: #39FF14;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* PRODUCT CARD */

        .details-card {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding: 30px;
            background: #10233d;
            border: 1px solid #263f60;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        /* PRODUCT IMAGE */

        .details-image {
            min-height: 400px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            background: #172e4b;
            border-radius: 12px;
            padding: 15px;
        }

        .details-image img {
            width: 100%;
            max-height: 450px;
            object-fit: contain;
            border-radius: 8px;
        }

        .image-placeholder {
            text-align: center;
            color: #aabbd0;
        }

        .image-placeholder h2 {
            font-size: 30px;
            color: #39FF14;
        }

        /* PRODUCT INFORMATION */

        .details-info {
            min-width: 0;
            color: #ffffff;
        }

        .category {
            color: #39FF14;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }

        .details-info h1 {
            margin: 15px 0;
            font-size: 32px;
            line-height: 1.3;
            overflow-wrap: anywhere;
            color: #ffffff;
            font-weight: 800;
        }

        .price {
            margin: 20px 0;
            color: #39FF14;
            font-size: 30px;
            font-weight: bold;
        }

        .description {
            margin: 20px 0;
            line-height: 1.8;
            color: #c5d2e2;
        }

        /* OPTIONS */

        .option-section {
            margin: 25px 0;
        }

        .option-section strong {
            display: block;
            margin-bottom: 12px;
        }

        .option-section strong,
        .details-info p strong,
        .details-info label {
            color: #ffffff;
            font-weight: 700;
        }

        .size-select {
            width: 100%;
            max-width: 280px;
            padding: 13px;
            background: #071426;
            color: white;
            border: 1px solid #456080;
            border-radius: 7px;
            font-size: 16px;
            cursor: pointer;
        }

        .size-select:focus,
        .quantity-input:focus {
            outline: 2px solid #39FF14;
        }

        /* COLOURS */

        .color-options {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .color-option input {
            position: absolute;
            opacity: 0;
            width: 1px;
            height: 1px;
        }

        .color-option span {
            display: inline-block;
            padding: 11px 17px;
            background: #071426;
            color: white;
            border: 2px solid #456080;
            border-radius: 7px;
            cursor: pointer;
            transition: 0.2s;
        }

        .color-option input:checked + span {
            border-color: #39FF14;
            background: #183d36;
            color: #39FF14;
            font-weight: bold;
        }

        .color-option input:focus-visible + span {
            outline: 2px solid #39FF14;
        }

        .color-option span:hover {
            border-color: #2563EB;
        }

        /* STOCK */

        .in-stock {
            color: #39FF14;
            font-weight: bold;
        }

        .out-stock {
            color: #ff5555;
            font-weight: bold;
        }

        /* QUANTITY */

        .quantity-input {
            display: block;
            width: 110px;
            margin-top: 10px;
            padding: 12px;
            background: #071426;
            color: white;
            border: 1px solid #456080;
            border-radius: 7px;
            font-size: 16px;
        }

        /* BUTTONS */

        .button-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 25px;
        }

        .details-btn {
            display: inline-block;
            padding: 14px 22px;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            font-size: 15px;
            text-align: center;
            transition: 0.3s;
        }

        .btn-cart {
            background: #2563EB;
            color: white;
        }

        .btn-cart:hover {
            background: #1748b5;
            transform: translateY(-2px);
        }

        .btn-back {
            background: #263f60;
            color: white;
        }

        .btn-back:hover {
            background: #34577e;
        }

        /* MOBILE RESPONSIVENESS */

        @media (max-width: 750px) {

            .details-container {
                margin: 20px auto;
                padding: 15px;
            }

            .details-card {
                grid-template-columns: 1fr;
                padding: 18px;
                gap: 25px;
            }

            .details-image {
                min-height: 250px;
            }

            .details-info h1 {
                font-size: 25px;
            }

            .price {
                font-size: 26px;
            }

            .button-row {
                flex-direction: column;
            }

            .details-btn {
                width: 100%;
            }

        }

    </style>

</head>

<body>

    <!-- SHARED HEADER -->

    <?php include 'includes/header.php'; ?>


    <main class="details-container">

        <a href="products.php" class="back-link">
            &larr; Back to Products
        </a>


        <section class="details-card">


            <!-- PRODUCT IMAGE -->

            <div class="details-image">

                <?php if ($image !== ''): ?>

                    <img
                        src="<?= e($image) ?>"
                        alt="<?= e($product['name']) ?>"
                        onerror="
                            this.style.display='none';
                            this.nextElementSibling.style.display='block';
                        "
                    >

                    <div class="image-placeholder" style="display:none;">

                        <h2>Bayogoal</h2>

                        <p>Product image unavailable.</p>

                    </div>

                <?php else: ?>

                    <div class="image-placeholder">

                        <h2>Bayogoal</h2>

                        <p>Product picture coming soon.</p>

                    </div>

                <?php endif; ?>

            </div>


            <!-- PRODUCT INFORMATION -->

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
                        ?: 'Discover quality sports equipment from Bayogoal Enterprises.'
                    )) ?>

                </div>


                <?php if ($stock > 0): ?>

                    <form action="cart.php" method="POST">

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?= (int) $product['id'] ?>"
                        >


                        <!-- SIZE SELECTION -->

                        <?php if (!empty($sizes)): ?>

                            <div class="option-section">

                                <strong>Choose Your Size</strong>

                                <select
                                    name="size"
                                    class="size-select"
                                    required
                                >

                                    <option value="">Select a size</option>

                                    <?php foreach ($sizes as $size): ?>

                                        <option value="<?= e($size) ?>">

                                            <?= e($size) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                        <?php endif; ?>


                        <!-- COLOUR SELECTION -->

                        <?php if (!empty($colors)): ?>

                            <div class="option-section">

                                <strong>Choose Your Colour</strong>

                                <div class="color-options">

                                    <?php foreach ($colors as $index => $color): ?>

                                        <label class="color-option">

                                            <input
                                                type="radio"
                                                name="color"
                                                value="<?= e($color) ?>"
                                                <?= $index === 0 ? 'checked' : '' ?>
                                                required
                                            >

                                            <span><?= e($color) ?></span>

                                        </label>

                                    <?php endforeach; ?>

                                </div>

                            </div>

                        <?php endif; ?>


                        <!-- STOCK AVAILABILITY -->

                        <p>

                            <strong>Availability:</strong>

                            <span class="in-stock">

                                In Stock (<?= $stock ?> available)

                            </span>

                        </p>


                        <!-- QUANTITY -->

                        <div class="option-section">

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

                        </div>


                        <!-- BUTTONS -->

                        <div class="button-row">

                            <button
                                type="submit"
                                name="add_to_cart"
                                value="1"
                                class="details-btn btn-cart"
                            >

                                Add to Cart

                            </button>


                            <a
                                href="products.php"
                                class="details-btn btn-back"
                            >

                                Continue Shopping

                            </a>

                        </div>

                    </form>

                <?php else: ?>

                    <p class="out-stock">

                        This product is currently unavailable.

                    </p>

                    <a href="products.php" class="details-btn btn-back">

                        Continue Shopping

                    </a>

                <?php endif; ?>

            </div>

        </section>

    </main>


    <!-- SHARED FOOTER -->

    <?php include 'includes/footer.php'; ?>


    <script src="js/script.js"></script>

</body>

</html>