
<?php
// ==========================================
// SESSION AND DATABASE
// ==========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/includes/db.php";

$pageTitle = "DineNow | Discover & Book Restaurants";

$isLoggedIn = isset($_SESSION['user_id']);
$customerName = $_SESSION['full_name'] ?? '';

// ==========================================
// FETCH FEATURED RESTAURANTS
// ==========================================

$featuredRestaurants = [];

try {
    $restaurantQuery = $pdo->prepare("
        SELECT id, name, description, address, city, cuisine, image
        FROM restaurants
        WHERE status = 'active'
        ORDER BY id DESC
        LIMIT 3
    ");

    $restaurantQuery->execute();
    $featuredRestaurants = $restaurantQuery->fetchAll();

} catch (PDOException $e) {
    $featuredRestaurants = [];
}

// ==========================================
// FETCH POPULAR DISHES
// ==========================================

$popularDishes = [];

try {
    $dishQuery = $pdo->prepare("
        SELECT
            menu_items.*,
            restaurants.name AS restaurant_name
        FROM menu_items
        INNER JOIN restaurants
            ON menu_items.restaurant_id = restaurants.id
        WHERE restaurants.status = 'active'
        ORDER BY menu_items.id DESC
        LIMIT 4
    ");

    $dishQuery->execute();
    $popularDishes = $dishQuery->fetchAll();

} catch (PDOException $e) {
    $popularDishes = [];
}

// ==========================================
// COUNT ACTIVE RESTAURANTS
// ==========================================

$totalRestaurants = 0;

try {
    $countQuery = $pdo->query("
        SELECT COUNT(*) FROM restaurants
        WHERE status = 'active'
    ");

    $totalRestaurants = (int) $countQuery->fetchColumn();

} catch (PDOException $e) {
    $totalRestaurants = 0;
}

// ==========================================
// IMAGE HELPER
// ==========================================

function displayImage($image, $fallback)
{
    if (!empty($image)) {
        return htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
    }

    return $fallback;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>

/* ==========================================
   GENERAL HOMEPAGE STYLING
========================================== */

:root {
    --orange: #FF7628;
    --black: #101010;
    --dark: #191919;
    --white: #ffffff;
    --grey: #aaaaaa;
}

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    background: var(--black);
    color: var(--white);
    font-family: 'Poppins', sans-serif;
}

section {
    padding: 85px 7%;
}

.section-heading {
    text-align: center;
    margin-bottom: 45px;
}

.section-heading span {
    color: var(--orange);
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 2px;
}

.section-heading h2 {
    font-size: clamp(27px, 4vw, 40px);
    margin: 12px 0;
}

.section-heading p {
    color: var(--grey);
    font-size: 14px;
}

/* ==========================================
   NAVIGATION
========================================== */

.navbar {
    position: relative;
    z-index: 1000;
}

/* ==========================================
   HERO SECTION
========================================== */

.home-hero {
    min-height: 680px;
    padding: 90px 7%;
    display: flex;
    align-items: center;
    position: relative;
    overflow: hidden;

    background:
        linear-gradient(
            90deg,
            rgba(0,0,0,0.94) 0%,
            rgba(0,0,0,0.78) 48%,
            rgba(0,0,0,0.30) 100%
        ),
        url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1800')
        center/cover no-repeat;
}

.hero-content {
    width: 100%;
    max-width: 850px;
    position: relative;
    z-index: 2;
}

.hero-tag {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,118,40,0.15);
    border: 1px solid rgba(255,118,40,0.4);
    color: var(--orange);
    padding: 10px 17px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
}

.home-hero h1 {
    font-size: clamp(38px, 6vw, 67px);
    line-height: 1.2;
    margin: 25px 0 18px;
    font-weight: 800;
}

.home-hero h1 span {
    color: var(--orange);
}

.home-hero .hero-description {
    color: #dddddd;
    font-size: 15px;
    line-height: 1.9;
    max-width: 580px;
}

.hero-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 30px;
}

.primary-btn,
.secondary-btn {
    text-decoration: none;
    padding: 14px 23px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: 0.3s;
}

.primary-btn {
    background: var(--orange);
    color: white;
}

.primary-btn:hover {
    background: #e85d12;
    transform: translateY(-3px);
}

.secondary-btn {
    border: 1px solid rgba(255,255,255,0.5);
    color: white;
}

.secondary-btn:hover {
    background: white;
    color: black;
}

.hero-welcome {
    display: inline-block;
    margin-bottom: 15px;
    color: white;
    font-size: 14px;
}

.hero-welcome span {
    color: var(--orange);
    font-weight: 700;
}

/* ==========================================
   HERO SEARCH BAR
========================================== */

.hero-search {
    margin-top: 45px;
    max-width: 850px;
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 13px;
    padding: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.hero-search-field {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 13px;
}

.hero-search-field i {
    color: var(--orange);
    font-size: 19px;
}

.hero-search-field div {
    width: 100%;
}

.hero-search-field label {
    display: block;
    font-size: 11px;
    color: #cccccc;
    margin-bottom: 3px;
}

.hero-search-field input,
.hero-search-field select {
    width: 100%;
    border: none;
    outline: none;
    background: transparent;
    color: white;
    font-family: inherit;
    font-size: 13px;
}

.hero-search-field input::placeholder {
    color: #dddddd;
}

.hero-search-field select option {
    background: #191919;
    color: white;
}

.hero-search button {
    border: none;
    background: var(--orange);
    color: white;
    padding: 17px 23px;
    border-radius: 9px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: 0.3s;
}

.hero-search button:hover {
    background: #e85d12;
}

/* ==========================================
   HERO STATS
========================================== */

.hero-stats {
    display: flex;
    gap: 45px;
    margin-top: 38px;
}

.hero-stat h3 {
    font-size: 25px;
    color: var(--orange);
    margin: 0;
}

.hero-stat p {
    color: #cccccc;
    font-size: 12px;
    margin: 3px 0;
}

/* ==========================================
   FEATURED RESTAURANTS
========================================== */

.featured-section {
    background: #151515;
}

.restaurant-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
}

.restaurant-card {
    background: #202020;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 15px;
    overflow: hidden;
    transition: 0.3s;
}

.restaurant-card:hover {
    transform: translateY(-8px);
    border-color: var(--orange);
    box-shadow: 0 15px 35px rgba(255,118,40,0.10);
}

.restaurant-card-image {
    height: 220px;
    overflow: hidden;
    position: relative;
}

.restaurant-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: 0.5s;
}

