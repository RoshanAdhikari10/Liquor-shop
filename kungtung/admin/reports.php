<?php
session_start();
include("../config/database.php");

$pageTitle = "Reports";
$adminName = "Administrator";

include("includes/header.php");
include("includes/sidebar.php");

/* =========================================================
   DATE FILTER
========================================================= */

$fromDate = isset($_GET['from']) && !empty($_GET['from'])
    ? $_GET['from']
    : date('Y-m-01');

$toDate = isset($_GET['to']) && !empty($_GET['to'])
    ? $_GET['to']
    : date('Y-m-d');


/* =========================================================
   BASIC SALES STATISTICS
========================================================= */

$statsQuery = mysqli_query($conn, "
    SELECT
        COUNT(*) AS total_orders,

        COALESCE(SUM(
            CASE
                WHEN status = 'Completed' THEN total
                ELSE 0
            END
        ), 0) AS total_sales,

        SUM(
            CASE
                WHEN status = 'Completed' THEN 1
                ELSE 0
            END
        ) AS completed_orders,

        SUM(
            CASE
                WHEN status = 'Cancelled' THEN 1
                ELSE 0
            END
        ) AS cancelled_orders,

        SUM(
            CASE
                WHEN status = 'Pending' THEN 1
                ELSE 0
            END
        ) AS pending_orders,

        SUM(
            CASE
                WHEN status = 'Processing' THEN 1
                ELSE 0
            END
        ) AS processing_orders,

        SUM(
            CASE
                WHEN status = 'Out for Delivery' THEN 1
                ELSE 0
            END
        ) AS delivery_orders

    FROM orders

    WHERE DATE(created_at)
    BETWEEN '$fromDate' AND '$toDate'
");

$stats = mysqli_fetch_assoc($statsQuery);

$totalOrders = (int)$stats['total_orders'];
$totalSales = (float)$stats['total_sales'];
$completedOrders = (int)$stats['completed_orders'];
$cancelledOrders = (int)$stats['cancelled_orders'];
$pendingOrders = (int)$stats['pending_orders'];
$processingOrders = (int)$stats['processing_orders'];
$deliveryOrders = (int)$stats['delivery_orders'];

$averageOrder = $completedOrders > 0
    ? $totalSales / $completedOrders
    : 0;


/* =========================================================
   SALES BY DATE
========================================================= */

$salesByDate = [];

$salesDateQuery = mysqli_query($conn, "
    SELECT
        DATE(created_at) AS sale_date,
        COALESCE(SUM(total), 0) AS sales,
        COUNT(*) AS orders

    FROM orders

    WHERE status = 'Completed'
    AND DATE(created_at)
    BETWEEN '$fromDate' AND '$toDate'

    GROUP BY DATE(created_at)

    ORDER BY sale_date ASC
");

while ($row = mysqli_fetch_assoc($salesDateQuery)) {
    $salesByDate[] = $row;
}


/* =========================================================
   TOP SELLING PRODUCTS
========================================================= */

$topProductsQuery = mysqli_query($conn, "
    SELECT
        p.name,
        p.brand,
        SUM(oi.quantity) AS units_sold,
        SUM(oi.quantity * oi.price) AS revenue

    FROM order_items oi

    INNER JOIN orders o
        ON oi.order_id = o.id

    INNER JOIN products p
        ON oi.product_id = p.id

    WHERE o.status = 'Completed'

    AND DATE(o.created_at)
    BETWEEN '$fromDate' AND '$toDate'

    GROUP BY p.id

    ORDER BY units_sold DESC

    LIMIT 10
");


/* =========================================================
   CATEGORY PERFORMANCE
========================================================= */

$categoryQuery = mysqli_query($conn, "
    SELECT
        c.name AS category_name,
        SUM(oi.quantity) AS units_sold,
        SUM(oi.quantity * oi.price) AS revenue

    FROM order_items oi

    INNER JOIN orders o
        ON oi.order_id = o.id

    INNER JOIN products p
        ON oi.product_id = p.id

    INNER JOIN categories c
        ON p.category_id = c.id

    WHERE o.status = 'Completed'

    AND DATE(o.created_at)
    BETWEEN '$fromDate' AND '$toDate'

    GROUP BY c.id

    ORDER BY revenue DESC
");


/* =========================================================
   LOW STOCK PRODUCTS - FROM PRODUCT VARIANTS
========================================================= */

$lowStockQuery = mysqli_query($conn, "
    SELECT 
        p.id AS product_id,
        p.name AS product_name,
        p.brand,
        pv.id AS variant_id,
        pv.volume,
        pv.stock,
        p.status
    FROM products p
    INNER JOIN product_variants pv ON p.id = pv.product_id
    WHERE pv.stock <= 10
    ORDER BY pv.stock ASC
    LIMIT 10
");


/* =========================================================
   ORDER STATUS DATA
========================================================= */

$statusQuery = mysqli_query($conn, "
    SELECT
        status,
        COUNT(*) AS total

    FROM orders

    WHERE DATE(created_at)
    BETWEEN '$fromDate' AND '$toDate'

    GROUP BY status
");

$orderStatuses = [];

while ($row = mysqli_fetch_assoc($statusQuery)) {
    $orderStatuses[$row['status']] = (int)$row['total'];
}

?>

<div class="main">

<?php include("includes/navbar.php"); ?>

<div class="content">

<div class="container-fluid">

    <div class="header">

        <div>
            <h2><i class="fa fa-chart-bar"></i> Reports</h2>
            <p class="text-muted">Sales analytics and performance metrics</p>
        </div>

        <div class="header-actions">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fa fa-print"></i> Print Report
            </button>
            <button onclick="location.reload()" class="btn btn-outline-secondary">
                <i class="fa fa-sync"></i> Refresh
            </button>
        </div>

    </div>

    <div class="reports-container">

        <!-- =====================================================
             FILTER
        ====================================================== -->

        <div class="report-filter">

            <form method="GET">

                <div class="filter-group">

                    <label><i class="fa fa-calendar-alt"></i> From Date</label>

                    <input
                        type="date"
                        name="from"
                        value="<?= htmlspecialchars($fromDate) ?>"
                        required>

                </div>

                <div class="filter-group">

                    <label><i class="fa fa-calendar-alt"></i> To Date</label>

                    <input
                        type="date"
                        name="to"
                        value="<?= htmlspecialchars($toDate) ?>"
                        required>

                </div>

                <div class="filter-group">

                    <button type="submit">
                        <i class="fa fa-filter"></i>
                        Generate Report
                    </button>

                </div>

                <div class="filter-group ml-auto">
                    <span class="date-range-badge">
                        <i class="fa fa-clock"></i>
                        <?= date('M d, Y', strtotime($fromDate)) ?> - <?= date('M d, Y', strtotime($toDate)) ?>
                    </span>
                </div>

            </form>

        </div>



        <!-- =====================================================
             STATISTICS
        ====================================================== -->

        <div class="report-stats">

            <div class="report-stat-card" style="border-left: 4px solid #28a745;">
                <div class="stat-icon" style="color: #28a745;">
                    <i class="fa fa-money-bill-wave"></i>
                </div>
                <div class="stat-title">Total Sales</div>
                <div class="stat-value">Rs. <?= number_format($totalSales, 2) ?></div>
                <div class="stat-change positive">
                    <i class="fa fa-arrow-up"></i> +12.5%
                </div>
            </div>

            <div class="report-stat-card" style="border-left: 4px solid #007bff;">
                <div class="stat-icon" style="color: #007bff;">
                    <i class="fa fa-shopping-cart"></i>
                </div>
                <div class="stat-title">Total Orders</div>
                <div class="stat-value"><?= number_format($totalOrders) ?></div>
                <div class="stat-change positive">
                    <i class="fa fa-arrow-up"></i> +8.3%
                </div>
            </div>

            <div class="report-stat-card" style="border-left: 4px solid #17a2b8;">
                <div class="stat-icon" style="color: #17a2b8;">
                    <i class="fa fa-check-circle"></i>
                </div>
                <div class="stat-title">Completed Orders</div>
                <div class="stat-value"><?= number_format($completedOrders) ?></div>
                <div class="stat-change neutral">
                    <i class="fa fa-minus"></i> <?= $totalOrders > 0 ? number_format(($completedOrders/$totalOrders)*100, 1) : 0 ?>% of total
                </div>
            </div>

            <div class="report-stat-card" style="border-left: 4px solid #dc3545;">
                <div class="stat-icon" style="color: #dc3545;">
                    <i class="fa fa-ban"></i>
                </div>
                <div class="stat-title">Cancelled Orders</div>
                <div class="stat-value"><?= number_format($cancelledOrders) ?></div>
                <div class="stat-change negative">
                    <i class="fa fa-arrow-down"></i> <?= $totalOrders > 0 ? number_format(($cancelledOrders/$totalOrders)*100, 1) : 0 ?>% of total
                </div>
            </div>

            <div class="report-stat-card" style="border-left: 4px solid #ffc107;">
                <div class="stat-icon" style="color: #ffc107;">
                    <i class="fa fa-receipt"></i>
                </div>
                <div class="stat-title">Average Order</div>
                <div class="stat-value">Rs. <?= number_format($averageOrder, 2) ?></div>
                <div class="stat-change positive">
                    <i class="fa fa-arrow-up"></i> +5.2%
                </div>
            </div>

        </div>



        <!-- =====================================================
             CHART + ORDER STATUS
        ====================================================== -->

        <div class="report-grid">

            <!-- SALES CHART -->

            <div class="report-card">

                <h5>
                    <i class="fa fa-chart-line"></i>
                    Sales Overview
                </h5>

                <div class="chart-container">

                    <canvas id="salesChart"></canvas>

                </div>

            </div>

            <!-- ORDER STATUS -->

            <div class="report-card">

                <h5>
                    <i class="fa fa-chart-pie"></i>
                    Order Status
                </h5>

                <?php

                $statusTotal = array_sum($orderStatuses);

                $statuses = [
                    "Pending" => "Pending",
                    "Processing" => "Processing",
                    "Out for Delivery" => "Out for Delivery",
                    "Completed" => "Completed",
                    "Cancelled" => "Cancelled"
                ];

                $statusColors = [
                    "Pending" => "#ffc107",
                    "Processing" => "#17a2b8",
                    "Out for Delivery" => "#007bff",
                    "Completed" => "#28a745",
                    "Cancelled" => "#dc3545"
                ];

                ?>

                <div class="status-list">

                    <?php foreach ($statuses as $statusKey => $statusLabel): ?>

                        <?php

                        $statusValue =
                            $orderStatuses[$statusKey] ?? 0;

                        $percentage =
                            $statusTotal > 0
                            ? ($statusValue / $statusTotal) * 100
                            : 0;

                        $color = $statusColors[$statusKey] ?? '#6c757d';

                        ?>

                        <div>

                            <div class="status-row">

                                <span class="status-name">
                                    <span class="status-dot" style="background: <?= $color ?>;"></span>
                                    <?= htmlspecialchars($statusLabel) ?>
                                </span>

                                <span class="status-number">
                                    <?= $statusValue ?>
                                    <span class="status-percent">(<?= number_format($percentage, 1) ?>%)</span>
                                </span>

                            </div>

                            <div class="status-bar">

                                <div
                                    class="status-progress"
                                    style="width: <?= $percentage ?>%; background: <?= $color ?>;">
                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>



        <!-- =====================================================
             TOP PRODUCTS + CATEGORY
        ====================================================== -->

        <div class="report-grid">

            <!-- TOP PRODUCTS -->

            <div class="report-card">

                <h5>
                    <i class="fa fa-trophy"></i>
                    Top Selling Products
                </h5>

                <div style="overflow-x:auto;">

                    <table class="report-table">

                        <thead>

                            <tr>

                                <th>#</th>
                                <th>Product</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if ($topProductsQuery && mysqli_num_rows($topProductsQuery) > 0): ?>

                                <?php 
                                $rank = 1;
                                while ($product = mysqli_fetch_assoc($topProductsQuery)): 
                                ?>

                                    <tr>

                                        <td>
                                            <span class="rank-badge">#<?= $rank ?></span>
                                        </td>

                                        <td>

                                            <div class="product-info">
                                                <div>
                                                    <strong>
                                                        <?= htmlspecialchars($product['name']) ?>
                                                    </strong>

                                                    <?php if (!empty($product['brand'])): ?>

                                                        <div class="small text-muted">
                                                            <i class="fa fa-building"></i> <?= htmlspecialchars($product['brand']) ?>
                                                        </div>

                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                        </td>

                                        <td>
                                            <span class="units-badge">
                                                <i class="fa fa-box"></i> <?= number_format($product['units_sold']) ?>
                                            </span>
                                        </td>

                                        <td class="revenue-cell">
                                            <i class="fa fa-money-bill"></i> Rs. <?= number_format($product['revenue'], 2) ?>
                                        </td>

                                    </tr>

                                <?php 
                                $rank++;
                                endwhile; 
                                ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="4" class="text-center text-muted">
                                        <i class="fa fa-info-circle"></i> No sales data available
                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>



            <!-- CATEGORY -->

            <div class="report-card">

                <h5>
                    <i class="fa fa-layer-group"></i>
                    Category Performance
                </h5>

                <div style="overflow-x:auto;">

                    <table class="report-table">

                        <thead>

                            <tr>

                                <th>Category</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                                <th>% of Total</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if ($categoryQuery && mysqli_num_rows($categoryQuery) > 0): 

                                // Calculate total revenue for percentage
                                $totalRevenue = 0;
                                $categoriesData = [];
                                while ($cat = mysqli_fetch_assoc($categoryQuery)) {
                                    $categoriesData[] = $cat;
                                    $totalRevenue += $cat['revenue'];
                                }
                            ?>

                                <?php foreach ($categoriesData as $category): ?>

                                    <tr>

                                        <td>
                                            <strong>
                                                <i class="fa fa-tag" style="color: #C89B3C;"></i>
                                                <?= htmlspecialchars(
                                                    $category['category_name']
                                                ) ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <span class="units-badge">
                                                <i class="fa fa-box"></i> <?= number_format(
                                                    $category['units_sold']
                                                ) ?>
                                            </span>
                                        </td>

                                        <td class="revenue-cell">
                                            <i class="fa fa-money-bill"></i> Rs. <?= number_format(
                                                $category['revenue'],
                                                2
                                            ) ?>
                                        </td>

                                        <td>
                                            <span class="percentage-badge">
                                                <?= $totalRevenue > 0 ? number_format(($category['revenue']/$totalRevenue)*100, 1) : 0 ?>%
                                            </span>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="4"
                                        class="text-center text-muted">

                                        <i class="fa fa-info-circle"></i> No sales data available

                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>



        <!-- =====================================================
             LOW STOCK - FROM PRODUCT VARIANTS
        ====================================================== -->

        <div class="report-card">

            <h5>

                <i class="fa fa-exclamation-triangle"></i>

                Low Stock Products (By Variant)

            </h5>

            <div style="overflow-x:auto;">

                <table class="report-table">

                    <thead>

                        <tr>

                            <th>Product</th>
                            <th>Brand</th>
                            <th>Variant</th>
                            <th>Stock</th>
                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if ($lowStockQuery && mysqli_num_rows($lowStockQuery) > 0): ?>

                            <?php while ($product = mysqli_fetch_assoc($lowStockQuery)): ?>

                                <?php

                                $stock = (int)$product['stock'];

                                if ($stock <= 0) {

                                    $stockClass = "stock-low";
                                    $stockStatus = "Out of Stock";
                                    $stockIcon = "fa-times-circle";

                                } elseif ($stock <= 5) {

                                    $stockClass = "stock-critical";
                                    $stockStatus = "Critical";
                                    $stockIcon = "fa-exclamation-circle";

                                } elseif ($stock <= 10) {

                                    $stockClass = "stock-warning";
                                    $stockStatus = "Low Stock";
                                    $stockIcon = "fa-exclamation-triangle";

                                } else {

                                    $stockClass = "stock-good";
                                    $stockStatus = "In Stock";
                                    $stockIcon = "fa-check-circle";

                                }

                                ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars(
                                                $product['product_name']
                                            ) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $product['brand'] ?? 'N/A'
                                        ) ?>
                                    </td>

                                    <td>
                                        <span class="variant-badge">
                                            <i class="fa fa-flask"></i>
                                            <?= htmlspecialchars($product['volume']) ?>
                                        </span>
                                    </td>

                                    <td class="<?= $stockClass ?>">

                                        <i class="fa <?= $stockIcon ?>"></i>
                                        <?= number_format($stock) ?>

                                    </td>

                                    <td>
                                        <span class="stock-status-badge <?= $stockClass ?>">
                                            <?= $stockStatus ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="text-center text-muted">

                                    <i class="fa fa-check-circle" style="color: #28a745;"></i>
                                    No low-stock products found

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</div>
</div>

<style>

/* =========================================================
   REPORTS - ENHANCED STYLES
========================================================= */

:root {
    --primary-gold: #C89B3C;
    --primary-gold-dark: #b48830;
    --primary-gold-light: rgba(200, 155, 60, 0.1);
}

.reports-container {
    padding: 0;
    margin-top: 0;
}

/* HEADER ACTIONS */
.header-actions {
    display: flex;
    gap: 10px;
}

.header-actions .btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
}

/* FILTER */
.report-filter {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,.08);
    margin-bottom: 25px;
    border: 1px solid #f0f0f0;
}

.report-filter form {
    display: flex;
    align-items: end;
    gap: 15px;
    flex-wrap: wrap;
}

.report-filter .filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.report-filter .filter-group.ml-auto {
    margin-left: auto;
}

.report-filter label {
    font-size: 13px;
    font-weight: 600;
    color: #555;
}

.report-filter label i {
    margin-right: 5px;
    color: var(--primary-gold);
}

.report-filter input {
    height: 42px;
    border: 2px solid #e8e8e8;
    border-radius: 8px;
    padding: 0 14px;
    min-width: 200px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #fafafa;
}

.report-filter input:focus {
    border-color: var(--primary-gold);
    outline: none;
    box-shadow: 0 0 0 4px var(--primary-gold-light);
    background: #fff;
}

.report-filter button {
    height: 42px;
    border: none;
    border-radius: 8px;
    padding: 0 30px;
    background: var(--primary-gold);
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
}

.report-filter button:hover {
    background: var(--primary-gold-dark);
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(200, 155, 60, 0.4);
}

.report-filter button i {
    margin-right: 8px;
}

.date-range-badge {
    background: var(--primary-gold-light);
    color: var(--primary-gold-dark);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    display: inline-block;
    border: 1px solid rgba(200, 155, 60, 0.2);
}

.date-range-badge i {
    margin-right: 6px;
}

/* STAT CARDS */
.report-stats {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 18px;
    margin-bottom: 25px;
}

.report-stat-card {
    background: #fff;
    padding: 22px 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.report-stat-card::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    background: radial-gradient(circle, rgba(200, 155, 60, 0.05) 0%, transparent 70%);
    border-radius: 50%;
}

.report-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,.12);
}

.report-stat-card .stat-title {
    color: #888;
    font-size: 13px;
    margin-bottom: 8px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.report-stat-card .stat-value {
    font-size: 26px;
    font-weight: 700;
    color: #222;
    margin-bottom: 6px;
}

.report-stat-card .stat-icon {
    font-size: 22px;
    margin-bottom: 12px;
}

.report-stat-card .stat-change {
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 12px;
    display: inline-block;
}

.report-stat-card .stat-change.positive {
    color: #28a745;
    background: #e8f5e9;
}

.report-stat-card .stat-change.negative {
    color: #dc3545;
    background: #fbe9ea;
}

.report-stat-card .stat-change.neutral {
    color: #6c757d;
    background: #f1f3f5;
}

/* GRID */
.report-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 25px;
}

.report-card {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    border: 1px solid #f5f5f5;
    transition: all 0.3s ease;
}

.report-card:hover {
    box-shadow: 0 5px 25px rgba(0,0,0,.1);
}

.report-card h5 {
    margin: 0 0 20px;
    font-size: 17px;
    font-weight: 700;
    color: #222;
    padding-bottom: 12px;
    border-bottom: 2px solid #f5f5f5;
}

.report-card h5 i {
    color: var(--primary-gold);
    margin-right: 8px;
}

/* CHART */
.chart-container {
    position: relative;
    height: 300px;
}

/* TABLE */
.report-table {
    width: 100%;
    border-collapse: collapse;
}

.report-table th {
    background: #f8f9fa;
    color: #555;
    font-size: 11px;
    text-align: left;
    padding: 12px 14px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e9ecef;
}

.report-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
    vertical-align: middle;
}

