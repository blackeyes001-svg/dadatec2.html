
<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$adminName = $_SESSION['admin_name'] ?? 'Administrator';

if (empty($_SESSION['stock_out_token'])) {
    $_SESSION['stock_out_token'] = bin2hex(random_bytes(32));
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['token']) ||
        !hash_equals($_SESSION['stock_out_token'], $_POST['token'])
    ) {
        $error = "Invalid request. Please refresh the page and try again.";
    } else {

        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        $reason = trim($_POST['reason'] ?? '');

        if (!$productId || !$quantity || $quantity < 1) {
            $error = "Please select a product and enter a valid quantity.";
        } elseif ($reason === '') {
            $error = "Please provide a reason for removing the stock.";
        } else {

            try {
                $pdo->beginTransaction();

                $productStmt = $pdo->prepare("
                    SELECT id, name, stock
                    FROM products
                    WHERE id = ?
                    FOR UPDATE
                ");
                $productStmt->execute([$productId]);
                $product = $productStmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    throw new Exception("Product not found.");
                }

                $batchStmt = $pdo->prepare("
                    SELECT id, quantity_remaining
                    FROM inventory_batches
                    WHERE product_id = ?
                    AND quantity_remaining > 0
                    ORDER BY received_at ASC, id ASC
                    FOR UPDATE
                ");
                $batchStmt->execute([$productId]);
                $batches = $batchStmt->fetchAll(PDO::FETCH_ASSOC);

                $availableStock = 0;

                foreach ($batches as $batch) {
                    $availableStock += (int)$batch['quantity_remaining'];
                }

                if ($availableStock !== (int)$product['stock']) {
                    throw new Exception(
                        "Stock records do not match the batch balances. Please check inventory before proceeding."
                    );
                }

                if ($quantity > $availableStock) {
                    throw new Exception(
                        "Insufficient stock. Only " . $availableStock . " units are available."
                    );
                }

                $remainingToRemove = $quantity;

                foreach ($batches as $batch) {

                    if ($remainingToRemove <= 0) {
                        break;
                    }

                    $batchAvailable = (int)$batch['quantity_remaining'];
                    $deduct = min($remainingToRemove, $batchAvailable);
                    $newRemaining = $batchAvailable - $deduct;

                    $updateBatch = $pdo->prepare("
                        UPDATE inventory_batches
                        SET quantity_remaining = ?,
                            status = ?
                        WHERE id = ?
                    ");

                    $updateBatch->execute([
                        $newRemaining,
                        $newRemaining > 0 ? 'active' : 'finished',
                        $batch['id']
                    ]);

                    $movement = $pdo->prepare("
                        INSERT INTO inventory_movements (
                            product_id,
                            batch_id,
                            movement_type,
                            quantity,
                            reference_type,
                            reference_id,
                            notes
                        ) VALUES (?, ?, 'stock_out', ?, 'manual', ?, ?)
                    ");

                    $movement->execute([
                        $productId,
                        $batch['id'],
                        $deduct,
                        $batch['id'],
                        $reason
                    ]);

                    $remainingToRemove -= $deduct;
                }

                $updateProduct = $pdo->prepare("
                    UPDATE products
                    SET stock = stock - ?
                    WHERE id = ?
                ");

                $updateProduct->execute([$quantity, $productId]);

                $pdo->commit();

                $_SESSION['stock_out_token'] = bin2hex(random_bytes(32));

                $success = "Stock successfully removed from " . $product['name'] . ".";

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = $e->getMessage();
            }
        }
    }
}

// Fetch products
$productStmt = $pdo->query("
    SELECT id, name, stock
    FROM products
    WHERE stock > 0
    ORDER BY name ASC
");

$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

$totalAvailable = $pdo->query("
    SELECT COALESCE(SUM(quantity_remaining), 0)
    FROM inventory_batches
")->fetchColumn();

$totalRemoved = $pdo->query("
    SELECT COALESCE(SUM(quantity), 0)
    FROM inventory_movements
    WHERE movement_type = 'stock_out'
")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Stock Out | BayoGoal Enterprises</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #071426;
    color: #fff;
}

.sidebar {
    width: 250px;
    min-height: 100vh;
    background: #10233d;
    position: fixed;
    padding: 30px 18px;
    border-right: 1px solid #203b5c;
}

.brand {
    text-align: center;
    margin-bottom: 40px;
}

.brand h2 {
    font-size: 25px;
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
    margin-bottom: 9px;
    border-radius: 7px;
    font-size: 14px;
}

.sidebar a:hover,
.sidebar a.active {
    background: #2563EB;
    color: #fff;
}

.submenu {
    padding-left: 12px;
    margin-bottom: 10px;
}

.submenu a {
    font-size: 13px;
    padding: 11px 12px;
    border-left: 2px solid #39FF14;
}

.main {
    margin-left: 250px;
    padding: 35px;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 30px;
}

.topbar h1 {
    font-size: 27px;
    margin-bottom: 8px;
}

.topbar p {
    color: #9ca3af;
    font-size: 14px;
}

.topbar strong {
    color: #39FF14;
}

.back-link {
    background: #2563EB;
    color: #fff;
    padding: 12px 20px;
    text-decoration: none;
    border-radius: 7px;
}

.cards {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    background: #10233d;
    padding: 23px;
    border-radius: 10px;
    border-left: 4px solid #f87171;
}

.card h3 {
    color: #9ca3af;
    font-size: 13px;
    font-weight: normal;
    margin-bottom: 15px;
}

.card p {
    color: #f87171;
    font-size: 27px;
    font-weight: bold;
}

.panel {
    background: #10233d;
    border: 1px solid #203b5c;
    border-radius: 12px;
    padding: 28px;
    max-width: 850px;
}

.panel h2 {
    margin-bottom: 8px;
}

.panel-description {
    color: #9ca3af;
    font-size: 13px;
    margin-bottom: 25px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 9px;
    font-size: 14px;
    color: #d1d5db;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 14px;
    background: #071426;
    border: 1px solid #304968;
    border-radius: 7px;
    color: #fff;
    outline: none;
    font-size: 14px;
}

.form-group textarea {
    resize: vertical;
    min-height: 110px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #2563EB;
}

.form-group select option {
    background: #10233d;
}

.submit-btn {
    width: 100%;
    padding: 15px;
    background: #dc2626;
    border: none;
    color: #fff;
    font-size: 15px;
    font-weight: bold;
    border-radius: 7px;
    cursor: pointer;
}

.submit-btn:hover {
    background: #b91c1c;
}

.alert {
    padding: 15px;
    border-radius: 7px;
    margin-bottom: 22px;
    font-size: 14px;
}

.success {
    background: rgba(57,255,20,.12);
    color: #39FF14;
    border: 1px solid #39FF14;
}

.error {
    background: rgba(239,68,68,.12);
    color: #f87171;
    border: 1px solid #ef4444;
}

.notice {
    background: rgba(37,99,235,.12);
    border: 1px solid #2563EB;
    color: #93c5fd;
    padding: 15px;
    border-radius: 7px;
    font-size: 13px;
    margin-bottom: 22px;
    line-height: 1.6;
}

@media(max-width: 700px) {
    .sidebar {
        width: 100%;
        min-height: auto;
        position: relative;
        padding: 20px 15px;
    }

    .brand {
        margin-bottom: 20px;
    }

    .sidebar a {
        display: inline-block;
        padding: 11px;
        margin: 4px 2px;
    }

    .submenu {
        padding-left: 0;
    }

    .main {
        margin-left: 0;
        padding: 22px 15px;
    }

    .cards {
        grid-template-columns: 1fr;
    }

    .panel {
        padding: 20px;
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
        <a href="../dashboard.php">Dashboard</a>
        <a href="../products.php">Manage Products</a>
        <a href="index.php">Inventory Overview</a>

        <div class="submenu">
            <a href="stock-in.php">Stock In</a>
            <a href="stock-out.php" class="active">Stock Out</a>
            <a href="batches.php">Batches</a>
            <a href="history.php">History</a>
        </div>

        <a href="../orders.php">Manage Orders</a>
        <a href="../customers.php">Manage Customers</a>
        <a href="../../index.php" target="_blank">View Website</a>
        <a href="../logout.php">Logout</a>
    </nav>

</aside>

<main class="main">

    <div class="topbar">
        <div>
            <h1>Stock Out</h1>
            <p>Welcome, <strong><?= e($adminName) ?></strong>. Record goods removed from inventory.</p>
        </div>

        <a href="index.php" class="back-link">← Inventory Overview</a>
    </div>

    <div class="cards">

        <div class="card">
            <h3>Total Available Stock</h3>
            <p><?= number_format((int)$totalAvailable) ?></p>
        </div>

        <div class="card">
            <h3>Total Stock Removed</h3>
            <p><?= number_format((int)$totalRemoved) ?></p>
        </div>

    </div>

    <section class="panel">

        <h2>Remove Goods from Inventory</h2>
        <p class="panel-description">
            Record damaged, missing, expired or manually removed goods.
        </p>

        <?php if ($success): ?>
            <div class="alert success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="notice">
            Stock will be deducted from the oldest available batch first.
            The product stock and inventory history will update automatically.
        </div>

        <form method="POST">

            <input
                type="hidden"
                name="token"
                value="<?= e($_SESSION['stock_out_token']) ?>"
            >

            <div class="form-group">
                <label>Select Product</label>

                <select name="product_id" required>
                    <option value="">Choose a product</option>

                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int)$product['id'] ?>">
                            <?= e($product['name']) ?>
                            — <?= number_format((int)$product['stock']) ?> available
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="form-group">
                <label>Quantity to Remove</label>
                <input
                    type="number"
                    name="quantity"
                    min="1"
                    required
                    placeholder="Enter quantity"
                >
            </div>

            <div class="form-group">
                <label>Reason for Removal</label>

                <textarea
                    name="reason"
                    required
                    placeholder="Example: Damaged goods, expired products, missing goods..."
                ></textarea>
            </div>

            <button type="submit" class="submit-btn">
                Record Stock Out
            </button>

        </form>

    </section>

</main>

</body>
</html>