.restaurant-card:hover img {
    transform: scale(1.07);
}

.restaurant-label {
    position: absolute;
    top: 15px;
    left: 15px;
    background: var(--orange);
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 7px 12px;
    border-radius: 20px;
}

.restaurant-card-content {
    padding: 22px;
}

.restaurant-card-content h3 {
    margin: 0 0 10px;
    font-size: 19px;
}

.restaurant-card-content p {
    color: var(--grey);
    font-size: 12px;
    line-height: 1.8;
    min-height: 43px;
}

.restaurant-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    color: #bbbbbb;
    font-size: 11px;
    margin: 15px 0;
}

.restaurant-meta i {
    color: var(--orange);
    margin-right: 5px;
}

.card-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--orange);
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    margin-top: 8px;
}

.card-link:hover {
    gap: 13px;
}

.empty-message {
    grid-column: 1 / -1;
    text-align: center;
    background: #202020;
    padding: 35px;
    border-radius: 12px;
    color: var(--grey);
}

/* ==========================================
   POPULAR DISHES
========================================== */

.dishes-section {
    background: var(--black);
}

.dishes-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 22px;
}

.dish-card {
    background: #1d1d1d;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px;
    overflow: hidden;
    transition: 0.3s;
}

.dish-card:hover {
    transform: translateY(-7px);
    border-color: var(--orange);
}

.dish-image {
    height: 190px;
    overflow: hidden;
}

.dish-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: 0.5s;
}

.dish-card:hover .dish-image img {
    transform: scale(1.08);
}

.dish-content {
    padding: 18px;
}

.dish-content h3 {
    font-size: 16px;
    margin: 0 0 8px;
}

.dish-content p {
    color: var(--grey);
    font-size: 11px;
    line-height: 1.7;
    min-height: 38px;
}

.dish-restaurant {
    color: #bbbbbb;
    font-size: 11px;
    margin-top: 12px;
}

