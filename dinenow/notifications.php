<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/includes/db.php";

// CUSTOMER AUTHENTICATION
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];

// CSRF PROTECTION
if (empty($_SESSION['notification_token'])) {
    $_SESSION['notification_token'] = bin2hex(random_bytes(32));
}

$token = $_SESSION['notification_token'];

$successMessage = "";
$errorMessage = "";

// HANDLE NOTIFICATION ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['notification_token']) ||
        !hash_equals($token, $_POST['notification_token'])
    ) {
        die("Invalid request. Please refresh the page and try again.");
    }

    $action = $_POST['action'] ?? '';

    try {

        // MARK ONE NOTIFICATION AS READ
        if ($action === 'mark_read') {

            $notificationId = (int) ($_POST['notification_id'] ?? 0);

            if ($notificationId > 0) {

                $stmt = $pdo->prepare("
                    UPDATE notifications
                    SET is_read = 1
                    WHERE id = :id
                    AND user_id = :user_id
                ");

                $stmt->execute([
                    ':id' => $notificationId,
                    ':user_id' => $userId
                ]);

                $successMessage = "Notification marked as read.";
            }
        }

        // MARK ALL NOTIFICATIONS AS READ
        elseif ($action === 'mark_all') {

            $stmt = $pdo->prepare("
                UPDATE notifications
                SET is_read = 1
                WHERE user_id = :user_id
                AND is_read = 0
            ");

            $stmt->execute([
                ':user_id' => $userId
            ]);

            $successMessage = "All notifications marked as read.";
        }

    } catch (PDOException $e) {
        $errorMessage = "Something went wrong. Please try again.";
    }
}

// FETCH CUSTOMER NOTIFICATIONS
$stmt = $pdo->prepare("
    SELECT *
    FROM notifications
    WHERE user_id = :user_id
    ORDER BY created_at DESC
");

$stmt->execute([
    ':user_id' => $userId
]);

$notifications = $stmt->fetchAll();

// COUNT UNREAD NOTIFICATIONS
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM notifications
    WHERE user_id = :user_id
    AND is_read = 0
");

$stmt->execute([
    ':user_id' => $userId
]);

$unreadCount = (int) $stmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifications | DineNow</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #101010;
            color: #fff;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* HEADER */

        .top-header {
            background: #151515;
            border-bottom: 1px solid #292929;
            padding: 20px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .logo {
            font-size: 26px;
            font-weight: 800;
        }

        .logo span {
            color: #ff7628;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .home-btn {
            color: #ccc;
            font-size: 13px;
            transition: .3s;
        }

        .home-btn:hover {
            color: #ff7628;
        }

        .logout-btn {
            background: #ff7628;
            color: #fff;
            padding: 11px 18px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            transition: .3s;
        }

        .logout-btn:hover {
            background: #e85d10;
            transform: translateY(-2px);
        }

        /* MAIN CONTENT */

        .notifications-container {
            width: 90%;
            max-width: 950px;
            margin: 55px auto;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .page-heading h1 {
            font-size: 30px;
            font-weight: 700;
        }

        .page-heading h1 i {
            color: #ff7628;
            margin-right: 10px;
        }

        .unread-badge {
            background: #ff7628;
            color: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .page-description {
            color: #999;
            font-size: 13px;
            margin-bottom: 30px;
        }

        /* MESSAGES */

        .alert {
            padding: 13px 17px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .success {
            background: #123d25;
            border: 1px solid #1d7542;
            color: #8be5ad;
        }

        .error {
            background: #451b1b;
            border: 1px solid #853333;
            color: #ff9999;
        }

        /* MARK ALL BUTTON */

        .mark-all-form {
            margin-bottom: 20px;
            text-align: right;
        }

        .mark-all-btn {
            background: transparent;
            color: #ff7628;
            border: 1px solid #ff7628;
            padding: 10px 16px;
            border-radius: 6px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: .3s;
        }

        .mark-all-btn:hover {
            background: #ff7628;
            color: #fff;
        }

        /* NOTIFICATION CARD */

        .notification-card {
            background: #191919;
            border: 1px solid #292929;
            border-radius: 10px;
            padding: 22px;
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
            gap: 18px;
            transition: .3s;
        }

        .notification-card:hover {
            border-color: #ff7628;
            transform: translateY(-2px);
        }

        .notification-card.unread {
            background: #201a16;
            border-left: 4px solid #ff7628;
        }

        .notification-icon {
            width: 45px;
            height: 45px;
            min-width: 45px;
            background: #302016;
            color: #ff7628;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 18px;
        }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .notification-message {
            color: #aaa;
            font-size: 13px;
            line-height: 1.8;
            overflow-wrap: anywhere;
        }

        .notification-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 13px;
        }

        .notification-date {
            color: #777;
            font-size: 11px;
        }

        .status-label {
            font-size: 10px;
            font-weight: 700;
            padding: 4px 9px;
            border-radius: 20px;
        }

        .new-label {
            background: #3a2415;
            color: #ff7628;
        }

        .read-label {
            background: #252525;
            color: #999;
        }

        .booking-reference {
            color: #ff7628;
            font-size: 11px;
            font-weight: 600;
        }

        /* MARK AS READ */

        .mark-read-form {
            margin-top: 13px;
        }

        .mark-read-btn {
            background: transparent;
            border: none;
            color: #ff7628;
            font-family: inherit;
            font-size: 12px;
            cursor: pointer;
            font-weight: 600;
        }

        .mark-read-btn:hover {
            text-decoration: underline;
        }

        /* EMPTY STATE */

        .empty-state {
            background: #191919;
            border: 1px solid #292929;
            border-radius: 12px;
            padding: 65px 20px;
            text-align: center;
        }

        .empty-state i {
            font-size: 50px;
            color: #ff7628;
            margin-bottom: 20px;
        }

        .empty-state h2 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #888;
            font-size: 13px;
        }

        /* FOOTER */

        footer {
            text-align: center;
            padding: 25px;
            color: #777;
            font-size: 12px;
            border-top: 1px solid #292929;
            margin-top: 70px;
        }

        /* MOBILE RESPONSIVENESS */

        @media (max-width: 600px) {

            .top-header {
                padding: 18px 5%;
            }

            .logo {
                font-size: 22px;
            }

            .home-btn {
                display: none;
            }

            .logout-btn {
                padding: 9px 13px;
                font-size: 11px;
            }

            .notifications-container {
                width: 90%;
                margin: 35px auto;
            }

            .page-heading h1 {
                font-size: 23px;
            }

            .notification-card {
                padding: 16px;
                gap: 12px;
            }

            .notification-icon {
                width: 38px;
                height: 38px;
                min-width: 38px;
                font-size: 15px;
            }

            .notification-title {
                font-size: 13px;
            }

            .notification-message {
                font-size: 12px;
            }

        }

    </style>

</head>

<body>

<!-- HEADER -->

<header class="top-header">

    <a href="index.php" class="logo">
        Dine<span>Now.</span>
    </a>

    <div class="header-actions">

        <a href="index.php" class="home-btn">
            <i class="fas fa-home"></i> Home
        </a>

        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>

    </div>

</header>


<!-- NOTIFICATIONS -->

<main class="notifications-container">

    <div class="page-heading">

        <h1>
            <i class="fas fa-bell"></i>
            Notifications
        </h1>

        <span class="unread-badge">
            <?php echo $unreadCount; ?> Unread
        </span>

    </div>

    <p class="page-description">
        Stay updated on your restaurant bookings and reservation activities.
    </p>


    <?php if (!empty($successMessage)): ?>

        <div class="alert success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($successMessage); ?>
        </div>

    <?php endif; ?>


    <?php if (!empty($errorMessage)): ?>

        <div class="alert error">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>

    <?php endif; ?>


    <?php if ($unreadCount > 0): ?>

        <form method="POST" class="mark-all-form">

            <input
                type="hidden"
                name="notification_token"
                value="<?php echo htmlspecialchars($token); ?>"
            >

            <input type="hidden" name="action" value="mark_all">

            <button type="submit" class="mark-all-btn">
                <i class="fas fa-check-double"></i>
                Mark All as Read
            </button>

        </form>

    <?php endif; ?>


    <?php if (!empty($notifications)): ?>

        <?php foreach ($notifications as $notification): ?>

            <div class="notification-card <?php echo $notification['is_read'] ? '' : 'unread'; ?>">

                <div class="notification-icon">
                    <i class="fas fa-bell"></i>
                </div>

                <div class="notification-content">

                    <h3 class="notification-title">
                        <?php echo htmlspecialchars($notification['title']); ?>
                    </h3>

                    <p class="notification-message">
                        <?php echo htmlspecialchars($notification['message']); ?>
                    </p>

                    <div class="notification-meta">

                        <span class="booking-reference">
                            Booking #<?php echo (int) $notification['booking_id']; ?>
                        </span>

                        <span class="notification-date">
                            <i class="far fa-clock"></i>
                            <?php echo date("d M Y, h:i A", strtotime($notification['created_at'])); ?>
                        </span>

                        <?php if (!$notification['is_read']): ?>

                            <span class="status-label new-label">
                                NEW
                            </span>

                        <?php else: ?>

                            <span class="status-label read-label">
                                READ
                            </span>

                        <?php endif; ?>

                    </div>


                    <?php if (!$notification['is_read']): ?>

                        <form method="POST" class="mark-read-form">

                            <input
                                type="hidden"
                                name="notification_token"
                                value="<?php echo htmlspecialchars($token); ?>"
                            >

                            <input type="hidden" name="action" value="mark_read">

                            <input
                                type="hidden"
                                name="notification_id"
                                value="<?php echo (int) $notification['id']; ?>"
                            >

                            <button type="submit" class="mark-read-btn">
                                <i class="fas fa-check"></i>
                                Mark as Read
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty-state">

            <i class="far fa-bell-slash"></i>

            <h2>No Notifications Yet</h2>

            <p>
                You don't have any booking notifications at the moment.
                We'll notify you when there is an update.
            </p>

        </div>

    <?php endif; ?>

</main>


<!-- FOOTER -->

<footer>

    &copy; <?php echo date("Y"); ?>
    DineNow. All Rights Reserved.

</footer>

</body>
</html>