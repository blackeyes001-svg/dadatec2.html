
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

    LEFT JOIN bookings b
        ON b.user_id = u.id

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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>DineNow | Manage Customers</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #101010;
            color: #FFFFFF;
            padding: 30px;
        }

        .container {
            max-width: 1250px;
            margin: auto;
        }

        /* PAGE HEADER */

        .page-header {
            margin-bottom: 25px;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #AAAAAA;
            font-size: 14px;
        }

        /* NAVIGATION */

        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }

        .nav a {
            color: #FF7628;
            text-decoration: none;
            font-size: 14px;
        }

        .nav a:hover {
            text-decoration: underline;
        }

        /* CUSTOMER SUMMARY */

        .summary {
            background: #191919;
            border: 1px solid #333333;
            border-left: 4px solid #FF7628;
            border-radius: 10px;
            padding: 22px;
            margin-bottom: 25px;
        }

        .summary h3 {
            color: #AAAAAA;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .summary h2 {
            color: #FF7628;
            font-size: 30px;
        }

        /* CUSTOMER TABLE */

        .box {
            background: #191919;
            border: 1px solid #333333;
            border-radius: 10px;
            padding: 25px;
            overflow-x: auto;
        }

        .box h2 {
            font-size: 20px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #333333;
            font-size: 14px;
        }

        th {
            color: #FF7628;
            white-space: nowrap;
        }

        td {
            color: #DDDDDD;
        }

        tbody tr:hover {
            background: #222222;
        }

        /* BOOKING COUNT */

        .booking-count {
            display: inline-block;
            background: rgba(255, 118, 40, 0.15);
            color: #FF7628;
            padding: 6px 10px;
            border-radius: 20px;
            font-weight: bold;
        }

        /* EMPTY MESSAGE */

        .empty-message {
            color: #AAAAAA;
            text-align: center;
            padding: 25px;
        }

        /* MOBILE RESPONSIVENESS */

        @media (max-width: 600px) {

            body {
                padding: 15px;
            }

            h1 {
                font-size: 24px;
            }

            .box {
                padding: 15px;
            }

            .summary {
                padding: 18px;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <!-- PAGE HEADER -->

    <div class="page-header">

        <h1>Manage Customers</h1>

        <p class="subtitle">
            View and manage customers registered on DineNow.
        </p>

    </div>

    <!-- NAVIGATION -->

    <div class="nav">

        <a href="dashboard.php">Dashboard</a>

        <a href="restaurants.php">Restaurants</a>

        <a href="bookings.php">Bookings</a>

        <a href="customers.php">Customers</a>

        <a href="../index.php">View Website</a>

    </div>

    <!-- CUSTOMER SUMMARY -->

    <div class="summary">

        <h3>Total Registered Customers</h3>

        <h2><?php echo $totalCustomers; ?></h2>

    </div>

    <!-- CUSTOMER LIST -->

    <div class="box">

        <h2>Customer List</h2>

        <table>

            <thead>

                <tr>

                    <th>Customer ID</th>

                    <th>Full Name</th>

                    <th>Email Address</th>

                    <th>Phone Number</th>

                    <th>Total Bookings</th>

                    <th>Registered</th>

                </tr>

            </thead>

            <tbody>

                <?php if (!empty($customers)): ?>

                    <?php foreach ($customers as $customer): ?>

                        <tr>

                            <td>
                                #<?php echo (int)$customer['id']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($customer['full_name']); ?>
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
                                <?php echo htmlspecialchars($customer['created_at']); ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="6" class="empty-message">
                            No customers registered yet.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>