.dish-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-top: 15px;
}

.dish-price {
    color: var(--orange);
    font-size: 16px;
    font-weight: 800;
}

.dish-bottom a {
    background: var(--orange);
    color: white;
    text-decoration: none;
    padding: 9px 12px;
    border-radius: 7px;
    font-size: 10px;
    font-weight: 700;
}

/* ==========================================
   HOW DINENOW WORKS
========================================== */

.how-section {
    background: #151515;
}

.steps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
}

.step-card {
    text-align: center;
    background: #202020;
    padding: 35px 25px;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 15px;
    transition: 0.3s;
}

.step-card:hover {
    transform: translateY(-7px);
    border-color: var(--orange);
}

.step-icon {
    width: 75px;
    height: 75px;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0 auto 22px;
    background: rgba(255,118,40,0.13);
    color: var(--orange);
    border-radius: 20px;
    font-size: 28px;
}

.step-number {
    color: var(--orange);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
}

.step-card h3 {
    font-size: 19px;
    margin: 12px 0;
}

.step-card p {
    color: var(--grey);
    font-size: 12px;
    line-height: 1.9;
}

/* ==========================================
   ABOUT DINENOW
========================================== */

.about-section {
    background: var(--black);
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 55px;
    align-items: center;
}

.about-image {
    height: 390px;
    border-radius: 16px;
    overflow: hidden;
    position: relative;
}

.about-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.about-content .section-tag {
    color: var(--orange);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 2px;
}

.about-content h2 {
    font-size: clamp(27px, 4vw, 39px);
    line-height: 1.35;
    margin: 15px 0;
}

.about-content h2 span {
    color: var(--orange);
}

.about-content p {
    color: var(--grey);
    font-size: 13px;
    line-height: 2;
}

.about-features {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin: 25px 0;
}

.about-feature {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12px;
}

.about-feature i {
    color: var(--orange);
}

/* ==========================================
   CALL TO ACTION
========================================== */

.cta-section {
    padding: 65px 7%;
    background:
        linear-gradient(rgba(0,0,0,0.82), rgba(0,0,0,0.82)),
        url('https://images.unsplash.com/photo-1559339352-11d035aa65de?w=1400')
        center/cover no-repeat;
    text-align: center;
}

.cta-section h2 {
    font-size: clamp(25px, 4vw, 38px);
    margin: 0 0 12px;
}

.cta-section p {
    color: #dddddd;
    font-size: 13px;
    margin-bottom: 25px;
}

/* ==========================================
   FOOTER
========================================== */

.home-footer {
    background: #090909;
    padding: 60px 7% 20px;
    border-top: 1px solid rgba(255,255,255,0.08);
}

.footer-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1fr;
    gap: 35px;
    padding-bottom: 40px;
}

.footer-brand .logo {
    display: inline-block;
    font-size: 27px;
    font-weight: 800;
    color: white;
    text-decoration: none;
}

.footer-brand .logo span {
    color: var(--orange);
}

.footer-brand p {
    color: var(--grey);
    font-size: 12px;
    line-height: 1.9;
    max-width: 270px;
}

.footer-column h3 {
    font-size: 14px;
    margin: 5px 0 20px;
}

.footer-column a,
.footer-column p {
    display: block;
    color: var(--grey);
    font-size: 12px;
    text-decoration: none;
    margin: 12px 0;
    line-height: 1.8;
}

.footer-column a:hover {
    color: var(--orange);
}

.footer-column i {
    color: var(--orange);
    margin-right: 8px;
}

.footer-socials {
    display: flex;
    gap: 12px;
    margin-top: 18px;
}

.footer-socials a {
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #202020;
    border-radius: 50%;
    color: white;
    margin: 0;
}

.footer-socials a:hover {
    background: var(--orange);
    color: white;
}

.footer-bottom {
    border-top: 1px solid rgba(255,255,255,0.09);
    padding-top: 20px;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    color: #888888;
    font-size: 11px;
}

/* ==========================================
   RESPONSIVE DESIGN
========================================== */

@media (max-width: 1000px) {

    .restaurant-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .dishes-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .footer-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .about-section {
        gap: 30px;
    }
}

