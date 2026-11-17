
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/includes/db.php";

// Require customer login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// CSRF protection
if (empty($_SESSION['booking_token'])) {
    $_SESSION['booking_token'] = bin2hex(random_bytes(32));
}

$restaurantId = filter_input(INPUT_GET, 'restaurant_id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurantId = filter_input(INPUT_POST, 'restaurant_id', FILTER_VALIDATE_INT);
}

if (!$restaurantId || $restaurantId < 1) {
    die("Invalid restaurant selected. Please return to the restaurants page and select a restaurant.");
}

// Get restaurant
$stmt = $pdo->prepare("
    SELECT *
    FROM restaurants
    WHERE id = :id AND status = 'active'
    LIMIT 1
");
$stmt->execute(['id' => $restaurantId]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    die("Restaurant not found or currently unavailable.");
}

$error = "";
$success = "";
$bookingReference = "";

$bookingDate = "";
$bookingTime = "";
$guests = 2;
$specialRequest = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['booking_token'] ?? '';

    if (!hash_equals($_SESSION['booking_token'], $token)) {
        $error = "Invalid request. Please refresh the page and try again.";
    } else {

        $bookingDate = trim($_POST['booking_date'] ?? '');
        $bookingTime = trim($_POST['booking_time'] ?? '');
        $guests = (int)($_POST['guests'] ?? 2);
        $specialRequest = trim($_POST['special_request'] ?? '');

        $dateObject = DateTime::createFromFormat('Y-m-d', $bookingDate);
        $timeObject = DateTime::createFromFormat('H:i', $bookingTime);

        if (!$dateObject || $dateObject->format('Y-m-d') !== $bookingDate) {
            $error = "Please select a valid booking date.";
        } elseif (!$timeObject || $timeObject->format('H:i') !== $bookingTime) {
            $error = "Please select a valid booking time.";
        } elseif ($bookingDate < date('Y-m-d')) {
            $error = "You cannot book a date in the past.";
        } elseif ($bookingDate === date('Y-m-d') && $bookingTime <= date('H:i')) {
            $error = "Please select a future booking time.";
        } elseif ($guests < 1 || $guests > 20) {
            $error = "Guest number must be between 1 and 20.";
        } elseif (strlen($specialRequest) > 1000) {
            $error = "Your special request is too long.";
        } else {

            $openingTime = substr($restaurant['opening_time'], 0, 5);
            $closingTime = substr($restaurant['closing_time'], 0, 5);

            if ($bookingTime < $openingTime || $bookingTime >= $closingTime) {
                $error = "Please choose a time within the restaurant's opening hours.";
            } else {

                try {
                    $pdo->beginTransaction();

                    // Find a suitable available table
                    $tableQuery = $pdo->prepare("
                        SELECT bt.id
                        FROM booking_tables bt
                        WHERE bt.restaurant_id = :restaurant_id
                        AND bt.capacity >= :guests
                        AND bt.status = 'available'
                        AND NOT EXISTS (
                            SELECT 1
                            FROM bookings b
                            WHERE b.table_id = bt.id
                            AND b.booking_date = :booking_date
                            AND b.status <> 'cancelled'
                            AND b.booking_time < ADDTIME(:requested_end, '02:00:00')
                            AND ADDTIME(b.booking_time, '02:00:00') > :requested_start
                        )
                        ORDER BY bt.capacity ASC
                        LIMIT 1
                        FOR UPDATE
                    ");

                    $tableQuery->execute([
                        'restaurant_id' => $restaurantId,
                        'guests' => $guests,
                        'booking_date' => $bookingDate,
                        'requested_end' => $bookingTime,
                        'requested_start' => $bookingTime
                    ]);

                    $table = $tableQuery->fetch();

                    if (!$table) {
                        $pdo->rollBack();
                        $error = "Sorry, no suitable table is available at that date and time. Please try another time.";
                    } else {

                        $insert = $pdo->prepare("
                            INSERT INTO bookings
                            (
                                user_id,
                                restaurant_id,
                                table_id,
                                booking_date,
                                booking_time,
                                guests,
                                special_request,
                                status,
                                created_at
                            )
                            VALUES
                            (
                                :user_id,
                                :restaurant_id,
                                :table_id,
                                :booking_date,
                                :booking_time,
                                :guests,
                                :special_request,
                                'pending',
                                NOW()
                            )
                        ");

                        $insert->execute([
                            'user_id' => $_SESSION['user_id'],
                            'restaurant_id' => $restaurantId,
                            'table_id' => $table['id'],
                            'booking_date' => $bookingDate,
                            'booking_time' => $bookingTime,
                            'guests' => $guests,
                            'special_request' => $specialRequest
                        ]);

                        $bookingReference = $pdo->lastInsertId();

                        $pdo->commit();

                        $success = "Your table reservation has been submitted successfully!";

                        $_SESSION['booking_token'] = bin2hex(random_bytes(32));

                        $bookingDate = "";
                        $bookingTime = "";
                        $guests = 2;
                        $specialRequest = "";
                    }

                } catch (PDOException $e) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    error_log($e->getMessage());
                    $error = "Something went wrong while processing your booking. Please try again.";
                }
            }
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
            min-height: 100vh;
            padding: 40px 15px;
        }

        .booking-container {
            max-width: 850px;
            margin: 0 auto;
        }

        .brand {
            text-align: center;
            margin-bottom: 30px;
            font-size: 30px;
            font-weight: bold;
        }

        .brand span {
            color: #ff7628;
        }

        .booking-card {
            background: #191919;
            padding: 35px;
            border-radius: 15px;
            border: 1px solid #333;
            box-shadow: 0 10px 35px rgba(0,0,0,.3);
        }

        .booking-card h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #aaa;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .restaurant-name {
            color: #ff7628;
            font-weight: bold;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 9px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 14px;
            background: #101010;
            border: 1px solid #444;
            border-radius: 7px;
            color: white;
            font-size: 15px;
            outline: none;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #ff7628;
        }

        textarea {
            resize: vertical;
            min-height: 110px;
        }

        button {
            width: 100%;
            padding: 16px;
            background: #ff7628;
            color: #101010;
            border: none;
            border-radius: 7px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: .3s;
        }

        button:hover {
            background: #fff;
        }

        .message {
            padding: 15px;
            border-radius: 7px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .error {
            background: #421b1b;
            color: #ffaaaa;
            border: 1px solid #8b3333;
        }

        .success {
            background: #173c25;
            color: #a5f0b9;
            border: 1px solid #287a43;
        }

        .success a {
            display: inline-block;
            margin-top: 10px;
            color: #ff7628;
            font-weight: bold;
        }

        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #ff7628;
            text-decoration: none;
        }

        .back-link:hover {
            color: white;
        }

        @media(max-width: 600px) {
            body {
                padding: 20px 12px;
            }

            .booking-card {
                padding: 22px 17px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .booking-card h1 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

<div class="booking-container">

    <div class="brand">
        Dine<span>Now.</span>
    </div>

    <div class="booking-card">

        <h1>Reserve Your Table</h1>

        <p class="subtitle">
            Complete the form below to reserve your table at
            <span class="restaurant-name">
                <?= htmlspecialchars($restaurant['name']) ?>
            </span>.
        </p>

        <?php if ($error): ?>
            <div class="message error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success">
                <?= htmlspecialchars($success) ?>

                <br>
                Your booking reference is:
                <strong>#<?= htmlspecialchars($bookingReference) ?></strong>

                <br>

                <a href="my-bookings.php">View My Bookings</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="booking.php?restaurant_id=<?= (int)$restaurantId ?>">

            <input type="hidden" name="restaurant_id" value="<?= (int)$restaurantId ?>">

            <input
                type="hidden"
                name="booking_token"
                value="<?= htmlspecialchars($_SESSION['booking_token']) ?>"
            >

            <div class="form-grid">

                <div class="form-group">
                    <label for="booking_date">Booking Date</label>

                    <input
                        type="date"
                        id="booking_date"
                        name="booking_date"
                        min="<?= date('Y-m-d') ?>"
                        value="<?= htmlspecialchars($bookingDate) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="booking_time">Booking Time</label>

                    <input
                        type="time"
                        id="booking_time"
                        name="booking_time"
                        value="<?= htmlspecialchars($bookingTime) ?>"
                        required
                    >
                </div>

                <div class="form-group full">
                    <label for="guests">Number of Guests</label>

                    <select name="guests" id="guests" required>
                        <?php for ($i = 1; $i <= 20; $i++): ?>
                            <option
                                value="<?= $i ?>"
                                <?= ((int)$guests === $i) ? 'selected' : '' ?>
                            >
                                <?= $i ?> <?= $i === 1 ? 'Guest' : 'Guests' ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group full">
                    <label for="special_request">Special Requests (Optional)</label>

                    <textarea
                        name="special_request"
                        id="special_request"
                        maxlength="1000"
                        placeholder="Any special requests or preferences?"
                    ><?= htmlspecialchars($specialRequest) ?></textarea>
                </div>

            </div>

            <button type="submit">
                Confirm Reservation
            </button>

        </form>

        <a href="restaurant-details.php?id=<?= (int)$restaurantId ?>" class="back-link">
            ← Back to Restaurant
        </a>

    </div>

</div>

</body>
</html>