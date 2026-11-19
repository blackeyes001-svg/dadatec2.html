<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/db.php";

/* =========================================
   ADMIN AUTHENTICATION
========================================= */

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header("Location: login.php");
    exit;
}

/* =========================================
   FETCH CUSTOMERS
========================================= */

$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.phone,
        u.created_at,
        COUNT(b.id) AS total_bookings
    FROM users u
    LEFT JOIN bookings b ON b.user_id = u.id
    WHERE u.role = 'customer'
    GROUP BY
        u.id,
        u.full_name,
        u.email,
        u.phone,
        u.created_at
    ORDER BY u.created_at DESC
");

$stmt->execute();

$customers = $stmt->fetchAll();

$totalCustomers = count($customers);

$totalBookings = 0;

foreach ($customers as $customer) {
    $totalBookings += (int)$customer['total_bookings'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>DineNow | Customers Management</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

:root {
    --orange: #FF7628;
    --black: #101010;
    --dark: #191919;
    --white: #FFFFFF;
    --grey: #AAAAAA;
}

body {
    background:
        radial-gradient(circle at 80% 10%, rgba(255,118,40,0.10), transparent 30%),
        radial-gradient(circle at 10% 90%, rgba(255,118,40,0.06), transparent 30%),
        var(--black);
    color: var(--white);
    min-height: 100vh;
}

/* SIDEBAR */

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    height: 100vh;
    padding: 30px 20px;
    background: rgba(20,20,20,0.88);
    backdrop-filter: blur(20px);
    border-right: 1px solid rgba(255,255,255,0.08);
    z-index: 1000;
    transition: 0.3s ease;
}

.logo {
    font-size: 27px;
    font-weight: bold;
    margin-bottom: 8px;
}

.logo span {
    color: var(--orange);
}

.sidebar-subtitle {
    color: var(--grey);
    font-size: 11px;
    letter-spacing: 2px;
    margin-bottom: 45px;
}

.menu-heading {
    color: #777;
    font-size: 10px;
    letter-spacing: 2px;
    margin: 25px 0 12px 12px;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 15px;
    margin-bottom: 7px;
    color: #BBBBBB;
    text-decoration: none;
    border-radius: 10px;
    font-size: 14px;
    transition: 0.3s;
}

.sidebar a i {
    width: 18px;
    font-size: 16px;
}

.sidebar a:hover,
.sidebar a.active {
    color: var(--white);
    background: linear-gradient(
        90deg,
        rgba(255,118,40,0.25),
        rgba(255,118,40,0.06)
    );
    border: 1px solid rgba(255,118,40,0.18);
}

.sidebar a.active {
    box-shadow: 0 0 20px rgba(255,118,40,0.08);
}

.sidebar .logout {
    margin-top: 35px;
    color: #FF7777;
}

/* MAIN CONTENT */

.main {
    margin-left: 250px;
    padding: 30px 35px;
    min-height: 100vh;
}

/* TOPBAR */

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 35px;
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 15px;
}

.menu-toggle {
    display: none;
    background: rgba(255,255,255,0.08);
    color: white;
    border: 1px solid rgba(255,255,255,0.1);
    padding: 10px 12px;
    border-radius: 9px;
    cursor: pointer;
    font-size: 18px;
}

.welcome h2 {
    font-size: 22px;
    margin-bottom: 6px;
}

.welcome h2 span {
    color: var(--orange);
}

.welcome p {
    color: var(--grey);
    font-size: 13px;
}

.admin-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 9px 15px;
    border: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.04);
    border-radius: 30px;
}

.avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, var(--orange), #9F3B0A);
    font-weight: bold;
}

.admin-profile strong {
    font-size: 12px;
    display: block;
}

.admin-profile small {
    color: var(--grey);
    font-size: 10px;
}

/* PAGE HEADING */

.page-heading {
    margin-bottom: 25px;
}

.page-heading h1 {
    font-size: 28px;
    margin-bottom: 9px;
}

.page-heading h1 i {
    color: var(--orange);
    margin-right: 10px;
}

.page-heading p {
    color: var(--grey);
    font-size: 14px;
    line-height: 1.6;
}

/* GLASS CARDS */

.stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    position: relative;
    overflow: hidden;
    padding: 25px;
    border-radius: 17px;
    background: rgba(255,255,255,0.045);
    border: 1px solid rgba(255,255,255,0.09);
    backdrop-filter: blur(18px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.2);
}

.stat-card::after {
    content: "";
    position: absolute;
    width: 120px;
    height: 120px;
    right: -35px;
    top: -35px;
    background: rgba(255,118,40,0.10);
    filter: blur(35px);
    border-radius: 50%;
}

.stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.stat-top p {
    color: var(--grey);
    font-size: 13px;
}