@media (max-width: 700px) {

    section {
        padding: 65px 6%;
    }

    .home-hero {
        min-height: auto;
        padding: 75px 6%;
        background-position: center;
    }

    .home-hero h1 {
        font-size: 39px;
    }

    .home-hero .hero-description {
        font-size: 13px;
    }

    .hero-search {
        flex-direction: column;
        align-items: stretch;
        padding: 15px;
        gap: 5px;
    }

    .hero-search-field {
        border-bottom: 1px solid rgba(255,255,255,0.15);
        padding: 12px 5px;
    }

    .hero-search button {
        width: 100%;
        margin-top: 10px;
    }

    .hero-stats {
        gap: 22px;
    }

    .hero-stat h3 {
        font-size: 20px;
    }

    .hero-stat p {
        font-size: 10px;
    }

    .restaurant-grid,
    .dishes-grid,
    .steps-grid {
        grid-template-columns: 1fr;
    }

    .restaurant-card-image {
        height: 230px;
    }

    .dish-image {
        height: 220px;
    }

    .about-section {
        grid-template-columns: 1fr;
    }

    .about-image {
        height: 280px;
    }

    .about-content h2 {
        font-size: 29px;
    }

    .footer-grid {
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }

    .footer-bottom {
        flex-direction: column;
        text-align: center;
    }

}

@media (max-width: 420px) {

    .home-hero h1 {
        font-size: 33px;
    }

    .hero-buttons {
        flex-direction: column;
    }

    .hero-buttons a {
        justify-content: center;
    }

    .hero-stats {
        justify-content: space-between;
        gap: 10px;
    }

    .footer-grid {
        grid-template-columns: 1fr;
    }

    .about-features {
        grid-template-columns: 1fr;
    }

}

    </style>

</head>

<body>

<!-- ==========================================
     NAVIGATION BAR
========================================== -->

<header class="navbar">

    <a href="index.php" class="logo">
        Dine<span>Now.</span>
    </a>

    <button
        class="menu-toggle"
        id="menuToggle"
        aria-label="Open navigation menu"
        aria-expanded="false"
        aria-controls="navLinks"
        type="button"
    >
        <i class="fas fa-bars"></i>
    </button>

    <nav class="nav-links" id="navLinks">

        <a href="index.php">
            <i class="fas fa-home"></i> Home
        </a>

        <a href="restaurants.php">
            <i class="fas fa-utensils"></i> Restaurants
        </a>

        <a href="restaurants.php">
            <i class="fas fa-store"></i> Restaurant Details
        </a>

        <a href="restaurants.php">
            <i class="fas fa-calendar-check"></i> Book a Table
        </a>

        <?php if ($isLoggedIn): ?>

            <a href="profile.php">
                <i class="fas fa-user"></i> My Profile
            </a>

            <a href="my-bookings.php">
                <i class="fas fa-book"></i> My Bookings
            </a>

            <a href="notifications.php">
                <i class="fas fa-bell"></i> Notifications
            </a>

        <?php else: ?>

            <a href="login.php">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>

            <a href="register.php">
                <i class="fas fa-user-plus"></i> Register
            </a>

        <?php endif; ?>

        <a href="#about">
            <i class="fas fa-info-circle"></i> About
        </a>

        <a href="#contact">
            <i class="fas fa-envelope"></i> Contact
        </a>

    </nav>

</header>


<main>

<!-- ==========================================
     HERO SECTION WITH SEARCH
========================================== -->

