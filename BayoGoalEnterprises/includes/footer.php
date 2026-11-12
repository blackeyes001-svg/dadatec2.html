
<footer class="site-footer" id="contact">

    <div class="footer-container">

        <div class="footer-brand">
            <h2>BAYO<span>GOAL</span></h2>
            <p>ENTERPRISES</p>

            <p class="footer-description">
                Your trusted destination for quality sports equipment,
                football kits, sportswear and fitness accessories.
            </p>
        </div>

        <div class="footer-links">
            <h3>Quick Links</h3>

            <a href="index.php">Home</a>
            <a href="products.php">Shop</a>
            <a href="index.php#about">About Us</a>
            <a href="index.php#contact">Contact Us</a>
        </div>

        <div class="footer-links">
            <h3>Customer Account</h3>

            <?php if (isset($_SESSION['user_id'])): ?>

                <a href="cart.php">Shopping Cart</a>
                <a href="orders.php">My Orders</a>
                <a href="logout.php">Logout</a>

            <?php else: ?>

                <a href="login.php">Login</a>
                <a href="register.php">Create Account</a>
                <a href="cart.php">Shopping Cart</a>

            <?php endif; ?>
        </div>

        <div class="footer-contact">
            <h3>Contact Us</h3>

            <p>
                Opp. Daughter of Charity Hospital,<br>
                F.O. 1, Paulad Sport Center,<br>
                Kubwa, Abuja, Nigeria.
            </p>

            <p>
                <a href="tel:+2348035798303">08035798303</a>
            </p>

            <p>
                <a href="tel:+2349060993935">09060993935</a>
            </p>

            <p>
                <a href="mailto:dadabayo11@gmail.com">
                    dadabayo11@gmail.com
                </a>
            </p>
        </div>
   <a href="https://www.google.com/maps/search/?api=1&query=YOUR+BUSINESS+ADDRESS"
   target="_blank"
   rel="noopener noreferrer"
   class="location-link">

    📍 Your Business Address

</a>
    </div>

    <div class="footer-bottom">
        <p>
            &copy; <?= date('Y') ?> Bayogoal Enterprises.
            All Rights Reserved.
        </p>

        <p>Gear Up. Play Hard. Win Big.</p>
    </div>

</footer>

<style>
.site-footer {
    background: #071426;
    color: #ffffff;
    border-top: 1px solid #203b5c;
    margin-top: 50px;
}

.footer-container {
    max-width: 1250px;
    margin: auto;
    padding: 50px 25px;
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1.5fr;
    gap: 35px;
}

.footer-brand h2 {
    font-size: 27px;
    font-weight: 800;
}

.footer-brand h2 span {
    color: #39FF14;
}

.footer-brand > p:first-of-type {
    color: #39FF14;
    font-size: 10px;
    letter-spacing: 4px;
}

.footer-description {
    color: #aab8ca;
    line-height: 1.8;
    margin-top: 18px;
    font-size: 14px;
}

.footer-container h3 {
    color: #39FF14;
    margin-bottom: 18px;
    font-size: 17px;
}

.footer-links a,
.footer-contact a {
    display: block;
    color: #cbd5e1;
    text-decoration: none;
    margin-bottom: 13px;
    font-size: 14px;
    transition: 0.3s;
    overflow-wrap: anywhere;
}

.footer-links a:hover,
.footer-contact a:hover {
    color: #39FF14;
}

.footer-contact p {
    color: #cbd5e1;
    line-height: 1.7;
    font-size: 14px;
    margin-bottom: 12px;
}

.footer-contact p a {
    display: inline;
}

.footer-bottom {
    border-top: 1px solid #203b5c;
    padding: 20px 15px;
    text-align: center;
    color: #aab8ca;
    font-size: 13px;
    line-height: 1.8;
}

@media (max-width: 900px) {
    .footer-container {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 550px) {
    .footer-container {
        grid-template-columns: 1fr;
        padding: 35px 20px;
        gap: 30px;
    }
}
.location-link {
    color: #ffffff;
    text-decoration: none;
    transition: 0.3s;
}

.location-link:hover {
    color: #39FF14;
    text-decoration: underline;
}
</style>
