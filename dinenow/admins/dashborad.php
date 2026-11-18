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

    JOIN users u
        ON b.user_id = u.id

    JOIN restaurants r
        ON b.restaurant_id = r.id

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

        /* GENERAL RESET */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #101010;
            color: #FFFFFF;
        }

        /* SIDEBAR */

        .sidebar {
            width: 240px;
            height: 100vh;

            position: fixed;
            top: 0;
            left: 0;

            background: #191919;

            padding: 25px 18px;

            border-right: 1px solid #333333;
        }

        .logo {
            font-size: 27px;
            font-weight: bold;

            margin-bottom: 40px;
        }

        .logo span {
            color: #FF7628;
        }

        .sidebar a {
            display: block;

            color: #CCCCCC;
            text-decoration: none;

            padding: 14px;

            margin-bottom: 8px;

            border-radius: 6px;

            transition: 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #FF7628;
            color: #FFFFFF;
        }

        /* MAIN CONTENT */

        .main {
            margin-left: 240px;
            padding: 35px;
        }

        /* TOP BAR */

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 15px;

            margin-bottom: 35px;
        }

        .topbar h1 {
            font-size: 28px;
        }

        .topbar p {
            color: #AAAAAA;
            margin-top: 7px;
        }

        .logout {
            background: #FF7628;
            color: #FFFFFF;

            text-decoration: none;

            padding: 11px 18px;

            border-radius: 6px;
        }

        /* STATISTICS */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);

            gap: 20px;

            margin-bottom: 35px;
        }

        .card {
            background: #191919;

            border: 1px solid #333333;
            border-radius: 10px;

            padding: 25px;

            transition: 0.3s;
        }

        .card:hover {
            border-color: #FF7628;
            transform: translateY(-3px);
        }

        .card p {
            color: #AAAAAA;
            margin-bottom: 15px;
        }

        .card h2 {
            font-size: 30px;
            color: #FF7628;
        }

        /* RECENT BOOKINGS */

        .section {
            background: #191919;

            padding: 25px;

            border-radius: 10px;
            border: 1px solid #333333;

            overflow-x: auto;
        }

        .section h2 {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;

            min-width: 650px;
        }

        th,
        td {
            text-align: left;

            padding: 14px;

            border-bottom: 1px solid #333333;

            font-size: 14px;
        }

        th {
            color: #FF7628;
        }

        td {
            color: #DDDDDD;
        }

        .status {
            color: #FF7628;
            text-transform: capitalize;
        }

        /* TABLE EMPTY MESSAGE */

        .empty-message {
            color: #AAAAAA;
            text-align: center;
        }

        /* TABLET RESPONSIVENESS */

        @media (max-width: 1000px) {

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        /* MOBILE RESPONSIVENESS */

        @media (max-width: 700px) {

            .sidebar {
                position: relative;

                width: 100%;
                height: auto;

                padding: 20px;
            }

            .logo {
                margin-bottom: 15px;
            }

            .sidebar a {
                display: inline-block;

                margin: 3px;
                padding: 10px;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }

            .topbar {
                align-items: flex-start;
            }

            .stats {
                gap: 10px;
            }

            .card {
                padding: 17px;
            }

            .card h2 {
                font-size: 24px;
            }

        }

        @media (max-width: 400px) {

            .stats {
                grid-template-columns: 1fr;
            }

            .topbar h1 {
                font-size: 23px;
            }

        }

    </style>

</head>

<body>

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="logo">
            Dine<span>Now.</span>
        </div>

        <a href="dashboard.php" class="active">Dashboard</a>

        <a href="restaurants.php">Restaurants</a>

        <a href="bookings.php">Bookings</a>

        <a href="customers.php">Customers</a>

        <a href="../index.php">View Website</a>

        <a href="../logout.php">Logout</a>

    </aside>

    <!-- MAIN CONTENT -->

    <main class="main">

        <div class="topbar">

            <div>

                <h1>Admin Dashboard</h1>

                <p>
                    Welcome,
                    <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                </p>

            </div>

            <a href="../logout.php" class="logout">
                Logout
            </a>

        </div>

        <!-- STATISTICS -->

        <div class="stats">

            <div class="card">

                <p>Total Restaurants</p>

                <h2><?php echo $totalRestaurants; ?></h2>

            </div>

            <div class="card">

                <p>Total Customers</p>

                <h2><?php echo $totalCustomers; ?></h2>

            </div>

            <div class="card">

                <p>Total Bookings</p>

                <h2><?php echo $totalBookings; ?></h2>

            </div>

            <div class="card">

                <p>Pending Bookings</p>

                <h2><?php echo $pendingBookings; ?></h2>

            </div>

        </div>

        <!-- RECENT BOOKINGS -->

        <div class="section">

            <h2>Recent Bookings</h2>

            <table>

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

                            <td>#<?php echo (int)$booking['id']; ?></td>

                            <td>
                                <?php echo htmlspecialchars($booking['full_name']); ?>
                            </td>

                            <td>
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

                            <td class="status">
                                <?php echo htmlspecialchars($booking['status']); ?>
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

    </main>

</body>
</html>