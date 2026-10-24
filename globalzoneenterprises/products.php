
<?php
session_start();

require_once __DIR__ . '/includes/db.php';

// Get search, category, and sorting options
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort = $_GET['sort'] ?? 'latest';

// Build the products query
$sql = "SELECT id, name, category, description, price, image, stock
        FROM products
        WHERE 1=1";

$params = [];

if ($search !== '') {
    $sql .= " AND (
        name LIKE :search_name
        OR category LIKE :search_category
        OR description LIKE :search_description
    )";

    $searchTerm = '%' . $search . '%';

    $params['search_name'] = $searchTerm;
    $params['search_category'] = $searchTerm;
    $params['search_description'] = $searchTerm;
}

if ($category !== '') {
    $sql .= " AND category = :category";
    $params['category'] = $category;
}

// Sort products
switch ($sort) {
    case 'name':
        $sql .= " ORDER BY name ASC";
        break;

    case 'price_low':
        $sql .= " ORDER BY price ASC";
        break;

    case 'price_high':
        $sql .= " ORDER BY price DESC";
        break;

    default:
        $sql .= " ORDER BY id DESC";
        break;
}

// Get products and categories
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categoryStmt = $pdo->query(
        "SELECT DISTINCT category
         FROM products
         WHERE category IS NOT NULL
         AND category <> ''
         ORDER BY category ASC"
    );

    $categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log('Products page error: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to load products. Please check your database connection.');
}

