<?php require_once __DIR__ . '/../../includes/session.php'; require_once __DIR__ . '/../../includes/db.php';  // Admin protection if (!isset($\_SESSION['admin_id'])) {     header("Location: ../login.php");     exit; }  // Inventory statistics $totalAvailable = $pdo->query("     SELECT COALESCE(SUM(quantity_remaining), 0)     FROM inventory_batches ")->fetchColumn();  $totalReceived = $pdo->query("     SELECT COALESCE(SUM(quantity), 0)     FROM inventory_movements     WHERE movement_type = 'stock_in' ")->fetchColumn();  $totalSold = $pdo->query("     SELECT COALESCE(SUM(quantity), 0)     FROM inventory_movements     WHERE movement_type = 'stock_out' ")->fetchColumn();  $activeBatches = $pdo->query("     SELECT COUNT(\*)     FROM inventory_batches     WHERE status = 'active' ")->fetchColumn();  $finishedBatches = $pdo->query("     SELECT COUNT(\*)     FROM inventory_batches     WHERE status = 'finished' ")->fetchColumn();  // Products with low stock (10 units or less) $lowStock = $pdo->query("     SELECT         p.id,         p.name,         COALESCE(SUM(b.quantity_remaining), 0) AS available_stock     FROM products p     LEFT JOIN inventory_batches b ON p.id = b.product_id     GROUP BY p.id, p.name     HAVING available_stock <= 10     ORDER BY available_stock ASC     LIMIT 10 ")->fetchAll(PDO::FETCH_ASSOC);  // Recent inventory activities $recentActivities = $pdo->query("     SELECT         m.movement_type,         m.quantity,         m.notes,         m.created_at,         p.name AS product_name,         b.batch_code     FROM inventory_movements m     LEFT JOIN products p ON m.product_id = p.id     LEFT JOIN inventory_batches b ON m.batch_id = b.id     ORDER BY m.created_at DESC, m.id DESC     LIMIT 10 ")->fetchAll(PDO::FETCH_ASSOC);  function e($value) {     return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }  $adminName = $\_SESSION['admin_name'] ?? 'Administrator'; ?>

<!DOCTYPE html>

<html lang="en"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Inventory Overview | BayoGoal Enterprises</title>