.report-table tr:last-child td {
    border-bottom: none;
}

.report-table tr:hover td {
    background: #fafbfc;
}

.product-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: var(--primary-gold-light);
    color: var(--primary-gold-dark);
    font-weight: 700;
    font-size: 13px;
    flex-shrink: 0;
}

.units-badge {
    display: inline-block;
    background: #e9ecef;
    padding: 4px 14px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 13px;
    color: #495057;
}

.units-badge i {
    margin-right: 4px;
    color: var(--primary-gold);
}

.revenue-cell {
    font-weight: 600;
    color: #28a745;
}

.revenue-cell i {
    margin-right: 4px;
}

.percentage-badge {
    display: inline-block;
    background: #e3f2fd;
    color: #1976d2;
    padding: 3px 12px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 13px;
}

.variant-badge {
    display: inline-block;
    background: #f3e5f5;
    color: #7b1fa2;
    padding: 3px 12px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
}

.variant-badge i {
    margin-right: 4px;
}

/* STATUS */
.status-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.status-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.status-name {
    font-size: 14px;
    color: #555;
    display: flex;
    align-items: center;
    gap: 8px;
}

.status-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.status-number {
    font-weight: 700;
    color: #222;
}

.status-percent {
    font-weight: 400;
    color: #888;
    font-size: 12px;
    margin-left: 4px;
}

