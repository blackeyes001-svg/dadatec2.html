<?php
require_once __DIR__ . '/includes/db.php';

function escapeHtml($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatPrice($price) {
    return '₦' . number_format((float) $price, 2);
}

// Read search, category and sorting options.
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort = $_GET['sort'] ?? 'default';

$allowedCategories = [
    'football',
    'basketball',
    'fitness',
    'protection',
    'racket-sports',
    'sportswear',
    'accessories'
];

$allowedSorts = [
    'default',
    'price_low',
    'price_high',
    'name'
];

// Build the database query safely.
$sql = "SELECT id, name, category, description, price, image, sizes, colors, stock
        FROM products
        WHERE 1 = 1";

$params = [];

if ($search !== '') {
    $sql .= " AND (name LIKE :search_name OR description LIKE :search_description)";
    $params['search_name'] = '%' . $search . '%';
    $params['search_description'] = '%' . $search . '%';
}

if ($category !== '' && in_array($category, $allowedCategories, true)) {
    $sql .= " AND category = :category";
    $params['category'] = $category;
}

if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'default';
}

switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY price ASC";
        break;

    case 'price_high':
        $sql .= " ORDER BY price DESC";
        break;

    case 'name':
        $sql .= " ORDER BY name ASC";
        break;

    default:
        $sql .= " ORDER BY id DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categoryNames = [
    'football' => 'Football',
    'basketball' => 'Basketball',
    'fitness' => 'Fitness',
    'protection' => 'Protective Gear',
    'racket-sports' => 'Racket Sports & Volleyball',
    'sportswear' => 'Sportswear',
    'accessories' => 'Accessories'
];
?>

<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>Shop Sports Equipment | Global Zone Sport Enterprise</title>

<link rel="stylesheet" href="css/style.css">
<script src="js/script.js" defer></script>


</head>

<body>

<header class="site-header">
    <a href="index.php" class="logo">
        <span class="logo-icon">GZ</span>


    <span class="logo-text">
        GLOBAL ZONE
        <small>SPORT ENTERPRISE</small>
    </span>
</a>

<button class="menu-toggle"
        type="button"
        aria-label="Toggle navigation"
        aria-expanded="false">
    ☰
</button>

<nav class="nav-links">
    <a href="index.php">Home</a>
    <a href="products.php" class="active">Shop</a>
    <a href="index.php#about">About Us</a>
    <a href="index.php#contact">Contact</a>
    <a href="login.php" class="login-link">Login</a>
    <a href="register.php" class="register-link">Sign Up</a>
</nav>


</header>

<main>

<section class="shop-banner">
    <div class="container">
        <p class="eyebrow">YOUR SPORTS. YOUR PASSION.</p>

        <h1>Explore Our Sports Collection</h1>

        <p>
            Discover quality sporting equipment designed to help you
            train harder, play better and perform at your best.
        </p>
    </div>
</section>

<section class="shop-section container">

    <div class="shop-toolbar">

        <form action="products.php" method="GET" class="search-form">
            <input
                type="search"
                name="search"
                placeholder="Search footballs, boots, fitness gear..."
                value="<?= escapeHtml($search) ?>"
                aria-label="Search products"
            >

            <?php if ($category !== ''): ?>
                <input
                    type="hidden"
                    name="category"
                    value="<?= escapeHtml($category) ?>"
                >
            <?php endif; ?>

            <input type="hidden" name="sort" value="<?= escapeHtml($sort) ?>">

            <button type="submit">Search</button>
        </form>

        <form action="products.php" method="GET" class="sort-form">
            <?php if ($search !== ''): ?>
                <input
                    type="hidden"
                    name="search"
                    value="<?= escapeHtml($search) ?>"
                >
            <?php endif; ?>

            <?php if ($category !== ''): ?>
                <input
                    type="hidden"
                    name="category"
                    value="<?= escapeHtml($category) ?>"
                >
            <?php endif; ?>

            <label for="sort">Sort by:</label>

            <select name="sort" id="sort" onchange="this.form.submit()">
                <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>
                    Recommended
                </option>

                <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>
                    Price: Low to High
                </option>

                <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>
                    Price: High to Low
                </option>

                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>
                    Name: A to Z
                </option>
            </select>
        </form>

    </div>

    <div class="category-filters">
        <a href="products.php"
           class="<?= $category === '' ? 'active' : '' ?>">
            All Products
        </a>

        <?php foreach ($categoryNames as $key => $label): ?>
            <a
                href="products.php?category=<?= urlencode($key) ?>"
                class="<?= $category === $key ? 'active' : '' ?>"
            >
                <?= escapeHtml($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="products-heading">
        <div>
            <h2>
                <?= $category !== '' && isset($categoryNames[$category])
                    ? escapeHtml($categoryNames[$category])
                    : 'Our Products' ?>
            </h2>

            <p>
                <?= count($products) ?>
                product<?= count($products) === 1 ? '' : 's' ?> found
            </p>
        </div>
    </div>

    <?php if (empty($products)): ?>

        <div class="no-products">
            <h3>No products found</h3>
            <p>Try another search or select a different category.</p>
            <a href="products.php" class="btn-primary">View All Products</a>
        </div>

    <?php else: ?>

        <div class="products-grid">

            <?php foreach ($products as $product): ?>

                <article class="product-card">

                    <a
                        class="product-image"
                        href="product.php?id=<?= (int) $product['id'] ?>"
                        aria-label="View <?= escapeHtml($product['name']) ?>"
                    >
                        <?php if (!empty($product['image'])): ?>

                            <img
                                src="images/<?= escapeHtml($product['image']) ?>"
                                alt="<?= escapeHtml($product['name']) ?>"
                                loading="lazy"
                            >

                        <?php else: ?>

                            <span class="product-placeholder" aria-hidden="true">
                                <?= escapeHtml($product['name']) ?>
                            </span>

                        <?php endif; ?>
                    </a>

                    <div class="product-info">

                        <span class="product-category">
                            <?= escapeHtml(
                                $categoryNames[$product['category']]
                                ?? ucfirst(str_replace('-', ' ', $product['category']))
                            ) ?>
                        </span>

                        <h3>
                            <a href="product.php?id=<?= (int) $product['id'] ?>">
                                <?= escapeHtml($product['name']) ?>
                            </a>
                        </h3>

                        <p class="product-description">
                            <?= escapeHtml($product['description']) ?>
                        </p>

                        <p class="product-price">
                            <?= formatPrice($product['price']) ?>
                        </p>

                        <p class="stock-status">
                            <?php if ((int) $product['stock'] > 0): ?>
                                <span>In stock: <?= (int) $product['stock'] ?></span>
                            <?php else: ?>
                                <span>Out of stock</span>
                            <?php endif; ?>
                        </p>

                        <a
                            class="btn-primary product-button"
                            href="product.php?id=<?= (int) $product['id'] ?>"
                        >
                            View Product
                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>
```

</main>

<footer class="site-footer">
    <div class="container">
        <p>
            &copy; <?= date('Y') ?> Global Zone Sport Enterprise.
            All rights reserved.
        </p>


    <p>Gear Up. Play Hard. Win Big.</p>
</div>

</footer>

</body>
</html>