.stat-icon {
    width: 43px;
    height: 43px;
    display: grid;
    place-items: center;
    color: var(--orange);
    background: rgba(255,118,40,0.13);
    border-radius: 12px;
    font-size: 18px;
}

.stat-card h2 {
    font-size: 32px;
    margin-bottom: 7px;
}

.stat-card small {
    color: var(--grey);
    font-size: 12px;
}

/* CUSTOMER TABLE PANEL */

.panel {
    background: rgba(255,255,255,0.045);
    border: 1px solid rgba(255,255,255,0.09);
    backdrop-filter: blur(18px);
    border-radius: 17px;
    padding: 25px;
    box-shadow: 0 12px 35px rgba(0,0,0,0.2);
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.panel-header h2 {
    font-size: 19px;
}

.panel-header h2 i {
    color: var(--orange);
    margin-right: 9px;
}

.panel-header p {
    color: var(--grey);
    font-size: 12px;
    margin-top: 7px;
}

.search-box {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    width: 280px;
    max-width: 100%;
    background: rgba(0,0,0,0.25);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
}

.search-box i {
    color: var(--orange);
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
    color: #777;
}

/* TABLE */

.table-wrapper {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 850px;
    border-collapse: collapse;
}

th {
    text-align: left;
    padding: 16px 13px;
    color: var(--orange);
    font-size: 11px;
    letter-spacing: 0.7px;
    text-transform: uppercase;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    white-space: nowrap;
}

td {
    padding: 17px 13px;
    color: #DDDDDD;
    font-size: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    vertical-align: middle;
}

tbody tr {
    transition: 0.2s;
}

tbody tr:hover {
    background: rgba(255,118,40,0.055);
}

.customer-name {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 160px;
}

.customer-avatar {
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    border-radius: 50%;
    display: grid;
    place-items: center;
    color: var(--orange);
    background: rgba(255,118,40,0.14);
    font-weight: bold;
    font-size: 12px;
}

.customer-name strong {
    color: white;
    font-size: 12px;
}

.booking-count {
    display: inline-block;
    min-width: 32px;
    text-align: center;
    padding: 7px 10px;
    color: var(--orange);
    background: rgba(255,118,40,0.13);
    border: 1px solid rgba(255,118,40,0.15);
    border-radius: 20px;
    font-weight: bold;
}

.empty-message {
    text-align: center;
    padding: 35px !important;
    color: var(--grey);
}

.no-results {
    display: none;
    text-align: center;
    color: var(--grey);
    padding: 25px;
    font-size: 13px;
}

/* OVERLAY */

.overlay {
    display: none;
}

/* RESPONSIVE */

@media (max-width: 1000px) {

    .main {
        padding: 25px;
    }

}

@media (max-width: 750px) {

    .sidebar {
        transform: translateX(-105%);
        width: 270px;
    }

    .sidebar.show {
        transform: translateX(0);
    }

    .main {
        margin-left: 0;
        padding: 22px 17px;
    }

    .menu-toggle {
        display: block;
    }

    .overlay.show {
        display: block;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.7);
        z-index: 900;
    }

    .topbar {
        margin-bottom: 28px;
    }

}

@media (max-width: 550px) {

    .welcome h2 {
        font-size: 17px;
    }

    .welcome p {
        font-size: 11px;
    }

    .admin-profile {
        padding: 6px;
    }

    .admin-profile .profile-info {
        display: none;
    }

    .page-heading h1 {
        font-size: 23px;
    }

    .stats {
        grid-template-columns: 1fr;
        gap: 14px;
    }

    .stat-card {
        padding: 20px;
    }

    .panel {
        padding: 17px;
    }

    .panel-header {
        align-items: stretch;
    }

    .search-box {
        width: 100%;
    }

}

</style>

</head>

<body>

<!-- SIDEBAR -->

<aside class="sidebar" id="sidebar">

    <div class="logo">Dine<span>Now.</span></div>

    <p class="sidebar-subtitle">ADMINISTRATION PANEL</p>

    <p class="menu-heading">MAIN MENU</p>

    <a href="dashboard.php">
        <i class="fas fa-chart-pie"></i>
        Dashboard
    </a>

    <a href="restaurants.php">
        <i class="fas fa-utensils"></i>
        Restaurants
    </a>

    <a href="bookings.php">
        <i class="fas fa-calendar-check"></i>
        Bookings
    </a>

    <a href="customers.php" class="active">
        <i class="fas fa-users"></i>
        Customers
    </a>

    <p class="menu-heading">OTHER</p>

    <a href="../index.php" target="_blank">
        <i class="fas fa-globe"></i>
        View Website
    </a>

    <a href="logout.php" class="logout">
        <i class="fas fa-sign-out-alt"></i>
        Logout
    </a>

