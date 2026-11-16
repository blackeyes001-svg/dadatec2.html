<?php

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "DineNow | Discover & Book Restaurants";

// Check login status
$isLoggedIn = isset($_SESSION['user_id']);
$customerName = $_SESSION['full_name'] ?? '';

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

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body>

<header class="navbar">

    <a href="index.php" class="logo">
        Dine<span>Now.</span>
    </a>

    <nav class="nav-links" id="navLinks">

        <a href="index.php" class="active">
            Home
        </a>

        <a href="restaurants.php">
            Restaurants
        </a>

        <a href="restaurants.php">
            Restaurant Details
        </a>

        <a href="restaurants.php">
            Book a Table
        </a>

        <a href="#about">
            About
        </a>

        <a href="#contact">
            Contact
        </a>

        <?php if ($isLoggedIn): ?>

            <a href="profile.php">
                My Profile
            </a>

            <a href="my-bookings.php">
                My Bookings
            </a>

        <?php endif; ?>

    </nav>


    <div class="nav-buttons">

        <?php if ($isLoggedIn): ?>

            <span style="color: #FFFFFF; font-weight: 600; margin-right: 12px;">
                Welcome, <?php echo htmlspecialchars($customerName); ?>
            </span>

            <a href="logout.php" class="signup-btn">
                Logout
            </a>

        <?php else: ?>

            <a href="login.php" class="login-btn">
                Login
            </a>

            <a href="register.php" class="signup-btn">
                Register
            </a>

        <?php endif; ?>

    </div>


    <button class="menu-toggle" id="menuToggle" aria-label="Open navigation menu">
        <i class="fas fa-bars"></i>
    </button>

</header>


<main>

    <!-- HERO SECTION -->

    <section class="hero">

        <div class="hero-content">

            <span class="hero-tag">
                <i class="fas fa-utensils"></i>
                YOUR TABLE, YOUR EXPERIENCE
            </span>

            <h1>
                Discover Great Food.<br>
                <span>Reserve Your Table.</span>
            </h1>

            <p>
                Find the perfect restaurant, explore amazing menus
                and reserve your table effortlessly.
            </p>

            <div class="hero-buttons">

                <a href="restaurants.php" class="primary-btn">
                    Explore Restaurants
                    <i class="fas fa-arrow-right"></i>
                </a>

                <a href="#about" class="secondary-btn">
                    Learn More
                </a>

            </div>


            <div class="hero-stats">

                <div>
                    <h3>5+</h3>
                    <p>Restaurants</p>
                </div>

                <div>
                    <h3>1K+</h3>
                    <p>Happy Customers</p>
                </div>

                <div>
                    <h3>100%</h3>
                    <p>Easy Booking</p>
                </div>

            </div>

        </div>


        <div class="hero-image">

            <img
                src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=900"
                alt="Beautiful restaurant interior"
            >

            <div class="floating-card">

                <i class="fas fa-calendar-check"></i>

                <div>
                    <strong>Easy Reservations</strong>
                    <small>Book your table in minutes</small>
                </div>

            </div>

        </div>

    </section>



    <!-- RESTAURANT SEARCH -->

    <section class="search-section">

        <div class="search-heading">

            <h2>Find Your Perfect Restaurant</h2>

            <p>Where would you like to dine today?</p>

        </div>


        <form action="restaurants.php" method="GET" class="search-box">


            <div class="search-field">

                <i class="fas fa-map-marker-alt"></i>

                <div>

                    <label>Location</label>

                    <input
                        type="text"
                        name="location"
                        placeholder="Enter location"
                    >

                </div>

            </div>



            <div class="search-field">

                <i class="fas fa-utensils"></i>

                <div>

                    <label>Cuisine</label>

                    <select name="cuisine">

                        <option value="">All cuisines</option>

                        <option>Italian</option>

                        <option>African</option>

                        <option>Chinese</option>

                        <option>Fast Food</option>

                    </select>

                </div>

            </div>



            <button type="submit" class="search-btn">

                <i class="fas fa-search"></i>
                Find Restaurants

            </button>


        </form>

    </section>



    <!-- ABOUT SECTION -->

    <section class="about-section" id="about">

        <div>

            <span class="section-tag">
                WHY DINENOW?
            </span>

            <h2>
                Your Next Great Dining Experience Starts Here.
            </h2>

            <p>
                DineNow makes restaurant discovery and table reservations
                simple. Explore restaurants, check available tables and
                book your next dining experience.
            </p>

        </div>

    </section>

</main>



<!-- FOOTER -->

<footer id="contact">

    <a href="index.php" class="logo">
        Dine<span>Now.</span>
    </a>

    <p>Discover. Reserve. Enjoy.</p>

    <p>
        &copy; <?php echo date("Y"); ?>
        DineNow. All Rights Reserved.
    </p>

</footer>


<script src="js/script.js"></script>

</body>
</html>