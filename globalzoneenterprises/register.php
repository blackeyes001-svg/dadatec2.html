
<?php
// ========================================
// GLOBAL ZONE SPORT ENTERPRISE
// PRODUCTS PAGE
// ========================================

// Sample products for development.
// Later, we will load these products from MySQL.

$products = [
    [
        "id" => 1,
        "name" => "Professional Football",
        "category" => "football",
        "price" => 25000,
        "icon" => "⚽",
        "description" => "Quality football for training and matches.",
        "badge" => "POPULAR"
    ],
    [
        "id" => 2,
        "name" => "Football Boots",
        "category" => "football",
        "price" => 65000,
        "icon" => "👟",
        "description" => "Comfortable boots designed for the pitch.",
        "badge" => "BEST SELLER"
    ],
    [
        "id" => 3,
        "name" => "Football Jersey",
        "category" => "football",
        "price" => 30000,
        "icon" => "👕",
        "description" => "Sporty jersey for training and match days.",
        "badge" => ""
    ],
    [
        "id" => 4,
        "name" => "Goalkeeper Gloves",
        "category" => "football",
        "price" => 22000,
        "icon" => "🧤",
        "description" => "Grip and protection for goalkeepers.",
        "badge" => ""
    ],
    [
        "id" => 5,
        "name" => "Basketball",
        "category" => "basketball",
        "price" => 28000,
        "icon" => "🏀",
        "description" => "Basketball for practice and recreational games.",
        "badge" => "POPULAR"
    ],
    [
        "id" => 6,
        "name" => "Basketball Shoes",
        "category" => "basketball",
        "price" => 85000,
        "icon" => "👟",
        "description" => "Supportive shoes for court movement.",
        "badge" => ""
    ],
    [
        "id" => 7,
        "name" => "Training Dumbbells",
        "category" => "fitness",
        "price" => 35000,
        "icon" => "🏋️",
        "description" => "Weights for strength and fitness training.",
        "badge" => ""
    ],
    [
        "id" => 8,
        "name" => "Skipping Rope",
        "category" => "fitness",
        "price" => 8000,
        "icon" => "💪",
        "description" => "A simple essential for cardio workouts.",
        "badge" => "BUDGET PICK"
    ],
    [
        "id" => 9,
        "name" => "Protective Sports Helmet",
        "category" => "protection",
        "price" => 40000,
        "icon" => "⛑️",
        "description" => "Protective headgear for compatible sports.",
        "badge" => ""
    ],
    [
        "id" => 10,
        "name" => "Sports Shin Guards",
        "category" => "protection",
        "price" => 12000,
        "icon" => "🛡️",
        "description" => "Shin protection for football players.",
        "badge" => ""
    ],
    [
        "id" => 11,
        "name" => "Tennis Racket",
        "category" => "racket-sports",
        "price" => 45000,
        "icon" => "🎾",
        "description" => "A racket for recreational tennis players.",
        "badge" => ""
    ],
    [
        "id" => 12,
        "name" => "Sports Tracksuit",
        "category" => "sportswear",
        "price" => 55000,
        "icon" => "🥋",
        "description" => "Comfortable sportswear for training and travel.",
        "badge" => "NEW"
    ],
    [
        "id" => 13,
        "name" => "Sports Water Bottle",
        "category" => "sportswear",
        "price" => 10000,
        "icon" => "🧴",
        "description" => "A reusable bottle for workouts and matches.",
        "badge" => ""
    ],
    [
        "id" => 14,
        "name" => "Volleyball",
        "category" => "racket-sports",
        "price" => 24000,
        "icon" => "🏐",
        "description" => "A ball for volleyball practice and games.",
        "badge" => ""
    ],
    [
        "id" => 15,
        "name" => "Sports Duffel Bag",
        "category" => "sportswear",
        "price" => 32000,
        "icon" => "🎒",
        "description" => "Carry your equipment to training and matches.",
        "badge" => ""
    ]
];

// Read search and filter values from the URL.
$search = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");
$sort = trim($_GET["sort"] ?? "");

$allowedCategories = [
    "football",
    "basketball",
    "fitness",
    "protection",
    "racket-sports",
    "sportswear"
];

