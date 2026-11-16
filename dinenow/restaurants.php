<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/includes/db.php";

// Get search and filter values
$search = trim($_GET['search'] ?? '');
$location = trim($_GET['location'] ?? '');
$cuisine = trim($_GET['cuisine'] ?? '');

try {

    $sql = "SELECT * FROM restaurants WHERE status = 'active'";

    $params = [];

    if ($search !== '') {
        $sql .= " AND (name LIKE :search OR description LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if ($location !== '') {
        $sql .= " AND city LIKE :location";
        $params[':location'] = "%$location%";
    }

    if ($cuisine !== '') {
        $sql .= " AND cuisine = :cuisine";
        $params[':cuisine'] = $cuisine;
    }

    $sql .= " ORDER BY name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $restaurants = $stmt->fetchAll();

    // Get available cuisines
    $cuisineStmt = $pdo->query("
        SELECT DISTINCT cuisine
        FROM restaurants
        WHERE status = 'active'
        ORDER BY cuisine ASC
    ");

    $cuisines = $cuisineStmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {

    $restaurants = [];
    $cuisines = [];
    $databaseError = true;

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Explore Restaurants | DineNow</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>

        .restaurants-page {
            background: #101010;
            min-height: 100vh;
            color: #fff;
            padding-bottom: 80px;
        }

        .restaurant-hero {
            padding: 90px 8% 55px;
            text-align: center;
            background: linear-gradient(135deg, #191919, #101010);
        }

        .restaurant-hero span {
            color: #FF7628;
            font-weight: 600;
            letter-spacing: 2px;
            font-size: 13px;
        }

        .restaurant-hero h1 {
            font-size: 42px;
            margin: 15px 0;
        }

        .restaurant-hero h1 strong {
            color: #FF7628;
        }

        .restaurant-hero p {
            color: #AAAAAA;
            font-size: 15px;
        }

        .restaurant-container {
            width: 84%;
            max-width: 1300px;
            margin: auto;
        }

        .restaurant-search {
            background: #191919;
            padding: 25px;
            border-radius: 12px;
            display: flex;
            gap: 15px;
            margin: 35px 0;
            flex-wrap: wrap;
        }

        .restaurant-search input,
        .restaurant-search select {
            flex: 1;
            min-width: 180px;
            padding: 15px;
            background: #101010;
            color: #fff;
            border: 1px solid #333;
            border-radius: 7px;
            outline: none;
        }

        .restaurant-search input:focus,
        .restaurant-search select:focus {
            border-color: #FF7628;
        }

        .restaurant-search button {
            background: #FF7628;
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 7px;
            font-weight: 600;
            cursor: pointer;
        }

        .restaurant-search button:hover {
            background: #e65f15;
        }

        .restaurant-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 40px 0 25px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .restaurant-heading h2 {
            font-size: 25px;
        }

        .restaurant-heading span {
            color: #FF7628;
        }

        .restaurant-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .restaurant-card {
            background: #191919;
            border-radius: 12px;
            overflow: hidden;
            transition: 0.3s ease;
            border: 1px solid #292929;
        }

        .restaurant-card:hover {
            transform: translateY(-7px);
            border-color: #FF7628;
        }

        .restaurant-image {
            height: 220px;
            overflow: hidden;
            position: relative;
        }

        .restaurant-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: 0.4s;
        }

        .restaurant-card:hover .restaurant-image img {
            transform: scale(1.07);
        }

        .cuisine-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: #FF7628;
            color: #fff;
            padding: 6px 13px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .restaurant-info {
            padding: 22px;
        }

        .restaurant-info h3 {
            font-size: 19px;
            margin-bottom: 10px;
        }

        .restaurant-info .description {
            color: #AAAAAA;
            font-size: 13px;
            line-height: 1.7;
            min-height: 65px;
        }

        .restaurant-location {
            color: #AAAAAA;
            font-size: 13px;
            margin: 15px 0;
        }

        .restaurant-location i {
            color: #FF7628;
            margin-right: 7px;
        }

        .restaurant-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #333;
            padding-top: 17px;
            gap: 10px;
        }

        .opening-time {
            color: #AAAAAA;
            font-size: 11px;
        }

        .view-details {
            background: #FF7628;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .view-details:hover {
            background: #e65f15;
        }

        .no-results {
            background: #191919;
            padding: 55px 20px;
            text-align: center;
            border-radius: 12px;
            grid-column: 1 / -1;
        }

        .no-results i {
            color: #FF7628;
            font-size: 40px;
            margin-bottom: 15px;
        }

        .no-results h3 {
            margin-bottom: 10px;
        }

        .no-results p {
            color: #AAAAAA;
        }

        .clear-filter {
            color: #FF7628;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
        }

        @media(max-width: 1000px) {
            .restaurant-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width: 650px) {

            .restaurant-hero {
                padding: 65px 6% 40px;
            }

            .restaurant-hero h1 {
                font-size: 30px;
            }

            .restaurant-container {
                width: 90%;
            }

            .restaurant-search {
                flex-direction: column;
                padding: 18px;
            }

            .restaurant-search input,
            .restaurant-search select,
            .restaurant-search button {
                width: 100%;
            }

            .restaurant-grid {
                grid-template-columns: 1fr;
            }

            .restaurant-image {
                height: 230px;
            }

            .restaurant-heading h2 {
                font-size: 21px;
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


<main class="restaurants-page">

    <section class="restaurant-hero">

        <span>DISCOVER YOUR NEXT MEAL</span>

        <h1>
            Explore <strong>Restaurants</strong>
        </h1>

        <p>
            Find amazing restaurants and discover places
            where unforgettable dining experiences begin.
        </p>

    </section>


    <div class="restaurant-container">

        <form action="restaurants.php" method="GET" class="restaurant-search">

            <input
                type="text"
                name="search"
                placeholder="Search restaurant..."
                value="<?= htmlspecialchars($search) ?>"
            >

            <input
                type="text"
                name="location"
                placeholder="Enter city or location..."
                value="<?= htmlspecialchars($location) ?>"
            >

            <select name="cuisine">

                <option value="">All Cuisines</option>

                <?php foreach ($cuisines as $item): ?>

                    <option
                        value="<?= htmlspecialchars($item) ?>"
                        <?= $cuisine === $item ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($item) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <button type="submit">
                <i class="fas fa-search"></i> Search
            </button>

        </form>


        <div class="restaurant-heading">

            <h2>Available Restaurants</h2>

            <span>
                <?= count($restaurants) ?>
                Restaurant(s) Found
            </span>

        </div>


        <div class="restaurant-grid">

            <?php if (isset($databaseError)): ?>

                <div class="no-results">

                    <i class="fas fa-exclamation-circle"></i>

                    <h3>Unable to Load Restaurants</h3>

                    <p>Please check your database connection and try again.</p>

                </div>

            <?php elseif (!empty($restaurants)): ?>

                <?php foreach ($restaurants as $restaurant): ?>

                    <div class="restaurant-card">

                        <div class="restaurant-image">

                            <img
                                src="<?= htmlspecialchars($restaurant['image'] ?: 'images/logo.png') ?>"
                                alt="<?= htmlspecialchars($restaurant['name']) ?>"
                                loading="lazy"
                            >

                            <span class="cuisine-badge">
                                <?= htmlspecialchars($restaurant['cuisine']) ?>
                            </span>

                        </div>


                        <div class="restaurant-info">

                            <h3>
                                <?= htmlspecialchars($restaurant['name']) ?>
                            </h3>

                            <p class="description">
                                <?= htmlspecialchars($restaurant['description']) ?>
                            </p>

                            <p class="restaurant-location">

                                <i class="fas fa-map-marker-alt"></i>

                                <?= htmlspecialchars($restaurant['address']) ?>,
                                <?= htmlspecialchars($restaurant['city']) ?>

                            </p>


                            <div class="restaurant-details">

                                <span class="opening-time">

                                    <i class="far fa-clock"></i>

                                    <?= date('g:i A', strtotime($restaurant['opening_time'])) ?>

                                    -

                                    <?= date('g:i A', strtotime($restaurant['closing_time'])) ?>

                                </span>

                                <a
                                    href="restaurant-details.php?id=<?= (int)$restaurant['id'] ?>"
                                    class="view-details"
                                >
                                    View Details
                                    <i class="fas fa-arrow-right"></i>
                                </a>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="no-results">

                    <i class="fas fa-utensils"></i>

                    <h3>No Restaurants Found</h3>

                    <p>
                        We couldn't find any restaurants matching your search.
                        Try another search or cuisine.
                    </p>

                    <a href="restaurants.php" class="clear-filter">
                        Clear Filters
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>

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