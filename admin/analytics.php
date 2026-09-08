<?php
include '../includes/session_check.php';
checkRole('admin');
include '../configure.php';

// --- Summary numbers (Cancelled orders excluded from revenue) ---
$summary = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS order_count, COALESCE(SUM(Total), 0) AS revenue
     FROM orders WHERE Status != 'Cancelled'"
));
$order_count = (int)$summary['order_count'];
$revenue = (float)$summary['revenue'];
$avg_order_value = $order_count > 0 ? $revenue / $order_count : 0;

$cancelled_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM orders WHERE Status = 'Cancelled'"
))['c'];

// --- Orders by status ---
$status_rows = mysqli_query($conn, "SELECT Status, COUNT(*) AS c FROM orders GROUP BY Status");

// --- Sales for the last 7 days ---
$daily_rows = mysqli_query($conn,
    "SELECT DATE(Order_Time) AS day, COUNT(*) AS orders, COALESCE(SUM(Total), 0) AS revenue
     FROM orders
     WHERE Status != 'Cancelled' AND Order_Time >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(Order_Time)
     ORDER BY day ASC"
);
$daily = [];
while ($r = mysqli_fetch_assoc($daily_rows)) {
    $daily[$r['day']] = $r;
}
// Fill in any days with zero orders so the chart has a full 7-day run
$daily_filled = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i day"));
    $daily_filled[$day] = $daily[$day] ?? ['day' => $day, 'orders' => 0, 'revenue' => 0];
}
$max_daily_revenue = max(array_map(fn($d) => (float)$d['revenue'], $daily_filled)) ?: 1;

// --- Top 5 items by quantity sold ---
$top_items = mysqli_query($conn,
    "SELECT f.Name, SUM(oi.Quantity) AS total_qty, SUM(oi.Quantity * oi.Price) AS total_revenue
     FROM order_items oi
     JOIN food_items f ON oi.Item_ID = f.Item_ID
     JOIN orders o ON oi.Order_ID = o.ID
     WHERE o.Status != 'Cancelled'
     GROUP BY oi.Item_ID
     ORDER BY total_qty DESC
     LIMIT 5"
);
$top_items_rows = [];
while ($r = mysqli_fetch_assoc($top_items)) {
    $top_items_rows[] = $r;
}
$max_item_qty = !empty($top_items_rows) ? max(array_column($top_items_rows, 'total_qty')) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics - Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
<?php include '../includes/admin_header.php'; ?>

<div class="admin-container">
    <h2>Analytics</h2>

    <div style="display:flex; gap:16px; flex-wrap:wrap; justify-content:center; margin-bottom:24px;">
        <div class="admin-form-container" style="flex:1; min-width:180px; text-align:center;">
            <h3 style="margin:0;">$<?= number_format($revenue, 2) ?></h3>
            <p style="color:#666; margin:4px 0 0;">Total Revenue</p>
        </div>
        <div class="admin-form-container" style="flex:1; min-width:180px; text-align:center;">
            <h3 style="margin:0;"><?= $order_count ?></h3>
            <p style="color:#666; margin:4px 0 0;">Orders (excl. cancelled)</p>
        </div>
        <div class="admin-form-container" style="flex:1; min-width:180px; text-align:center;">
            <h3 style="margin:0;">$<?= number_format($avg_order_value, 2) ?></h3>
            <p style="color:#666; margin:4px 0 0;">Average Order Value</p>
        </div>
        <div class="admin-form-container" style="flex:1; min-width:180px; text-align:center;">
            <h3 style="margin:0;"><?= (int)$cancelled_count ?></h3>
            <p style="color:#666; margin:4px 0 0;">Cancelled Orders</p>
        </div>
    </div>

    <div class="admin-form-container">
        <h3>Orders by Status</h3>
        <table class="admin-orders-table">
            <thead><tr><th>Status</th><th>Count</th></tr></thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($status_rows)): ?>
                <tr><td><?= htmlspecialchars($row['Status']) ?></td><td><?= (int)$row['c'] ?></td></tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-form-container">
        <h3>Last 7 Days</h3>
        <table class="admin-orders-table">
            <thead><tr><th>Date</th><th>Orders</th><th>Revenue</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($daily_filled as $d): ?>
                <?php $bar_pct = round(((float)$d['revenue'] / $max_daily_revenue) * 100); ?>
                <tr>
                    <td><?= htmlspecialchars($d['day']) ?></td>
                    <td><?= (int)$d['orders'] ?></td>
                    <td>$<?= number_format($d['revenue'], 2) ?></td>
                    <td style="width:40%;">
                        <div style="background:#eee; border-radius:4px; overflow:hidden;">
                            <div style="background:#3498db; color:#fff; font-size:0.8em; padding:2px 6px; width:<?= max($bar_pct, 4) ?>%; white-space:nowrap;">&nbsp;</div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-form-container">
        <h3>Top 5 Items</h3>
        <?php if (empty($top_items_rows)): ?>
            <p style="color:#666;">No sales data yet.</p>
        <?php else: ?>
        <table class="admin-orders-table">
            <thead><tr><th>Item</th><th>Qty Sold</th><th>Revenue</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($top_items_rows as $item): ?>
                <?php $bar_pct = round(((int)$item['total_qty'] / $max_item_qty) * 100); ?>
                <tr>
                    <td><?= htmlspecialchars($item['Name']) ?></td>
                    <td><?= (int)$item['total_qty'] ?></td>
                    <td>$<?= number_format($item['total_revenue'], 2) ?></td>
                    <td style="width:40%;">
                        <div style="background:#eee; border-radius:4px; overflow:hidden;">
                            <div style="background:#2ecc71; color:#fff; font-size:0.8em; padding:2px 6px; width:<?= max($bar_pct, 4) ?>%; white-space:nowrap;">&nbsp;</div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
