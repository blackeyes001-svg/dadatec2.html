<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

// Admin protection
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$adminName = $_SESSION['admin_name'] ?? 'Administrator';

// Search and filter
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$sql = "
    SELECT
        b.*,
        p.name AS product_name
    FROM inventory_batches b
    LEFT JOIN products p ON b.product_id = p.id
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (
        p.name LIKE ?
        OR b.batch_code LIKE ?
        OR b.supplier LIKE ?
    )";

    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (in_array($status, ['active', 'finished'], true)) {
    $sql .= " AND b.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY b.received_at DESC, b.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary
$totalBatches = $pdo->query("
    SELECT COUNT(*) FROM inventory_batches
")->fetchColumn();

$activeBatches = $pdo->query("
    SELECT COUNT(*) FROM inventory_batches WHERE status = 'active'
")->fetchColumn();

$finishedBatches = $pdo->query("
    SELECT COUNT(*) FROM inventory_batches WHERE status = 'finished'
")->fetchColumn();

$totalRemaining = $pdo->query("
    SELECT COALESCE(SUM(quantity_remaining), 0)
    FROM inventory_batches
")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Inventory Batches | BayoGoal Enterprises</title>

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
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 30px;
}

.card {
    background: #10233d;
    padding: 22px;
    border-radius: 10px;
    border-left: 4px solid #39FF14;
}

.card h3 {
    color: #9ca3af;
    font-size: 13px;
    font-weight: normal;
    margin-bottom: 15px;
}

.card p {
    color: #39FF14;
    font-size: 25px;
    font-weight: bold;
}

.panel {
    background: #10233d;
    border: 1px solid #203b5c;
    border-radius: 12px;
    padding: 25px;
}

.panel-heading {
    margin-bottom: 22px;
}

.panel-heading h2 {
    font-size: 20px;
    margin-bottom: 7px;
}

.panel-heading p {
    color: #9ca3af;
    font-size: 13px;
}

.filters {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 22px;
}

.filters input,
.filters select {
    padding: 12px;
    background: #071426;
    border: 1px solid #304968;
    color: #fff;
    border-radius: 7px;
    outline: none;
}

.filters input {
    flex: 1;
    min-width: 200px;
}

.filters button {
    padding: 12px 20px;
    background: #2563EB;
    border: none;
    color: #fff;
    border-radius: 7px;
    cursor: pointer;
}

.reset {
    padding: 12px 18px;
    background: #334155;
    color: #fff;
    border-radius: 7px;
    text-decoration: none;
    font-size: 13px;
}

.table-wrapper {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1050px;
}

th, td {
    padding: 15px 12px;
    text-align: left;
    border-bottom: 1px solid #304968;
}

th {
    color: #39FF14;
    font-size: 12px;
    text-transform: uppercase;
}

td {
    color: #e5e7eb;
    font-size: 13px;
}

.batch-code {
    color: #60a5fa;
    font-weight: bold;
}

.badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.active-badge {
    background: rgba(57,255,20,.12);
    color: #39FF14;
}

.finished-badge {
    background: rgba(239,68,68,.15);
    color: #f87171;
}

.empty {
    text-align: center;
    padding: 35px;
    color: #9ca3af;
}

@media(max-width: 1100px) {
    .cards {
        grid-template-columns: repeat(2, 1fr);
    }
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
        padding: 18px;
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
            <a href="stock-out.php">Stock Out</a>
            <a href="batches.php" class="active">Batches</a>
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
            <h1>Inventory Batches</h1>
            <p>Welcome, <strong><?= e($adminName) ?></strong>. Track every delivery.</p>
        </div>

        <a href="index.php" class="back-link">← Inventory Overview</a>
    </div>

    <div class="cards">

        <div class="card">
            <h3>Total Batches</h3>
            <p><?= number_format((int)$totalBatches) ?></p>
        </div>

        <div class="card">
            <h3>Active Batches</h3>
            <p><?= number_format((int)$activeBatches) ?></p>
        </div>

        <div class="card">
            <h3>Finished Batches</h3>
            <p><?= number_format((int)$finishedBatches) ?></p>
        </div>

        <div class="card">
            <h3>Remaining Units</h3>
            <p><?= number_format((int)$totalRemaining) ?></p>
        </div>

    </div>

    <section class="panel">

        <div class="panel-heading">
            <h2>All Delivery Batches</h2>
            <p>Each delivery is tracked separately from the others.</p>
        </div>

        <form method="GET" class="filters">

            <input
                type="text"
                name="search"
                placeholder="Search product, batch code or supplier..."
                value="<?= e($search) ?>"
            >

            <select name="status">
                <option value="">All Batch Statuses</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>
                    Active
                </option>
                <option value="finished" <?= $status === 'finished' ? 'selected' : '' ?>>
                    Finished
                </option>
            </select>

            <button type="submit">Search</button>

            <a href="batches.php" class="reset">Reset</a>

        </form>

        <div class="table-wrapper">

            <table>
                <thead>
                    <tr>
                        <th>Batch Code</th>
                        <th>Product</th>
                        <th>Supplier</th>
                        <th>Qty Received</th>
                        <th>Qty Remaining</th>
                        <th>Unit Cost</th>
                        <th>Date Received</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($batches): ?>

                    <?php foreach ($batches as $batch): ?>

                        <tr>
                            <td class="batch-code">
                                <?= e($batch['batch_code']) ?>
                            </td>

                            <td>
                                <?= e($batch['product_name'] ?? 'Unknown Product') ?>
                            </td>

                            <td>
                                <?= e($batch['supplier'] ?: 'Not specified') ?>
                            </td>

                            <td>
                                <?= number_format((int)$batch['quantity_received']) ?>
                            </td>

                            <td>
                                <strong>
                                    <?= number_format((int)$batch['quantity_remaining']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= $batch['unit_cost'] !== null
                                    ? '₦' . number_format((float)$batch['unit_cost'], 2)
                                    : '—' ?>
                            </td>

                            <td>
                                <?= date('d M Y, h:i A', strtotime($batch['received_at'])) ?>
                            </td>

                            <td>
                                <?php if ($batch['status'] === 'active'): ?>
                                    <span class="badge active-badge">Active</span>
                                <?php else: ?>
                                    <span class="badge finished-badge">Finished</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" class="empty">
                            No inventory batches found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>

        </div>

    </section>

</main>

</body>
</html>