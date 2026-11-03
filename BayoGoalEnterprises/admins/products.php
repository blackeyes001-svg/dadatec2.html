<?php
session_start();
require_once "../includes/db.php";

// Protect admin page
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// CSRF token
if (empty($_SESSION['admin_products_token'])) {
    $_SESSION['admin_products_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['admin_products_token'];

$message = "";
$error = "";
$editProduct = null;

// Delete product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_product'])) {

    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please refresh the page and try again.";
    } else {
        $id = (int)($_POST['product_id'] ?? 0);

        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$id]);

                $message = $stmt->rowCount() > 0
                    ? "Product deleted successfully."
                    : "Product not found.";

            } catch (PDOException $e) {
                error_log("Product deletion error: " . $e->getMessage());
                $error = "Could not delete this product. It may be linked to an existing order.";
            }
        } else {
            $error = "Invalid product selected.";
        }
    }
}

// Add or update product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_product'])) {

    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please refresh the page and try again.";
    } else {

        $id = (int)($_POST['product_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $image = trim($_POST['image'] ?? '');
        $sizes = trim($_POST['sizes'] ?? '');
        $colors = trim($_POST['colors'] ?? '');
        $stock = (int)($_POST['stock'] ?? 0);

        if ($name === '' || $category === '' || $price <= 0 || $stock < 0) {
            $error = "Please enter a valid name, category, price and stock quantity.";
        } else {

            try {
                if ($id > 0) {

                    $stmt = $pdo->prepare(
                        "UPDATE products
                         SET name = ?, category = ?, description = ?,
                             price = ?, image = ?, sizes = ?,
                             colors = ?, stock = ?
                         WHERE id = ?"
                    );

                    $stmt->execute([
                        $name,
                        $category,
                        $description,
                        $price,
                        $image,
                        $sizes,
                        $colors,
                        $stock,
                        $id
                    ]);

                    $message = "Product updated successfully.";

                } else {

                    $stmt = $pdo->prepare(
                        "INSERT INTO products
                        (name, category, description, price, image, sizes, colors, stock)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->execute([
                        $name,
                        $category,
                        $description,
                        $price,
                        $image,
                        $sizes,
                        $colors,
                        $stock
                    ]);

                    $message = "New product added successfully.";
                }

            } catch (PDOException $e) {
                error_log("Product saving error: " . $e->getMessage());
                $error = "Unable to save product. Please check your product details.";
            }
        }
    }
}

// Load product for editing
if (isset($_GET['edit'])) {

    $id = (int)$_GET['edit'];

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$editProduct) {
            $error = "Product not found.";
        }
    } else {
        $error = "Invalid product selected.";
    }
}