<style> \* {     margin: 0;     padding: 0;     box-sizing: border-box; }  body {     font-family: Arial, sans-serif;     background: #071426;     color: #fff; }  .sidebar {     width: 250px;     min-height: 100vh;     background: #10233d;     position: fixed;     top: 0;     left: 0;     padding: 30px 18px;     border-right: 1px solid #203b5c; }  .brand {     text-align: center;     margin-bottom: 40px; }  .brand h2 {     font-size: 25px; }  .brand span {     color: #39FF14; }  .brand p {     color: #9ca3af;     font-size: 11px;     margin-top: 7px;     letter-spacing: 2px; }  .sidebar a {     display: block;     color: #d1d5db;     text-decoration: none;     padding: 14px 15px;     margin-bottom: 9px;     border-radius: 7px;     font-size: 14px; }  .sidebar a\:hover, .sidebar a.active {     background: #2563EB;     color: #fff; }  .submenu {     padding-left: 12px;     margin-bottom: 10px; }  .submenu a {     font-size: 13px;     padding: 11px 12px;     border-left: 2px solid #39FF14; }  .main {     margin-left: 250px;     padding: 35px; }  .topbar {     display: flex;     justify-content: space-between;     align-items: center;     gap: 15px;     flex-wrap: wrap;     margin-bottom: 35px; }  .topbar h1 {     font-size: 27px;     margin-bottom: 8px; }  .topbar p {     color: #9ca3af;     font-size: 14px; }  .topbar strong {     color: #39FF14; }  .back-link {     background: #2563EB;     color: #fff;     padding: 12px 20px;     text-decoration: none;     border-radius: 7px; }  .section-title {     margin-bottom: 20px; }  .section-title h2 {     font-size: 20px;     margin-bottom: 7px; }  .section-title p {     color: #9ca3af;     font-size: 13px; }  .cards {     display: grid;     grid-template-columns: repeat(3, minmax(0, 1fr));     gap: 20px;     margin-bottom: 35px; }  .card {     background: #10233d;     padding: 25px;     border-radius: 12px;     border-left: 4px solid #39FF14; }  .card h3 {     color: #9ca3af;     font-size: 14px;     font-weight: normal;     margin-bottom: 18px; }  .card p {     color: #39FF14;     font-size: 28px;     font-weight: bold; }  .panel {     background: #10233d;     padding: 25px;     border-radius: 12px;     border: 1px solid #203b5c;     margin-bottom: 25px; }  .panel-heading {     display: flex;     justify-content: space-between;     align-items: center;     gap: 15px;     flex-wrap: wrap;     margin-bottom: 20px; }  .panel-heading h2 {     font-size: 19px; }  .panel-heading a {     color: #39FF14;     text-decoration: none;     font-size: 13px; }  .table-wrapper {     width: 100%;     overflow-x: auto; }  table {     width: 100%;     border-collapse: collapse;     min-width: 550px; }  th, td {     padding: 15px;     text-align: left;     border-bottom: 1px solid #304968; }  th {     color: #39FF14;     font-size: 12px;     text-transform: uppercase; }  td {     color: #e5e7eb;     font-size: 14px; }  .badge {     display: inline-block;     padding: 6px 10px;     border-radius: 20px;     font-size: 11px;     font-weight: bold;     text-transform: uppercase; }  .stock-in {     background: rgba(57,255,20,.12);     color: #39FF14; }  .stock-out {     background: rgba(239,68,68,.15);     color: #f87171; }  .low {     color: #f87171;     font-weight: bold; }  .empty {     color: #9ca3af;     text-align: center;     padding: 25px; }  .footer {     text-align: center;     color: #64748b;     padding: 20px;     font-size: 13px; }  @media(max-width: 1100px) {     .cards {         grid-template-columns: repeat(2, minmax(0, 1fr));     } }  @media(max-width: 700px) {     .sidebar {         width: 100%;         min-height: auto;         position: relative;         padding: 20px 15px;     }      .brand {         margin-bottom: 20px;     }      .sidebar a {         display: inline-block;         padding: 11px;         margin: 4px 2px;     }      .submenu {         padding-left: 0;     }      .main {         margin-left: 0;         padding: 22px 15px;     }      .cards {         grid-template-columns: 1fr;     }      .panel {         padding: 18px;     } } \</style>

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

    <a href="index.php" class="active">Inventory Overview</a>

    <div class="submenu">
        <a href="stock-in.php">Stock In</a>
        <a href="stock-out.php">Stock Out</a>
        <a href="batches.php">Batches</a>
        <a href="history.php">History</a>
    </div>

    <a href="../orders.php">Manage Orders</a>
    <a href="../customers.php">Manage Customers</a>
    <a href="../../index.php" target="_blank">View Website</a>
    <a href="../logout.php">Logout</a>
</nav>


\</aside>

\<main class="main">


<div class="topbar">
    <div>
        <h1>Inventory Overview</h1>
        <p>Welcome, <strong><?= e($adminName) ?></strong>. Here's your stock summary.</p>
    </div>

    <a href="../dashboard.php" class="back-link">← Admin Dashboard</a>
</div>

<div class="section-title">
    <h2>Inventory Statistics</h2>
    <p>Monitor goods received, available stock and completed batches.</p>
</div>

<div class="cards">

    <div class="card">
        <h3>Available Stock</h3>
        <p><?= number_format((int)$totalAvailable) ?></p>
    </div>

    <div class="card">
        <h3>Total Goods Received</h3>
        <p><?= number_format((int)$totalReceived) ?></p>
    </div>

    <div class="card">
        <h3>Total Goods Sold</h3>
        <p><?= number_format((int)$totalSold) ?></p>
    </div>

    <div class="card">
        <h3>Active Batches</h3>
        <p><?= number_format((int)$activeBatches) ?></p>
    </div>

    <div class="card">
        <h3>Finished Batches</h3>
        <p><?= number_format((int)$finishedBatches) ?></p>
    </div>

</div>

<section class="panel">

    <div class="panel-heading">
        <h2>⚠ Low Stock Products</h2>
        <a href="batches.php">View Batches →</a>
    </div>

    <?php if ($lowStock): ?>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Product Name</th>
                    <th>Available Quantity</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($lowStock as $product): ?>
                <tr>
                    <td>#<?= (int)$product['id'] ?></td>
                    <td><?= e($product['name']) ?></td>
                    <td class="low">
                        <?= number_format((int)$product['available_stock']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php else: ?>
        <p class="empty">No low-stock products found.</p>
    <?php endif; ?>

</section>

<section class="panel">

    <div class="panel-heading">
        <h2>Recent Inventory Activities</h2>
        <a href="history.php">View Full History →</a>
    </div>

    <?php if ($recentActivities): ?>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Batch</th>
                    <th>Activity</th>
                    <th>Quantity</th>
                    <th>Date</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($recentActivities as $activity): ?>
                <tr>
                    <td><?= e($activity['product_name'] ?? 'Unknown Product') ?></td>
                    <td><?= e($activity['batch_code'] ?? 'N/A') ?></td>
                    <td>
                        <span class="badge <?= $activity['movement_type'] === 'stock_in' ? 'stock-in' : 'stock-out' ?>">
                            <?= e(str_replace('_', ' ', $activity['movement_type'])) ?>
                        </span>
                    </td>
                    <td><?= number_format((int)$activity['quantity']) ?></td>
                    <td><?= date('d M Y, h:i A', strtotime($activity['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php else: ?>
        <p class="empty">No inventory activities yet.</p>
    <?php endif; ?>

</section>

<div class="footer">
    &copy; <?= date('Y') ?> BayoGoal Enterprises. Inventory Management.
</div>


</main>

</body>
</html>
