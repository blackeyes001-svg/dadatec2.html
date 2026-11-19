?php // Start session safely if (session_status() === PHP_SESSION_NONE) {     session_start(); }  $pageTitle    = "DineNow | Discover & Book Restaurants"; $isLoggedIn   = isset($\_SESSION['user_id']); $customerName = $\_SESSION['full_name'] ?? ''; ?>
<!DOCTYPE html>

<html lang="en">

zhead>
&#x20;   \<meta charset="UTF-8">
&#x20;   \<meta name="viewport" content="width=device-width, initial-scale=1.0">


<title><?php echo htmlspecialchars($pageTitle); ?></title>

<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/responsive.css">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


</head>

<body>

<!-- NAVIGATION BAR -->

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

    <!-- RESTAURANT IMAGE -->
    <div class="hero-image">

        <?php if ($isLoggedIn): ?>

            <div class="welcome-message">
                Welcome,
                <span><?php echo htmlspecialchars($customerName); ?></span>
            </div>

        <?php endif; ?>

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
                <label for="location">Location</label>
                <input
                    type="text"
                    id="location"
                    name="location"
                    placeholder="Enter location"
                >
            </div>

        </div>

        <div class="search-field">

            <i class="fas fa-utensils"></i>

            <div>
                <label for="cuisine">Cuisine</label>
                <select name="cuisine" id="cuisine">
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

<!-- HAMBURGER MENU SCRIPT -->

<script> const menuToggle = document.getElementById("menuToggle"); const navLinks   = document.getElementById("navLinks"); const menuIcon   = menuToggle.querySelector("i");  function closeMenu() {     navLinks.classList.remove("show");     menuToggle.setAttribute("aria-expanded", "false");     menuIcon.classList.remove("fa-times");     menuIcon.classList.add("fa-bars"); }  function toggleMenu() {     const isOpen = navLinks.classList.toggle("show");      menuToggle.setAttribute("aria-expanded", isOpen);      if (isOpen) {         menuIcon.classList.remove("fa-bars");         menuIcon.classList.add("fa-times");     } else {         menuIcon.classList.remove("fa-times");         menuIcon.classList.add("fa-bars");     } }  menuToggle.addEventListener("click", toggleMenu);  // Close menu after clicking a link navLinks.querySelectorAll("a").forEach(function (link) {     link.addEventListener("click", closeMenu); });  // Close menu when clicking outside document.addEventListener("click", function (event) {     if (!menuToggle.contains(event.target) && !navLinks.contains(event.target)) {         closeMenu();     } });  // Close menu with Escape key document.addEventListener("keydown", function (event) {     if (event.key === "Escape") {         closeMenu();     } }); \</script>

</body> \</html>