.status-bar {
    height: 8px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 6px;
}

.status-progress {
    height: 100%;
    border-radius: 10px;
    transition: width 0.8s ease;
}

/* STOCK */
.stock-low {
    color: #dc3545;
    font-weight: 700;
}

.stock-critical {
    color: #dc3545;
    font-weight: 700;
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.stock-warning {
    color: #f0ad4e;
    font-weight: 700;
}

.stock-good {
    color: #198754;
    font-weight: 700;
}

.stock-status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.stock-status-badge.stock-low,
.stock-status-badge.stock-critical {
    background: #fbe9ea;
    color: #dc3545;
}

.stock-status-badge.stock-warning {
    background: #fff3cd;
    color: #f0ad4e;
}

.stock-status-badge.stock-good {
    background: #e8f5e9;
    color: #198754;
}

/* RESPONSIVE */
@media(max-width: 1200px) {
    .report-stats {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media(max-width: 900px) {
    .report-grid {
        grid-template-columns: 1fr;
    }
    
    .report-filter .filter-group.ml-auto {
        margin-left: 0;
        width: 100%;
    }
    
    .date-range-badge {
        width: 100%;
        text-align: center;
    }
}

@media(max-width: 768px) {
    .report-stats {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    
    .report-stat-card {
        padding: 16px;
    }
    
    .report-stat-card .stat-value {
        font-size: 20px;
    }
    
    .report-filter form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .report-filter input {
        min-width: auto;
        width: 100%;
    }
    
    .report-filter button {
        width: 100%;
    }
    
    .chart-container {
        height: 200px;
    }
    
    .report-card {
        padding: 16px;
    }
    
    .header-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .header-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}

@media(max-width: 480px) {
    .report-stats {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    
    .report-stat-card .stat-value {
        font-size: 17px;
    }
    
    .report-stat-card .stat-title {
        font-size: 11px;
    }
    
    .report-table th,
    .report-table td {
        padding: 8px 10px;
        font-size: 12px;
    }
    
    .product-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
}

/* Print Styles */
@media print {
    .sidebar,
    .navbar,
    .header .btn,
    .report-filter button,
    .header-actions,
    .report-stat-card .stat-change {
        display: none !important;
    }

    .main {
        margin-left: 0 !important;
    }
    
    .content {
        padding: 20px !important;
    }

    .report-stat-card,
    .report-card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        break-inside: avoid;
    }

    .report-filter {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        padding: 15px !important;
    }

    .report-filter input {
        border: 1px solid #999 !important;
        background: #fff !important;
    }
    
    .report-stats {
        grid-template-columns: repeat(3, 1fr) !important;
    }
    
    .report-grid {
        grid-template-columns: 1fr 1fr !important;
    }
    
    .chart-container {
        height: 250px !important;
    }
}

</style>

<!-- =========================================================
     CHART.JS
========================================================= -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function() {

    const salesLabels = <?= json_encode(
        array_column($salesByDate, 'sale_date')
    ) ?>;

    const salesValues = <?= json_encode(
        array_map(
            'floatval',
            array_column($salesByDate, 'sales')
        )
    ) ?>;

    const salesCtx =
        document.getElementById('salesChart').getContext('2d');

    new Chart(salesCtx, {

        type: 'line',

        data: {

            labels: salesLabels,

            datasets: [

                {

                    label: 'Sales (Rs.)',

                    data: salesValues,

                    tension: 0.4,

                    fill: true,

                    borderWidth: 3,

                    borderColor: '#C89B3C',

                    backgroundColor: function(context) {
                        const chart = context.chart;
                        const {ctx, chartArea} = chart;
                        if (!chartArea) {
                            return 'rgba(200, 155, 60, 0.1)';
                        }
                        const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        gradient.addColorStop(0, 'rgba(200, 155, 60, 0.3)');
                        gradient.addColorStop(1, 'rgba(200, 155, 60, 0.02)');
                        return gradient;
                    },

                    pointBackgroundColor: '#C89B3C',

                    pointBorderColor: '#fff',

                    pointBorderWidth: 2,

                    pointRadius: 5,

                    pointHoverRadius: 8

                }

            ]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {

                    display: true,

                    labels: {

                        font: {
                            size: 13,
                            weight: '600'
                        },

                        color: '#555',

                        padding: 20,

                        usePointStyle: true,

                        pointStyle: 'circle'

                    }

                },

                tooltip: {

                    backgroundColor: 'rgba(0,0,0,0.8)',

                    titleFont: {
                        size: 13,
                        weight: '600'
                    },

                    bodyFont: {
                        size: 13
                    },

                    padding: 12,

                    cornerRadius: 8,

                    callbacks: {
                        label: function(context) {
                            return 'Rs. ' + context.parsed.y.toLocaleString('en-IN', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    }

                }

            },

            scales: {

                y: {

                    beginAtZero: true,

                    grid: {
                        color: 'rgba(0,0,0,0.05)',
                        drawBorder: false
                    },

                    ticks: {
                        callback: function(value) {
                            return 'Rs. ' + value.toLocaleString('en-IN');
                        },
                        font: {
                            size: 11
                        }
                    }

                },

                x: {

                    grid: {
                        display: false
                    },

                    ticks: {
                        font: {
                            size: 11
                        },
                        maxRotation: 45,
                        minRotation: 30
                    }

                }

            },

            interaction: {
                intersect: false,
                mode: 'index'
            }

        }

    });

});

</script>

<?php include("includes/footer.php"); ?>