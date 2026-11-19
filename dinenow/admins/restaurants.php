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
   CSRF TOKEN
========================================= */

if (empty($_SESSION['restaurant_admin_token'])) {
    $_SESSION['restaurant_admin_token'] = bin2hex(random_bytes(32));
}

$message = "";
$error = "";
$editing = null;

/* =========================================
   HANDLE FORM SUBMISSIONS
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['restaurant_admin_token'], $token)) {

        $error = "Invalid request. Please refresh the page.";

    } else {

        $action = $_POST['action'] ?? '';

        /* =========================================
           ADD OR UPDATE RESTAURANT
        ========================================= */

        if ($action === 'save') {

            $id = (int)($_POST['id'] ?? 0);

            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $cuisine = trim($_POST['cuisine'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $image = trim($_POST['image'] ?? '');
            $opening = trim($_POST['opening_time'] ?? '');
            $closing = trim($_POST['closing_time'] ?? '');

            if ($name === '' || $city === '') {

                $error = "Restaurant name and city are required.";

            } elseif (
                $email !== '' &&
                !filter_var($email, FILTER_VALIDATE_EMAIL)
            ) {

                $error = "Please enter a valid email address.";

            } else {

                try {

                    if ($id > 0) {

                        $stmt = $pdo->prepare("
                            UPDATE restaurants SET
                                name = :name,
                                description = :description,
                                address = :address,
                                city = :city,
                                cuisine = :cuisine,
                                phone = :phone,
                                email = :email,
                                image = :image,
                                opening_time = :opening,
                                closing_time = :closing
                            WHERE id = :id
                        ");

                        $stmt->execute([
                            'name' => $name,
                            'description' => $description,
                            'address' => $address,
                            'city' => $city,
                            'cuisine' => $cuisine,
                            'phone' => $phone,
                            'email' => $email,
                            'image' => $image,
                            'opening' => $opening,
                            'closing' => $closing,
                            'id' => $id
                        ]);

                        $message = "Restaurant updated successfully.";

                    } else {

                        $stmt = $pdo->prepare("
                            INSERT INTO restaurants (
                                name,
                                description,
                                address,
                                city,
                                cuisine,
                                phone,
                                email,
                                image,
                                opening_time,
                                closing_time,
                                status
                            )
                            VALUES (
                                :name,
                                :description,
                                :address,
                                :city,
                                :cuisine,
                                :phone,
                                :email,
                                :image,
                                :opening,
                                :closing,
                                'active'
                            )
                        ");

                        $stmt->execute([
                            'name' => $name,
                            'description' => $description,
                            'address' => $address,
                            'city' => $city,
                            'cuisine' => $cuisine,
                            'phone' => $phone,
                            'email' => $email,
                            'image' => $image,
                            'opening' => $opening,
                            'closing' => $closing
                        ]);

                        $message = "Restaurant added successfully.";
                    }

                } catch (PDOException $e) {

                    $error = "Database error: " . $e->getMessage();

                }
            }
        }

        /* =========================================
           ACTIVATE OR DEACTIVATE RESTAURANT
        ========================================= */

        elseif ($action === 'toggle') {

            $id = (int)($_POST['id'] ?? 0);

            if ($id > 0) {

                try {

                    $stmt = $pdo->prepare("
                        UPDATE restaurants
                        SET status = CASE
                            WHEN status = 'active' THEN 'inactive'
                            ELSE 'active'
                        END
                        WHERE id = :id
                    ");

                    $stmt->execute(['id' => $id]);

                    $message = "Restaurant status updated successfully.";

                } catch (PDOException $e) {

                    $error = "Unable to update restaurant status.";

                }
            }
        }
    }
}

/* =========================================
   FETCH RESTAURANT FOR EDITING
========================================= */

if (isset($_GET['edit'])) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM restaurants
        WHERE id = :id
    ");

    $stmt->execute([
        'id' => (int)$_GET['edit']
    ]);

    $editing = $stmt->fetch();

}

/* =========================================
   FETCH ALL RESTAURANTS
========================================= */

$restaurants = $pdo->query("
    SELECT *
    FROM restaurants
    ORDER BY id DESC
")->fetchAll();

$totalRestaurants = count($restaurants);
$activeRestaurants = 0;
$inactiveRestaurants = 0;

foreach ($restaurants as $restaurant) {

    if (($restaurant['status'] ?? '') === 'active') {
        $activeRestaurants++;
    } else {
        $inactiveRestaurants++;
    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>DineNow | Restaurants Management</title>

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
    color: white;
    background: linear-gradient(
        90deg,
        rgba(255,118,40,0.25),
        rgba(255,118,40,0.06)
    );
    border: 1px solid rgba(255,118,40,0.18);
}

.sidebar .logout {
    margin-top: 35px;
    color: #FF7777;
}

/* MAIN */

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

/* MESSAGES */

.alert {
    padding: 15px 18px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 13px;
}

.alert.success {
    background: rgba(46,204,113,0.10);
    border: 1px solid rgba(46,204,113,0.25);
    color: #70DB90;
}

.alert.error {
    background: rgba(255,80,80,0.10);
    border: 1px solid rgba(255,80,80,0.25);
    color: #FF7777;
}

/* STAT CARDS */

.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    position: relative;
    overflow: hidden;
    padding: 23px;
    border-radius: 17px;
    background: rgba(255,255,255,0.045);
    border: 1px solid rgba(255,255,255,0.09);
    backdrop-filter: blur(18px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.2);
}

.stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
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
    font-size: 30px;
    margin-bottom: 7px;
}

.stat-card small {
    color: var(--grey);
    font-size: 11px;
}

/* GLASS PANEL */

.panel {
    background: rgba(255,255,255,0.045);
    border: 1px solid rgba(255,255,255,0.09);
    backdrop-filter: blur(18px);
    border-radius: 17px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 12px 35px rgba(0,0,0,0.2);
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
    flex-wrap: wrap;
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

/* FORM */

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.form-group label {
    display: block;
    color: #CCCCCC;
    font-size: 12px;
    margin-bottom: 9px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 13px 14px;
    background: rgba(0,0,0,0.28);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 9px;
    color: white;
    outline: none;
    font-size: 13px;
    transition: 0.3s;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: var(--orange);
    box-shadow: 0 0 0 3px rgba(255,118,40,0.08);
}

.form-group input::placeholder,
.form-group textarea::placeholder {
    color: #777;
}

.form-group textarea {
    min-height: 110px;
    resize: vertical;
}

.description-group {
    margin-top: 20px;
}

.form-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 22px;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 18px;
    border: none;
    border-radius: 9px;
    cursor: pointer;
    font-size: 12px;
    font-weight: bold;
    text-decoration: none;
    transition: 0.3s;
}

.btn-primary {
    background: var(--orange);
    color: white;
}

.btn-primary:hover {
    background: #E65F12;
    transform: translateY(-2px);
}

.btn-secondary {
    color: #CCCCCC;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.1);
}

.btn-secondary:hover {
    background: rgba(255,255,255,0.14);
}

.cancel-link {
    color: var(--grey);
    text-decoration: none;
    font-size: 12px;
}

.cancel-link:hover {
    color: white;
}

/* SEARCH */

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
    min-width: 950px;
    border-collapse: collapse;
}

th {
    text-align: left;
    padding: 16px 13px;
    color: var(--orange);
    font-size: 11px;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    white-space: nowrap;
}

td {
    padding: 16px 13px;
    color: #DDDDDD;
    font-size: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    vertical-align: middle;
}

tbody tr:hover {
    background: rgba(255,118,40,0.055);
}

.restaurant-name {
    color: white;
    font-weight: bold;
    min-width: 130px;
}

.restaurant-cuisine {
    color: var(--grey);
    margin-top: 5px;
    font-size: 11px;
}

.status {
    display: inline-block;
    padding: 7px 11px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: bold;
}

.status.active {
    color: #70DB90;
    background: rgba(46,204,113,0.12);
    border: 1px solid rgba(46,204,113,0.15);
}

.status.inactive {
    color: #FF7777;
    background: rgba(255,80,80,0.12);
    border: 1px solid rgba(255,80,80,0.15);
}

/* TABLE ACTIONS */

.actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 10px;
    border-radius: 7px;
    font-size: 11px;
    font-weight: bold;
    text-decoration: none;
    border: none;
    cursor: pointer;
}