// Get all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Safe output function
function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Products | Bayogoal Enterprises</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #071426;
            color: #ffffff;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: #10233d;
            position: fixed;
            top: 0;
            left: 0;
            padding: 30px 18px;
        }

        .brand {
            text-align: center;
            margin-bottom: 40px;
        }

        .brand h2 {
            color: #ffffff;
            font-size: 25px;
            letter-spacing: 1px;
        }

        .brand span {
            color: #39FF14;
        }

        .brand p {
            color: #9ca3af;
            font-size: 11px;
            margin-top: 7px;
            letter-spacing: 2px;
        }

        .sidebar a {
            display: block;
            color: #d1d5db;
            text-decoration: none;
            padding: 14px 15px;
            margin-bottom: 10px;
            border-radius: 7px;
            transition: 0.3s;
            font-size: 14px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #2563EB;
            color: #ffffff;
        }

        .sidebar .website-link {
            margin-top: 25px;
            border: 1px solid #334155;
        }

        .main {
            margin-left: 250px;
            padding: 35px;
            min-width: 0;
        }

        .heading {
            margin-bottom: 30px;
        }

        .heading h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .heading p {
            color: #9ca3af;
            font-size: 14px;
        }

        .form-box,
        .table-box {
            background: #10233d;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .form-box h2,
        .table-box h2 {
            margin-bottom: 22px;
            font-size: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group {
            min-width: 0;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 13px;
            border: 1px solid #334155;
            border-radius: 7px;
            background: #071426;
            color: #ffffff;
            margin-top: 8px;
            font-size: 14px;
            font-family: inherit;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #2563EB;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        label {
            font-size: 14px;
            color: #e5e7eb;
        }

        .full {
            grid-column: 1 / -1;
        }

        button,
        .btn {
            padding: 12px 17px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            color: #ffffff;
            display: inline-block;
            font-size: 13px;
            font-weight: bold;
            transition: 0.3s;
        }

        button:hover,
        .btn:hover {
            opacity: 0.85;
        }

        .save-btn {
            background: #2563EB;
        }

        .edit-btn {
            background: #15803d;
        }

        .delete-btn {
            background: #dc2626;
        }

        .cancel-btn {
            background: #475569;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th,
        td {
            padding: 15px 13px;
            border-bottom: 1px solid #334155;
            text-align: left;
            font-size: 13px;
        }

        th {
            color: #39FF14;
            font-size: 13px;
        }

        tr:hover {
            background: rgba(37, 99, 235, 0.08);
        }

        .message,
        .error {
            padding: 14px;
            margin-bottom: 20px;
            border-radius: 7px;
            font-size: 14px;
        }

        .message {
            background: #14532d;
            border: 1px solid #166534;
            color: #dcfce7;
        }

        .error {
            background: #7f1d1d;
            border: 1px solid #991b1b;
            color: #fee2e2;
        }

        .actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .actions form {
            margin: 0;
        }

        .empty-message {
            text-align: center;
            color: #9ca3af;
            padding: 25px;
        }

        .footer {
            text-align: center;
            color: #64748b;
            font-size: 13px;
            padding: 20px 0;
        }

        @media (max-width: 1000px) {
            .main {
                padding: 25px 20px;
            }
        }

        @media (max-width: 700px) {
            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
                padding: 20px 15px;
            }

            .brand {
                margin-bottom: 20px;
            }

            .sidebar a {
                display: inline-block;
                padding: 11px;
                margin: 4px 2px;
                font-size: 13px;
            }

            .sidebar .website-link {
                margin-top: 5px;
            }

            .main {
                margin-left: 0;
                padding: 20px 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full {
                grid-column: auto;
            }

            .heading h1 {
                font-size: 24px;
            }

            .form-box,
            .table-box {
                padding: 18px;
            }
        }

        @media (max-width: 400px) {
            .actions {
                flex-direction: column;
                align-items: flex-start;
            }

            .form-box,
            .table-box {
                padding: 15px;
            }
        }
    </style>

</head>

<body>

<aside class="sidebar">

    <div class="brand">
        <h2>BAYO<span>GOAL</span></h2>
        <p>ENTERPRISES</p>
    </div>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php" class="active">Manage Products</a>
        <a href="orders.php">Manage Orders</a>
        <a href="../index.php" class="website-link">View Website</a>
        <a href="logout.php">Logout</a>
    </nav>

</aside>

<main class="main">

    <div class="heading">
        <h1>Manage Products</h1>
        <p>Add, edit and manage your Bayogoal sports products.</p>
    </div>

    <?php if ($message): ?>
        <div class="message">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <section class="form-box">

        <h2>
            <?= $editProduct ? "Edit Product" : "Add New Product" ?>
        </h2>

        <form method="POST">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= h($csrfToken) ?>">

            <input type="hidden"
                   name="product_id"
                   value="<?= h($editProduct['id'] ?? '') ?>">

            <div class="form-grid">

                <div class="form-group">
                    <label>Product Name</label>
                    <input
                        type="text"
                        name="name"
                        required
                        value="<?= h($editProduct['name'] ?? '') ?>"
                        placeholder="Enter product name">
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <input
                        type="text"
                        name="category"
                        required
                        placeholder="football, basketball, fitness..."
                        value="<?= h($editProduct['category'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Price (₦)</label>
                    <input
                        type="number"
                        name="price"
                        min="0.01"
                        step="0.01"
                        required
                        value="<?= h($editProduct['price'] ?? '') ?>"
                        placeholder="Enter price">
                </div>

                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input
                        type="number"
                        name="stock"
                        min="0"
                        required
                        value="<?= h($editProduct['stock'] ?? '0') ?>">
                </div>

                <div class="form-group">
                    <label>Sizes</label>
                    <input
                        type="text"
                        name="sizes"
                        placeholder="S, M, L, XL"
                        value="<?= h($editProduct['sizes'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Colours</label>
                    <input
                        type="text"
                        name="colors"
                        placeholder="Black, White, Blue"
                        value="<?= h($editProduct['colors'] ?? '') ?>">
                </div>

                <div class="form-group full">
                    <label>Product Image Path or URL</label>
                    <input
                        type="text"
                        name="image"
                        placeholder="images/football/product.jpg"
                        value="<?= h($editProduct['image'] ?? '') ?>">
                </div>

                <div class="form-group full">
                    <label>Description</label>
                    <textarea
                        name="description"
                        placeholder="Describe your product..."><?= h($editProduct['description'] ?? '') ?></textarea>
                </div>

                <div class="full">

                    <button
                        type="submit"
                        name="save_product"
                        class="save-btn">

                        <?= $editProduct ? "Update Product" : "Add Product" ?>

                    </button>

                    <?php if ($editProduct): ?>

                        <a href="products.php" class="btn cancel-btn">
                            Cancel
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </form>

    </section>

    <section class="table-box">

        <h2>All Products (<?= count($products) ?>)</h2>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($products as $product): ?>

                    <tr>

                        <td><?= (int)$product['id'] ?></td>

                        <td><?= h($product['name']) ?></td>

                        <td><?= h($product['category']) ?></td>

                        <td>
                            ₦<?= number_format((float)$product['price'], 2) ?>
                        </td>

                        <td><?= (int)$product['stock'] ?></td>

                        <td>

                            <div class="actions">

                                <a
                                    class="btn edit-btn"
                                    href="products.php?edit=<?= (int)$product['id'] ?>">
                                    Edit
                                </a>

                                <form
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this product?');">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= h($csrfToken) ?>">

                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?= (int)$product['id'] ?>">

                                    <button
                                        type="submit"
                                        name="delete_product"
                                        class="delete-btn">
                                        Delete
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (empty($products)): ?>

                    <tr>
                        <td colspan="6" class="empty-message">
                            No products found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <div class="footer">
        &copy; <?= date('Y') ?> Bayogoal Enterprises. Admin Control Panel.
    </div>

</main>

</body>
</html>