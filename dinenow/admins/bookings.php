
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
   CSRF TOKEN
========================================= */

if (empty($_SESSION['booking_admin_token'])) {
    $_SESSION['booking_admin_token'] = bin2hex(random_bytes(32));
}

$message = "";
$error = "";

/* =========================================
   GET VALID BOOKING STATUSES FROM DATABASE
========================================= */

$statusColumn = $pdo->query("
    SHOW COLUMNS FROM bookings LIKE 'status'
")->fetch();

$allowedStatuses = [];

if ($statusColumn && preg_match('/^enum\((.*)\)$/i', $statusColumn['Type'], $matches)) {
    $allowedStatuses = str_getcsv($matches[1], ',', "'");
} else {
    $allowedStatuses = ['pending', 'cancelled'];
}

/* =========================================
   UPDATE BOOKING STATUS
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['booking_admin_token'], $token)) {

        $error = "Invalid request. Please refresh the page.";

    } else {

        $id = (int)($_POST['booking_id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if (
            $id < 1 ||
            !in_array($status, $allowedStatuses, true)
        ) {

            $error = "Invalid booking update.";

        } else {

            try {

                $stmt = $pdo->prepare("
                    UPDATE bookings
                    SET status = :status
                    WHERE id = :id
                ");

                $stmt->execute([
                    'status' => $status,
                    'id' => $id
                ]);

                $message = "Booking status updated successfully.";

            } catch (PDOException $e) {

                $error = "Unable to update booking status.";

            }
        }
    }
}

/* =========================================
   FETCH BOOKINGS
========================================= */

$bookings = $pdo->query("
    SELECT
        b.id,
        b.booking_date,
        b.booking_time,
        b.guests,
        b.special_request,
        b.status,
        b.created_at,

        u.full_name,
        u.email,
        u.phone,

        r.name AS restaurant_name

    FROM bookings b

    JOIN users u
        ON b.user_id = u.id

    JOIN restaurants r
        ON b.restaurant_id = r.id

    ORDER BY b.created_at DESC
")->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>DineNow | Manage Bookings</title>

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
            max-width: 1350px;
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

        /* MAIN CONTENT */

        .box {
            background: #191919;
            border: 1px solid #333333;
            border-radius: 10px;
            padding: 25px;
            overflow-x: auto;
        }

        /* MESSAGES */

        .message {
            color: #70DB90;
            margin-bottom: 18px;
        }

        .error {
            color: #FF7777;
            margin-bottom: 18px;
        }

        /* BOOKINGS TABLE */

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1050px;
        }

        th,
        td {
            padding: 14px;
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

        /* SPECIAL REQUEST */

        .request {
            max-width: 180px;
            white-space: normal;
        }

        /* STATUS SELECT */

        select {
            padding: 9px;
            background: #101010;
            color: #FFFFFF;
            border: 1px solid #444444;
            border-radius: 5px;
        }

        /* UPDATE BUTTON */

        button {
            padding: 9px 12px;
            background: #FF7628;
            color: #FFFFFF;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 5px;
        }

        button:hover {
            background: #E65F12;
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

</head>

<body>

<div class="container">

    <h1>Manage Bookings</h1>

    <!-- NAVIGATION -->

    <div class="nav">

        <a href="dashboard.php">Dashboard</a>

        <a href="restaurants.php">Restaurants</a>

        <a href="bookings.php">Bookings</a>

        <a href="customers.php">Customers</a>

        <a href="../index.php">View Website</a>

    </div>

    <!-- BOOKINGS SECTION -->

    <div class="box">

        <?php if ($message): ?>

            <p class="message">
                <?php echo htmlspecialchars($message); ?>
            </p>

        <?php endif; ?>

        <?php if ($error): ?>

            <p class="error">
                <?php echo htmlspecialchars($error); ?>
            </p>

        <?php endif; ?>

        <table>

            <thead>

                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Restaurant</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Guests</th>
                    <th>Request</th>
                    <th>Status</th>
                    <th>Update</th>
                </tr>

            </thead>

            <tbody>

                <?php foreach ($bookings as $booking): ?>

                    <tr>

                        <td>
                            #<?php echo (int)$booking['id']; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($booking['full_name']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($booking['email']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($booking['phone'] ?? ''); ?>
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

                        <td class="request">
                            <?php echo htmlspecialchars($booking['special_request'] ?? 'None'); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                        </td>

                        <td>

                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['booking_admin_token']); ?>"
                                >

                                <input
                                    type="hidden"
                                    name="booking_id"
                                    value="<?php echo (int)$booking['id']; ?>"
                                >

                                <select name="status">

                                    <?php foreach ($allowedStatuses as $status): ?>

                                        <option
                                            value="<?php echo htmlspecialchars($status); ?>"
                                            <?php echo $booking['status'] === $status ? 'selected' : ''; ?>
                                        >
                                            <?php echo htmlspecialchars(ucfirst($status)); ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <button type="submit">
                                    Save
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (!$bookings): ?>

                    <tr>
                        <td colspan="11">
                            No bookings found.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>