.edit-btn {
    color: var(--orange);
    background: rgba(255,118,40,0.12);
}

.edit-btn:hover {
    background: rgba(255,118,40,0.22);
}

.toggle-btn {
    color: #70DB90;
    background: rgba(46,204,113,0.12);
}

.toggle-btn.deactivate {
    color: #FF7777;
    background: rgba(255,80,80,0.12);
}

.toggle-btn:hover {
    filter: brightness(1.3);
}

.actions form {
    display: inline;
}

.empty-message {
    text-align: center;
    padding: 35px !important;
    color: var(--grey);
}

.no-results {
    display: none;
    text-align: center;
    padding: 25px;
    color: var(--grey);
    font-size: 13px;
}

/* MOBILE SIDEBAR */

.overlay {
    display: none;
}

/* RESPONSIVE */

@media (max-width: 1050px) {

    .main {
        padding: 25px;
    }

    .stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
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

    .panel {
        padding: 17px;
    }

    .form-grid {
        grid-template-columns: 1fr;
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

    <a href="restaurants.php" class="active">
        <i class="fas fa-utensils"></i>
        Restaurants
    </a>

    <a href="bookings.php">
        <i class="fas fa-calendar-check"></i>
        Bookings
    </a>

    <a href="customers.php">
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

                <p>Manage your DineNow restaurants.</p>

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
            <i class="fas fa-utensils"></i>
            Restaurants Management
        </h1>

        <p>
            Add, update and manage the restaurants available on DineNow.
        </p>

    </div>

    <!-- NOTIFICATIONS -->

    <?php if ($message): ?>

        <div class="alert success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <?php if ($error): ?>

        <div class="alert error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>

    <!-- SUMMARY CARDS -->

    <div class="stats">

        <div class="stat-card">

            <div class="stat-top">

                <p>Total Restaurants</p>

                <div class="stat-icon">
                    <i class="fas fa-utensils"></i>
                </div>

            </div>

            <h2><?php echo $totalRestaurants; ?></h2>

            <small>Restaurants listed on DineNow</small>

        </div>

        <div class="stat-card">

            <div class="stat-top">

                <p>Active Restaurants</p>

                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>

            </div>

            <h2><?php echo $activeRestaurants; ?></h2>

            <small>Currently available to customers</small>

        </div>

        <div class="stat-card">

            <div class="stat-top">

                <p>Inactive Restaurants</p>

                <div class="stat-icon">
                    <i class="fas fa-ban"></i>
                </div>

            </div>

            <h2><?php echo $inactiveRestaurants; ?></h2>

            <small>Currently unavailable to customers</small>

        </div>

    </div>

    <!-- ADD / EDIT RESTAURANT FORM -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    <i class="fas <?php echo $editing ? 'fa-pen-to-square' : 'fa-plus-circle'; ?>"></i>

                    <?php echo $editing ? 'Edit Restaurant' : 'Add New Restaurant'; ?>
                </h2>

                <p>
                    <?php echo $editing
                        ? 'Update the information for this restaurant.'
                        : 'Enter the details of a new restaurant.'; ?>
                </p>

            </div>

        </div>

        <form method="POST" action="restaurants.php<?php echo $editing ? '?edit=' . (int)$editing['id'] : ''; ?>">

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['restaurant_admin_token']); ?>"
            >

            <input type="hidden" name="action" value="save">

            <input
                type="hidden"
                name="id"
                value="<?php echo (int)($editing['id'] ?? 0); ?>"
            >

            <div class="form-grid">

                <div class="form-group">

                    <label>Restaurant Name *</label>

                    <input
                        type="text"
                        name="name"
                        placeholder="Enter restaurant name"
                        required
                        value="<?php echo htmlspecialchars($editing['name'] ?? ''); ?>"
                    >

                </div>

                <div class="form-group">

                    <label>Cuisine</label>

                    <input
                        type="text"
                        name="cuisine"
                        placeholder="e.g. African, Italian, Chinese"
                        value="<?php echo htmlspecialchars($editing['cuisine'] ?? ''); ?>"
                    >

                </div>

                <div class="form-group">

                    <label>City *</label>

                    <input
                        type="text"
                        name="city"
                        placeholder="Enter city"
                        required
                        value="<?php echo htmlspecialchars($editing['city'] ?? ''); ?>"
                    >

                </div>

                <div class="form-group">

                    <label>Address</label>

                    <input
                        type="text"
                        name="address"
                        placeholder="Restaurant address"
                        value="<?php echo htmlspecialchars($editing['address'] ?? ''); ?>"
                    >

                </div>

                <div class="form-group">

                    <label>Phone Number</label>

                    <input
                        type="text"
                        name="phone"
                        placeholder="Restaurant phone number"
                        value="<?php echo htmlspecialchars($editing['phone'] ?? ''); ?>"
                    >

                </div>

                <div class="form-group">

                    <label>Email Address</label>

                    <input
                        type="email"
                        name="email"
                        placeholder="Restaurant email"
                        value="<?php echo htmlspecialchars($editing['email'] ?? ''); ?>"
                    >

                </div>

                <div class="form-group">

                    <label>Restaurant Image Path</label>

                    <input
                        type="text"
                        name="image"
                        placeholder="images/restaurants/example.jpg"
                        value="<?php echo htmlspecialchars($editing['image'] ?? ''); ?>"
                    >

                </div>

                <div class="form-group">

                    <label>Opening Time</label>

                    <input
                        type="time"
                        name="opening_time"
                        value="<?php echo htmlspecialchars(
                            isset($editing['opening_time'])
                            ? substr($editing['opening_time'], 0, 5)
                            : ''
                        ); ?>"
                    >

                </div>

                <div class="form-group">

                    <label>Closing Time</label>

                    <input
                        type="time"
                        name="closing_time"
                        value="<?php echo htmlspecialchars(
                            isset($editing['closing_time'])
                            ? substr($editing['closing_time'], 0, 5)
                            : ''
                        ); ?>"
                    >

                </div>

            </div>

            <div class="form-group description-group">

                <label>Restaurant Description</label>

                <textarea
                    name="description"
                    placeholder="Write a short description about the restaurant..."
                ><?php echo htmlspecialchars($editing['description'] ?? ''); ?></textarea>

            </div>

            <div class="form-actions">

                <button type="submit" class="btn btn-primary">

                    <i class="fas <?php echo $editing ? 'fa-save' : 'fa-plus'; ?>"></i>

                    <?php echo $editing ? 'Update Restaurant' : 'Add Restaurant'; ?>

                </button>

                <?php if ($editing): ?>

                    <a href="restaurants.php" class="cancel-link">
                        Cancel Editing
                    </a>

                <?php endif; ?>

            </div>

        </form>

    </section>

    <!-- RESTAURANT DIRECTORY -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    <i class="fas fa-list"></i>
                    Restaurant Directory
                </h2>

                <p>All restaurants registered on the platform.</p>

            </div>

            <div class="search-box">

                <i class="fas fa-search"></i>

                <input
                    type="text"
                    id="restaurantSearch"
                    placeholder="Search restaurants..."
                >

            </div>

        </div>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Restaurant</th>

                        <th>Location</th>

                        <th>Contact</th>

                        <th>Opening Hours</th>

                        <th>Status</th>

                        <th>Actions</th>

                    </tr>

                </thead>

                <tbody id="restaurantTable">

                    <?php if (!empty($restaurants)): ?>

                        <?php foreach ($restaurants as $restaurant): ?>

                            <?php
                                $isActive = ($restaurant['status'] ?? '') === 'active';
                            ?>

                            <tr class="restaurant-row">

                                <td>
                                    #<?php echo (int)$restaurant['id']; ?>
                                </td>

                                <td>

                                    <div class="restaurant-name">

                                        <?php echo htmlspecialchars($restaurant['name']); ?>

                                    </div>

                                    <div class="restaurant-cuisine">

                                        <?php echo htmlspecialchars($restaurant['cuisine'] ?? 'Other'); ?>

                                    </div>

                                </td>

                                <td>

                                    <?php echo htmlspecialchars($restaurant['city']); ?>

                                    <?php if (!empty($restaurant['address'])): ?>

                                        <div class="restaurant-cuisine">
                                            <?php echo htmlspecialchars($restaurant['address']); ?>
                                        </div>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php echo htmlspecialchars($restaurant['phone'] ?? 'Not provided'); ?>

                                    <div class="restaurant-cuisine">
                                        <?php echo htmlspecialchars($restaurant['email'] ?? ''); ?>
                                    </div>

                                </td>

                                <td>

                                    <?php

                                    $opening = !empty($restaurant['opening_time'])
                                        ? date('g:i A', strtotime($restaurant['opening_time']))
                                        : 'Not set';

                                    $closing = !empty($restaurant['closing_time'])
                                        ? date('g:i A', strtotime($restaurant['closing_time']))
                                        : 'Not set';

                                    echo htmlspecialchars($opening . ' - ' . $closing);

                                    ?>

                                </td>

                                <td>

                                    <span class="status <?php echo $isActive ? 'active' : 'inactive'; ?>">

                                        <?php echo $isActive ? 'ACTIVE' : 'INACTIVE'; ?>

                                    </span>

                                </td>

                                <td>

                                    <div class="actions">

                                        <a
                                            href="restaurants.php?edit=<?php echo (int)$restaurant['id']; ?>"
                                            class="action-btn edit-btn"
                                        >

                                            <i class="fas fa-edit"></i>
                                            Edit

                                        </a>

                                        <form method="POST" action="restaurants.php">

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?php echo htmlspecialchars($_SESSION['restaurant_admin_token']); ?>"
                                            >

                                            <input type="hidden" name="action" value="toggle">

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?php echo (int)$restaurant['id']; ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="action-btn toggle-btn <?php echo $isActive ? 'deactivate' : ''; ?>"
                                            >

                                                <i class="fas <?php echo $isActive ? 'fa-ban' : 'fa-check'; ?>"></i>

                                                <?php echo $isActive ? 'Deactivate' : 'Activate'; ?>

                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="7" class="empty-message">

                                <i class="fas fa-utensils" style="font-size:25px;display:block;margin-bottom:12px;"></i>

                                No restaurants have been added yet.

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

            <div class="no-results" id="noResults">
                No matching restaurants found.
            </div>

        </div>

    </section>

