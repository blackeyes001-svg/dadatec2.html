<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/db.php";

/* ADMIN AUTHENTICATION */

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header("Location: login.php");
    exit;
}

/* DASHBOARD STATISTICS */

$totalRestaurants = $pdo
    ->query("SELECT COUNT(*) FROM restaurants")
    ->fetchColumn();

$totalCustomers = $pdo
    ->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")
    ->fetchColumn();

$totalBookings = $pdo
    ->query("SELECT COUNT(*) FROM bookings")
    ->fetchColumn();

$pendingBookings = $pdo
    ->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")
    ->fetchColumn();

/* RECENT BOOKINGS */

$recentBookings = $pdo->query("
    SELECT
        b.id,
        b.booking_date,
        b.booking_time,
        b.guests,
        b.status,
        u.full_name,
        r.name AS restaurant_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN restaurants r ON b.restaurant_id = r.id
    ORDER BY b.created_at DESC
    LIMIT 8
")->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>DineNow | Admin Dashboard</title>

<style>

/* GLOBAL SETTINGS */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Arial, sans-serif;
}

:root {
    --orange: #FF7628;
    --black: #101010;
    --dark: #191919;
    --white: #FFFFFF;
    --muted: #AAAAAA;
    --glass: rgba(255, 255, 255, 0.055);
    --border: rgba(255, 255, 255, 0.12);
}

body {
    min-height: 100vh;
    color: var(--white);

    background:
        radial-gradient(circle at 15% 10%, rgba(255, 118, 40, 0.12), transparent 35%),
        radial-gradient(circle at 90% 90%, rgba(255, 118, 40, 0.07), transparent 35%),
        linear-gradient(135deg, #080808, #171717, #101010);

    background-attachment: fixed;
}

/* GLASS EFFECT */

.glass {
    background: var(--glass);
    border: 1px solid var(--border);

    backdrop-filter: blur(22px);
    -webkit-backdrop-filter: blur(22px);

    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.35);
}

/* SIDEBAR */

.sidebar {
    position: fixed;
    top: 18px;
    left: 18px;
    bottom: 18px;

    width: 255px;
    padding: 25px 17px;

    border-radius: 22px;
    z-index: 100;

    display: flex;
    flex-direction: column;

    overflow-y: auto;
}

.logo {
    font-size: 27px;
    font-weight: 800;
    letter-spacing: -1px;
    padding: 5px 12px 30px;
}

.logo span {
    color: var(--orange);
}

.sidebar-label {
    color: #777777;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.7px;
    padding: 15px 12px 10px;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 13px;

    color: #CCCCCC;
    text-decoration: none;

    padding: 13px 14px;
    margin-bottom: 5px;

    border-radius: 12px;
    font-size: 14px;

    transition: 0.3s;
}

.sidebar a:hover {
    background: rgba(255, 118, 40, 0.10);
    color: white;
    transform: translateX(4px);
}

.sidebar a.active {
    color: white;

    background: linear-gradient(
        110deg,
        rgba(255, 118, 40, 0.30),
        rgba(255, 118, 40, 0.09)
    );

    border: 1px solid rgba(255, 118, 40, 0.35);

    box-shadow: 0 0 20px rgba(255, 118, 40, 0.10);
}

.nav-icon {
    width: 22px;
    text-align: center;
    font-size: 17px;
}

.sidebar-bottom {
    margin-top: auto;
    padding-top: 25px;
}

.sidebar .logout-link {
    color: #FF9A80;
}

/* MAIN CONTENT */

.main {
    margin-left: 290px;
    padding: 30px 35px 40px;
    max-width: 1700px;
}

/* TOPBAR */

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 35px;
}

.welcome h1 {
    font-size: 29px;
    font-weight: 700;
    letter-spacing: -0.7px;
}

.welcome p {
    color: var(--muted);
    font-size: 14px;
    margin-top: 7px;
}

.welcome p span {
    color: var(--orange);
    font-weight: 600;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 15px;
}

.search-box {
    display: flex;
    align-items: center;
    gap: 10px;

    padding: 12px 15px;
    width: 230px;
    border-radius: 12px;
}

.search-box input {
    width: 100%;
    background: transparent;
    border: none;
    outline: none;
    color: white;
    font-size: 13px;
}

.search-box input::placeholder {
    color: #888888;
}

.profile {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 13px;
    border-radius: 13px;
}

.avatar {
    width: 38px;
    height: 38px;

    display: grid;
    place-items: center;

    border-radius: 50%;
    background: linear-gradient(135deg, #FF7628, #B94412);

    font-weight: bold;
}

.profile-info strong {
    display: block;
    font-size: 12px;
}

.profile-info small {
    color: var(--muted);
    font-size: 11px;
}

/* PAGE INTRO */

.page-intro {
    margin-bottom: 25px;
}

.page-intro h2 {
    font-size: 19px;
    margin-bottom: 6px;
}

.page-intro p {
    color: var(--muted);
    font-size: 13px;
}

/* STATISTICS */

.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 19px;
    margin-bottom: 32px;
}

