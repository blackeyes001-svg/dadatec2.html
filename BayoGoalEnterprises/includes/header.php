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
            type="button"
            aria-label="Toggle navigation"
            aria-expanded="false"
        >
            ☰
        </button>

        <nav class="nav-links">

            <a href="index.php"
               class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
                Home
            </a>

            <a href="products.php"
               class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">
                Shop
            </a>

            <a href="index.php#categories">Categories</a>

            <a href="index.php#about">About</a>

            <a href="index.php#contact">Contact</a>

            <?php if ($isLoggedIn): ?>

                <a href="cart.php">Cart</a>

                <a href="orders.php">My Orders</a>

                <a href="logout.php" class="nav-button">
                    Logout
                </a>

            <?php else: ?>

                <a href="login.php">Login</a>

                <a href="register.php" class="nav-button">
                    Sign Up
                </a>

            <?php endif; ?>

        </nav>

    </div>

</header>

<style>
.site-header {
    background: #071426;
    border-bottom: 1px solid #203b5c;
    width: 100%;
    position: relative;
    z-index: 1000;
}

.header-container {
    max-width: 1250px;
    margin: auto;
    padding: 16px 25px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.site-logo {
    color: #ffffff;
    text-decoration: none;
    font-size: 25px;
    font-weight: 800;
    letter-spacing: 1px;
    white-space: nowrap;
}

.site-logo span {
    color: #39FF14;
}

.site-logo small {
    display: block;
    font-size: 9px;
    letter-spacing: 4px;
    color: #aab8ca;
    margin-top: 3px;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 22px;
}

.nav-links a {
    color: #ffffff;
    text-decoration: none;
    font-size: 14px;
    transition: 0.3s;
}

.nav-links a:hover,
.nav-links a.active {
    color: #39FF14;
}

.nav-links .nav-button {
    background: #2563EB;
    padding: 11px 17px;
    border-radius: 6px;
}

.nav-links .nav-button:hover {
    background: #1d4ed8;
    color: #ffffff;
}

.menu-toggle {
    display: none;
    background: transparent;
    color: #39FF14;
    border: none;
    font-size: 27px;
    cursor: pointer;
}

@media (max-width: 850px) {

    .header-container {
        flex-wrap: wrap;
        padding: 15px 18px;
    }

    .menu-toggle {
        display: block;
    }

    .nav-links {
        display: none;
        width: 100%;
        flex-direction: column;
        align-items: stretch;
        gap: 0;
    }

    .nav-links.show {
        display: flex;
    }

    .nav-links a {
        padding: 13px 10px;
        border-top: 1px solid #203b5c;
    }

    .nav-links .nav-button {
        margin-top: 8px;
        text-align: center;
    }
}
</style>