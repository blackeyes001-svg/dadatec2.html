<?php

if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/session.php';
}

$isLoggedIn = isset($_SESSION['user_id']);
$currentPage = basename($_SERVER['PHP_SELF']);

?>

<header class="site-header">

    <div class="header-container">

        <!-- CENTRED LOGO -->
        <a href="index.php" class="site-logo">
            BAYO<span>GOAL</span>
            <small>ENTERPRISES</small>
        </a>

        <!-- DESKTOP NAVIGATION -->
        <nav class="desktop-nav">

            <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a>

            <a href="products.php" class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">Shop</a>

            <a href="index.php#categories">Categories</a>

            <a href="index.php#about">About</a>

            <a href="index.php#contact">Contact</a>

            <?php if ($isLoggedIn): ?>

                <a href="cart.php">Cart</a>
                <a href="orders.php">My Orders</a>
                <a href="logout.php" class="nav-button">Logout</a>

            <?php else: ?>

                <a href="login.php">Login</a>
                <a href="register.php" class="nav-button">Sign Up</a>

            <?php endif; ?>

        </nav>

        <!-- MOBILE MENU BUTTON -->
        <button
            class="menu-toggle"
            id="menuToggle"
            type="button"
            aria-label="Open navigation"
            aria-expanded="false">
            ☰
        </button>

    </div>

</header>


<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>


<!-- MOBILE SIDEBAR -->
<aside class="mobile-sidebar" id="mobileSidebar">

    <div class="sidebar-header">

        <a href="index.php" class="sidebar-logo">
            BAYO<span>GOAL</span>
            <small>ENTERPRISES</small>
        </a>

        <button class="sidebar-close" id="sidebarClose" type="button">
            &times;
        </button>

    </div>

    <nav class="sidebar-links">

        <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">🏠 Home</a>

        <a href="products.php" class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">🛍️ Shop</a>

        <a href="index.php#categories">📦 Categories</a>

        <a href="index.php#about">ℹ️ About Us</a>

        <a href="index.php#contact">📞 Contact</a>

        <div class="sidebar-divider"></div>

        <?php if ($isLoggedIn): ?>

            <a href="cart.php">🛒 My Cart</a>
            <a href="orders.php">📋 My Orders</a>
            <a href="logout.php" class="sidebar-button">Logout</a>

        <?php else: ?>

            <a href="login.php">🔐 Login</a>
            <a href="register.php" class="sidebar-button">Create Account</a>

        <?php endif; ?>

    </nav>

    <div class="sidebar-footer">
        <p>Gear Up. Play Hard. Win Big.</p>
        <small>&copy; <?= date('Y') ?> Bayogoal Enterprises</small>
    </div>

</aside>


<style>

/* MAIN HEADER */

.site-header {
    background: #071426;
    border-bottom: 1px solid #203b5c;
    width: 100%;
    position: relative;
    z-index: 1000;
}

.header-container {
    max-width: 1400px;
    min-height: 105px;
    margin: auto;
    padding: 15px 25px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    position: relative;
}

/* CENTRED LOGO */

.site-logo {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    color: white;
    text-decoration: none;
    font-size: 30px;
    font-weight: 800;
    letter-spacing: 1px;
    white-space: nowrap;
    text-align: center;
    line-height: 1.1;
}

.site-logo span,
.sidebar-logo span {
    color: #39FF14;
}

.site-logo small,
.sidebar-logo small {
    display: block;
    font-size: 10px;
    letter-spacing: 5px;
    color: #aab8ca;
    margin-top: 7px;
}

/* DESKTOP NAVIGATION - TOP RIGHT */

.desktop-nav {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 13px;
    margin-left: auto;
    position: relative;
    z-index: 2;
}

.desktop-nav a {
    color: white;
    text-decoration: none;
    font-size: 13px;
    white-space: nowrap;
    transition: 0.3s;
}

.desktop-nav a:hover,
.desktop-nav a.active {
    color: #39FF14;
}

.desktop-nav .nav-button {
    background: #2563EB;
    padding: 10px 14px;
    border-radius: 6px;
}

.desktop-nav .nav-button:hover {
    background: #1d4ed8;
    color: white;
}

/* MOBILE MENU BUTTON */

.menu-toggle {
    display: none;
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    color: #39FF14;
    border: none;
    font-size: 30px;
    cursor: pointer;
    z-index: 5;
}

/* MOBILE SIDEBAR */

.mobile-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 290px;
    max-width: 85%;
    height: 100vh;
    height: 100dvh;
    background: #071426;
    z-index: 2002;
    transform: translateX(-105%);
    transition: transform 0.35s ease;
    display: flex;
    flex-direction: column;
    box-shadow: 5px 0 25px rgba(0,0,0,0.4);
}

.mobile-sidebar.open {
    transform: translateX(0);
}

.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 22px 20px;
    border-bottom: 1px solid #203b5c;
}

.sidebar-logo {
    color: white;
    text-decoration: none;
    font-size: 22px;
    font-weight: 800;
}

.sidebar-close {
    background: transparent;
    border: none;
    color: white;
    font-size: 35px;
    cursor: pointer;
}

.sidebar-links {
    display: flex;
    flex-direction: column;
    padding: 15px;
    overflow-y: auto;
}

.sidebar-links a {
    color: white;
    text-decoration: none;
    padding: 16px 13px;
    font-size: 15px;
    border-radius: 7px;
    transition: 0.3s;
}

.sidebar-links a:hover,
.sidebar-links a.active {
    background: #102b4c;
    color: #39FF14;
}

.sidebar-divider {
    height: 1px;
    background: #203b5c;
    margin: 15px 0;
}

.sidebar-links .sidebar-button {
    background: #2563EB;
    text-align: center;
    margin-top: 12px;
}

.sidebar-footer {
    margin-top: auto;
    padding: 20px;
    text-align: center;
    border-top: 1px solid #203b5c;
    color: #aab8ca;
}

.sidebar-footer p {
    color: #39FF14;
    font-size: 13px;
}

/* SIDEBAR OVERLAY */

.sidebar-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.65);
    z-index: 2001;
    opacity: 0;
    visibility: hidden;
    transition: 0.3s;
}

.sidebar-overlay.show {
    opacity: 1;
    visibility: visible;
}

body.sidebar-open {
    overflow: hidden;
}

/* TABLET AND MOBILE */

@media (max-width: 1050px) {

    .header-container {
        min-height: 90px;
        justify-content: center;
        padding: 15px 18px;
    }

    .site-logo {
        font-size: 27px;
    }

    .desktop-nav {
        display: none;
    }

    .menu-toggle {
        display: block;
    }

}

</style>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('mobileSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const closeButton = document.getElementById('sidebarClose');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('show');
        document.body.classList.add('sidebar-open');

        menuToggle.setAttribute('aria-expanded', 'true');
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        document.body.classList.remove('sidebar-open');

        menuToggle.setAttribute('aria-expanded', 'false');
    }

    menuToggle.addEventListener('click', openSidebar);

    closeButton.addEventListener('click', closeSidebar);

    overlay.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', closeSidebar);
    });

});

</script>