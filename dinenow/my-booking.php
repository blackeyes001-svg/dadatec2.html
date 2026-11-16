<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/includes/db.php";

// Customers must log in before booking
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// CSRF protection
if (empty($_SESSION['booking_token'])) {
    $_SESSION['booking_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = false;
$bookingReference = null;
$selectedRestaurant = null;

// Get restaurant ID
$restaurantId = isset($_GET['restaurant_id'])
    ? (int) $_GET['restaurant_id']
    : (int) ($_POST['restaurant_id'] ?? 0);

if ($restaurantId <= 0) {
    die("Invalid restaurant selected.");
}

// Fetch restaurant
$stmt = $pdo->prepare("
    SELECT *
    FROM restaurants
    WHERE id = ?
    AND status = 'active'
");

$stmt->execute([$restaurantId]);

$selectedRestaurant = $stmt->fetch();

if (!$selectedRestaurant) {
    die("Restaurant not found or currently unavailable.");
}


// ==========================================
// PROCESS BOOKING
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify CSRF token
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals(
            $_SESSION['booking_token'],
            $_POST['csrf_token']
        )
    ) {
        $errors[] = "Invalid request. Please refresh the page and try again.";
    }

    $bookingDate = trim($_POST['booking_date'] ?? '');
    $bookingTime = trim($_POST['booking_time'] ?? '');
    $guests = (int) ($_POST['guests'] ?? 0);
    $specialRequest = trim($_POST['special_request'] ?? '');

    // Validate guests
    if ($guests < 1 || $guests > 20) {
        $errors[] = "Please select between 1 and 20 guests.";
    }

    // Validate date
    $dateObject = DateTime::createFromFormat('!Y-m-d', $bookingDate);

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d') !== $bookingDate
    ) {
        $errors[] = "Please select a valid booking date.";
    } elseif ($bookingDate < date('Y-m-d')) {
        $errors[] = "You cannot book a date in the past.";
    }

    // Validate time
    $timeObject = DateTime::createFromFormat('!H:i', $bookingTime);

    if (
        !$timeObject ||
        $timeObject->format('H:i') !== $bookingTime
    ) {
        $errors[] = "Please select a valid booking time.";
    }

    // Validate special request length
    if (strlen($specialRequest) > 1000) {
        $errors[] = "Your special request is too long.";
    }


    // ======================================
    // CHECK RESTAURANT OPENING HOURS
    // ======================================

    if (!$errors) {

        $openingTime = substr(
            $selectedRestaurant['opening_time'],
            0,
            5
        );

        $closingTime = substr(
            $selectedRestaurant['closing_time'],
            0,
            5
        );

        $bookingStart = strtotime($bookingTime);
        $opening = strtotime($openingTime);
        $closing = strtotime($closingTime);

        // Reservations last two hours
        $bookingEnd = $bookingStart + (2 * 60 * 60);

        if (
            $bookingStart < $opening ||
            $bookingEnd > $closing
        ) {
            $errors[] = "Please select a time within the restaurant's opening hours. Reservations last two hours.";
        }

        // Prevent same-day bookings in the past
        if (
            $bookingDate === date('Y-m-d') &&
            $bookingStart <= strtotime(date('H:i'))
        ) {
            $errors[] = "Please select a future booking time.";
        }
    }


    // ======================================
    // FIND AVAILABLE TABLE
    // ======================================

    if (!$errors) {

        try {

            $pdo->beginTransaction();

            /*
             * Find a table that:
             * 1. Belongs to the selected restaurant.
             * 2. Can accommodate the guests.
             * 3. Has no overlapping reservation.
             */

            $tableQuery = $pdo->prepare("

                SELECT bt.id

                FROM booking_tables bt

                WHERE bt.restaurant_id = :restaurant_id

                AND bt.capacity >= :guest_count

                AND NOT EXISTS (

                    SELECT 1

                    FROM bookings b

                    WHERE b.table_id = bt.id

                    AND b.booking_date = :booking_date

                    AND b.status <> 'cancelled'

                    AND b.booking_time < ADDTIME(
                        :requested_time_end,
                        '02:00:00'
                    )

                    AND ADDTIME(
                        b.booking_time,
                        '02:00:00'
                    ) > :requested_time_start

                )

                ORDER BY bt.capacity ASC

                LIMIT 1

                FOR UPDATE

            ");

            $tableQuery->execute([

                ':restaurant_id' => $restaurantId,

                ':guest_count' => $guests,

                ':booking_date' => $bookingDate,

                ':requested_time_end' => $bookingTime,

                ':requested_time_start' => $bookingTime

            ]);

            $availableTable = $tableQuery->fetch();


            if (!$availableTable) {

                $pdo->rollBack();

                $errors[] = "Sorry! No suitable table is available for your selected date, time and number of guests.";

            } else {

                // Save booking

                $insertBooking = $pdo->prepare("

                    INSERT INTO bookings (

                        user_id,
                        restaurant_id,
                        table_id,
                        booking_date,
                        booking_time,
                        guests,
                        special_request,
                        status

                    )

                    VALUES (

                        :user_id,
                        :restaurant_id,
                        :table_id,
                        :booking_date,
                        :booking_time,
                        :guests,
                        :special_request,
                        'pending'

                    )

                ");

                $insertBooking->execute([

                    ':user_id' => $_SESSION['user_id'],

                    ':restaurant_id' => $restaurantId,

                    ':table_id' => $availableTable['id'],

                    ':booking_date' => $bookingDate,

                    ':booking_time' => $bookingTime,

                    ':guests' => $guests,

                    ':special_request' => $specialRequest

                ]);

                $bookingReference = $pdo->lastInsertId();

                $pdo->commit();

                $success = true;

                // Generate a fresh token
                $_SESSION['booking_token'] = bin2hex(random_bytes(32));

            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $errors[] = "Something went wrong while processing your booking. Please try again.";

        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Book a Table | DineNow</title>

    <link rel="stylesheet" href="css/style.css">

    <link rel="stylesheet" href="css/responsive.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            background: #101010;
            color: #ffffff;
            font-family: Arial, sans-serif;
            margin: 0;
        }

        .booking-container {
            width: 90%;
            max-width: 1100px;
            margin: 60px auto;
        }

        .booking-heading {
            text-align: center;
            margin-bottom: 40px;
        }

        .booking-heading h1 {
            font-size: 38px;
            margin-bottom: 10px;
        }

        .booking-heading h1 span {
            color: #FF7628;
        }

        .booking-heading p {
            color: #AAAAAA;
        }

        .booking-card {
            display: grid;
            grid-template-columns: 0.9fr 1.1fr;
            background: #191919;
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid #333333;
        }

        .restaurant-info {
            padding: 40px;
            background: #202020;
        }

        .restaurant-info h2 {
            font-size: 27px;
            margin-bottom: 15px;
        }

        .restaurant-info h2 span {
            color: #FF7628;
        }

        .restaurant-info p {
            color: #AAAAAA;
            line-height: 1.8;
        }

        .restaurant-detail {
            margin-top: 25px;
            padding: 15px;
            background: #292929;
            border-radius: 8px;
        }

        .restaurant-detail strong {
            display: block;
            color: #FF7628;
            margin-bottom: 7px;
        }

        .booking-form {
            padding: 40px;
        }

        .booking-form h2 {
            margin-top: 0;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px;
            background: #101010;
            color: #ffffff;
            border: 1px solid #444444;
            border-radius: 7px;
            outline: none;
            font-size: 15px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #FF7628;
        }

        .form-group select option {
            background: #191919;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .booking-btn {
            width: 100%;
            padding: 16px;
            background: #FF7628;
            border: none;
            color: #ffffff;
            font-size: 16px;
            font-weight: bold;
            border-radius: 7px;
            cursor: pointer;
            transition: 0.3s;
        }

        .booking-btn:hover {
            background: #e65f12;
            transform: translateY(-2px);
        }

        .error-message {
            background: #441c1c;
            color: #ff9999;
            padding: 15px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .success-box {
            background: #183d25;
            border: 1px solid #32844d;
            color: #b8f5c8;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            line-height: 1.8;
        }

        .success-box h2 {
            color: #62df86;
        }

        .success-box strong {
            color: #ffffff;
        }

        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #FF7628;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media(max-width: 768px) {

            .booking-container {
                margin: 35px auto;
            }

            .booking-card {
                grid-template-columns: 1fr;
            }

            .restaurant-info,
            .booking-form {
                padding: 25px;
            }

            .booking-heading h1 {
                font-size: 29px;
            }

        }

        @media(max-width: 450px) {

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

        }

    </style>

</head>

<body>


<div class="booking-container">

    <div class="booking-heading">

        <h1>Reserve Your <span>Table</span></h1>

        <p>Book your favourite restaurant and enjoy a wonderful dining experience.</p>

    </div>


    <div class="booking-card">


        <!-- RESTAURANT INFORMATION -->

        <div class="restaurant-info">

            <h2>
                Welcome to <span>DineNow</span>
            </h2>

            <p>
                You're just a few steps away from enjoying a memorable
                dining experience. Complete your reservation details
                to secure your table.
            </p>


            <div class="restaurant-detail">

                <strong>Restaurant</strong>

                <?php echo htmlspecialchars($selectedRestaurant['name']); ?>

            </div>


            <div class="restaurant-detail">

                <strong>Location</strong>

                <?php echo htmlspecialchars($selectedRestaurant['address']); ?>

            </div>


            <div class="restaurant-detail">

                <strong>Opening Hours</strong>

                <?php

                echo date(
                    'h:i A',
                    strtotime($selectedRestaurant['opening_time'])
                );

                ?>

                -

                <?php

                echo date(
                    'h:i A',
                    strtotime($selectedRestaurant['closing_time'])
                );

                ?>

            </div>


            <div class="restaurant-detail">

                <strong>Reservation Duration</strong>

                2 Hours

            </div>

        </div>



        <!-- BOOKING FORM -->

        <div class="booking-form">


            <?php if ($success): ?>

                <div class="success-box">

                    <h2>Booking Successful!</h2>

                    <p>
                        Your reservation has been submitted successfully.
                    </p>

                    <p>
                        <strong>Booking Reference:</strong>
                        #<?php echo htmlspecialchars($bookingReference); ?>
                    </p>

                    <p>
                        <strong>Restaurant:</strong>
                        <?php echo htmlspecialchars($selectedRestaurant['name']); ?>
                    </p>

                    <p>
                        Your booking is currently pending confirmation.
                    </p>

                    <a href="my-bookings.php" class="booking-btn"
                       style="display:block;text-align:center;text-decoration:none;margin-top:20px;">

                        View My Bookings

                    </a>

                </div>


            <?php else: ?>


                <h2>Complete Your Reservation</h2>


                <?php if (!empty($errors)): ?>

                    <div class="error-message">

                        <?php foreach ($errors as $error): ?>

                            <p>
                                <?php echo htmlspecialchars($error); ?>
                            </p>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


                <form method="POST" action="booking.php?restaurant_id=<?php echo $restaurantId; ?>">


                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['booking_token']); ?>"
                    >


                    <input
                        type="hidden"
                        name="restaurant_id"
                        value="<?php echo $restaurantId; ?>"
                    >


                    <div class="form-group">

                        <label for="booking_date">
                            Reservation Date
                        </label>

                        <input
                            type="date"
                            id="booking_date"
                            name="booking_date"
                            min="<?php echo date('Y-m-d'); ?>"
                            value="<?php echo htmlspecialchars($_POST['booking_date'] ?? ''); ?>"
                            required
                        >

                    </div>



                    <div class="form-row">


                        <div class="form-group">

                            <label for="booking_time">
                                Reservation Time
                            </label>

                            <input
                                type="time"
                                id="booking_time"
                                name="booking_time"
                                value="<?php echo htmlspecialchars($_POST['booking_time'] ?? ''); ?>"
                                step="1800"
                                required
                            >

                        </div>



                        <div class="form-group">

                            <label for="guests">
                                Number of Guests
                            </label>

                            <select name="guests" id="guests" required>

                                <option value="">Select Guests</option>

                                <?php for ($i = 1; $i <= 20; $i++): ?>

                                    <option
                                        value="<?php echo $i; ?>"
                                        <?php echo (
                                            isset($_POST['guests']) &&
                                            $_POST['guests'] == $i
                                        ) ? 'selected' : ''; ?>
                                    >

                                        <?php echo $i; ?>
                                        <?php echo $i === 1 ? 'Guest' : 'Guests'; ?>

                                    </option>

                                <?php endfor; ?>

                            </select>

                        </div>


                    </div>



                    <div class="form-group">

                        <label for="special_request">
                            Special Requests (Optional)
                        </label>

                        <textarea
                            id="special_request"
                            name="special_request"
                            maxlength="1000"
                            placeholder="Any special requests? Birthday celebration, preferred seating, etc."
                        ><?php echo htmlspecialchars($_POST['special_request'] ?? ''); ?></textarea>

                    </div>



                    <button type="submit" class="booking-btn">

                        Confirm Reservation

                    </button>


                </form>


            <?php endif; ?>


        </div>


    </div>


    <a href="restaurant-details.php?id=<?php echo $restaurantId; ?>" class="back-link">

        ← Back to Restaurant

    </a>


</div>


</body>
</html>