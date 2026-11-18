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

/\* CSRF TOKEN \*/

if (empty($\_SESSION['booking_admin_token'])) {
&#x20;   $\_SESSION['booking_admin_token'] = bin2hex(random_bytes(32));
}

$message = "";
$error = "";

/\* UPDATE BOOKING STATUS \*/

if ($\_SERVER['REQUEST_METHOD'] === 'POST') {

&#x20;   $token = $\_POST['csrf_token'] ?? '';

&#x20;   if (!hash_equals($\_SESSION['booking_admin_token'], $token)) {

&#x20;       $error = "Invalid request. Please refresh the page.";

&#x20;   } else {

&#x20;       $id = (int)($\_POST['booking_id'] ?? 0);

&#x20;       $status = $\_POST['status'] ?? '';

&#x20;       $allowed = [
&#x20;           'pending',
&#x20;           'confirmed',
&#x20;           'cancelled'
&#x20;       ];

&#x20;       if (
&#x20;           $id < 1 ||
&#x20;           !in_array($status, $allowed, true)
&#x20;       ) {

&#x20;           $error = "Invalid booking update.";

&#x20;       } else {

&#x20;           $stmt = $pdo->prepare("
&#x20;               UPDATE bookings

&#x20;               SET status = \:status

&#x20;               WHERE id = \:id
&#x20;           ");

&#x20;           $stmt->execute([
&#x20;               'status' => $status,
&#x20;               'id' => $id
&#x20;           ]);

&#x20;           $message = "Booking status updated successfully.";
&#x20;       }
&#x20;   }
}

/\* FETCH BOOKINGS \*/

$bookings = $pdo->query("
&#x20;   SELECT
&#x20;       b.id,
&#x20;       b.booking_date,
&#x20;       b.booking_time,
&#x20;       b.guests,
&#x20;       b.special_request,
&#x20;       b.status,
&#x20;       b.created_at,

&#x20;       u.full_name,
&#x20;       u.email,
&#x20;       u.phone,

&#x20;       r.name AS restaurant_name

&#x20;   FROM bookings b

&#x20;   JOIN users u
&#x20;       ON b.user_id = u.id

&#x20;   JOIN restaurants r
&#x20;       ON b.restaurant_id = r.id

&#x20;   ORDER BY b.created_at DESC
")->fetchAll();

?>

\<!DOCTYPE html>

\<html lang="en">

\<head>

```
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


\</head>

\<body>


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
                            <?php echo htmlspecialchars($booking['status']); ?>
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

                                    <?php foreach (
                                        ['pending', 'confirmed', 'cancelled']
                                        as $status
                                    ): ?>

                                        <option
                                            value="<?php echo $status; ?>"
                                            <?php echo $booking['status'] === $status ? 'selected' : ''; ?>
                                        >

                                            <?php echo ucfirst($status); ?>

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


\</body>
\</html>