<section class="home-hero">

    <div class="hero-content">

        <span class="hero-tag">
            <i class="fas fa-utensils"></i>
            YOUR TABLE, YOUR EXPERIENCE
        </span>

        <?php if ($isLoggedIn): ?>

            <div class="hero-welcome">
                Welcome back,
                <span><?php echo htmlspecialchars($customerName); ?></span>!
            </div>

        <?php endif; ?>

        <h1>
            Discover Great Food.<br>
            <span>Reserve Your Table.</span>
        </h1>

        <p class="hero-description">
            Find amazing restaurants, explore delicious meals and
            reserve your table without the stress. Your next great
            dining experience starts with DineNow.
        </p>

        <div class="hero-buttons">

            <a href="restaurants.php" class="primary-btn">
                Explore Restaurants
                <i class="fas fa-arrow-right"></i>
            </a>

            <a href="#how-it-works" class="secondary-btn">
                How It Works
            </a>

        </div>

        <!-- SEARCH BAR -->

        <form action="restaurants.php" method="GET" class="hero-search">

            <div class="hero-search-field">

                <i class="fas fa-map-marker-alt"></i>

                <div>

                    <label for="searchLocation">Restaurant or Location</label>

                    <input
                        type="text"
                        id="searchLocation"
                        name="search"
                        placeholder="Restaurant name or city..."
                    >

                </div>

            </div>

            <div class="hero-search-field">

                <i class="fas fa-utensils"></i>

                <div>

                    <label for="searchCuisine">Cuisine</label>

                    <select name="cuisine" id="searchCuisine">

                        <option value="">All cuisines</option>
                        <option value="African">African</option>
                        <option value="Italian">Italian</option>
                        <option value="Chinese">Chinese</option>
                        <option value="Fast Food">Fast Food</option>
                        <option value="Continental">Continental</option>
                        <option value="Other">Other</option>

                    </select>

                </div>

            </div>

            <button type="submit">
                <i class="fas fa-search"></i>
                Find Restaurants
            </button>

        </form>

        <!-- HERO STATISTICS -->

        <div class="hero-stats">

            <div class="hero-stat">
                <h3><?php echo $totalRestaurants; ?>+</h3>
                <p>Restaurants</p>
            </div>

            <div class="hero-stat">
                <h3>Easy</h3>
                <p>Online Booking</p>
            </div>

            <div class="hero-stat">
                <h3>24/7</h3>
                <p>Discover Anytime</p>
            </div>

        </div>

    </div>

</section>


<!-- ==========================================
     FEATURED RESTAURANTS
========================================== -->

<section class="featured-section" id="featured">

    <div class="section-heading">

        <span>EXPLORE DINING</span>

        <h2>Featured Restaurants</h2>

        <p>
            Discover some of the restaurants waiting to serve you.
        </p>

    </div>

    <div class="restaurant-grid">

        <?php if (!empty($featuredRestaurants)): ?>

            <?php foreach ($featuredRestaurants as $restaurant): ?>

                <div class="restaurant-card">

                    <div class="restaurant-card-image">

                        <img
                            src="<?php echo displayImage(
                                $restaurant['image'],
                                'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800'
                            ); ?>"
                            alt="<?php echo htmlspecialchars($restaurant['name']); ?>"
                            loading="lazy"
                        >

                        <span class="restaurant-label">
                            FEATURED
                        </span>

                    </div>

                    <div class="restaurant-card-content">

                        <h3>
                            <?php echo htmlspecialchars($restaurant['name']); ?>
                        </h3>

                        <p>
                            <?php
                            $description = $restaurant['description'] ?? '';

                            echo htmlspecialchars(
                                strlen($description) > 105
                                    ? substr($description, 0, 105) . '...'
                                    : $description
                            );
                            ?>
                        </p>

                        <div class="restaurant-meta">

                            <span>
                                <i class="fas fa-utensils"></i>
                                <?php echo htmlspecialchars($restaurant['cuisine'] ?? 'Restaurant'); ?>
                            </span>

                            <span>
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($restaurant['city'] ?? $restaurant['address'] ?? 'Location available'); ?>
                            </span>

                        </div>

                        <a
                            href="restaurant-details.php?id=<?php echo (int) $restaurant['id']; ?>"
                            class="card-link"
                        >
                            View Restaurant
                            <i class="fas fa-arrow-right"></i>
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty-message">
                <i class="fas fa-utensils"></i>
                <p>No active restaurants are available right now.</p>
            </div>

        <?php endif; ?>

    </div>

    <div style="text-align:center; margin-top:40px;">

        <a href="restaurants.php" class="primary-btn">
            View All Restaurants
            <i class="fas fa-arrow-right"></i>
        </a>

    </div>

</section>


<!-- ==========================================
     POPULAR DISHES
========================================== -->