</aside>

<div class="overlay" id="overlay"></div>

<!-- MAIN CONTENT -->

<main class="main">

    <!-- TOPBAR -->

    <div class="topbar">

        <div class="topbar-left">

            <button class="menu-toggle" id="menuToggle" aria-label="Open menu">
                <i class="fas fa-bars"></i>
            </button>

            <div class="welcome">

                <h2>Welcome, <span>Dada David.</span></h2>

                <p>Manage your DineNow customers.</p>

            </div>

        </div>

        <div class="admin-profile">

            <div class="avatar">D</div>

            <div class="profile-info">

                <strong>Dada David</strong>

                <small>Administrator</small>

            </div>

        </div>

    </div>

    <!-- PAGE HEADING -->

    <div class="page-heading">

        <h1>
            <i class="fas fa-users"></i>
            Customers Management
        </h1>

        <p>
            View registered customers, their contact information
            and restaurant booking activities.
        </p>

    </div>

    <!-- SUMMARY CARDS -->

    <div class="stats">

        <div class="stat-card">

            <div class="stat-top">

                <p>Total Registered Customers</p>

                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>

            </div>

            <h2><?php echo $totalCustomers; ?></h2>

            <small>Customers registered on DineNow</small>

        </div>

        <div class="stat-card">

            <div class="stat-top">

                <p>Total Customer Bookings</p>

                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>

            </div>

            <h2><?php echo $totalBookings; ?></h2>

            <small>Bookings made by registered customers</small>

        </div>

    </div>

    <!-- CUSTOMER LIST -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    <i class="fas fa-address-book"></i>
                    Customer Directory
                </h2>

                <p>All registered customer accounts.</p>

            </div>

            <div class="search-box">

                <i class="fas fa-search"></i>

                <input
                    type="text"
                    id="customerSearch"
                    placeholder="Search customers..."
                >

            </div>

        </div>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>Customer ID</th>

                        <th>Customer</th>

                        <th>Email Address</th>

                        <th>Phone Number</th>

                        <th>Total Bookings</th>

                        <th>Registered</th>

                    </tr>

                </thead>

                <tbody id="customerTable">

                    <?php if (!empty($customers)): ?>

                        <?php foreach ($customers as $customer): ?>

                            <?php
                                $initial = strtoupper(
                                    substr(trim($customer['full_name']), 0, 1)
                                );
                            ?>

                            <tr class="customer-row">

                                <td>
                                    #<?php echo (int)$customer['id']; ?>
                                </td>

                                <td>

                                    <div class="customer-name">

                                        <div class="customer-avatar">
                                            <?php echo htmlspecialchars($initial); ?>
                                        </div>

                                        <strong>
                                            <?php echo htmlspecialchars($customer['full_name']); ?>
                                        </strong>

                                    </div>

                                </td>

                                <td>
                                    <?php echo htmlspecialchars($customer['email']); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($customer['phone'] ?? 'Not provided'); ?>
                                </td>

                                <td>

                                    <span class="booking-count">
                                        <?php echo (int)$customer['total_bookings']; ?>
                                    </span>

                                </td>

                                <td>
                                    <?php
                                        echo htmlspecialchars(
                                            date('d M Y', strtotime($customer['created_at']))
                                        );
                                    ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="6" class="empty-message">
                                <i class="fas fa-users" style="font-size:25px; display:block; margin-bottom:12px;"></i>
                                No customers registered yet.
                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

            <div class="no-results" id="noResults">
                No matching customers found.
            </div>

        </div>

    </section>

</main>

<script>

/* MOBILE SIDEBAR */

const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");
const menuToggle = document.getElementById("menuToggle");

menuToggle.addEventListener("click", function () {
    sidebar.classList.toggle("show");
    overlay.classList.toggle("show");
});

overlay.addEventListener("click", function () {
    sidebar.classList.remove("show");
    overlay.classList.remove("show");
});

document.addEventListener("keydown", function (event) {

    if (event.key === "Escape") {
        sidebar.classList.remove("show");
        overlay.classList.remove("show");
    }

});

/* CUSTOMER SEARCH */

const searchInput = document.getElementById("customerSearch");
const customerRows = document.querySelectorAll(".customer-row");
const noResults = document.getElementById("noResults");

searchInput.addEventListener("input", function () {

    const searchValue = this.value.toLowerCase().trim();

    let visibleCount = 0;

    customerRows.forEach(function (row) {

        const rowText = row.textContent.toLowerCase();

        if (rowText.includes(searchValue)) {

            row.style.display = "";
            visibleCount++;

        } else {

            row.style.display = "none";

        }

    });

    noResults.style.display =
        visibleCount === 0 && customerRows.length > 0
            ? "block"
            : "none";

});

</script>

</body>
</html>