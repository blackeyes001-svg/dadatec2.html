\<?php

if (session_status() === PHP_SESSION_NONE) {
&#x20;   session_start();
}

require_once \_\_DIR\_\_ . "/../includes/db.php";

/\* ADMIN AUTHENTICATION \*/

if (
&#x20;   !isset($\_SESSION['user_id']) ||
&#x20;   ($\_SESSION['role'] ?? '') !== 'admin'
) {
&#x20;   header("Location: login.php");
&#x20;   exit;
}

/\* FETCH CUSTOMERS \*/

$customers = $pdo->query("
&#x20;   SELECT
&#x20;       u.id,
&#x20;       u.full_name,
&#x20;       u.email,
&#x20;       u.phone,
&#x20;       u.created_at,

&#x20;       COUNT(b.id) AS total_bookings

&#x20;   FROM users u

&#x20;   LEFT JOIN bookings b
&#x20;       ON b.user_id = u.id

&#x20;   WHERE u.role = 'customer'

&#x20;   GROUP BY
&#x20;       u.id,
&#x20;       u.full_name,
&#x20;       u.email,
&#x20;       u.phone,
&#x20;       u.created_at

&#x20;   ORDER BY u.created_at DESC
")->fetchAll();

?>

\<!DOCTYPE html>

\<html lang="en">

\<head>


<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>DineNow | Manage Customers</title>

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

        padding: 30px;
    }

    .container {
        max-width: 1200px;
        margin: auto;
    }

    /* PAGE HEADER */

    h1 {
        font-size: 28px;
        margin-bottom: 20px;
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
    }

    .nav a:hover {
        text-decoration: underline;
    }

    /* CUSTOMER TABLE CONTAINER */

    .box {
        background: #191919;

        border: 1px solid #333333;
        border-radius: 10px;

        padding: 25px;

        overflow-x: auto;
    }

    /* TABLE */

    table {
        width: 100%;

        border-collapse: collapse;

        min-width: 700px;
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
    }

    td {
        color: #DDDDDD;
    }

    /* EMPTY MESSAGE */

    .empty-message {
        color: #AAAAAA;
        text-align: center;
    }

    /* MOBILE RESPONSIVENESS */

    @media (max-width: 600px) {

        body {
            padding: 15px;
        }

        .box {
            padding: 15px;
        }

        h1 {
            font-size: 24px;
        }

    }

</style>


\</head>

\<body>


<div class="container">

    <h1>Manage Customers</h1>

    <!-- NAVIGATION -->

    <div class="nav">

        <a href="dashboard.php">Dashboard</a>

        <a href="restaurants.php">Restaurants</a>

        <a href="bookings.php">Bookings</a>

        <a href="customers.php">Customers</a>

        <a href="../index.php">View Website</a>

    </div>

    <!-- CUSTOMER LIST -->

    <div class="box">

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
                            <?php echo (int)$customer['total_bookings']; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($customer['created_at']); ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (!$customers): ?>

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


\</body>
\</html>
