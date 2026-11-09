<?php

if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/session.php';
}

$isLoggedIn = isset($_SESSION['user_id']);
$currentPage = basename($_SERVER['PHP_SELF']);

?>

<header class="site-header">

    <div class="header-container">

        <a href="index.php" class="site-logo">
            BAYO<span>GOAL</span>
            <small>ENTERPRISES</small>
        </a>

        <button
            class="menu-toggle"
            id="menuToggle"
            type="button"
            aria-label="Open navigation"
            aria-expanded="false"
        >
            ☰
        </button>

    </div>

</header>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Mobile Sidebar -->
<aside class="mobile-sidebar" id="mobileSidebar">

    <div class="sidebar-header">

        <a href="index.php" class="sidebar-logo">
            BAYO<span>GOAL</span>
            <small>ENTERPRISES</small>
        </a>

        <button
            class="sidebar-close"
            id="sidebarClose"
            aria-label="Close navigation"
            type="button"
        >
            &times;
        </button>

    </div>

    <nav class="sidebar-links">

        <a href="index.php"
           class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
            🏠 Home
        </a>

        <a href="products.php"
           class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">
            🛍️ Shop
        </a>

        <a href="index.php#categories">
            📦 Categories
        </a>

        <a href="index.php#about">
            ℹ️ About Us
        </a>

        <a href="index.php#contact">
            📞 Contact
        </a>

        <div class="sidebar-divider"></div>

        <?php if ($isLoggedIn): ?>

            <a href="cart.php">🛒 My Cart</a>

            <a href="orders.php">📋 My Orders</a>

            <a href="logout.php" class="sidebar-button">
                Logout
            </a>

        <?php else: ?>

            <a href="login.php">🔐 Login</a>

            <a href="register.php" class="sidebar-button">
                Create Account
            </a>

        <?php endif; ?>

    </nav>

    <div class="sidebar-footer">
        <p>Gear Up. Play Hard. Win Big.</p>
        <small>&copy; <?= date('Y') ?> Bayogoal Enterprises</small>
    </div>

</aside>


<style>

/* HEADER */

.header-container {
    max-width: 1250px;
    margin: auto;
    padding: 16px 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.site-logo {
    text-align: center;
}

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
}

@media (max-width: 850px) {
    .menu-toggle {
        display: block;
    }
}
/* MENU BUTTON */

.menu-toggle {
    display: none;
    background: transparent;
    color: #39FF14;
    border: none;
    font-size: 30px;
    cursor: pointer;
}

/* DESKTOP NAVIGATION IS HIDDEN ON MOBILE */

@media (min-width: 851px) {

    .header-container {
        justify-content: space-between;
    }

    .menu-toggle {
        display: none;
    }

}

/* SIDEBAR */

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

/* SIDEBAR HEADER */

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

.sidebar-logo small {
    display: block;
    font-size: 9px;
    letter-spacing: 4px;
    color: #aab8ca;
}

.sidebar-close {
    background: transparent;
    border: none;
    color: white;
    font-size: 35px;
    cursor: pointer;
}

/* SIDEBAR LINKS */

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

.sidebar-links .sidebar-button:hover {
    background: #1d4ed8;
    color: white;
}

/* SIDEBAR FOOTER */

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

.sidebar-footer small {
    font-size: 11px;
}

/* DARK OVERLAY */

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

/* PREVENT BACKGROUND SCROLL */

body.sidebar-open {
    overflow: hidden;
}

/* MOBILE SETTINGS */

@media (max-width: 850px) {

    .header-container {
        padding: 15px 18px;
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