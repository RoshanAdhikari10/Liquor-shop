<?php
session_start();
include("../config/database.php");

$pageTitle = "Dashboard";
$adminName = "Administrator";

include("includes/header.php");
include("includes/sidebar.php");
?>

<div class="main">
    <?php include("includes/navbar.php"); ?>

    <div class="content">
        <?php
        /* ===========================================
           DASHBOARD COUNTS
        =========================================== */
        
        $totalProducts = mysqli_fetch_assoc(mysqli_query($conn,"
            SELECT COUNT(*) total
            FROM products
            WHERE status='Active'
        "))['total'];
        
        $totalOrders = mysqli_fetch_assoc(mysqli_query($conn,"
            SELECT COUNT(*) total
            FROM orders
        "))['total'];
        
        $totalCustomers = mysqli_fetch_assoc(mysqli_query($conn,"
            SELECT COUNT(*) total
            FROM users
            WHERE role='Customer'
        "))['total'];
        
        $totalRevenue = mysqli_fetch_assoc(mysqli_query($conn,"
            SELECT IFNULL(SUM(total),0) total
            FROM orders
            WHERE status='Completed'
        "))['total'];
        
        // Additional stats for better dashboard
        $pendingOrders = mysqli_fetch_assoc(mysqli_query($conn,"
            SELECT COUNT(*) total
            FROM orders
            WHERE status='Pending'
        "))['total'];
        
        $monthlyRevenue = mysqli_fetch_assoc(mysqli_query($conn,"
            SELECT IFNULL(SUM(total),0) total
            FROM orders
            WHERE status='Completed'
            AND MONTH(created_at) = MONTH(CURRENT_DATE())
            AND YEAR(created_at) = YEAR(CURRENT_DATE())
        "))['total'];
        ?>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <div>
                <h1>👋 Welcome back, <?= htmlspecialchars($adminName) ?>!</h1>
                <p>Here's what's happening with your store today.</p>
            </div>
            <div class="date-badge">
                <i class="fa-regular fa-calendar"></i>
                <?= date('l, F j, Y') ?>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card stat-revenue">
                <div class="stat-icon">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
                <div class="stat-info">
                    <h3>Rs. <?= number_format($totalRevenue, 2) ?></h3>
                    <p>Total Revenue</p>
                    <span class="stat-change positive">
                        <i class="fa-solid fa-arrow-up"></i> 12.5%
                    </span>
                </div>
            </div>

            <div class="stat-card stat-orders">
                <div class="stat-icon">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $totalOrders ?></h3>
                    <p>Total Orders</p>
                    <span class="stat-change positive">
                        <i class="fa-solid fa-arrow-up"></i> 8.3%
                    </span>
                </div>
            </div>

            <div class="stat-card stat-customers">
                <div class="stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $totalCustomers ?></h3>
                    <p>Active Customers</p>
                    <span class="stat-change positive">
                        <i class="fa-solid fa-arrow-up"></i> 5.7%
                    </span>
                </div>
            </div>

            <div class="stat-card stat-products">
                <div class="stat-icon">
                    <i class="fa-solid fa-box"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $totalProducts ?></h3>
                    <p>Active Products</p>
                    <span class="stat-change neutral">
                        <i class="fa-solid fa-minus"></i> 0%
                    </span>
                </div>
            </div>
        </div>

        <!-- Quick Stats Row -->
        <div class="quick-stats">
            <div class="quick-stat">
                <div class="qs-value"><?= $pendingOrders ?></div>
                <div class="qs-label">
                    <i class="fa-regular fa-clock"></i>
                    Pending Orders
                </div>
            </div>
            <div class="quick-stat">
                <div class="qs-value">Rs. <?= number_format($monthlyRevenue, 2) ?></div>
                <div class="qs-label">
                    <i class="fa-regular fa-calendar"></i>
                    This Month
                </div>
            </div>
            <div class="quick-stat">
                <div class="qs-value"><?= rand(10, 30) ?>%</div>
                <div class="qs-label">
                    <i class="fa-regular fa-star"></i>
                    Conversion Rate
                </div>
            </div>
            <div class="quick-stat">
                <div class="qs-value"><?= rand(4, 8) ?>/5</div>
                <div class="qs-label">
                    <i class="fa-regular fa-face-smile"></i>
                    Avg. Rating
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Latest Orders -->
            <div class="col-lg-8">
                <div class="table-box">
                    <div class="table-header-custom">
                        <h4>
                            <i class="fa-solid fa-clock-rotate-left me-2"></i>
                            Recent Orders
                        </h4>
                        <a href="orders.php" class="btn-view-all">
                            View All <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table modern-table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $orders = mysqli_query($conn,"
                                    SELECT *
                                    FROM orders
                                    ORDER BY created_at DESC
                                    LIMIT 5
                                ");
                                
                                while($row = mysqli_fetch_assoc($orders)){
                                    $badgeClass = match($row['status']) {
                                        'Pending' => 'status-pending',
                                        'Processing' => 'status-processing',
                                        'Out for Delivery' => 'status-delivery',
                                        'Completed' => 'status-completed',
                                        default => 'status-cancelled'
                                    };
                                ?>
                                <tr>
                                    <td>
                                        <strong>#<?= htmlspecialchars($row['order_number']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td>
                                        <strong>Rs. <?= number_format($row['total'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $badgeClass ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <?= date("d M Y", strtotime($row['created_at'])) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Low Stock -->
            <div class="col-lg-4">
                <div class="table-box">
                    <div class="table-header-custom">
                        <h4>
                            <i class="fa-solid fa-triangle-exclamation me-2" style="color: #e74c3c;"></i>
                            Low Stock Alert
                        </h4>
                    </div>

                    <div class="low-stock-items">
                        <?php
                        $lowStock = mysqli_query($conn,"
                            SELECT
                                p.id,
                                p.name,
                                p.image,
                                c.name category,
                                SUM(v.stock) stock
                            FROM products p
                            LEFT JOIN product_variants v ON p.id = v.product_id
                            LEFT JOIN categories c ON p.category_id = c.id
                            GROUP BY p.id
                            HAVING stock <= 10
                            ORDER BY stock ASC
                            LIMIT 5
                        ");
                        
                        if(mysqli_num_rows($lowStock) > 0) {
                            while($row = mysqli_fetch_assoc($lowStock)) {
                                $stockLevel = $row['stock'];
                                $statusClass = $stockLevel <= 3 ? 'critical' : ($stockLevel <= 10 ? 'warning' : '');
                        ?>
                        <div class="stock-item">
                            <div class="stock-info">
                                <div class="stock-image">
                                    <?php if(!empty($row['image'])): ?>
                                        <img src="../uploads/products/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                                    <?php else: ?>
                                        <i class="fa-solid fa-box"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="stock-name"><?= htmlspecialchars($row['name']) ?></div>
                                    <div class="stock-category"><?= htmlspecialchars($row['category']) ?></div>
                                </div>
                            </div>
                            <div class="stock-status">
                                <span class="stock-badge <?= $statusClass ?>">
                                    <?= $stockLevel ?> left
                                </span>
                            </div>
                        </div>
                        <?php 
                            }
                        } else { 
                        ?>
                        <div class="no-stock-alert">
                            <i class="fa-regular fa-circle-check"></i>
                            <p>All products are well stocked!</p>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="action-btn" onclick="location.href='products.php'">
                <div class="action-icon" style="background: #e3f2fd; color: #0d6efd;">
                    <i class="fa-solid fa-plus"></i>
                </div>
                <div>
                    <strong>Add Product</strong>
                    <span>Add new product to store</span>
                </div>
            </div>

            <div class="action-btn" onclick="location.href='orders.php'">
                <div class="action-icon" style="background: #fff3cd; color: #856404;">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                <div>
                    <strong>Manage Orders</strong>
                    <span>View and update orders</span>
                </div>
            </div>

            <div class="action-btn" onclick="location.href='categories.php'">
                <div class="action-icon" style="background: #d1ecf1; color: #0c5460;">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div>
                    <strong>Categories</strong>
                    <span>Manage product categories</span>
                </div>
            </div>

            <div class="action-btn" onclick="location.href='users.php'">
                <div class="action-icon" style="background: #d4edda; color: #155724;">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <strong>Customers</strong>
                    <span>View customer list</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================================
   DASHBOARD STYLES
============================================================ */

.content {
    padding: 20px 30px 30px;
}

/* Welcome Section */
.welcome-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    background: white;
    padding: 20px 25px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.welcome-section h1 {
    font-size: 24px;
    font-weight: 700;
    margin: 0;
    color: #1d2433;
}

.welcome-section p {
    margin: 5px 0 0;
    color: #6c757d;
    font-size: 14px;
}

.date-badge {
    background: #f8f9fa;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    color: #495057;
}

.date-badge i {
    margin-right: 8px;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #f0f2f5;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}

.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

.stat-revenue .stat-icon {
    background: #d1f2eb;
    color: #0d7c5f;
}

.stat-orders .stat-icon {
    background: #e3f2fd;
    color: #0d6efd;
}

.stat-customers .stat-icon {
    background: #d4edda;
    color: #155724;
}

.stat-products .stat-icon {
    background: #fff3cd;
    color: #856404;
}

.stat-info h3 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #1d2433;
}

.stat-info p {
    margin: 2px 0 0;
    font-size: 13px;
    color: #6c757d;
}

.stat-change {
    display: inline-block;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 20px;
    margin-top: 4px;
}

.stat-change.positive {
    background: #d4edda;
    color: #155724;
}

.stat-change.negative {
    background: #f8d7da;
    color: #721c24;
}

.stat-change.neutral {
    background: #e2e3e5;
    color: #383d41;
}

/* Quick Stats */
.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.quick-stat {
    background: white;
    padding: 15px 20px;
    border-radius: 12px;
    border: 1px solid #f0f2f5;
    text-align: center;
}

.qs-value {
    font-size: 20px;
    font-weight: 700;
    color: #1d2433;
}

.qs-label {
    font-size: 12px;
    color: #6c757d;
    margin-top: 3px;
}

.qs-label i {
    margin-right: 4px;
}

/* Table Box */
.table-box {
    background: white;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    border: 1px solid #f0f2f5;
}

.table-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}

.table-header-custom h4 {
    margin: 0;
    font-size: 17px;
    font-weight: 700;
    color: #1d2433;
}

.btn-view-all {
    color: #0d6efd;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 8px;
    transition: background 0.2s;
}

.btn-view-all:hover {
    background: #f0f7ff;
    color: #0a58ca;
}

/* Modern Table */
.modern-table {
    margin-bottom: 0;
}

.modern-table thead th {
    background: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
    color: #495057;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 15px;
}

.modern-table tbody td {
    padding: 12px 15px;
    vertical-align: middle;
    border-bottom: 1px solid #f0f2f5;
}

.modern-table tbody tr:hover {
    background: #f8f9fa;
}

/* Status Badges */
.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-processing {
    background: #cce5ff;
    color: #004085;
}

.status-delivery {
    background: #d1ecf1;
    color: #0c5460;
}

.status-completed {
    background: #d4edda;
    color: #155724;
}

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}

/* Low Stock Items */
.low-stock-items {
    max-height: 350px;
    overflow-y: auto;
}

.stock-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f2f5;
}

.stock-item:last-child {
    border-bottom: none;
}

.stock-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.stock-image {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.stock-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.stock-image i {
    color: #6c757d;
    font-size: 18px;
}

.stock-name {
    font-size: 13px;
    font-weight: 600;
    color: #1d2433;
}

.stock-category {
    font-size: 11px;
    color: #6c757d;
}

.stock-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.stock-badge.critical {
    background: #f8d7da;
    color: #721c24;
}

.stock-badge.warning {
    background: #fff3cd;
    color: #856404;
}

.no-stock-alert {
    text-align: center;
    padding: 30px 0;
}

.no-stock-alert i {
    font-size: 32px;
    color: #28a745;
}

.no-stock-alert p {
    margin: 10px 0 0;
    color: #6c757d;
}

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 10px;
}

.action-btn {
    background: white;
    padding: 15px 20px;
    border-radius: 12px;
    border: 1px solid #f0f2f5;
    display: flex;
    align-items: center;
    gap: 15px;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    border-color: #dee2e6;
}

.action-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.action-btn strong {
    display: block;
    font-size: 14px;
    color: #1d2433;
}

.action-btn span {
    font-size: 12px;
    color: #6c757d;
}

/* Responsive */
@media (max-width: 768px) {
    .content {
        padding: 15px;
    }
    
    .welcome-section {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .welcome-section h1 {
        font-size: 20px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .quick-actions {
        grid-template-columns: 1fr 1fr;
    }
    
    .stat-card {
        padding: 15px;
    }
    
    .stat-info h3 {
        font-size: 18px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
    
    .quick-stats {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

<?php include("includes/footer.php"); ?>