<section class="dishes-section" id="popular-dishes">

    <div class="section-heading">

        <span>MADE TO SATISFY</span>

        <h2>Popular Dishes</h2>

        <p>
            Take a look at some delicious meals on our restaurant menus.
        </p>

    </div>

    <div class="dishes-grid">

        <?php if (!empty($popularDishes)): ?>

            <?php foreach ($popularDishes as $dish): ?>

                <div class="dish-card">

                    <div class="dish-image">

                        <img
                            src="<?php echo displayImage(
                                $dish['image'] ?? '',
                                'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800'
                            ); ?>"
                            alt="<?php echo htmlspecialchars($dish['name']); ?>"
                            loading="lazy"
                        >

                    </div>

                    <div class="dish-content">

                        <h3>
                            <?php echo htmlspecialchars($dish['name']); ?>
                        </h3>

                        <p>
                            <?php
                            $dishDescription = $dish['description'] ?? '';

                            echo htmlspecialchars(
                                strlen($dishDescription) > 85
                                    ? substr($dishDescription, 0, 85) . '...'
                                    : $dishDescription
                            );
                            ?>
                        </p>

                        <div class="dish-restaurant">
                            <i class="fas fa-store"></i>
                            <?php echo htmlspecialchars($dish['restaurant_name']); ?>
                        </div>

                        <div class="dish-bottom">

                            <span class="dish-price">
                                ₦<?php echo number_format((float) $dish['price'], 2); ?>
                            </span>

                            <a href="restaurant-details.php?id=<?php echo (int) $dish['restaurant_id']; ?>">
                                View Menu
                            </a>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty-message">
                <i class="fas fa-bowl-food"></i>
                <p>Our dishes will appear here soon.</p>
            </div>

        <?php endif; ?>

    </div>

</section>


<!-- ==========================================
     HOW DINENOW WORKS
========================================== -->

<section class="how-section" id="how-it-works">

    <div class="section-heading">

        <span>SIMPLE AND CONVENIENT</span>

        <h2>How DineNow Works</h2>

        <p>
            Your next dining experience is only three simple steps away.
        </p>

    </div>

    <div class="steps-grid">

        <div class="step-card">

            <div class="step-icon">
                <i class="fas fa-search"></i>
            </div>

            <span class="step-number">STEP 01</span>

            <h3>Discover</h3>

            <p>
                Explore available restaurants, check their locations
                and browse the meals on their menus.
            </p>

        </div>


        <div class="step-card">

            <div class="step-icon">
                <i class="fas fa-calendar-check"></i>
            </div>

            <span class="step-number">STEP 02</span>

            <h3>Reserve</h3>

            <p>
                Choose your preferred restaurant, select a date,
                time and number of guests, then submit your booking.
            </p>

        </div>


        <div class="step-card">

            <div class="step-icon">
                <i class="fas fa-utensils"></i>
            </div>

            <span class="step-number">STEP 03</span>

            <h3>Enjoy</h3>

            <p>
                Keep track of your reservation and get ready to enjoy
                a wonderful dining experience.
            </p>

        </div>

    </div>

</section>


<!-- ==========================================
     ABOUT DINENOW
========================================== -->

<section class="about-section" id="about">

    <div class="about-image">

        <img
            src="https://images.unsplash.com/photo-1559339352-11d035aa65de?w=1000"
            alt="People enjoying a restaurant dining experience"
            loading="lazy"
        >

    </div>

    <div class="about-content">

        <span class="section-tag">
            ABOUT DINENOW
        </span>

        <h2>
            Making Every Dining Experience
            <span>Worth Remembering.</span>
        </h2>

        <p>
            DineNow is a restaurant discovery and table reservation
            platform designed to make dining easier and more convenient.
        </p>

        <p>
            We connect customers with restaurants, allowing them to
            explore different dining options, view menus and reserve
            tables without unnecessary stress.
        </p>

        <div class="about-features">

            <div class="about-feature">
                <i class="fas fa-check-circle"></i>
                Easy Restaurant Discovery
            </div>

            <div class="about-feature">
                <i class="fas fa-check-circle"></i>
                Simple Table Reservations
            </div>

            <div class="about-feature">
                <i class="fas fa-check-circle"></i>
                Explore Restaurant Menus
            </div>

            <div class="about-feature">
                <i class="fas fa-check-circle"></i>
                Manage Your Bookings
            </div>

        </div>

        <a href="restaurants.php" class="primary-btn">
            Start Exploring
            <i class="fas fa-arrow-right"></i>
        </a>

    </div>