if (!in_array($category, $allowedCategories, true)) {
    $category = "";
}

$allowedSorts = [
    "price-low",
    "price-high",
    "name"
];

if (!in_array($sort, $allowedSorts, true)) {
    $sort = "";
}

// Filter the products.
$filteredProducts = array_filter(
    $products,
    function ($product) use ($search, $category) {
        $matchesSearch = (
            $search === "" ||
            stripos($product["name"], $search) !== false ||
            stripos($product["description"], $search) !== false
        );

        $matchesCategory = (
            $category === "" ||
            $product["category"] === $category
        );

        return $matchesSearch && $matchesCategory;
    }
);

// Sort the filtered products.
if ($sort === "price-low") {
    usort($filteredProducts, function ($a, $b) {
        return $a["price"] <=> $b["price"];
    });
} elseif ($sort === "price-high") {
    usort($filteredProducts, function ($a, $b) {
        return $b["price"] <=> $a["price"];
    });
} elseif ($sort === "name") {
    usort($filteredProducts, function ($a, $b) {
        return strcasecmp($a["name"], $b["name"]);
    });
}

// Helper to display prices in naira.
function formatPrice($price)
{
    return "₦" . number_format($price);
}

// Escape text before displaying it in HTML.
function escapeHtml($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="Explore sports equipment, football boots, jerseys, basketball gear, fitness equipment and sportswear at Global Zone Sport Enterprise."
    >

    <title>Shop Products | Global Zone Sport Enterprise</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- HEADER -->
    <header class="site-header">

        <div class="logo">
            <a href="index.php">
                <span class="logo-icon">GZ</span>

                <span class="logo-text">
                    GLOBAL <strong>ZONE</strong>
                    <small>SPORT ENTERPRISE</small>
                </span>
            </a>
        </div>

        <button
            class="menu-toggle"
            id="menuToggle"
            aria-label="Open navigation menu"
            aria-expanded="false"
        >
            ☰
        </button>

        <nav class="nav-links" id="navLinks">
            <a href="index.php">Home</a>
            <a href="products.php" class="active">Shop</a>
            <a href="index.php#categories">Categories</a>
            <a href="index.php#about">About Us</a>
            <a href="index.php#contact">Contact</a>
            <a href="login.php" class="login-link">Login</a>
            <a href="register.php" class="register-link">Sign Up</a>
        </nav>

    </header>

    <!-- PRODUCTS PAGE BANNER -->
    <section class="shop-banner">

        <div class="shop-banner-content">
            <p class="section-label">THE GLOBAL ZONE COLLECTION</p>

            <h1>
                FIND YOUR <span>GAME.</span>
            </h1>

            <p>
                Discover the gear you need for every match,
                workout and sporting adventure.
            </p>
        </div>

        <div class="shop-banner-mark">GZ</div>

    </section>

    <!-- MAIN SHOP -->
    <main class="shop-section">

        <div class="shop-heading">

            <div>
                <p class="section-label">OUR COLLECTION</p>
                <h2>SPORTS <span>EQUIPMENT</span></h2>

                <p class="product-count">
                    <?php echo count($filteredProducts); ?>
                    product(s) found
                </p>
            </div>

        </div>

        <!-- SEARCH AND SORT -->
        <form method="GET" action="products.php" class="shop-toolbar">

            <div class="search-box">
                <input
                    type="search"
                    name="search"
                    placeholder="Search boots, jerseys, balls..."
                    value="<?php echo escapeHtml($search); ?>"
                    aria-label="Search products"
                >

                <button type="submit" aria-label="Search">
                    SEARCH
                </button>
            </div>

            <input
                type="hidden"
                name="category"
                value="<?php echo escapeHtml($category); ?>"
            >

            <label class="sort-control">
                <span>Sort by:</span>

                <select
                    name="sort"
                    onchange="this.form.submit()"
                    aria-label="Sort products"
                >
                    <option
                        value=""
                        <?php echo $sort === "" ? "selected" : ""; ?>
                    >
                        Recommended
                    </option>

                    <option
                        value="price-low"
                        <?php echo $sort === "price-low" ? "selected" : ""; ?>
                    >
                        Price: Low to High
                    </option>

                    <option
                        value="price-high"
                        <?php echo $sort === "price-high" ? "selected" : ""; ?>
                    >
                        Price: High to Low
                    </option>

                    <option
                        value="name"
                        <?php echo $sort === "name" ? "selected" : ""; ?>
                    >
                        Name: A to Z
                    </option>
                </select>
            </label>

        </form>

        <!-- CATEGORY FILTERS -->
        <div class="category-filters">

            <a
                href="products.php"
                class="filter-link <?php echo $category === "" ? "selected" : ""; ?>"
            >
                All Products
            </a>

            <a
                href="products.php?category=football"
                class="filter-link <?php echo $category === "football" ? "selected" : ""; ?>"
            >
                Football
            </a>

            <a
                href="products.php?category=basketball"
                class="filter-link <?php echo $category === "basketball" ? "selected" : ""; ?>"
            >
                Basketball
            </a>

            <a
                href="products.php?category=fitness"
                class="filter-link <?php echo $category === "fitness" ? "selected" : ""; ?>"
            >
                Fitness
            </a>

            <a
                href="products.php?category=protection"
                class="filter-link <?php echo $category === "protection" ? "selected" : ""; ?>"
            >
                Protective Gear
            </a>

            <a
                href="products.php?category=racket-sports"
                class="filter-link <?php echo $category === "racket-sports" ? "selected" : ""; ?>"
            >
                Racket Sports
            </a>

            <a
                href="products.php?category=sportswear"
                class="filter-link <?php echo $category === "sportswear" ? "selected" : ""; ?>"
            >
                Sportswear
            </a>

        </div>

        <!-- PRODUCT GRID -->
        <section class="product-grid" aria-label="Products">

            <?php if (count($filteredProducts) > 0): ?>

                <?php foreach ($filteredProducts as $product): ?>

                    <article class="product-card">

                        <a
                            class="product-image"
                            href="product-details.php?id=<?php echo (int) $product["id"]; ?>"
                            aria-label="View <?php echo escapeHtml($product["name"]); ?>"
                        >

                            <?php if ($product["badge"] !== ""): ?>
                                <span class="product-badge">
                                    <?php echo escapeHtml($product["badge"]); ?>
                                </span>
                            <?php endif; ?>

                            <span class="product-emoji">
                                <?php echo escapeHtml($product["icon"]); ?>
                            </span>

                            <span class="product-view">
                                VIEW DETAILS →
                            </span>

                        </a>

                        <div class="product-info">

                            <p class="product-category">
                                <?php echo escapeHtml(
                                    ucwords(str_replace("-", " ", $product["category"]))
                                ); ?>
                            </p>

                            <h3>
                                <a href="product-details.php?id=<?php echo (int) $product["id"]; ?>">
                                    <?php echo escapeHtml($product["name"]); ?>
                                </a>
                            </h3>

                            <p class="product-description">
                                <?php echo escapeHtml($product["description"]); ?>
                            </p>

                            <div class="product-card-bottom">

                                <span class="product-price">
                                    <?php echo formatPrice($product["price"]); ?>
                                </span>

                                <a
                                    href="product-details.php?id=<?php echo (int) $product["id"]; ?>"
                                    class="product-action"
                                    aria-label="View product details"
                                >
                                    →
                                </a>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="no-products">
                    <span>🔎</span>

                    <h3>No products found</h3>

                    <p>
                        Try another search term or select a different category.
                    </p>

                    <a href="products.php" class="btn btn-primary">
                        VIEW ALL PRODUCTS
                    </a>
                </div>

            <?php endif; ?>

        </section>

        <!-- SHOPPING NOTICE -->
        <div class="shop-notice">
            <span>GZ</span>

            <p>
                Your game. Your gear. Your next level.
                <strong>Gear Up. Play Hard. Win Big.</strong>
            </p>
        </div>

    </main>

    <!-- FOOTER -->
    <footer class="site-footer">

        <div class="footer-bottom">

            <p>
                &copy; <?php echo date("Y"); ?>
                Global Zone Sport Enterprise.
                All rights reserved.
            </p>

            <a href="index.php">Back to Home ↑</a>

        </div>

    </footer>

    <script src="js/script.js"></script>

</body>
</html>
