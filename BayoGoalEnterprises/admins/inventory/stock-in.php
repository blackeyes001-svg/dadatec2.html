<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

if (empty($_SESSION['inventory_csrf'])) {
    $_SESSION['inventory_csrf'] = bin2hex(random_bytes(32));
}

$products = $pdo->query("
    SELECT id, name
    FROM products
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['inventory_csrf'], $_POST['csrf_token'])
    ) {
        $error = "Invalid request. Please refresh the page and try again.";
    } else {

        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        $unitCost = trim($_POST['unit_cost'] ?? '');
        $supplier = trim($_POST['supplier'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (!$productId || !$quantity || $quantity < 1) {
            $error = "Please select a product and enter a valid quantity.";
        } elseif (
            $unitCost !== '' &&
            (!is_numeric($unitCost) || (float)$unitCost < 0)
        ) {
            $error = "Please enter a valid unit cost.";
        } else {

            try {
                $pdo->beginTransaction();

                // Confirm product exists and lock its stock record.
                $check = $pdo->prepare("
                    SELECT id
                    FROM products
                    WHERE id = ?
                    FOR UPDATE
                ");
                $check->execute([$productId]);

                if (!$check->fetch()) {
                    throw new Exception("Selected product does not exist.");
                }

                // Generate a unique batch code.
                $batchCode = 'BATCH-' . date('YmdHis') . '-' . $productId
                    . '-' . strtoupper(bin2hex(random_bytes(2)));

                // Create the new delivery batch.
                $insertBatch = $pdo->prepare("
                    INSERT INTO inventory_batches (
                        product_id,
                        batch_code,
                        quantity_received,
                        quantity_remaining,
                        unit_cost,
                        supplier,
                        notes,
                        status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
                ");

                $insertBatch->execute([
                    $productId,
                    $batchCode,
                    $quantity,
                    $quantity,
                    $unitCost === '' ? null : $unitCost,
                    $supplier ?: null,
                    $notes ?: null
                ]);

                $batchId = $pdo->lastInsertId();

                // Record stock-in activity.
                $movement = $pdo->prepare("
                    INSERT INTO inventory_movements (
                        product_id,
                        batch_id,
                        movement_type,
                        quantity,
                        reference_type,
                        reference_id,
                        notes
                    ) VALUES (?, ?, 'stock_in', ?, 'delivery', ?, ?)
                ");

                $movement->execute([
                    $productId,
                    $batchId,
                    $quantity,
                    $batchId,
                    $notes ?: 'New stock delivery received'
                ]);

                // Keep the existing product stock synchronized.
                $updateStock = $pdo->prepare("
                    UPDATE products
                    SET stock = stock + ?
                    WHERE id = ?
                ");

                $updateStock->execute([$quantity, $productId]);

                $pdo->commit();

                $message = "Stock received successfully! Batch: " . $batchCode;

                // Refresh the form token after successful submission.
                $_SESSION['inventory_csrf'] = bin2hex(random_bytes(32));

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = "Unable to record stock: " . $e->getMessage();
            }
        }
    }
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$adminName = $_SESSION['admin_name'] ?? 'Administrator';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Stock In | BayoGoal Enterprises</title>

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
    top: 0;
    left: 0;
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

.form-panel {
    max-width: 850px;
    background: #10233d;
    border: 1px solid #203b5c;
    border-radius: 12px;
    padding: 30px;
}

.form-panel h2 {
    font-size: 21px;
    margin-bottom: 8px;
}

.form-panel .description {
    color: #9ca3af;
    font-size: 13px;
    margin-bottom: 28px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 9px;
}

.form-group.full {
    grid-column: 1 / -1;
}

label {
    color: #d1d5db;
    font-size: 14px;
    font-weight: bold;
}

input, select, textarea {
    width: 100%;
    padding: 14px;
    background: #071426;
    color: #fff;
    border: 1px solid #304968;
    border-radius: 7px;
    outline: none;
    font-size: 14px;
}

input:focus,
select:focus,
textarea:focus {
    border-color: #39FF14;
}

select option {
    background: #10233d;
}

textarea {
    resize: vertical;
    min-height: 110px;
}

.help {
    color: #94a3b8;
    font-size: 12px;
}

.submit-btn {
    margin-top: 25px;
    background: #2563EB;
    color: #fff;
    border: none;
    padding: 15px 25px;
    border-radius: 7px;
    cursor: pointer;
    font-size: 15px;
    font-weight: bold;
}

.submit-btn:hover {
    background: #1d4ed8;
}

.message {
    padding: 15px;
    border-radius: 7px;
    margin-bottom: 22px;
    font-size: 14px;
}

.success {
    background: rgba(57,255,20,.12);
    border: 1px solid #39FF14;
    color: #39FF14;
}

.error {
    background: rgba(239,68,68,.12);
    border: 1px solid #ef4444;
    color: #f87171;
}

.note {
    margin-top: 25px;
    padding: 17px;
    background: rgba(37,99,235,.12);
    border-left: 3px solid #2563EB;
    color: #cbd5e1;
    font-size: 13px;
    line-height: 1.7;
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

    .form-panel {
        padding: 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full {
        grid-column: auto;
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
            <a href="stock-in.php" class="active">Stock In</a>
            <a href="stock-out.php">Stock Out</a>
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
            <h1>Stock In</h1>
            <p>Welcome, <strong><?= e($adminName) ?></strong>. Record a new delivery.</p>
        </div>

        <a href="index.php" class="back-link">← Inventory Overview</a>
    </div>

    <section class="form-panel">

        <h2>Record New Stock Delivery</h2>

        <p class="description">
            Enter the details of the goods received from your supplier.
        </p>

        <?php if ($message): ?>
            <div class="message success"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['inventory_csrf']) ?>"
            >

            <div class="form-grid">

                <div class="form-group full">
                    <label for="product_id">Select Product</label>

                    <select name="product_id" id="product_id" required>
                        <option value="">-- Choose a product --</option>

                        <?php foreach ($products as $product): ?>
                            <option
                                value="<?= (int)$product['id'] ?>"
                                <?= isset($_POST['product_id']) && (int)$_POST['product_id'] === (int)$product['id'] ? 'selected' : '' ?>
                            >
                                <?= e($product['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity Received</label>

                    <input
                        type="number"
                        name="quantity"
                        id="quantity"
                        min="1"
                        step="1"
                        value="<?= e($_POST['quantity'] ?? '') ?>"
                        placeholder="e.g. 50"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="unit_cost">Unit Cost (₦)</label>

                    <input
                        type="number"
                        name="unit_cost"
                        id="unit_cost"
                        min="0"
                        step="0.01"
                        value="<?= e($_POST['unit_cost'] ?? '') ?>"
                        placeholder="e.g. 15000"
                    >

                    <span class="help">Optional: purchase cost per item.</span>
                </div>

                <div class="form-group full">
                    <label for="supplier">Supplier Name</label>

                    <input
                        type="text"
                        name="supplier"
                        id="supplier"
                        maxlength="150"
                        value="<?= e($_POST['supplier'] ?? '') ?>"
                        placeholder="Enter supplier or company name"
                    >
                </div>

                <div class="form-group full">
                    <label for="notes">Additional Notes</label>

                    <textarea
                        name="notes"
                        id="notes"
                        placeholder="Enter delivery details, condition of goods, invoice reference, etc."
                    ><?= e($_POST['notes'] ?? '') ?></textarea>
                </div>

            </div>

            <button type="submit" class="submit-btn">
                + Record Stock Delivery
            </button>

        </form>

        <div class="note">
            <strong>Important:</strong> Every delivery creates a separate
            inventory batch. The available product stock is also updated
            automatically. Existing batches will not be overwritten.
        </div>

    </section>

</main>

</body>
</html>