```php
<?php
session_start();

require_once __DIR__ . '/includes/db.php';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort = $_GET['sort'] ?? 'newest';

$allowedSorts = [
    'newest' => 'id DESC',
    'name' => 'name ASC',
    'price_low' => 'price ASC',
    'price_high' => 'price DESC'
];

$orderBy = $allowedSorts[$sort] ?? 'id DESC';

$sql = "SELECT id, name, category, description, price, image, sizes, colors, stock
        FROM products
        WHERE 1=1";

$params = [];

if ($search !== '') {
    $sql .= " AND (name LIKE ? OR category LIKE ? OR description LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($category !== '' && strtolower($category) !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $category;
}

$sql .= " ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categoryStmt = $pdo->query(
    "SELECT DISTINCT category
     FROM products
     WHERE category IS NOT NULL AND category <> ''
     ORDER BY category ASC"
);

$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Shop Sports Products | Global Zone Sport Enterprise</title>

    <style>
        :root {
            --blue: #2563eb;
            --green: #39ff14;
            --dark: #080d18;
            --card: #111827;
            --text: #ffffff;
            --muted: #a8b3c7;
            --border: #263449;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--dark);
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        /* HEADER */

        header {
            background: rgba(0, 0, 0, 0.95);
            border-bottom: 1px solid var(--border);
            padding: 17px 6%;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar {
            max-width: 1400px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .logo {
            font-size: 21px;
            font-weight: bold;
            color: var(--text);
        }

        .logo span {
            color: var(--green);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }

        .nav-links a {
            font-size: 14px;
            color: var(--muted);
            transition: 0.2s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: var(--green);
        }

        .nav-button {
            background: var(--blue);
            color: white !important;
            padding: 9px 16px;
            border-radius: 7px;
        }

        /* PAGE INTRODUCTION */

        .page-intro {
            text-align: center;
            padding: 55px 20px 35px;
        }

        .page-intro h1 {
            font-size: clamp(30px, 5vw, 44px);
            margin-bottom: 10px;
        }

        .page-intro h1 span {
            color: var(--green);
        }

        .page-intro p {
            color: var(--muted);
            max-width: 650px;
            margin: auto;
        }

        /* SEARCH AND FILTERS */

        .shop-container {
            max-width: 1400px;
            margin: auto;
            padding: 0 6% 60px;
        }

        .filter-panel {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 12px;
        }

        .filter-form input,
        .filter-form select {
            min-width: 0;
            width: 100%;
            padding: 12px;
            background: var(--dark);
            color: white;
            border: 1px solid var(--border);
            border-radius: 7px;
            outline: none;
        }

        .filter-form input:focus,
        .filter-form select:focus {
            border-color: var(--green);
        }

        .filter-form button {
            background: var(--blue);
            color: white;
            border: none;
            border-radius: 7px;
            padding: 12px 20px;
            cursor: pointer;
            font-weight: bold;
        }

        .filter-form button:hover {
            background: #1d4ed8;
        }

        .results-heading {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .results-heading p {
            color: var(--muted);
            font-size: 14px;
        }

        /* PRODUCT GRID */

        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 22px;
        }

        .product-card {
            display: flex;
            flex-direction: column;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s, border-color 0.2s;
        }

        .product-card:hover {
            transform: translateY(-4px);
            border-color: var(--blue);
        }

        .product-image {
            height: 210px;
            background: #172033;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 12px;
        }

        .image-placeholder {
            color: var(--muted);
            font-size: 13px;
            text-align: center;
            padding: 15px;
        }

        .product-info {
            padding: 18px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .category-label {
            color: var(--green);
            font-size: 12px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .product-info h2 {
            font-size: 17px;
            line-height: 1.4;
            margin-bottom: 9px;
        }

        .description {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 12px;
        }

        .price {
            font-size: 20px;
            font-weight: bold;
            margin-top: auto;
            margin-bottom: 12px;
        }

        .stock {
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 14px;
        }

        .in-stock {
            color: var(--green);
        }

        .out-of-stock {
            color: #ff7676;
        }

        .view-button {
            display: block;
            width: 100%;
            text-align: center;
            padding: 11px;
            border-radius: 7px;
            background: var(--blue);
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .view-button:hover {
            background: #1d4ed8;
        }

        .empty-state {
            grid-column: 1 / -1;
            background: var(--card);
            border: 1px solid var(--border);
            padding: 40px 20px;
            text-align: center;
            border-radius: 12px;
        }

        .empty-state p {
            color: var(--muted);
            margin: 10px 0 20px;
        }

        .clear-button {
            display: inline-block;
            background: var(--blue);
            padding: 10px 16px;
            border-radius: 7px;
        }

        /* FOOTER */

        footer {
            background: #050810;
            border-top: 1px solid var(--border);
            padding: 25px 6%;
            text-align: center;
            color: var(--muted);
            font-size: 13px;
        }

        footer span {
            color: var(--green);
        }

        /* RESPONSIVE DESIGN */

        @media (max-width: 1050px) {
            .product-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .filter-form {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 700px) {
            .navbar {
                justify-content: center;
            }

            .nav-links {
                justify-content: center;
                gap: 15px;
            }

            .product-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .product-image {
                height: 160px;
            }

            .product-info {
                padding: 13px;
            }

            .product-info h2 {
                font-size: 15px;
            }

            .price {
                font-size: 17px;
            }
        }

        @media (max-width: 420px) {
            .filter-form {
                grid-template-columns: 1fr;
            }

            .product-grid {
                grid-template-columns: 1fr;
            }

            .product-image {
                height: 220px;
            }
        }
    </style>
</head>

<body>

<header>
    <nav class="navbar">
        <a href="index.php" class="logo">
            GLOBAL ZONE <span>SPORT</span>
        </a>

        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="products.php" class="active">Shop</a>
            <a href="index.php#about">About Us</a>
            <a href="index.php#contact">Contact</a>

            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="cart.php">Cart</a>
                <a href="logout.php">Log Out</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php" class="nav-button">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<section class="page-intro">
    <h1>Our <span>Sports Products</span></h1>
    <p>
        Find the equipment you need to train harder, play better,
        and perform at your best.
    </p>
</section>

<main class="shop-container">

    <section class="filter-panel">
        <form action="products.php" method="GET" class="filter-form">

            <input
                type="search"
                name="search"
                placeholder="Search sports products..."
                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            >

            <select name="category">
                <option value="">All Categories</option>

                <?php foreach ($categories as $cat): ?>
                    <option
                        value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>"
                        <?= $category === $cat ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="sort">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>
                    Newest First
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

            <button type="submit">Find Products</button>

        </form>
    </section>

    <div class="results-heading">
        <h2>Browse Our Collection</h2>
        <p><?= count($products) ?> product(s) found</p>
    </div>

    <section class="product-grid">

        <?php if (empty($products)): ?>

            <div class="empty-state">
                <h2>No products found</h2>
                <p>Try another search term or choose a different category.</p>
                <a href="products.php" class="clear-button">Show All Products</a>
            </div>

        <?php else: ?>

            <?php foreach ($products as $product): ?>

                <article class="product-card">

                    <a
                        href="product.php?id=<?= (int) $product['id'] ?>"
                        class="product-image"
                    >
                        <?php
                        $imageName = trim((string) ($product['image'] ?? ''));
                        $imagePath = __DIR__ . '/images/' . basename($imageName);
                        ?>

                        <?php if (
                            $imageName !== '' &&
                            is_file($imagePath)
                        ): ?>

                            <img
                                src="images/<?= rawurlencode(basename($imageName)) ?>"
                                alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"
                            >

                        <?php else: ?>

                            <div class="image-placeholder">
                                Product image coming soon
                            </div>

                        <?php endif; ?>
                    </a>

                    <div class="product-info">

                        <span class="category-label">
                            <?= htmlspecialchars($product['category'] ?? 'Sports', ENT_QUOTES, 'UTF-8') ?>
                        </span>

                        <h2>
                            <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>
                        </h2>

                        <p class="description">
                            <?php
                            $description = trim((string) ($product['description'] ?? ''));

                            echo htmlspecialchars(
                                $description !== ''
                                    ? substr($description, 0, 110) .
                                        (strlen($description) > 110 ? '...' : '')
                                    : 'Quality sports equipment for your game.',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </p>

                        <div class="price">
                            ₦<?= number_format((float) $product['price'], 2) ?>
                        </div>

                        <p class="stock">
                            <?php if ((int) $product['stock'] > 0): ?>
                                <span class="in-stock">In stock</span>
                            <?php else: ?>
                                <span class="out-of-stock">Out of stock</span>
                            <?php endif; ?>
                        </p>

                        <a
                            class="view-button"
                            href="product.php?id=<?= (int) $product['id'] ?>"
                        >
                            View Product
                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</main>

<footer>
    <p>
        &copy; <?= date('Y') ?> Global Zone Sport Enterprise.
        <span>Gear Up. Play Hard. Win Big.</span>
    </p>
</footer>

</body>
</html>
```
