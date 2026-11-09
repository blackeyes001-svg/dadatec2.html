
<?php
require_once __DIR__ . '/includes/session.php';

$isLoggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="Shop quality football boots, jerseys, sports equipment, fitness gear and accessories at Bayogoal Enterprises.">

    <title>Bayogoal Enterprises | Gear Up. Play Hard. Win Big.</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main>

        <!-- HERO SECTION -->
        <section class="hero">

            <div class="hero-content">

                <p class="hero-label">
                    YOUR GAME. YOUR GLORY.
                </p>

                <h1>
                    GEAR UP.<br>
                    PLAY HARD.<br>
                    <span>WIN BIG.</span>
                </h1>

                <p class="hero-description">
                    Discover quality sports equipment, footwear,
                    jerseys and accessories designed to keep you
                    ready for every challenge.
                </p>

                <div class="hero-buttons">

                    <a href="products.php" class="btn btn-primary">
                        SHOP NOW <span>→</span>
                    </a>

                    <a href="#categories" class="btn btn-secondary">
                        EXPLORE SPORTS
                    </a>

                </div>

                <div class="hero-features">

                    <div>
                        <strong>01</strong>
                        <span>Quality Gear</span>
                    </div>

                    <div>
                        <strong>02</strong>
                        <span>Multiple Sports</span>
                    </div>

                    <div>
                        <strong>03</strong>
                        <span>Built to Perform</span>
                    </div>

                </div>

            </div>

            <div class="hero-visual">

                <div class="hero-badge">
                    <span>BG</span>
                    <small>SPORTS COLLECTION</small>
                </div>

                <div class="hero-ball">⚽</div>

                <div class="hero-bottom">
                    <span>READY FOR THE GAME?</span>
                    <span class="green-dot"></span>
                </div>

            </div>

        </section>


        <!-- CATEGORIES SECTION -->
        <section class="categories section" id="categories">

            <div class="section-heading">

                <div>
                    <p class="section-label">FIND YOUR GAME</p>
                    <h2>SHOP BY <span>SPORT</span></h2>
                </div>

                <a href="products.php" class="view-all">
                    VIEW ALL PRODUCTS →
                </a>

            </div>

            <div class="category-grid">

                <a href="products.php?category=football"
                   class="category-card">

                    <div class="category-number">01</div>
                    <div class="category-icon">⚽</div>

                    <h3>Football</h3>

                    <p>
                        Boots, jerseys, balls, gloves and shin guards.
                    </p>

                    <span class="category-link">EXPLORE →</span>

                </a>


                <a href="products.php?category=basketball"
                   class="category-card">

                    <div class="category-number">02</div>
                    <div class="category-icon">🏀</div>

                    <h3>Basketball</h3>

                    <p>
                        Basketballs, shoes, jerseys and accessories.
                    </p>

                    <span class="category-link">EXPLORE →</span>

                </a>


                <a href="products.php?category=fitness"
                   class="category-card">

                    <div class="category-number">03</div>
                    <div class="category-icon">💪</div>

                    <h3>Fitness & Training</h3>

                    <p>
                        Workout equipment and training essentials.
                    </p>

                    <span class="category-link">EXPLORE →</span>

                </a>


                <a href="products.php?category=protection"
                   class="category-card">

                    <div class="category-number">04</div>
                    <div class="category-icon">🛡️</div>

                    <h3>Protective Gear</h3>

                    <p>
                        Helmets, pads, mouthguards and body protection.
                    </p>

                    <span class="category-link">EXPLORE →</span>

                </a>


                <a href="products.php?category=racket-sports"
                   class="category-card">

                    <div class="category-number">05</div>
                    <div class="category-icon">🎾</div>

                    <h3>Racket Sports</h3>

                    <p>
                        Tennis, badminton and volleyball equipment.
                    </p>

                    <span class="category-link">EXPLORE →</span>

                </a>


                <a href="products.php?category=sportswear"
                   class="category-card">

                    <div class="category-number">06</div>
                    <div class="category-icon">👕</div>

                    <h3>Sportswear</h3>

                    <p>
                        Tracksuits, socks, bags and sports clothing.
                    </p>

                    <span class="category-link">EXPLORE →</span>

                </a>

            </div>

        </section>


        <!-- ABOUT SECTION -->
        <section class="about-section section" id="about">

            <div class="about-mark">
                <span>BG</span>
                <small>BAYOGOAL</small>
            </div>

            <div class="about-content">

                <p class="section-label">ABOUT OUR BUSINESS</p>

                <h2>
                    MORE THAN GEAR.<br>
                    <span>IT'S YOUR GAME.</span>
                </h2>

                <p>
                    Bayogoal Enterprises is being built as a
                    destination for sports enthusiasts seeking
                    equipment, apparel and accessories for their
                    favourite activities.
                </p>

                <p>
                    Whether you're preparing for match day, training
                    for your next challenge or simply enjoying sports,
                    our goal is to make finding your gear easier.
                </p>

                <a href="products.php" class="btn btn-primary">
                    EXPLORE OUR SHOP →
                </a>

            </div>

        </section>


        <!-- CALL TO ACTION -->
        <section class="cta-section">

            <p class="section-label">THE NEXT LEVEL STARTS HERE</p>

            <h2>YOUR GAME. YOUR RULES.</h2>

            <p>
                Find the equipment that helps you perform at your best.
            </p>

            <a href="products.php" class="btn btn-primary">
                START SHOPPING →
            </a>

        </section>

    </main>


    <!-- SHARED FOOTER -->
    <?php include 'includes/footer.php'; ?>


    <!-- JAVASCRIPT -->
    <script src="js/script.js"></script>

</body>
</html>
