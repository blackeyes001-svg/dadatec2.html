
<?php
session_start();
require_once "../includes/db.php";

// Protect admin page
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";
$editProduct = null;

// Delete product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_product'])) {
    $id = (int) $_POST['product_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Product deleted successfully.";
    } catch (PDOException $e) {
        $error = "Could not delete this product. It may be linked to an existing order.";
    }
}

// Add or update product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_product'])) {

    $id = (int) ($_POST['product_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $image = trim($_POST['image'] ?? '');
    $sizes = trim($_POST['sizes'] ?? '');
    $colors = trim($_POST['colors'] ?? '');
    $stock = (int) ($_POST['stock'] ?? 0);

    if ($name === '' || $category === '' || $price <= 0 || $stock < 0) {
        $error = "Please enter a valid name, category, price and stock quantity.";
    } else {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare(
                    "UPDATE products
                     SET name=?, category=?, description=?, price=?,
                         image=?, sizes=?, colors=?, stock=?
                     WHERE id=?"
                );

                $stmt->execute([
                    $name, $category, $description, $price,
                    $image, $sizes, $colors, $stock, $id
                ]);

                $message = "Product updated successfully.";

            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO products
                    (name, category, description, price, image, sizes, colors, stock)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $name, $category, $description, $price,
                    $image, $sizes, $colors, $stock
                ]);

                $message = "New product added successfully.";
            }

        } catch (PDOException $e) {
            $error = "Unable to save product. Please check your product details.";
        }
    }
}

// Load product for editing
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $editProduct = $stmt->fetch();

    if (!$editProduct) {
        $error = "Product not found.";
    }
}

// Get all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll();

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products | Global Zone Sports</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #071426;
            color: white;
        }

        .sidebar {
            width: 240px;
            min-height: 100vh;
            background: #10233d;
            position: fixed;
            padding: 25px 15px;
        }

        .sidebar h2 {
            color: #39FF14;
            text-align: center;
            margin-bottom: 35px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 14px;
            margin-bottom: 10px;
            border-radius: 6px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #2563EB;
        }

        .main {
            margin-left: 240px;
            padding: 30px;
        }

        .heading {
            margin-bottom: 25px;
        }

        .form-box, .table-box {
            background: #10233d;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .form-box h2, .table-box h2 {
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #334155;
            border-radius: 6px;
            background: #071426;
            color: white;
            margin-top: 7px;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        label {
            font-size: 14px;
        }

        .full {
            grid-column: 1 / -1;
        }

        button, .edit-btn {
            padding: 11px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            display: inline-block;
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
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th, td {
            padding: 14px;
            border-bottom: 1px solid #334155;
            text-align: left;
        }

        th {
            color: #39FF14;
        }

        .message {
            background: #14532d;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .error {
            background: #7f1d1d;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        @media(max-width: 700px) {
            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }

            .sidebar h2 {
                margin-bottom: 15px;
            }

            .sidebar a {
                display: inline-block;
                padding: 10px;
            }

            .main {
                margin-left: 0;
                padding: 18px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">
    <h2>GLOBAL ZONE</h2>

    <a href="dashboard.php">Dashboard</a>
    <a href="products.php" class="active">Manage Products</a>
    <a href="orders.php">Manage Orders</a>
    <a href="../index.php">View Website</a>
    <a href="logout.php">Logout</a>
</aside>

<main class="main">

    <div class="heading">
        <h1>Manage Products</h1>
        <p>Add, edit and manage your sports products.</p>
    </div>

    <?php if ($message): ?>
        <div class="message"><?= h($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <section class="form-box">

        <h2><?= $editProduct ? "Edit Product" : "Add New Product" ?></h2>

        <form method="POST">

            <input type="hidden" name="product_id"
                   value="<?= h($editProduct['id'] ?? '') ?>">

            <div class="form-grid">

                <div>
                    <label>Product Name</label>
                    <input type="text" name="name" required
                           value="<?= h($editProduct['name'] ?? '') ?>">
                </div>

                <div>
                    <label>Category</label>
                    <input type="text" name="category" required
                           placeholder="Football, Jerseys, Basketball..."
                           value="<?= h($editProduct['category'] ?? '') ?>">
                </div>

                <div>
                    <label>Price (₦)</label>
                    <input type="number" name="price" min="0.01"
                           step="0.01" required
                           value="<?= h($editProduct['price'] ?? '') ?>">
                </div>

                <div>
                    <label>Stock Quantity</label>
                    <input type="number" name="stock" min="0"
                           required
                           value="<?= h($editProduct['stock'] ?? '0') ?>">
                </div>

                <div>
                    <label>Sizes</label>
                    <input type="text" name="sizes"
                           placeholder="S, M, L, XL"
                           value="<?= h($editProduct['sizes'] ?? '') ?>">
                </div>

                <div>
                    <label>Colours</label>
                    <input type="text" name="colors"
                           placeholder="Black, White, Blue"
                           value="<?= h($editProduct['colors'] ?? '') ?>">
                </div>

                <div class="full">
                    <label>Product Image Path or URL</label>
                    <input type="text" name="image"
                           placeholder="images/product.jpg"
                           value="<?= h($editProduct['image'] ?? '') ?>">
                </div>

                <div class="full">
                    <label>Description</label>
                    <textarea name="description"><?= h($editProduct['description'] ?? '') ?></textarea>
                </div>

                <div class="full">
                    <button type="submit" name="save_product" class="save-btn">
                        <?= $editProduct ? "Update Product" : "Add Product" ?>
                    </button>

                    <?php if ($editProduct): ?>
                        <a href="products.php" class="cancel-btn edit-btn">
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
                        <td>₦<?= number_format((float)$product['price'], 2) ?></td>
                        <td><?= (int)$product['stock'] ?></td>

                        <td>
                            <div class="actions">

                                <a class="edit-btn"
                                   href="products.php?edit=<?= (int)$product['id'] ?>">
                                    Edit
                                </a>

                                <form method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this product?');">

                                    <input type="hidden" name="product_id"
                                           value="<?= (int)$product['id'] ?>">

                                    <button type="submit"
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
                        <td colspan="6">No products found.</td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>

        </div>

    </section>

</main>

</body>
</html>

