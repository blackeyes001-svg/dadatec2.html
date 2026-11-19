\<?php
require_once \_\_DIR\_\_ . '/../../includes/session.php';
require_once \_\_DIR\_\_ . '/../../includes/db.php';

// Admin protection
if (!isset($\_SESSION['admin_id'])) {
&#x20;   header("Location: ../login.php");
&#x20;   exit;
}

function e($value) {
&#x20;   return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$adminName = $\_SESSION['admin_name'] ?? 'Administrator';

// Filters
$search = trim($\_GET['search'] ?? '');
$type = $\_GET['type'] ?? '';
$fromDate = $\_GET['from'] ?? '';
$toDate = $\_GET['to'] ?? '';

$sql = "
&#x20;   SELECT
&#x20;       m.\*,
&#x20;       p.name AS product_name,
&#x20;       b.batch_code
&#x20;   FROM inventory_movements m
&#x20;   LEFT JOIN products p ON m.product_id = p.id
&#x20;   LEFT JOIN inventory_batches b ON m.batch_id = b.id
&#x20;   WHERE 1=1
";

$params = [];

if ($search !== '') {
&#x20;   $sql .= " AND (
&#x20;       p.name LIKE ?
&#x20;       OR b.batch_code LIKE ?
&#x20;       OR m.notes LIKE ?
&#x20;       OR m.reference_type LIKE ?
&#x20;   )";

&#x20;   $term = "%$search%";

&#x20;   $params[] = $term;
&#x20;   $params[] = $term;
&#x20;   $params[] = $term;
&#x20;   $params[] = $term;
}

if (in_array($type, ['stock_in', 'stock_out', 'adjustment'], true)) {
&#x20;   $sql .= " AND m.movement_type = ?";
&#x20;   $params[] = $type;
}

if ($fromDate !== '') {
&#x20;   $sql .= " AND DATE(m.created_at) >= ?";
&#x20;   $params[] = $fromDate;
}

if ($toDate !== '') {
&#x20;   $sql .= " AND DATE(m.created_at) <= ?";
&#x20;   $params[] = $toDate;
}

$sql .= " ORDER BY m.created_at DESC, m.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary statistics
$totalActivities = $pdo->query("
&#x20;   SELECT COUNT(\*) FROM inventory_movements
")->fetchColumn();

$totalStockIn = $pdo->query("
&#x20;   SELECT COALESCE(SUM(quantity), 0)
&#x20;   FROM inventory_movements
&#x20;   WHERE movement_type = 'stock_in'
")->fetchColumn();

$totalStockOut = $pdo->query("
&#x20;   SELECT COALESCE(SUM(quantity), 0)
&#x20;   FROM inventory_movements
&#x20;   WHERE movement_type = 'stock_out'
")->fetchColumn();
?>

\<!DOCTYPE html>

\<html lang="en">
\<head>
\<meta charset="UTF-8">
\<meta name="viewport" content="width=device-width, initial-scale=1.0">

\<title>Inventory History | BayoGoal Enterprises\</title>

\<style>
\* {
&#x20;   margin: 0;
&#x20;   padding: 0;
&#x20;   box-sizing: border-box;
}

body {
&#x20;   font-family: Arial, sans-serif;
&#x20;   background: #071426;
&#x20;   color: #fff;
}

.sidebar {
&#x20;   width: 250px;
&#x20;   min-height: 100vh;
&#x20;   background: #10233d;
&#x20;   position: fixed;
&#x20;   padding: 30px 18px;
&#x20;   border-right: 1px solid #203b5c;
}

.brand {
&#x20;   text-align: center;
&#x20;   margin-bottom: 40px;
}

.brand h2 {
&#x20;   font-size: 25px;
}

.brand span {
&#x20;   color: #39FF14;
}

.brand p {
&#x20;   color: #9ca3af;
&#x20;   font-size: 11px;
&#x20;   margin-top: 7px;
&#x20;   letter-spacing: 2px;
}

.sidebar a {
&#x20;   display: block;
&#x20;   color: #d1d5db;
&#x20;   text-decoration: none;
&#x20;   padding: 14px 15px;
&#x20;   margin-bottom: 9px;
&#x20;   border-radius: 7px;
&#x20;   font-size: 14px;
}

.sidebar a\:hover,
.sidebar a.active {
&#x20;   background: #2563EB;
&#x20;   color: #fff;
}

.submenu {
&#x20;   padding-left: 12px;
&#x20;   margin-bottom: 10px;
}

.submenu a {
&#x20;   font-size: 13px;
&#x20;   padding: 11px 12px;
&#x20;   border-left: 2px solid #39FF14;
}

.main {
&#x20;   margin-left: 250px;
&#x20;   padding: 35px;
}

.topbar {
&#x20;   display: flex;
&#x20;   justify-content: space-between;
&#x20;   align-items: center;
&#x20;   gap: 15px;
&#x20;   flex-wrap: wrap;
&#x20;   margin-bottom: 30px;
}

.topbar h1 {
&#x20;   font-size: 27px;
&#x20;   margin-bottom: 8px;
}

.topbar p {
&#x20;   color: #9ca3af;
&#x20;   font-size: 14px;
}

.topbar strong {
&#x20;   color: #39FF14;
}

.back-link {
&#x20;   background: #2563EB;
&#x20;   color: #fff;
&#x20;   padding: 12px 20px;
&#x20;   text-decoration: none;
&#x20;   border-radius: 7px;
}

.cards {
&#x20;   display: grid;
&#x20;   grid-template-columns: repeat(3, minmax(0, 1fr));
&#x20;   gap: 20px;
&#x20;   margin-bottom: 30px;
}

.card {
&#x20;   background: #10233d;
&#x20;   padding: 23px;
&#x20;   border-radius: 10px;
&#x20;   border-left: 4px solid #39FF14;
}

.card h3 {
&#x20;   color: #9ca3af;
&#x20;   font-size: 13px;
&#x20;   font-weight: normal;
&#x20;   margin-bottom: 15px;
}

.card p {
&#x20;   color: #39FF14;
&#x20;   font-size: 27px;
&#x20;   font-weight: bold;
}

.panel {
&#x20;   background: #10233d;
&#x20;   border: 1px solid #203b5c;
&#x20;   border-radius: 12px;
&#x20;   padding: 25px;
}

.panel-heading {
&#x20;   margin-bottom: 22px;
}

.panel-heading h2 {
&#x20;   font-size: 20px;
&#x20;   margin-bottom: 7px;
}

.panel-heading p {
&#x20;   color: #9ca3af;
&#x20;   font-size: 13px;
}

.filters {
&#x20;   display: flex;
&#x20;   gap: 12px;
&#x20;   flex-wrap: wrap;
&#x20;   margin-bottom: 22px;
}

.filters input,
.filters select {
&#x20;   padding: 12px;
&#x20;   background: #071426;
&#x20;   border: 1px solid #304968;
&#x20;   color: #fff;
&#x20;   border-radius: 7px;
&#x20;   outline: none;
}

.filters input[name="search"] {
&#x20;   flex: 1;
&#x20;   min-width: 200px;
}

.filters button {
&#x20;   padding: 12px 20px;
&#x20;   background: #2563EB;
&#x20;   border: none;
&#x20;   color: #fff;
&#x20;   border-radius: 7px;
&#x20;   cursor: pointer;
}

.reset {
&#x20;   padding: 12px 18px;
&#x20;   background: #334155;
&#x20;   color: #fff;
&#x20;   border-radius: 7px;
&#x20;   text-decoration: none;
&#x20;   font-size: 13px;
}

.table-wrapper {
&#x20;   width: 100%;
&#x20;   overflow-x: auto;
}

table {
&#x20;   width: 100%;
&#x20;   border-collapse: collapse;
&#x20;   min-width: 1050px;
}

th, td {
&#x20;   padding: 15px 12px;
&#x20;   text-align: left;
&#x20;   border-bottom: 1px solid #304968;
}

th {
&#x20;   color: #39FF14;
&#x20;   font-size: 12px;
&#x20;   text-transform: uppercase;
}

td {
&#x20;   color: #e5e7eb;
&#x20;   font-size: 13px;
}

.product-name {
&#x20;   font-weight: bold;
}

.batch-code {
&#x20;   color: #60a5fa;
}

.badge {
&#x20;   display: inline-block;
&#x20;   padding: 6px 10px;
&#x20;   border-radius: 20px;
&#x20;   font-size: 11px;
&#x20;   font-weight: bold;
&#x20;   text-transform: uppercase;
}

.stock-in {
&#x20;   background: rgba(57,255,20,.12);
&#x20;   color: #39FF14;
}

.stock-out {
&#x20;   background: rgba(239,68,68,.15);
&#x20;   color: #f87171;
}

.adjustment {
&#x20;   background: rgba(37,99,235,.15);
&#x20;   color: #60a5fa;
}

.empty {
&#x20;   text-align: center;
&#x20;   padding: 35px;
&#x20;   color: #9ca3af;
}

@media(max-width: 1000px) {
&#x20;   .cards {
&#x20;       grid-template-columns: repeat(2, 1fr);
&#x20;   }
}

@media(max-width: 700px) {
&#x20;   .sidebar {
&#x20;       width: 100%;
&#x20;       min-height: auto;
&#x20;       position: relative;
&#x20;       padding: 20px 15px;
&#x20;   }

&#x20;   .brand {
&#x20;       margin-bottom: 20px;
&#x20;   }

&#x20;   .sidebar a {
&#x20;       display: inline-block;
&#x20;       padding: 11px;
&#x20;       margin: 4px 2px;
&#x20;   }

&#x20;   .submenu {
&#x20;       padding-left: 0;
&#x20;   }

&#x20;   .main {
&#x20;       margin-left: 0;
&#x20;       padding: 22px 15px;
&#x20;   }

&#x20;   .cards {
&#x20;       grid-template-columns: 1fr;
&#x20;   }

&#x20;   .panel {
&#x20;       padding: 18px;
&#x20;   }
}
\</style>

\</head>

\<body>

\<aside class="sidebar">

```
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
```

\</aside>

\<main class="main">

```
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
```

\</main>

\</body>
\</html>