</section>


<!-- ==========================================
     CALL TO ACTION
========================================== -->

<section class="cta-section">

    <h2>Ready for Your Next Dining Experience?</h2>

    <p>
        Find a restaurant, reserve your table and enjoy the experience.
    </p>

    <a href="restaurants.php" class="primary-btn">
        Book Your Table
        <i class="fas fa-arrow-right"></i>
    </a>

</section>

</main>


<!-- ==========================================
     FOOTER AND CONTACT
========================================== -->

<footer class="home-footer" id="contact">

    <div class="footer-grid">

        <!-- BRAND -->

        <div class="footer-brand">

            <a href="index.php" class="logo">
                Dine<span>Now.</span>
            </a>

            <p>
                Discover great restaurants, explore delicious meals
                and reserve your table with ease.
                Your dining experience starts here.
            </p>

            <div class="footer-socials">

                <a href="#" aria-label="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>

                <a href="#" aria-label="Instagram">
                    <i class="fab fa-instagram"></i>
                </a>

                <a href="#" aria-label="X">
                    <i class="fab fa-x-twitter"></i>
                </a>

            </div>

        </div>


        <!-- QUICK LINKS -->

        <div class="footer-column">

            <h3>Quick Links</h3>

            <a href="index.php">Home</a>

            <a href="restaurants.php">Restaurants</a>

            <a href="#featured">Featured Restaurants</a>

            <a href="#popular-dishes">Popular Dishes</a>

            <a href="#how-it-works">How It Works</a>

        </div>


        <!-- CUSTOMER LINKS -->

        <div class="footer-column">

            <h3>My Account</h3>

            <?php if ($isLoggedIn): ?>

                <a href="profile.php">My Profile</a>

                <a href="my-bookings.php">My Bookings</a>

                <a href="notifications.php">Notifications</a>

            <?php else: ?>

                <a href="login.php">Login</a>

                <a href="register.php">Create Account</a>

            <?php endif; ?>

            <a href="restaurants.php">Book a Table</a>

        </div>


        <!-- CONTACT -->

        <div class="footer-column">

            <h3>Contact Us</h3>

            <p>
                <i class="fas fa-envelope"></i>
                Support through DineNow
            </p>

            <p>
                <i class="fas fa-utensils"></i>
                Restaurant Discovery & Reservations
            </p>

            <p>
                <i class="fas fa-clock"></i>
                Discover restaurants anytime
            </p>

        </div>

    </div>


    <div class="footer-bottom">

        <p>
            &copy; <?php echo date("Y"); ?>
            DineNow. All Rights Reserved.
        </p>

        <p>
            BOOK. DINE. ENJOY.
        </p>

    </div>

</footer>


<!-- ==========================================
     HAMBURGER MENU SCRIPT
========================================== -->

<script>

const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");
const menuIcon = menuToggle.querySelector("i");

function closeMenu() {

    navLinks.classList.remove("show");

    menuToggle.setAttribute("aria-expanded", "false");

    menuIcon.classList.remove("fa-times");
    menuIcon.classList.add("fa-bars");

}

function toggleMenu() {

    const isOpen = navLinks.classList.toggle("show");

    menuToggle.setAttribute("aria-expanded", isOpen);

    if (isOpen) {

        menuIcon.classList.remove("fa-bars");
        menuIcon.classList.add("fa-times");

    } else {

        menuIcon.classList.remove("fa-times");
        menuIcon.classList.add("fa-bars");

    }

}

menuToggle.addEventListener("click", toggleMenu);


// Close menu after clicking a link

navLinks.querySelectorAll("a").forEach(function(link) {

    link.addEventListener("click", closeMenu);

});


// Close menu when clicking outside

document.addEventListener("click", function(event) {

    if (
        !menuToggle.contains(event.target) &&
        !navLinks.contains(event.target)
    ) {

        closeMenu();

    }

});


// Close menu with Escape key

document.addEventListener("keydown", function(event) {

    if (event.key === "Escape") {

        closeMenu();

    }

});

</script>

</body>
</html>