.stat-card {
    position: relative;
    padding: 23px;
    border-radius: 18px;
    overflow: hidden;
    transition: 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    border-color: rgba(255, 118, 40, 0.55);
    box-shadow: 0 12px 30px rgba(255, 118, 40, 0.08);
}

.stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}

.stat-label {
    color: #BBBBBB;
    font-size: 13px;
}

.stat-icon {
    width: 43px;
    height: 43px;

    display: grid;
    place-items: center;

    border-radius: 13px;
    font-size: 20px;

    color: var(--orange);
    background: rgba(255, 118, 40, 0.14);
}

.stat-card h2 {
    font-size: 31px;
    font-weight: 700;
    margin-bottom: 8px;
    color: white;
}

.stat-card small {
    color: #999999;
    font-size: 11px;
}

/* RECENT BOOKINGS SECTION */

.content-section {
    border-radius: 20px;
    padding: 25px;
}

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 22px;
}

.section-heading h2 {
    font-size: 18px;
}

.section-heading p {
    color: var(--muted);
    font-size: 12px;
    margin-top: 5px;
}

.view-all {
    color: var(--orange);
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
}

.view-all:hover {
    color: white;
}

/* TABLE */

.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 720px;
    border-collapse: collapse;
}

th {
    color: var(--orange);
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    font-weight: 600;
    padding: 15px 12px;
    text-align: left;
    border-bottom: 1px solid rgba(255,255,255,0.12);
}

td {
    color: #DDDDDD;
    font-size: 13px;
    padding: 17px 12px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
}

tbody tr {
    transition: 0.2s;
}

tbody tr:hover {
    background: rgba(255, 118, 40, 0.045);
}

.reference {
    color: var(--orange);
    font-weight: 700;
}

.customer-name {
    font-weight: 600;
}

.restaurant-name {
    color: #CCCCCC;
}

/* BOOKING STATUS */

.status {
    display: inline-block;
    padding: 6px 11px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: capitalize;
    background: rgba(255,255,255,0.10);
}

.status.pending {
    color: #FFC56A;
    background: rgba(255, 180, 55, 0.13);
}

.status.confirmed,
.status.completed {
    color: #65E4AD;
    background: rgba(50, 220, 145, 0.13);
}

.status.cancelled,
.status.rejected {
    color: #FF9696;
    background: rgba(255, 80, 80, 0.13);
}

.status.processing {
    color: #FFAA75;
    background: rgba(255, 118, 40, 0.13);
}

.empty-message {
    color: var(--muted);
    text-align: center;
    padding: 35px;
}

/* MOBILE MENU */

.mobile-menu {
    display: none;
    background: var(--glass);
    border: 1px solid var(--border);
    color: white;
    width: 42px;
    height: 42px;
    border-radius: 10px;
    font-size: 21px;
    cursor: pointer;
}

/* RESPONSIVENESS */

@media (max-width: 1200px) {
    .stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 850px) {

    .sidebar {
        transform: translateX(-120%);
        transition: 0.3s;
        width: 260px;
    }

    .sidebar.open {
        transform: translateX(0);
    }

    .main {
        margin-left: 0;
        padding: 25px;
    }

    .mobile-menu {
        display: block;
    }

    .topbar {
        flex-wrap: wrap;
    }

    .welcome {
        flex: 1;
    }
}

@media (max-width: 600px) {

    .main {
        padding: 18px 13px;
    }

    .topbar {
        align-items: flex-start;
        gap: 13px;
    }

    .welcome h1 {
        font-size: 22px;
    }

    .welcome p {
        font-size: 12px;
    }

    .topbar-right {
        width: 100%;
        justify-content: space-between;
    }

    .search-box {
        flex: 1;
        width: auto;
    }

    .profile {
        padding: 5px;
    }

    .profile-info {
        display: none;
    }

    .stats {
        gap: 11px;
    }

    .stat-card {
        padding: 16px;
    }

    .stat-card h2 {
        font-size: 25px;
    }

    .stat-label {
        font-size: 11px;
    }

    .stat-icon {
        width: 35px;
        height: 35px;
        font-size: 16px;
    }

    .content-section {
        padding: 17px 12px;
    }

    .section-heading h2 {
        font-size: 16px;
    }
}

@media (max-width: 380px) {
    .stats {
        grid-template-columns: 1fr;
    }
}

/* SIDEBAR OVERLAY */

.overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.65);
    z-index: 90;
}

.overlay.show {
    display: block;
}

/* ANIMATIONS */

.stat-card,
.content-section,
.topbar {
    animation: fadeUp 0.6s ease both;
}

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

</style>

</head>

<body>

<!-- SIDEBAR -->