// Escape text before displaying it
function escapeHtml($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Our Products | Global Zone Sport Enterprise</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #071426;
            color: #ffffff;
        }

        /* HEADER */
        .shop-header {
            background: #030b18;
            color: #ffffff;
            padding: 20px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            border-bottom: 2px solid #2563EB;
        }

        .shop-header h1 {
            margin: 0;
            font-size: 24px;
        }

        .shop-header h1 span {
            color: #39FF14;
        }

        .shop-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
        }

        .shop-nav a {
            color: #ffffff;
            text-decoration: none;
            font-weight: bold;
        }

        .shop-nav a:hover {
            color: #39FF14;
        }

        /* MAIN CONTAINER */
        .shop-container {
            max-width: 1250px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .shop-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .shop-title h2 {
            font-size: 32px;
            margin-bottom: 8px;
            color: #ffffff;
        }

        .shop-title p {
            color: #cbd5e1;
        }

        .shop-title .tagline {
            color: #39FF14;
            font-weight: bold;
        }

        /* SEARCH AND FILTERS */
        .filter-box {
            background: #10233d;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 12px;
            border: 1px solid #1e3a5f;
        }

        .filter-box input,
        .filter-box select {
            width: 100%;
            padding: 12px;
            border: 1px solid #365477;
            border-radius: 6px;
            background: #071426;
            color: #ffffff;
            font-size: 14px;
        }

        .filter-box input::placeholder {
            color: #94a3b8;
        }

        .filter-button {
            border: none;
            border-radius: 6px;
            background: #2563EB;
            color: #ffffff;
            padding: 12px 22px;
            cursor: pointer;
            font-weight: bold;
        }

        .filter-button:hover {
            background: #39FF14;
            color: #071426;
        }

        .product-count {
            margin-bottom: 20px;
            color: #cbd5e1;
        }

        .product-count span {
            color: #39FF14;
            font-weight: bold;
        }

        /* PRODUCT GRID */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 22px;
        }

        .product-card {
            background: #10233d;
            border: 1px solid #1e3a5f;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.25);
            transition: transform 0.2s, border-color 0.2s;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-4px);
            border-color: #39FF14;
            box-shadow: 0 0 16px rgba(57, 255, 20, 0.12);
        }

        /* PRODUCT IMAGE */
        .product-image {
            height: 210px;
            background: #0a192c;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
        }

        .image-placeholder {
            font-size: 60px;
        }

        /* PRODUCT INFORMATION */
        .product-info {
            padding: 18px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .product-category {
            color: #39FF14;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .product-info h3 {
            margin: 0 0 10px;
            font-size: 18px;
            color: #ffffff;
        }

        .product-description {
            color: #cbd5e1;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 12px;
        }

        .product-price {
            font-size: 21px;
            color: #39FF14;
            font-weight: bold;
            margin-top: auto;
            margin-bottom: 12px;
        }

        .stock-label {
            font-size: 13px;
            margin-bottom: 14px;
        }

        .in-stock {
            color: #39FF14;
        }

        .out-stock {
            color: #f87171;
        }

        /* VIEW PRODUCT BUTTON */
        .view-product {
            display: block;
            text-align: center;
            background: #2563EB;
            color: #ffffff;
            text-decoration: none;
            padding: 12px;
            border-radius: 6px;
            font-weight: bold;
            transition: background 0.2s, color 0.2s;
        }

        .view-product:hover {
            background: #39FF14;
            color: #071426;
        }

        /* NO PRODUCTS FOUND */
        .empty-message {
            grid-column: 1 / -1;
            text-align: center;
            background: #10233d;
            color: #ffffff;
            padding: 40px 20px;
            border-radius: 10px;
            border: 1px solid #1e3a5f;
        }

        .empty-message a {
            color: #39FF14;
            text-decoration: none;
            font-weight: bold;
        }

        /* FOOTER */
        .shop-footer {
            text-align: center;
            padding: 25px;
            margin-top: 50px;
            background: #030b18;
            color: #cbd5e1;
            border-top: 2px solid #2563EB;
        }

        .shop-footer span {
            color: #39FF14;
        }

        /* TABLETS */
        @media (max-width: 1000px) {
            .products-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .filter-box {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* MOBILE PHONES */
        @media (max-width: 650px) {
            .products-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .product-image {
                height: 160px;
            }

            .product-info {
                padding: 12px;
            }

            .shop-title h2 {
                font-size: 25px;
            }

            .filter-box {
                grid-template-columns: 1fr;
            }

            .shop-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 380px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<header class="shop-header">

    <h1>GLOBAL ZONE <span>SPORT</span></h1>

    <nav class="shop-nav">
        <a href="index.php">Home</a>
        <a href="products.php">Shop</a>
        <a href="cart.php">Cart</a>

        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="orders.php">My Orders</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>

</header>

<main class="shop-container">

    <section class="shop-title">
        <h2>Our Sports Collection</h2>
        <p class="tagline">Gear Up. Play Hard. Win Big.</p>
        <p>Discover sportswear, footwear and sporting equipment for your game.</p>
    </section>

    <form action="products.php" method="GET" class="filter-box">

        <input
            type="search"
            name="search"
            placeholder="Search products..."
            value="<?= escapeHtml($search) ?>"
        >

        <select name="category">
            <option value="">All Categories</option>

            <?php foreach ($categories as $cat): ?>
                <option
                    value="<?= escapeHtml($cat) ?>"
                    <?= $category === $cat ? 'selected' : '' ?>
                >
                    <?= escapeHtml($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="sort">
            <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>
                Latest
            </option>

            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>
                Name: A–Z
            </option>

            <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>
                Price: Low to High
            </option>

            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>
                Price: High to Low
            </option>
        </select>

        <button type="submit" class="filter-button">Search</button>

    </form>

    <p class="product-count">
        Showing <span><?= count($products) ?></span>
        product<?= count($products) === 1 ? '' : 's' ?>
    </p>

    <section class="products-grid">

        <?php if (empty($products)): ?>

            <div class="empty-message">
                <h3>No products found</h3>
                <p>Try another search or select a different category.</p>
                <a href="products.php">Show all products</a>
            </div>

        <?php else: ?>

            <?php foreach ($products as $product): ?>

                <article class="product-card">

                    <div class="product-image">

                        <?php if (!empty($product['image'])): ?>

                            <img
                                src="<?= escapeHtml($product['image']) ?>"
                                alt="<?= escapeHtml($product['name']) ?>"
                                loading="lazy"
                            >

                        <?php else: ?>

                            <span class="image-placeholder" aria-hidden="true">
                                🏆
                            </span>

                        <?php endif; ?>

                    </div>

                    <div class="product-info">

                        <span class="product-category">
                            <?= escapeHtml($product['category'] ?? 'Sports') ?>
                        </span>

                        <h3><?= escapeHtml($product['name']) ?></h3>

                        <p class="product-description">
                            <?php
                            $description = trim(
                                (string) ($product['description'] ?? '')
                            );

                            if ($description === '') {
                                $description = 'Discover more details about this product.';
                            }

                            echo escapeHtml(
                                mb_strimwidth($description, 0, 100, '...')
                            );
                            ?>
                        </p>

                        <p class="product-price">
                            ₦<?= number_format((float) $product['price'], 2) ?>
                        </p>

                        <?php if ((int) $product['stock'] > 0): ?>
                            <p class="stock-label in-stock">● In stock</p>
                        <?php else: ?>
                            <p class="stock-label out-stock">● Out of stock</p>
                        <?php endif; ?>

                        <a
                            class="view-product"
                            href="products-details.php?id=<?= (int) $product['id'] ?>"
                        >
                            View Product
                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</main>

<footer class="shop-footer">
    <p>&copy; <?= date('Y') ?> Global Zone Sport Enterprise.</p>
    <p><span>Gear Up. Play Hard. Win Big.</span></p>
</footer>

</body>
</html>