</main>

<script>

/* =========================================
   MOBILE SIDEBAR
========================================= */

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

/* =========================================
   RESTAURANT SEARCH
========================================= */

const searchInput = document.getElementById("restaurantSearch");
const restaurantRows = document.querySelectorAll(".restaurant-row");
const noResults = document.getElementById("noResults");

searchInput.addEventListener("input", function () {

    const searchValue = this.value.toLowerCase().trim();

    let visibleCount = 0;

    restaurantRows.forEach(function (row) {

        const rowText = row.textContent.toLowerCase();

        if (rowText.includes(searchValue)) {

            row.style.display = "";
            visibleCount++;

        } else {

            row.style.display = "none";

        }

    });

    noResults.style.display =
        visibleCount === 0 && restaurantRows.length > 0
            ? "block"
            : "none";

});

/* =========================================
   CONFIRM RESTAURANT STATUS CHANGE
========================================= */

document.querySelectorAll(".toggle-btn").forEach(function (button) {

    button.addEventListener("click", function (event) {

        const isDeactivating = button.classList.contains("deactivate");

        const message = isDeactivating
            ? "Are you sure you want to deactivate this restaurant?"
            : "Are you sure you want to activate this restaurant?";

        if (!confirm(message)) {
            event.preventDefault();
        }

    });

});

</script>

</body>
</html>