<aside class="sidebar glass" id="sidebar">

    <div class="logo">
        Dine<span>Now.</span>
    </div>

    <div class="sidebar-label">OVERVIEW</div>

    <a href="dashboard.php" class="active">
        <span class="nav-icon">▦</span>
        Dashboard
    </a>

    <div class="sidebar-label">MANAGEMENT</div>

    <a href="restaurants.php">
        <span class="nav-icon">🍽</span>
        Restaurants
    </a>

    <a href="bookings.php">
        <span class="nav-icon">📅</span>
        Bookings
    </a>

    <a href="customers.php">
        <span class="nav-icon">♙</span>
        Customers
    </a>

    <div class="sidebar-label">WEBSITE</div>

    <a href="../index.php">
        <span class="nav-icon">⌂</span>
        View Website
    </a>

    <div class="sidebar-bottom">

        <a href="../logout.php" class="logout-link">
            <span class="nav-icon">⇥</span>
            Logout
        </a>

    </div>

</aside>

<div class="overlay" id="overlay"></div>

<!-- MAIN CONTENT -->

<main class="main">

    <!-- TOPBAR -->

    <header class="topbar">

        <button class="mobile-menu" id="menuButton" type="button">
            ☰
        </button>

        <div class="welcome">

            <h1>Dashboard Overview</h1>

            <p>
                Welcome, <span>Dada David.</span>
                Here's what's happening with DineNow.
            </p>

        </div>

        <div class="topbar-right">

            <div class="search-box glass">
                <span>⌕</span>
                <input type="text" id="bookingSearch" placeholder="Search bookings...">
            </div>

            <div class="profile glass">

                <div class="avatar">D</div>

                <div class="profile-info">
                    <strong>Dada David</strong>
                    <small>Administrator</small>
                </div>

            </div>

        </div>

    </header>

    <!-- PAGE INTRO -->

    <div class="page-intro">

        <h2>Platform Summary</h2>

        <p>Here's a quick look at your restaurant booking platform.</p>

    </div>

    <!-- STATISTICS -->

    <section class="stats">

        <div class="stat-card glass">

            <div class="stat-top">
                <p class="stat-label">Total Restaurants</p>
                <div class="stat-icon">🍽</div>
            </div>

            <h2><?php echo (int)$totalRestaurants; ?></h2>

            <small>Restaurants on your platform</small>

        </div>

        <div class="stat-card glass">

            <div class="stat-top">
                <p class="stat-label">Total Customers</p>
                <div class="stat-icon">♙</div>
            </div>

            <h2><?php echo (int)$totalCustomers; ?></h2>

            <small>Registered customers</small>

        </div>

        <div class="stat-card glass">

            <div class="stat-top">
                <p class="stat-label">Total Bookings</p>
                <div class="stat-icon">📅</div>
            </div>

            <h2><?php echo (int)$totalBookings; ?></h2>

            <small>All booking records</small>

        </div>

        <div class="stat-card glass">

            <div class="stat-top">
                <p class="stat-label">Pending Bookings</p>
                <div class="stat-icon">◷</div>
            </div>

            <h2><?php echo (int)$pendingBookings; ?></h2>

            <small>Awaiting processing</small>

        </div>

    </section>

    <!-- RECENT BOOKINGS -->

    <section class="content-section glass">

        <div class="section-heading">

            <div>
                <h2>Recent Bookings</h2>
                <p>Latest reservations made by your customers.</p>
            </div>

            <a href="bookings.php" class="view-all">
                View All →
            </a>

        </div>

        <div class="table-container">

            <table id="bookingsTable">

                <thead>

                    <tr>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Restaurant</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Guests</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($recentBookings as $booking): ?>

                        <tr>

                            <td class="reference">
                                #<?php echo (int)$booking['id']; ?>
                            </td>

                            <td class="customer-name">
                                <?php echo htmlspecialchars($booking['full_name']); ?>
                            </td>

                            <td class="restaurant-name">
                                <?php echo htmlspecialchars($booking['restaurant_name']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($booking['booking_date']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($booking['booking_time']); ?>
                            </td>

                            <td>
                                <?php echo (int)$booking['guests']; ?>
                            </td>

                            <td>
                                <span class="status <?php echo htmlspecialchars(strtolower($booking['status'])); ?>">
                                    <?php echo htmlspecialchars($booking['status']); ?>
                                </span>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (!$recentBookings): ?>

                        <tr>
                            <td colspan="7" class="empty-message">
                                No bookings available yet.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<script>

/* MOBILE SIDEBAR */

const sidebar = document.getElementById('sidebar');
const menuButton = document.getElementById('menuButton');
const overlay = document.getElementById('overlay');

menuButton.addEventListener('click', function () {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('show');
});

overlay.addEventListener('click', function () {
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
});

/* BOOKING SEARCH */

const searchInput = document.getElementById('bookingSearch');
const tableRows = document.querySelectorAll('#bookingsTable tbody tr');

searchInput.addEventListener('input', function () {

    const searchValue = this.value.toLowerCase();

    tableRows.forEach(function (row) {

        const rowText = row.textContent.toLowerCase();

        row.style.display = rowText.includes(searchValue) ? '' : 'none';

    });

});

</script>

</body>
</html>