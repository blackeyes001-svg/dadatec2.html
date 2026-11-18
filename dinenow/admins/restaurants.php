```php
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

if (empty($_SESSION['restaurant_admin_token'])) {
    $_SESSION['restaurant_admin_token'] = bin2hex(random_bytes(32));
}

$message = "";
$error = "";
$editing = null;

/* =========================================
   HANDLE FORM SUBMISSIONS
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !hash_equals($_SESSION['restaurant_admin_token'], $token)
    ) {

        $error = "Invalid request. Please refresh the page.";

    } else {

        $action = $_POST['action'] ?? '';

        /* =========================================
           ADD OR UPDATE RESTAURANT
        ========================================= */

        if ($action === 'save') {

            $id = (int)($_POST['id'] ?? 0);

            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $cuisine = trim($_POST['cuisine'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $image = trim($_POST['image'] ?? '');
            $opening = trim($_POST['opening_time'] ?? '');
            $closing = trim($_POST['closing_time'] ?? '');

            if ($name === '' || $city === '') {

                $error = "Restaurant name and city are required.";

            } elseif (
                $email !== '' &&
                !filter_var($email, FILTER_VALIDATE_EMAIL)
            ) {

                $error = "Please enter a valid email address.";

            } else {

                try {

                    if ($id > 0) {

                        /* UPDATE EXISTING RESTAURANT */

                        $stmt = $pdo->prepare("
                            UPDATE restaurants SET
                                name = :name,
                                description = :description,
                                address = :address,
                                city = :city,
                                cuisine = :cuisine,
                                phone = :phone,
                                email = :email,
                                image = :image,
                                opening_time = :opening,
                                closing_time = :closing
                            WHERE id = :id
                        ");

                        $stmt->execute([
                            'name' => $name,
                            'description' => $description,
                            'address' => $address,
                            'city' => $city,
                            'cuisine' => $cuisine,
                            'phone' => $phone,
                            'email' => $email,
                            'image' => $image,
                            'opening' => $opening,
                            'closing' => $closing,
                            'id' => $id
                        ]);

                        $message = "Restaurant updated successfully.";

                    } else {

                        /* ADD NEW RESTAURANT */

                        $stmt = $pdo->prepare("
                            INSERT INTO restaurants (
                                name,
                                description,
                                address,
                                city,
                                cuisine,
                                phone,
                                email,
                                image,
                                opening_time,
                                closing_time,
                                status
                            )
                            VALUES (
                                :name,
                                :description,
                                :address,
                                :city,
                                :cuisine,
                                :phone,
                                :email,
                                :image,
                                :opening,
                                :closing,
                                'active'
                            )
                        ");

                        $stmt->execute([
                            'name' => $name,
                            'description' => $description,
                            'address' => $address,
                            'city' => $city,
                            'cuisine' => $cuisine,
                            'phone' => $phone,
                            'email' => $email,
                            'image' => $image,
                            'opening' => $opening,
                            'closing' => $closing
                        ]);

                        $message = "Restaurant added successfully.";
                    }

                } catch (PDOException $e) {

                    $error = "Database error: " . $e->getMessage();

                }
            }
        }

        /* =========================================
           ACTIVATE OR DEACTIVATE RESTAURANT
        ========================================= */

        elseif ($action === 'toggle') {

            $id = (int)($_POST['id'] ?? 0);

            if ($id > 0) {

                try {

                    $stmt = $pdo->prepare("
                        UPDATE restaurants
                        SET status = CASE
                            WHEN status = 'active' THEN 'inactive'
                            ELSE 'active'
                        END
                        WHERE id = :id
                    ");

                    $stmt->execute(['id' => $id]);

                    $message = "Restaurant status updated successfully.";

                } catch (PDOException $e) {

                    $error = "Unable to update restaurant status.";

                }
            }
        }
    }
}

/* =========================================
   FETCH RESTAURANT FOR EDITING
========================================= */

if (isset($_GET['edit'])) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM restaurants
        WHERE id = :id
    ");

    $stmt->execute([
        'id' => (int)$_GET['edit']
    ]);

    $editing = $stmt->fetch();

}

/* =========================================
   FETCH ALL RESTAURANTS
========================================= */

$restaurants = $pdo->query("
    SELECT *
    FROM restaurants
    ORDER BY id DESC
")->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>DineNow | Manage Restaurants</title>

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

        /* HEADINGS */

        h1 {
            font-size: 28px;
            margin-bottom: 12px;
        }

        h2 {
            font-size: 21px;
            margin-bottom: 20px;
        }

        /* NAVIGATION */

        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin: 20px 0 30px;
        }

        .nav a {
            color: #FF7628;
            text-decoration: none;
        }

        .nav a:hover {
            text-decoration: underline;
        }

        /* CONTENT BOXES */

        .box {
            background: #191919;
            border: 1px solid #333333;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
        }

        /* FORM GRID */

        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        label {
            display: block;
            color: #CCCCCC;
            font-size: 14px;
            margin-bottom: 8px;
        }

        input,
        textarea {
            width: 100%;
            padding: 12px;
            background: #101010;
            border: 1px solid #444444;
            border-radius: 6px;
            color: #FFFFFF;
            outline: none;
        }

        input:focus,
        textarea:focus {
            border-color: #FF7628;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .description-group {
            margin-top: 18px;
        }

        /* BUTTONS */

        button {
            padding: 12px 18px;
            background: #FF7628;
            color: #FFFFFF;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #E65F12;
        }

        .submit-button {
            margin-top: 20px;
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

        /* TABLE */

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th,
        td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #333333;
        }

        th {
            color: #FF7628;
        }

        td {
            color: #DDDDDD;
        }

        /* TABLE ACTIONS */

        .actions form {
            display: inline;
        }

        .actions button {
            margin: 4px;
        }

        .edit-link {
            color: #FF7628;
        }

        .cancel-link {
            margin-left: 15px;
            color: #AAAAAA;
        }

        /* MOBILE RESPONSIVENESS */

        @media (max-width: 650px) {

            body {
                padding: 15px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .box {
                padding: 18px;
            }

            h1 {
                font-size: 24px;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <h1>Manage Restaurants</h1>

    <!-- NAVIGATION -->

    <div class="nav">

        <a href="dashboard.php">Dashboard</a>

        <a href="restaurants.php">Restaurants</a>

        <a href="bookings.php">Bookings</a>

        <a href="customers.php">Customers</a>

        <a href="../index.php">View Website</a>

    </div>

    <!-- ADD / EDIT FORM -->

    <div class="box">

        <h2>
            <?php echo $editing ? 'Edit Restaurant' : 'Add New Restaurant'; ?>
        </h2>

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

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['restaurant_admin_token']); ?>"
            >

            <input type="hidden" name="action" value="save">

            <input
                type="hidden"
                name="id"
                value="<?php echo (int)($editing['id'] ?? 0); ?>"
            >

            <div class="grid">

                <div>
                    <label>Restaurant Name</label>

                    <input
                        type="text"
                        name="name"
                        required
                        value="<?php echo htmlspecialchars($editing['name'] ?? ''); ?>"
                    >
                </div>

                <div>
                    <label>Cuisine</label>

                    <input
                        type="text"
                        name="cuisine"
                        placeholder="e.g. African, Italian"
                        value="<?php echo htmlspecialchars($editing['cuisine'] ?? ''); ?>"
                    >
                </div>

                <div>
                    <label>City</label>

                    <input
                        type="text"
                        name="city"
                        required
                        value="<?php echo htmlspecialchars($editing['city'] ?? ''); ?>"
                    >
                </div>

                <div>
                    <label>Address</label>

                    <input
                        type="text"
                        name="address"
                        value="<?php echo htmlspecialchars($editing['address'] ?? ''); ?>"
                    >
                </div>

                <div>
                    <label>Phone</label>

                    <input
                        type="text"
                        name="phone"
                        value="<?php echo htmlspecialchars($editing['phone'] ?? ''); ?>"
                    >
                </div>

                <div>
                    <label>Email</label>

                    <input
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($editing['email'] ?? ''); ?>"
                    >
                </div>

                <div>
                    <label>Image Path</label>

                    <input
                        type="text"
                        name="image"
                        placeholder="images/restaurants/example.jpg"
                        value="<?php echo htmlspecialchars($editing['image'] ?? ''); ?>"
                    >
                </div>

                <div>
                    <label>Opening Time</label>

                    <input
                        type="time"
                        name="opening_time"
                        value="<?php echo htmlspecialchars(isset($editing['opening_time']) ? substr($editing['opening_time'], 0, 5) : ''); ?>"
                    >
                </div>

                <div>
                    <label>Closing Time</label>

                    <input
                        type="time"
                        name="closing_time"
                        value="<?php echo htmlspecialchars(isset($editing['closing_time']) ? substr($editing['closing_time'], 0, 5) : ''); ?>"
                    >
                </div>

            </div>

            <div class="description-group">

                <label>Description</label>

                <textarea name="description"><?php echo htmlspecialchars($editing['description'] ?? ''); ?></textarea>

            </div>

            <button type="submit" class="submit-button">
                <?php echo $editing ? 'Update Restaurant' : 'Add Restaurant'; ?>
            </button>

            <?php if ($editing): ?>

                <a href="restaurants.php" class="cancel-link">
                    Cancel Edit
                </a>

            <?php endif; ?>

        </form>

    </div>

    <!-- RESTAURANT LIST -->

    <div class="box">

        <h2>All Restaurants</h2>

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th