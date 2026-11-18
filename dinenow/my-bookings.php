<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/includes/db.php";

// Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Get customer's bookings
$stmt = $pdo->prepare("
    SELECT
        b.id,
        b.booking_date,
        b.booking_time,
        b.guests,
        b.special_request,
        b.status,
        b.created_at,
        r.name AS restaurant_name,
        r.address,
        bt.table_number
    FROM bookings b
    INNER JOIN restaurants r ON b.restaurant_id = r.id
    LEFT JOIN booking_tables bt ON b.table_id = bt.id
    WHERE b.user_id = :user_id
    ORDER BY b.created_at DESC
");

$stmt->execute(['user_id' => $userId]);
$bookings = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Bookings | DineNow</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #101010;
            color: #fff;
            padding: 40px 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        .brand {
            text-align: center;
            font-size: 30px;
            font-weight: bold;
            margin-bottom: 35px;
        }

        .brand span {
            color: #ff7628;
        }

        .page-heading {
            margin-bottom: 30px;
        }

        .page-heading h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .page-heading p {
            color: #aaa;
            line-height: 1.6;
        }

        .booking-card {
            background: #191919;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
        }

        .booking-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .booking-top h2 {
            font-size: 22px;
            color: #ff7628;
        }

        .reference {
            color: #aaa;
            font-size: 14px;
            margin-top: 7px;
        }

        .status {
            padding: 9px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .pending {
            background: #493716;
            color: #ffc66d;
        }

        .confirmed {
            background: #173c25;
            color: #8ef0a7;
        }

        .cancelled {
            background: #421b1b;
            color: #ffaaaa;
        }

        .completed {
            background: #19334b;
            color: #8bcaff;
        }

        .details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            border-top: 1px solid #333;
            padding-top: 20px;
        }

        .detail small {
            display: block;
            color: #999;
            margin-bottom: 7px;
        }

        .detail strong {
            font-size: 15px;
        }

        .special-request {
            margin-top: 20px;
            padding: 15px;
            background: #101010;
            border-radius: 8px;
            color: #bbb;
            line-height: 1.6;
        }

        .empty-state {
            background: #191919;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 50px 20px;
            text-align: center;
        }

        .empty-state h2 {
            margin-bottom: 12px;
        }

        .empty-state p {
            color: #aaa;
            margin-bottom: 25px;
        }

        .btn {
            display: inline-block;
            background: #ff7628;
            color: #101010;
            padding: 13px 22px;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
            transition: .3s;
        }

        .btn:hover {
            background: #fff;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #ff7628;
            text-decoration: none;
        }

        @media(max-width: 600px) {
            body {
                padding: 25px 13px;
            }

            .page-heading h1 {
                font-size: 26px;
            }

            .booking-card {
                padding: 20px 15px;
            }

            .details {
                grid-template-columns: 1fr;
                gap: 17px;
            }

            .booking-top h2 {
                font-size: 19px;
            }
        }
    </style>

</head>

<body>

<div class="container">

    <div class="brand">
        Dine<span>Now.</span>
    </div>

    <div class="page-heading">

        <h1>My Bookings</h1>

        <p>
            View and keep track of all your restaurant reservations.
        </p>

    </div>


    <?php if (count($bookings) > 0): ?>

        <?php foreach ($bookings as $booking): ?>

            <?php
                $status = strtolower($booking['status']);

                $statusClass = in_array(
                    $status,
                    ['pending', 'confirmed', 'cancelled', 'completed']
                ) ? $status : 'pending';
            ?>

            <div class="booking-card">

                <div class="booking-top">

                    <div>

                        <h2>
                            <?= htmlspecialchars($booking['restaurant_name']) ?>
                        </h2>

                        <div class="reference">
                            Booking Reference:
                            #<?= (int)$booking['id'] ?>
                        </div>

                    </div>

                    <span class="status <?= htmlspecialchars($statusClass) ?>">
                        <?= htmlspecialchars($booking['status']) ?>
                    </span>

                </div>


                <div class="details">

                    <div class="detail">
                        <small>Booking Date</small>

                        <strong>
                            <?= date('d M Y', strtotime($booking['booking_date'])) ?>
                        </strong>
                    </div>

                    <div class="detail">
                        <small>Booking Time</small>

                        <strong>
                            <?= date('h:i A', strtotime($booking['booking_time'])) ?>
                        </strong>
                    </div>

                    <div class="detail">
                        <small>Number of Guests</small>

                        <strong>
                            <?= (int)$booking['guests'] ?>
                            <?= (int)$booking['guests'] === 1 ? 'Guest' : 'Guests' ?>
                        </strong>
                    </div>

                    <div class="detail">
                        <small>Table Number</small>

                        <strong>
                            <?= $booking['table_number'] !== null
                                ? htmlspecialchars($booking['table_number'])
                                : 'Not assigned' ?>
                        </strong>
                    </div>

                    <div class="detail">
                        <small>Restaurant Address</small>

                        <strong>
                            <?= htmlspecialchars($booking['address'] ?? 'Not available') ?>
                        </strong>
                    </div>

                </div>


                <?php if (!empty($booking['special_request'])): ?>

                    <div class="special-request">

                        <strong>Special Request:</strong><br>

                        <?= nl2br(htmlspecialchars($booking['special_request'])) ?>

                    </div>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty-state">

            <h2>No Bookings Yet</h2>

            <p>
                You haven't made any restaurant reservations yet.
                Explore our restaurants and book your first table!
            </p>

            <a href="restaurants.php" class="btn">
                Explore Restaurants
            </a>

        </div>

    <?php endif; ?>


    <a href="index.php" class="back-link">
        ← Back to Home
    </a>

</div>

</body>
</html>