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
   GET VALID BOOKING STATUSES
========================================= */

$statusColumn = $pdo->query("
    SHOW COLUMNS FROM bookings LIKE 'status'
")->fetch();

$allowedStatuses = [];

if (
    $statusColumn &&
    preg_match('/^enum\((.*)\)$/i', $statusColumn['Type'], $matches)
) {
    $allowedStatuses = str_getcsv($matches[1], ',', "'");
} else {
    $allowedStatuses = ['pending', 'cancelled'];
}

/* =========================================
   UPDATE BOOKING STATUS AND NOTIFY CUSTOMER
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

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    SELECT user_id, status
                    FROM bookings
                    WHERE id = :id
                    LIMIT 1
                ");

                $stmt->execute(['id' => $id]);

                $booking = $stmt->fetch();

                if (!$booking) {
                    throw new Exception("Booking not found.");
                }

                $oldStatus = $booking['status'];

                if ($oldStatus !== $status) {

                    $stmt = $pdo->prepare("
                        UPDATE bookings
                        SET status = :status
                        WHERE id = :id
                    ");

                    $stmt->execute([
                        'status' => $status,
                        'id' => $id
                    ]);

                    $title = "Booking Status Updated";

                    $notificationMessage =
                        "Your restaurant booking (#" . $id .
                        ") status has been changed from " .
                        ucfirst($oldStatus) . " to " .
                        ucfirst($status) . ".";

                    $stmt = $pdo->prepare("
                        INSERT INTO notifications
                        (
                            user_id,
                            booking_id,
                            title,
                            message
                        )
                        VALUES
                        (
                            :user_id,
                            :booking_id,
                            :title,
                            :message
                        )
                    ");

                    $stmt->execute([
                        'user_id' => $booking['user_id'],
                        'booking_id' => $id,
                        'title' => $title,
                        'message' => $notificationMessage
                    ]);

                    $message = "Booking updated and customer notified successfully.";

                } else {

                    $message = "No changes were made to this booking.";

                }

                $pdo->commit();

            } catch (PDOException $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = "Database error. Unable to update booking.";

            } catch (Exception $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = $e->getMessage();

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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        :root {
            --orange: #FF7628;
            --black: #101010;
            --dark: #191919;
            --white: #FFFFFF;
            --muted: #AAAAAA;
            --glass: rgba(255,255,255,0.055);
            --border: rgba(255,255,255,0.12);
        }

        body {
            min-height: 100vh;
            color: var(--white);

            background:
                radial-gradient(circle at 15% 10%, rgba(255,118,40,0.12), transparent 35%),
                radial-gradient(circle at 90% 90%, rgba(255,118,40,0.07), transparent 35%),
                linear-gradient(135deg, #080808, #171717, #101010);

            background-attachment: fixed;
        }

        .glass {
            background: var(--glass);
            border: 1px solid var(--border);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.35);
        }

        /* SIDEBAR */

        .sidebar {
            position: fixed;
            top: 18px;
            left: 18px;
            bottom: 18px;
            width: 255px;
            padding: 25px 17px;
            border-radius: 22px;
            z-index: 100;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .logo {
            font-size: 27px;
            font-weight: 800;
            letter-spacing: -1px;
            padding: 5px 12px 30px;
        }

        .logo span {
            color: var(--orange);
        }

        .sidebar-label {
            color: #777;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.7px;
            padding: 15px 12px 10px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 13px;
            color: #CCC;
            text-decoration: none;
            padding: 13px 14px;
            margin-bottom: 5px;
            border-radius: 12px;
            font-size: 14px;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background: rgba(255,118,40,0.10);
            color: white;
            transform: translateX(4px);
        }

        .sidebar a.active {
            color: white;
            background: linear-gradient(
                110deg,
                rgba(255,118,40,0.30),
                rgba(255,118,40,0.09)
            );
            border: 1px solid rgba(255,118,40,0.35);
            box-shadow: 0 0 20px rgba(255,118,40,0.10);
        }

        .nav-icon {
            width: 22px;
            text-align: center;
            font-size: 17px;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding-top: 25px;
        }

        .sidebar .logout-link {
            color: #FF9A80;
        }

        /* MAIN CONTENT */

        .main {
            margin-left: 290px;
            padding: 30px 35px 40px;
            max-width: 1800px;
        }

        /* TOPBAR */

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 35px;
        }

        .welcome h1 {
            font-size: 29px;
            font-weight: 700;
            letter-spacing: -0.7px;
        }

        .welcome p {
            color: var(--muted);
            font-size: 14px;
            margin-top: 7px;
        }

        .welcome p span {
            color: var(--orange);
            font-weight: 600;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 13px;
            border-radius: 13px;
        }

        .avatar {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: linear-gradient(135deg, #FF7628, #B94412);
            font-weight: bold;
        }

        .profile-info strong {
            display: block;
            font-size: 12px;
        }

        .profile-info small {
            color: var(--muted);
            font-size: 11px;
        }

        /* PAGE INTRO */

        .page-intro {
            margin-bottom: 25px;
        }

        .page-intro h2 {
            font-size: 20px;
            margin-bottom: 7px;
        }

        .page-intro p {
            color: var(--muted);
            font-size: 13px;
        }

        /* BOOKING SUMMARY */

        .summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
        }

        .summary-card {
            padding: 20px 23px;
            border-radius: 17px;
            flex: 1;
        }

        .summary-card small {
            color: var(--muted);
            font-size: 12px;
        }

        .summary-card h2 {
            font-size: 27px;
            margin-top: 9px;
            color: var(--orange);
        }

        /* MESSAGES */

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .message {
            color: #70DB90;
            background: rgba(50,220,145,0.09);
            border: 1px solid rgba(50,220,145,0.20);
        }

        .error {
            color: #FF9696;
            background: rgba(255,80,80,0.09);
            border: 1px solid rgba(255,80,80,0.20);
        }

        /* BOOKING TABLE SECTION */

        .content-section {
            border-radius: 20px;
            padding: 25px;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 22px;
        }

        .section-heading h2 {
            font-size: 18px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 12px;
            margin-top: 5px;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            width: 260px;
            border-radius: 12px;
        }

        .search-box input {
            width: 100%;
            background: transparent;
            border: none;
            outline: none;
            color: white;
            font-size: 13px;
        }

        .search-box input::placeholder {
            color: #888;
        }

        /* TABLE */

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1250px;
            border-collapse: collapse;
        }

        th {
            color: var(--orange);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            font-weight: 600;
            padding: 16px 12px;
            text-align: left;
            white-space: nowrap;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }

        td {
            color: #DDD;
            font-size: 13px;
            padding: 17px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            vertical-align: middle;
        }

        tbody tr {
            transition: 0.2s;
        }

        tbody tr:hover {
            background: rgba(255,118,40,0.045);
        }

        .reference {
            color: var(--orange);
            font-weight: 700;
        }

        .customer-name {
            font-weight: 600;
            color: white;
        }

        .restaurant-name {
            color: #CCC;
        }

        .request {
            max-width: 180px;
            white-space: normal;
            line-height: 1.5;
        }

        /* STATUS BADGES */

        .status {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
            background: rgba(255,255,255,0.10);
            white-space: nowrap;
        }

        .status.pending {
            color: #FFC56A;
            background: rgba(255,180,55,0.13);
        }

        .status.confirmed,
        .status.completed {
            color: #65E4AD;
            background: rgba(50,220,145,0.13);
        }

        .status.cancelled,
        .status.rejected {
            color: #FF9696;
            background: rgba(255,80,80,0.13);
        }

        .status.processing {
            color: #FFAA75;
            background: rgba(255,118,40,0.13);
        }

        /* UPDATE FORM */

        .update-form {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        select {
            padding: 10px 11px;
            background: #191919;
            color: white;
            border: 1px solid #444;
            border-radius: 8px;
            outline: none;
            cursor: pointer;
            font-size: 12px;
        }

        select:focus {
            border-color: var(--orange);
        }

        select option {
            background: #191919;
            color: white;
        }

        .save-btn {
            padding: 10px 14px;
            background: var(--orange);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: 0.3s;
            white-space: nowrap;
        }

        .save-btn:hover {
            background: #E65F12;
            transform: translateY(-2px);
        }

        .empty-message {
            color: var(--muted);
            text-align: center;
            padding: 35px;
        }

        /* MOBILE MENU */

        .mobile-menu {
            display: none;
            background: var(--glass);
            border: 1px solid var(--border);
            color: white;
            width: 42px;
            height: 42px;
            border-radius: 10px;
            font-size: 21px;
            cursor: pointer;
        }

        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.65);
            z-index: 90;
        }

        .overlay.show {
            display: block;
        }

        /* ANIMATIONS */

        .summary-card,
        .content-section,
        .topbar {
            animation: fadeUp 0.6s ease both;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* RESPONSIVE DESIGN */

        @media (max-width: 1200px) {
            .main {
                padding: 25px;
            }
        }

        @media (max-width: 850px) {

            .sidebar {
                transform: translateX(-120%);
                transition: 0.3s;
                width: 260px;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main {
                margin-left: 0;
                padding: 25px;
            }

            .mobile-menu {
                display: block;
            }

            .topbar {
                flex-wrap: wrap;
            }

            .welcome {
                flex: 1;
            }
        }

        @media (max-width: 600px) {

            .main {
                padding: 18px 13px;
            }

            .topbar {
                align-items: flex-start;
                gap: 13px;
            }

            .welcome h1 {
                font-size: 22px;
            }

            .welcome p {
                font-size: 12px;
            }

            .topbar-right {
                width: 100%;
                justify-content: flex-end;
            }

            .profile {
                padding: 5px;
            }

            .profile-info {
                display: none;
            }

            .summary {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .summary-card {
                padding: 17px;
            }

            .content-section {
                padding: 17px 12px;
            }

            .section-heading {
                align-items: stretch;
                flex-direction: column;
            }

            .search-box {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<!-- SIDEBAR -->

<aside class="sidebar glass" id="sidebar">

    <div class="logo">
        Dine<span>Now.</span>
    </div>

    <div class="sidebar-label">OVERVIEW</div>

    <a href="dashboard.php">
        <span class="nav-icon">▦</span>
        Dashboard
    </a>

    <div class="sidebar-label">MANAGEMENT</div>

    <a href="restaurants.php">
        <span class="nav-icon">🍽</span>
        Restaurants
    </a>

    <a href="bookings.php" class="active">
        <span class="nav-icon">📅</span>
        Bookings
    </a>

    <a href="customers.php">
        <span class="nav-icon">♙</span>
        Customers
    </a>

    <div class="sidebar-label">WEBSITE</div>

    <a href="../index.php">
        <span class="nav-icon">⌂</span>
        View Website
    </a>

    <div class="sidebar-bottom">

        <a href="../logout.php" class="logout-link">
            <span class="nav-icon">⇥</span>
            Logout
        </a>

    </div>

</aside>

<div class="overlay" id="overlay"></div>

<!-- MAIN CONTENT -->

<main class="main">

    <!-- TOPBAR -->

    <header class="topbar">

        <button class="mobile-menu" id="menuButton" type="button">
            ☰
        </button>

        <div class="welcome">

            <h1>Booking Management</h1>

            <p>
                Welcome, <span>Dada David.</span>
                Manage your customers' reservations.
            </p>

        </div>

        <div class="topbar-right">

            <div class="profile glass">

                <div class="avatar">D</div>

                <div class="profile-info">
                    <strong>Dada David</strong>
                    <small>Administrator</small>
                </div>

            </div>

        </div>

    </header>

    <!-- PAGE INTRO -->

    <div class="page-intro">

        <h2>All Reservations</h2>

        <p>
            View booking details, manage reservation statuses
            and notify customers about updates.
        </p>

    </div>

    <!-- BOOKING SUMMARY -->

    <section class="summary">

        <div class="summary-card glass">

            <small>Total Bookings</small>

            <h2><?php echo count($bookings); ?></h2>

        </div>

        <div class="summary-card glass">

            <small>Pending Bookings</small>

            <h2>
                <?php
                echo count(array_filter($bookings, function ($booking) {
                    return strtolower($booking['status']) === 'pending';
                }));
                ?>
            </h2>

        </div>

        <div class="summary-card glass">

            <small>Confirmed Bookings</small>

            <h2>
                <?php
                echo count(array_filter($bookings, function ($booking) {
                    return strtolower($booking['status']) === 'confirmed';
                }));
                ?>
            </h2>

        </div>

    </section>

    <!-- BOOKING MANAGEMENT -->

    <section class="content-section glass">

        <div class="section-heading">

            <div>
                <h2>Booking Records</h2>
                <p>Manage and update customer reservations.</p>
            </div>

            <div class="search-box glass">
                <span>⌕</span>
                <input
                    type="text"
                    id="bookingSearch"
                    placeholder="Search bookings..."
                >
            </div>

        </div>

        <?php if ($message): ?>

            <div class="alert message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <?php if ($error): ?>

            <div class="alert error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <div class="table-container">

            <table id="bookingsTable">

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

                            <td class="reference">
                                #<?php echo (int)$booking['id']; ?>
                            </td>

                            <td class="customer-name">
                                <?php echo htmlspecialchars($booking['full_name']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($booking['email']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($booking['phone'] ?? ''); ?>
                            </td>

                            <td class="restaurant-name">
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

                                <span class="status <?php echo htmlspecialchars(strtolower($booking['status'])); ?>">
                                    <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                                </span>

                            </td>

                            <td>

                                <form method="POST" class="update-form">

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

                                    <button type="submit" class="save-btn">
                                        Save
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (!$bookings): ?>

                        <tr>
                            <td colspan="11" class="empty-message">
                                No bookings found yet.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<script>

/* MOBILE SIDEBAR */

const sidebar = document.getElementById('sidebar');
const menuButton = document.getElementById('menuButton');
const overlay = document.getElementById('overlay');

menuButton.addEventListener('click', function () {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('show');
});

overlay.addEventListener('click', function () {
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
});

/* BOOKING SEARCH */

const searchInput = document.getElementById('bookingSearch');

const tableRows = document.querySelectorAll(
    '#bookingsTable tbody tr'
);

searchInput.addEventListener('input', function () {

    const searchValue = this.value.toLowerCase();

    tableRows.forEach(function (row) {

        const rowText = row.textContent.toLowerCase();

        row.style.display = rowText.includes(searchValue)
            ? ''
            : 'none';

    });

});

</script>

</body>
</html>