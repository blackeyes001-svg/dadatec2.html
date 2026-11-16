<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/includes/db.php";

// Get restaurant ID from URL
$restaurant_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$restaurant = null;
$menuItems = [];
$categories = [];

if ($restaurant_id) {

    // Fetch restaurant details
    $stmt = $pdo->prepare("
        SELECT *
        FROM restaurants
        WHERE id = :id AND status = 'active'
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $restaurant_id
    ]);

    $restaurant = $stmt->fetch();

    // Fetch restaurant menu
    if ($restaurant) {

        $menuStmt = $pdo->prepare("
            SELECT 
                menu_items.*,
                categories.name AS category_name
            FROM menu_items
            LEFT JOIN categories
                ON menu_items.category_id = categories.id
            WHERE menu_items.restaurant_id = :restaurant_id
            ORDER BY categories.name ASC, menu_items.name ASC
        ");

        $menuStmt->execute([
            ':restaurant_id' => $restaurant_id
        ]);

        $menuItems = $menuStmt->fetchAll();

        // Group menu items by category
        foreach ($menuItems as $item) {
            $category = $item['category_name'] ?: 'Other';

            $categories[$category][] = $item;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= $restaurant
            ? htmlspecialchars($restaurant['name']) . ' | DineNow'
            : 'Restaurant Details | DineNow'
        ?>
    </title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            background: #101010;
            color: #fff;
        }

        .details-page {
            min-height: 100vh;
            padding-bottom: 80px;
        }

        .details-container {
            width: 84%;
            max-width: 1250px;
            margin: auto;
        }

        .back-link {
            display: inline-block;
            color: #AAAAAA;
            text-decoration: none;
            margin: 30px 0;
            font-size: 14px;
        }

        .back-link:hover {
            color: #FF7628;
        }

        .restaurant-cover {
            height: 420px;
            position: relative;
            overflow: hidden;
            border-radius: 15px;
        }

        .restaurant-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cover-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to top,
                rgba(0,0,0,0.9),
                transparent
            );
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 40px;
        }

        .cover-overlay h1 {
            font-size: 38px;
            margin: 10px 0;
        }

        .cover-overlay p {
            color: #ddd;
        }

        .cuisine-label {
            background: #FF7628;
            color: white;
            padding: 7px 15px;
            border-radius: 20px;
            width: fit-content;
            font-size: 12px;
            font-weight: 600;
        }

        .restaurant-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 35px;
        }

        .details-main,
        .details-sidebar {
            background: #191919;
            border-radius: 12px;
            padding: 28px;
        }

        .section-title {
            font-size: 23px;
            margin-bottom: 18px;
        }

        .restaurant-description {
            color: #AAAAAA;
            line-height: 1.9;
            font-size: 14px;
        }

        .info-list {
            margin-top: 25px;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 22px;
        }

        .info-item i {
            color: #FF7628;
            font-size: 18px;
            width: 22px;
            margin-top: 3px;
        }

        .info-item div strong {
            display: block;
            margin-bottom: 5px;
        }

        .info-item div span {
            color: #AAAAAA;
            font-size: 13px;
        }

        .booking-box h3 {
            font-size: 21px;
            margin-bottom: 12px;
        }

        .booking-box p {
            color: #AAAAAA;
            font-size: 13px;
            line-height: 1.8;
            margin-bottom: 22px;
        }

        .booking-btn {
            display: block;
            background: #FF7628;
            color: white;
            text-decoration: none;
            text-align: center;
            padding: 15px;
            border-radius: 7px;
            font-weight: 600;
            transition: 0.3s;
        }

        .booking-btn:hover {
            background: #e65f15;
        }

        .menu-section {
            margin-top: 55px;
        }

        .menu-section > p {
            color: #AAAAAA;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .menu-category {
            margin-bottom: 35px;
        }

        .menu-category h3 {
            color: #FF7628;
            font-size: 19px;
            margin-bottom: 18px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .menu-card {
            background: #191919;
            border: 1px solid #292929;
            border-radius: 10px;
            padding: 18px;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .menu-card img {
            width: 85px;
            height: 85px;
            object-fit: cover;
            border-radius: 8px;
            background: #292929;
        }

        .menu-info {
            flex: 1;
        }

        .menu-info h4 {
            font-size: 15px;
            margin-bottom: 7px;
        }

        .menu-info p {
            color: #AAAAAA;
            font-size: 12px;
            line-height: 1.6;
        }

        .menu-price {
            display: block;
            color: #FF7628;
            font-weight: 700;
            margin-top: 8px;
        }

        .empty-menu {
            background: #191919;
            padding: 30px;
            border-radius: 10px;
            color: #AAAAAA;
            text-align: center;
        }

        .not-found {
            text-align: center;
            padding: 100px 20px;
        }

        .not-found i {
            color: #FF7628;
            font-size: 50px;
            margin-bottom: 20px;
        }

        .not-found h2 {
            margin-bottom: 15px;
        }

        .not-found p {
            color: #AAAAAA;
            margin-bottom: 25px;
        }

        .not-found a {
            display: inline-block;
            background: #FF7628;
            color: white;
            padding: 12px 22px;
            border-radius: 6px;
            text-decoration: none;
        }

        @media(max-width: 850px) {

            .restaurant-content {
                grid-template-columns: 1fr;
            }

        }

        @media(max-width: 600px) {

            .details-container {
                width: 90%;
            }

            .restaurant-cover {
                height: 300px;
            }

            .cover-overlay {
                padding: 22px;
            }

            .cover-overlay h1 {
                font-size: 27px;
            }

            .details-main,
            .details-sidebar {
                padding: 20px;
            }

            .menu-grid {
                grid-template-columns: 1fr;
            }

            .menu-card img {
                width: 75px;
                height: 75px;
            }

        }

    </style>

</head>

<body>

<header class="navbar">

    <a href="index.php" class="logo">
        Dine<span>Now.</span>
    </a>

    <nav class="nav-links" id="navLinks">

        <a href="index.php">Home</a>

        <a href="restaurants.php" class="active">Restaurants</a>

        <a href="index.php#about">About</a>

        <a href="index.php#contact">Contact</a>

    </nav>

    <div class="nav-buttons">

        <?php if (isset($_SESSION['user_id'])): ?>

            <span style="color:#fff; margin-right:10px;">
                Welcome, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Customer') ?>
            </span>

            <a href="logout.php" class="signup-btn">Logout</a>

        <?php else: ?>

            <a href="login.php" class="login-btn">Login</a>

            <a href="register.php" class="signup-btn">Sign Up</a>

        <?php endif; ?>

    </div>

    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

</header>


<main class="details-page">

<?php if ($restaurant): ?>

    <div class="details-container">

        <a href="restaurants.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Restaurants
        </a>


        <section class="restaurant-cover">

            <img
                src="<?= htmlspecialchars($restaurant['image'] ?: 'images/logo.png') ?>"
                alt="<?= htmlspecialchars($restaurant['name']) ?>"
            >

            <div class="cover-overlay">

                <span class="cuisine-label">
                    <?= htmlspecialchars($restaurant['cuisine']) ?>
                </span>

                <h1>
                    <?= htmlspecialchars($restaurant['name']) ?>
                </h1>

                <p>
                    <i class="fas fa-map-marker-alt"></i>

                    <?= htmlspecialchars($restaurant['address']) ?>,
                    <?= htmlspecialchars($restaurant['city']) ?>
                </p>

            </div>

        </section>


        <div class="restaurant-content">

            <section class="details-main">

                <h2 class="section-title">
                    About This Restaurant
                </h2>

                <p class="restaurant-description">
                    <?= nl2br(htmlspecialchars($restaurant['description'])) ?>
                </p>


                <div class="info-list">

                    <div class="info-item">

                        <i class="fas fa-map-marker-alt"></i>

                        <div>

                            <strong>Restaurant Address</strong>

                            <span>
                                <?= htmlspecialchars($restaurant['address']) ?>,
                                <?= htmlspecialchars($restaurant['city']) ?>
                            </span>

                        </div>

                    </div>


                    <div class="info-item">

                        <i class="fas fa-phone"></i>

                        <div>

                            <strong>Phone Number</strong>

                            <span>
                                <?= htmlspecialchars($restaurant['phone']) ?>
                            </span>

                        </div>

                    </div>


                    <div class="info-item">

                        <i class="fas fa-envelope"></i>

                        <div>

                            <strong>Email Address</strong>

                            <span>
                                <?= htmlspecialchars($restaurant['email']) ?>
                            </span>

                        </div>

                    </div>


                    <div class="info-item">

                        <i class="far fa-clock"></i>

                        <div>

                            <strong>Opening Hours</strong>

                            <span>
                                <?= date('g:i A', strtotime($restaurant['opening_time'])) ?>
                                -
                                <?= date('g:i A', strtotime($restaurant['closing_time'])) ?>
                            </span>

                        </div>

                    </div>

                </div>

            </section>


            <aside class="details-sidebar">

                <div class="booking-box">

                    <h3>Ready to Dine?</h3>

                    <p>
                        Reserve your table and enjoy an amazing
                        dining experience at
                        <?= htmlspecialchars($restaurant['name']) ?>.
                    </p>

                    <a
                        href="booking.php?restaurant_id=<?= (int)$restaurant['id'] ?>"
                        class="booking-btn"
                    >
                        <i class="fas fa-calendar-check"></i>
                        Book a Table
                    </a>

                </div>

            </aside>

        </div>


        <section class="menu-section">

            <h2 class="section-title">
                Explore Our Menu
            </h2>

            <p>
                Discover some of the delicious meals offered by this restaurant.
            </p>

            <?php if (!empty($categories)): ?>

                <?php foreach ($categories as $categoryName => $items): ?>

                    <div class="menu-category">

                        <h3>
                            <?= htmlspecialchars($categoryName) ?>
                        </h3>

                        <div class="menu-grid">

                            <?php foreach ($items as $item): ?>

                                <div class="menu-card">

                                    <?php if (!empty($item['image'])): ?>

                                        <img
                                            src="<?= htmlspecialchars($item['image']) ?>"
                                            alt="<?= htmlspecialchars($item['name']) ?>"
                                            loading="lazy"
                                        >

                                    <?php endif; ?>

                                    <div class="menu-info">

                                        <h4>
                                            <?= htmlspecialchars($item['name']) ?>
                                        </h4>

                                        <p>
                                            <?= htmlspecialchars($item['description'] ?? '') ?>
                                        </p>

                                        <span class="menu-price">
                                            ₦<?= number_format((float)$item['price'], 2) ?>
                                        </span>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-menu">

                    <i class="fas fa-utensils"></i>

                    <p>
                        The restaurant menu will be available soon.
                    </p>

                </div>

            <?php endif; ?>

        </section>

    </div>

<?php else: ?>

    <div class="not-found">

        <i class="fas fa-utensils"></i>

        <h2>Restaurant Not Found</h2>

        <p>
            Sorry, this restaurant does not exist or is currently unavailable.
        </p>

        <a href="restaurants.php">
            Explore Restaurants
        </a>

    </div>

<?php endif; ?>

</main>


<footer id="contact">

    <a href="index.php" class="logo">
        Dine<span>Now.</span>
    </a>

    <p>Discover. Reserve. Enjoy.</p>

    <p>
        &copy; <?= date("Y") ?> DineNow. All Rights Reserved.
    </p>

</footer>


<script src="js/script.js"></script>

</body>
</html>