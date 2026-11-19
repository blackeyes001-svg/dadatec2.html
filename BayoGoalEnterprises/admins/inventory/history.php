
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

// Filters
$search = trim($_GET['search'] ?? '');
$type = $_GET['type'] ?? '';
$fromDate = $_GET['from'] ?? '';
$toDate = $_GET['to'] ?? '';

$sql = "
    SELECT
        m.*,
        p.name AS product_name,
        b.batch_code
    FROM inventory_movements m
    LEFT JOIN products p ON m.product_id = p.id
    LEFT JOIN inventory_batches b ON m.batch_id = b.id
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (
        p.name LIKE ?
        OR b.batch_code LIKE ?
        OR m.notes LIKE ?
        OR m.reference_type LIKE ?
    )";

    $term = "%$search%";

    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (in_array($type, ['stock_in', 'stock_out', 'adjustment'], true)) {
    $sql .= " AND m.movement_type = ?";
    $params[] = $type;
}

if ($fromDate !== '') {
    $sql .= " AND DATE(m.created_at) >= ?";
    $params[] = $fromDate;
}

if ($toDate !== '') {
    $sql .= " AND DATE(m.created_at) <= ?";
    $params[] = $toDate;
}

$sql .= " ORDER BY m.created_at DESC, m.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary statistics
$totalActivities = $pdo->query("
    SELECT COUNT(*) FROM inventory_movements
")->fetchColumn();

$totalStockIn = $pdo->query("
    SELECT COALESCE(SUM(quantity), 0)
    FROM inventory_movements
    WHERE movement_type = 'stock_in'
")->fetchColumn();

$totalStockOut = $pdo->query("
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

<title>Inventory History | BayoGoal Enterprises</title>

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
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    background: #10233d;
    padding: 23px;
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
    font-size: 27px;
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

.filters input[name="search"] {
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

.product-name {
    font-weight: bold;
}

.batch-code {
    color: #60a5fa;
}

.badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.stock-in {
    background: rgba(57,255,20,.12);
    color: #39FF14;
}

.stock-out {
    background: rgba(239,68,68,.15);
    color: #f87171;
}

.adjustment {
    background: rgba(37,99,235,.15);
    color: #60a5fa;
}

.empty {
    text-align: center;
    padding: 35px;
    color: #9ca3af;
}

@media(max-width: 1000px) {
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
            <a href="batches.php">Batches</a>
            <a href="history.php" class="active">History</a>
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
            <h1>Inventory History</h1>
            <p>Welcome, <strong><?= e($adminName) ?></strong>. Track every stock movement.</p>
        </div>

        <a href="index.php" class="back-link">← Inventory Overview</a>
    </div>

    <div class="cards">

        <div class="card">
            <h3>Total Activities</h3>
            <p><?= number_format((int)$totalActivities) ?></p>
        </div>

        <div class="card">
            <h3>Total Goods Received</h3>
            <p><?= number_format((int)$totalStockIn) ?></p>
        </div>

        <div class="card">
            <h3>Total Stock Removed</h3>
            <p><?= number_format((int)$totalStockOut) ?></p>
        </div>

    </div>

    <section class="panel">

        <div class="panel-heading">
            <h2>Stock Movement Records</h2>
            <p>Search and review all recorded inventory activities.</p>
        </div>

        <form method="GET" class="filters">

            <input
                type="text"
                name="search"
                placeholder="Search product, batch or notes..."
                value="<?= e($search) ?>"
            >

            <select name="type">
                <option value="">All Activities</option>

                <option value="stock_in" <?= $type === 'stock_in' ? 'selected' : '' ?>>
                    Stock In
                </option>

                <option value="stock_out" <?= $type === 'stock_out' ? 'selected' : '' ?>>
                    Stock Out
                </option>

                <option value="adjustment" <?= $type === 'adjustment' ? 'selected' : '' ?>>
                    Adjustment
                </option>
            </select>

            <input type="date" name="from" value="<?= e($fromDate) ?>">
            <input type="date" name="to" value="<?= e($toDate) ?>">

            <button type="submit">Filter</button>

            <a href="history.php" class="reset">Reset</a>

        </form>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Batch Code</th>
                        <th>Activity</th>
                        <th>Quantity</th>
                        <th>Reference</th>
                        <th>Notes / Reason</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($activities): ?>

                    <?php foreach ($activities as $activity): ?>

                        <tr>

                            <td class="product-name">
                                <?= e($activity['product_name'] ?? 'Unknown Product') ?>
                            </td>

                            <td class="batch-code">
                                <?= e($activity['batch_code'] ?? 'N/A') ?>
                            </td>

                            <td>
                                <?php
                                $movementType = $activity['movement_type'];
                                $badgeClass = str_replace('_', '-', $movementType);
                                ?>

                                <span class="badge <?= e($badgeClass) ?>">
                                    <?= e(str_replace('_', ' ', $movementType)) ?>
                                </span>
                            </td>

                            <td>
                                <?= number_format((int)$activity['quantity']) ?>
                            </td>

                            <td>
                                <?= e($activity['reference_type'] ?? 'N/A') ?>
                            </td>

                            <td>
                                <?= e($activity['notes'] ?: '—') ?>
                            </td>

                            <td>
                                <?= date('d M Y, h:i A', strtotime($activity['created_at'])) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="empty">
                            No inventory history found.
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
