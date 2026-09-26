<?php
session_start();
include("../config/database.php");

$pageTitle = "Orders";
$adminName = "Administrator";
include("includes/header.php");
include("includes/sidebar.php");
?>

<div class="main">
    <?php include("includes/navbar.php"); ?>

    <?php
    // Get order statistics
    $stats = [
        'total' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM orders"))['total'],
        'pending' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM orders WHERE status='Pending'"))['total'],
        'processing' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM orders WHERE status='Processing'"))['total'],
        'outDelivery' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM orders WHERE status='Out for Delivery'"))['total'],
        'completed' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM orders WHERE status='Completed'"))['total'],
        'cancelled' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM orders WHERE status='Cancelled'"))['total'],
        'revenue' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(total),0) revenue FROM orders WHERE status='Completed'"))['revenue']
    ];

    // Build search/filter query
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

    $sql = "SELECT * FROM orders WHERE 1";
    if ($search) {
        $sql .= " AND (order_number LIKE '%$search%' OR full_name LIKE '%$search%' OR phone LIKE '%$search%' OR email LIKE '%$search%')";
    }
    if ($status) {
        $sql .= " AND status='$status'";
    }
    $sql .= " ORDER BY created_at DESC";
    $result = mysqli_query($conn, $sql);
    ?>

    <!-- Modern Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card stat-total">
            <div class="stat-icon">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <div class="stat-info">
                <h3><?= $stats['total'] ?></h3>
                <p>Total Orders</p>
            </div>
        </div>

        <div class="stat-card stat-pending">
            <div class="stat-icon">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?= $stats['pending'] ?></h3>
                <p>Pending</p>
            </div>
        </div>

        <div class="stat-card stat-processing">
            <div class="stat-icon">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <div class="stat-info">
                <h3><?= $stats['processing'] ?></h3>
                <p>Processing</p>
            </div>
        </div>

        <div class="stat-card stat-delivery">
            <div class="stat-icon">
                <i class="fa-solid fa-truck-fast"></i>
            </div>
            <div class="stat-info">
                <h3><?= $stats['outDelivery'] ?></h3>
                <p>Out for Delivery</p>
            </div>
        </div>

        <div class="stat-card stat-completed">
            <div class="stat-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= $stats['completed'] ?></h3>
                <p>Completed</p>
            </div>
        </div>

        <div class="stat-card stat-cancelled">
            <div class="stat-icon">
                <i class="fa-solid fa-ban"></i>
            </div>
            <div class="stat-info">
                <h3><?= $stats['cancelled'] ?></h3>
                <p>Cancelled</p>
            </div>
        </div>

        <div class="stat-card stat-revenue">
            <div class="stat-icon">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
            <div class="stat-info">
                <h3>Rs. <?= number_format($stats['revenue'], 2) ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="table-container">
        <div class="table-header">
            <h4><i class="fa-solid fa-cart-shopping me-2"></i>Orders Management</h4>
            
            <form method="GET" class="filter-form">
                <div class="search-wrapper">
                    <i class="fa-solid fa-search search-icon"></i>
                    <input type="text" name="search" class="search-input" 
                           placeholder="Search orders..." value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <select name="status" class="status-filter">
                    <option value="">All Status</option>
                    <option value="Pending" <?= $status == "Pending" ? "selected" : "" ?>>Pending</option>
                    <option value="Processing" <?= $status == "Processing" ? "selected" : "" ?>>Processing</option>
                    <option value="Out for Delivery" <?= $status == "Out for Delivery" ? "selected" : "" ?>>Out for Delivery</option>
                    <option value="Completed" <?= $status == "Completed" ? "selected" : "" ?>>Completed</option>
                    <option value="Cancelled" <?= $status == "Cancelled" ? "selected" : "" ?>>Cancelled</option>
                </select>
                
                <button type="submit" class="btn-filter">Filter</button>
                <a href="orders.php" class="btn-reset">Reset</a>
            </form>
        </div>

        <div class="table-responsive">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Order No.</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $count = 1;
                    while ($order = mysqli_fetch_assoc($result)):
                        $badgeClass = match($order['status']) {
                            'Pending' => 'status-pending',
                            'Processing' => 'status-processing',
                            'Out for Delivery' => 'status-delivery',
                            'Completed' => 'status-completed',
                            default => 'status-cancelled'
                        };
                    ?>
                    <tr>
                        <td><?= $count++ ?></td>
                        <td><strong>#<?= htmlspecialchars($order['order_number']) ?></strong></td>
                        <td><?= htmlspecialchars($order['full_name']) ?></td>
                        <td><?= htmlspecialchars($order['phone']) ?></td>
                        <td><strong>Rs. <?= number_format($order['total'], 2) ?></strong></td>
                        <td><?= htmlspecialchars($order['payment_method']) ?></td>
                        <td><span class="status-badge <?= $badgeClass ?>"><?= htmlspecialchars($order['status']) ?></span></td>
                        <td><?= date("d M Y", strtotime($order['created_at'])) ?></td>
                        <td>
                            <button class="btn-view-order viewOrderBtn" data-id="<?= $order['id'] ?>">
                                <i class="fa-solid fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- ============================================================
     ORDER DETAILS MODAL
============================================================ -->

<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content order-modal">

            <!-- Header -->
            <div class="modal-header order-modal-header">

                <div class="order-modal-title">
                    <div class="order-modal-icon">
                        <i class="fa-solid fa-receipt"></i>
                    </div>

                    <div>
                        <h4>Order Details</h4>
                        <p id="modalSubtitle">Loading order...</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <!-- Body -->
            <div class="modal-body order-modal-body" id="orderDetails">

                <div class="order-loading">
                    <div class="loading-circle">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                    </div>

                    <h5>Loading order</h5>
                    <p>Please wait while we fetch the order details.</p>
                </div>

            </div>

        </div>
    </div>
</div>


<!-- ============================================================
     STATUS CONFIRMATION MODAL
============================================================ -->

<div class="modal fade" id="statusConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">

        <div class="modal-content status-confirm-modal">

            <div class="status-confirm-icon" id="statusConfirmIcon">
                <i class="fa-solid fa-arrows-rotate"></i>
            </div>

            <h4>Change Order Status?</h4>

            <p id="statusConfirmText">
                Are you sure you want to change this order status?
            </p>

            <div class="status-confirm-actions">

                <button
                    type="button"
                    class="btn btn-light"
                    data-bs-dismiss="modal">
                    Keep Current
                </button>

                <button
                    type="button"
                    class="btn btn-primary"
                    id="confirmStatusBtn">
                    Change Status
                </button>

            </div>

        </div>

    </div>
</div>


<!-- ============================================================
     TOAST
============================================================ -->

<div class="order-toast" id="orderToast">

    <div class="toast-icon">
        <i class="fa-solid fa-check"></i>
    </div>

    <div>
        <strong id="toastTitle">Success</strong>
        <p id="toastMessage">Order updated successfully.</p>
    </div>

</div>


<style>
/* ============================================================
   ORDER MODAL
============================================================ */

.order-modal {
    border: 0;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 25px 80px rgba(0,0,0,.20);
}

.order-modal-header {
    padding: 20px 26px;
    background: #ffffff;
    border-bottom: 1px solid #edf0f4;
}

.order-modal-title {
    display: flex;
    align-items: center;
    gap: 14px;
}

.order-modal-icon {
    width: 48px;
    height: 48px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff4df;
    color: #d88b00;
    font-size: 20px;
}

.order-modal-title h4 {
    margin: 0;
    font-size: 19px;
    font-weight: 750;
    color: #1d2433;
}

.order-modal-title p {
    margin: 3px 0 0;
    font-size: 12px;
    color: #8a93a3;
}

.order-modal-body {
    background: #f6f8fb;
    padding: 24px;
}


/* ============================================================
   LOADING
============================================================ */

.order-loading {
    min-height: 400px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
}

.loading-circle {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff4df;
    color: #e09a12;
    font-size: 23px;
    margin-bottom: 15px;
}

.order-loading h5 {
    margin: 0;
    font-weight: 700;
}

.order-loading p {
    margin: 5px 0 0;
    color: #8a93a3;
    font-size: 13px;
}


/* ============================================================
   ORDER HERO
============================================================ */

.order-hero {
    background: linear-gradient(135deg, #1d2433, #30394d);
    border-radius: 16px;
    padding: 22px 24px;
    color: white;
    margin-bottom: 18px;
}

.order-hero-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.order-number {
    font-size: 22px;
    font-weight: 800;
    margin: 0;
}

.order-date {
    margin: 5px 0 0;
    color: rgba(255,255,255,.65);
    font-size: 12px;
}

.order-hero-actions {
    display: flex;
    gap: 8px;
}

.order-action-btn {
    border: 0;
    border-radius: 9px;
    padding: 9px 14px;
    font-size: 13px;
    font-weight: 650;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
    text-decoration: none;
    transition: .2s;
}

.order-action-btn:hover {
    transform: translateY(-1px);
}

.invoice-btn {
    background: white;
    color: #202737;
}

.invoice-btn:hover {
    background: #f2f4f7;
    color: #202737;
}


/* ============================================================
   STATUS PROGRESS
============================================================ */

.order-progress {
    background: white;
    border-radius: 16px;
    padding: 22px;
    margin-bottom: 18px;
    border: 1px solid #edf0f4;
}

.section-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 22px;
}

.section-title h5 {
    margin: 0;
    font-size: 15px;
    font-weight: 750;
    color: #252b38;
}

.section-title span {
    font-size: 12px;
    color: #8b94a4;
}

.progress-track {
    display: flex;
    align-items: flex-start;
    position: relative;
    justify-content: space-between;
}

.progress-line {
    position: absolute;
    height: 3px;
    background: #e7ebf0;
    top: 19px;
    left: 9%;
    right: 9%;
    z-index: 0;
}

.progress-line-active {
    position: absolute;
    height: 3px;
    background: #e4a11b;
    top: 19px;
    left: 9%;
    z-index: 1;
    transition: width .35s ease;
}

.progress-step {
    position: relative;
    z-index: 2;
    width: 25%;
    text-align: center;
}

.progress-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #eef1f5;
    color: #9ba3af;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: auto;
    font-size: 14px;
    border: 3px solid white;
    box-shadow: 0 0 0 1px #e2e6eb;
}

.progress-step.completed .progress-circle {
    background: #e4a11b;
    color: white;
    box-shadow: 0 0 0 1px #e4a11b;
}

.progress-step.current .progress-circle {
    background: #202737;
    color: white;
    box-shadow: 0 0 0 3px rgba(32,39,55,.12);
}

.progress-step button {
    border: 0;
    background: transparent;
    padding: 0;
    margin: 9px auto 0;
    font-size: 12px;
    color: #8b94a4;
    font-weight: 600;
    cursor: pointer;
}

.progress-step.completed button,
.progress-step.current button {
    color: #252b38;
}

.progress-step button:hover {
    color: #d88b00;
}

.cancelled-progress {
    margin-top: 15px;
    padding: 12px 15px;
    background: #fff0f0;
    border: 1px solid #ffd5d5;
    border-radius: 10px;
    color: #c53030;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
}


/* ============================================================
   INFORMATION CARDS
============================================================ */

.order-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
    margin-bottom: 18px;
}

.order-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #edf0f4;
    overflow: hidden;
}

.order-card-header {
    padding: 16px 18px;
    border-bottom: 1px solid #edf0f4;
    display: flex;
    align-items: center;
    gap: 9px;
}

.order-card-header i {
    color: #d99a18;
}

.order-card-header h6 {
    margin: 0;
    font-size: 14px;
    font-weight: 750;
    color: #252b38;
}

.order-card-body {
    padding: 17px 18px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 8px 0;
    border-bottom: 1px solid #f1f3f6;
}

.info-row:last-child {
    border-bottom: 0;
}

.info-label {
    color: #8992a1;
    font-size: 12px;
}

.info-value {
    color: #252b38;
    font-size: 13px;
    font-weight: 650;
    text-align: right;
    max-width: 65%;
}

.info-value.address {
    line-height: 1.5;
}


/* ============================================================
   PRODUCTS
============================================================ */

.products-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #edf0f4;
    overflow: hidden;
    margin-bottom: 18px;
}

.products-header {
    padding: 17px 18px;
    border-bottom: 1px solid #edf0f4;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.products-header h6 {
    margin: 0;
    font-size: 14px;
    font-weight: 750;
}

.products-header span {
    font-size: 12px;
    color: #8b94a4;
}

.products-table {
    width: 100%;
    border-collapse: collapse;
}

.products-table th {
    background: #fafbfc;
    padding: 11px 18px;
    text-align: left;
    color: #8992a1;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .5px;
    font-weight: 700;
}

.products-table td {
    padding: 13px 18px;
    border-top: 1px solid #f0f2f5;
    font-size: 13px;
    color: #303746;
}

.product-name {
    font-weight: 700;
}

.product-volume {
    color: #8992a1;
    font-size: 12px;
    margin-top: 2px;
}

.quantity-badge {
    background: #f1f3f6;
    padding: 4px 9px;
    border-radius: 6px;
    font-weight: 700;
}


/* ============================================================
   TOTAL
============================================================ */

.order-total-box {
    display: flex;
    justify-content: flex-end;
}

.total-card {
    background: white;
    width: 360px;
    border-radius: 16px;
    padding: 18px;
    border: 1px solid #edf0f4;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    font-size: 13px;
    color: #737d8c;
}

.total-row.grand-total {
    border-top: 1px dashed #dfe3e8;
    margin-top: 8px;
    padding-top: 14px;
    color: #202737;
    font-size: 18px;
    font-weight: 800;
}

.total-row.grand-total span:last-child {
    color: #d28c0c;
}


/* ============================================================
   NOTES
============================================================ */

.order-notes {
    background: #fffaf0;
    border: 1px solid #f8e6bb;
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 18px;
}

.order-notes strong {
    display: block;
    font-size: 12px;
    margin-bottom: 5px;
    color: #8b650e;
}

.order-notes p {
    margin: 0;
    color: #685c3d;
    font-size: 13px;
    line-height: 1.5;
}


/* ============================================================
   STATUS CONFIRMATION
============================================================ */

.status-confirm-modal {
    border: 0;
    border-radius: 18px;
    padding: 28px 24px;
    text-align: center;
}

.status-confirm-icon {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    background: #fff4df;
    color: #d9930d;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 20px;
}

.status-confirm-modal h4 {
    font-size: 18px;
    font-weight: 750;
}

.status-confirm-modal p {
    font-size: 13px;
    color: #7d8795;
    line-height: 1.5;
    margin-bottom: 20px;
}

.status-confirm-actions {
    display: flex;
    gap: 8px;
}

.status-confirm-actions button {
    flex: 1;
    border-radius: 9px;
    padding: 9px;
    font-size: 12px;
    font-weight: 650;
}


/* ============================================================
   TOAST
============================================================ */

.order-toast {
    position: fixed;
    right: 25px;
    bottom: 25px;
    z-index: 99999;
    background: white;
    border-radius: 12px;
    padding: 13px 16px;
    min-width: 280px;
    box-shadow: 0 15px 40px rgba(0,0,0,.16);
    display: flex;
    align-items: center;
    gap: 11px;
    transform: translateY(130px);
    opacity: 0;
    pointer-events: none;
    transition: .3s ease;
    border-left: 4px solid #28a745;
}

.order-toast.show {
    transform: translateY(0);
    opacity: 1;
}

.order-toast.error {
    border-left-color: #dc3545;
}

.toast-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e8f7ed;
    color: #28a745;
    display: flex;
    align-items: center;
    justify-content: center;
}

.order-toast.error .toast-icon {
    background: #fff0f0;
    color: #dc3545;
}

.order-toast strong {
    display: block;
    font-size: 13px;
}

.order-toast p {
    margin: 2px 0 0;
    font-size: 11px;
    color: #8992a1;
}


/* ============================================================
   RESPONSIVE
============================================================ */

@media(max-width: 768px) {

    .order-modal-body {
        padding: 14px;
    }

    .order-info-grid {
        grid-template-columns: 1fr;
    }

    .order-hero-top {
        flex-direction: column;
        align-items: flex-start;
    }

    .order-hero-actions {
        width: 100%;
    }

    .order-action-btn {
        flex: 1;
        justify-content: center;
    }

    .progress-step button {
        font-size: 10px;
    }

    .products-table {
        min-width: 600px;
    }

    .products-card {
        overflow-x: auto;
    }

    .total-card {
        width: 100%;
    }
}

/* Add existing status badge styles */
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

.btn-view-order {
    background: #1d2433;
    color: white;
    border: none;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    transition: 0.2s;
}

.btn-view-order:hover {
    background: #30394d;
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.stat-total .stat-icon { background: #e3f2fd; color: #0d6efd; }
.stat-pending .stat-icon { background: #fff3cd; color: #856404; }
.stat-processing .stat-icon { background: #cce5ff; color: #004085; }
.stat-delivery .stat-icon { background: #d1ecf1; color: #0c5460; }
.stat-completed .stat-icon { background: #d4edda; color: #155724; }
.stat-cancelled .stat-icon { background: #f8d7da; color: #721c24; }
.stat-revenue .stat-icon { background: #d1f2eb; color: #0d7c5f; }

.stat-info h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
}

.stat-info p {
    margin: 0;
    font-size: 12px;
    color: #6c757d;
}

.table-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.table-header h4 {
    margin: 0;
    font-size: 18px;
}

.filter-form {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.search-wrapper {
    position: relative;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

.search-input {
    padding: 8px 12px 8px 35px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 13px;
    width: 200px;
}

.status-filter {
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 13px;
}

.btn-filter, .btn-reset {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    text-decoration: none;
}

.btn-filter {
    background: #1d2433;
    color: white;
}

.btn-filter:hover {
    background: #30394d;
    color: white;
}

.btn-reset {
    background: #f8f9fa;
    color: #212529;
}

.btn-reset:hover {
    background: #e2e6ea;
    color: #212529;
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
}

.orders-table th {
    text-align: left;
    padding: 12px 15px;
    background: #f8f9fa;
    font-size: 12px;
    font-weight: 600;
    color: #495057;
}

.orders-table td {
    padding: 12px 15px;
    border-top: 1px solid #e9ecef;
    font-size: 13px;
}

.table-responsive {
    overflow-x: auto;
}
</style>





<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>

/* ============================================================
   MODALS
============================================================ */

const orderModalElement = document.getElementById("orderModal");
const orderModal = new bootstrap.Modal(orderModalElement);
const statusConfirmElement = document.getElementById("statusConfirmModal");
const statusConfirmModal = new bootstrap.Modal(statusConfirmElement);


/* ============================================================
   VARIABLES
============================================================ */

let pendingStatus = null;
let pendingOrderId = null;


/* ============================================================
   VIEW ORDER
============================================================ */

document.querySelectorAll(".viewOrderBtn").forEach(button => {
    button.addEventListener("click", function() {
        const id = this.dataset.id;
        document.getElementById("modalSubtitle").textContent = "Loading order information...";
        document.getElementById("orderDetails").innerHTML = `
            <div class="order-loading">
                <div class="loading-circle">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                </div>
                <h5>Loading order</h5>
                <p>Please wait while we fetch the order details.</p>
            </div>
        `;
        orderModal.show();

        fetch("get-order.php?id=" + encodeURIComponent(id))
            .then(response => {
                if (!response.ok) {
                    throw new Error("Request failed");
                }
                return response.text();
            })
            .then(html => {
                document.getElementById("orderDetails").innerHTML = html;
                const orderNumber = document.querySelector("[data-order-number]");
                if (orderNumber) {
                    document.getElementById("modalSubtitle").textContent = "Order #" + orderNumber.dataset.orderNumber;
                } else {
                    document.getElementById("modalSubtitle").textContent = "Order Details";
                }
                // IMPORTANT: Re-bind status buttons after content is loaded
                setTimeout(function() {
                    bindStatusButtons();
                }, 150);
            })
            .catch(error => {
                console.error(error);
                document.getElementById("orderDetails").innerHTML = `
                    <div class="alert alert-danger m-2">
                        <i class="fa-solid fa-circle-exclamation me-2"></i>
                        Unable to load this order. Please try again.
                    </div>
                `;
            });
    });
});


/* ============================================================
   BIND STATUS BUTTONS - FIXED VERSION WITH GLOBAL ACCESS
============================================================ */

// Make function globally accessible
window.bindStatusButtons = function() {
    console.log("Binding status buttons...");
    
    // Get all status change buttons
    const buttons = document.querySelectorAll(".statusChangeBtn");
    console.log("Found " + buttons.length + " status buttons");
    
    // Remove existing listeners by cloning
    buttons.forEach(button => {
        // Skip if already processed (has data-bound attribute)
        if (button.dataset.bound === "true") {
            return;
        }
        
        // Mark as bound
        button.dataset.bound = "true";
        
        // Add click listener
        button.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const orderId = this.dataset.id || this.dataset.orderId;
            const newStatus = this.dataset.status;
            const currentStatus = this.dataset.current;
            
            console.log("Status button clicked:", {orderId, newStatus, currentStatus});
            
            // Check if status is same
            if (newStatus === currentStatus) {
                showOrderToast("Info", "Order is already " + currentStatus, false);
                return;
            }
            
            // Check if order is completed or cancelled
            if (currentStatus === "Completed" || currentStatus === "Cancelled") {
                showOrderToast(
                    "Cannot Change Status",
                    "A " + currentStatus + " order cannot be moved to another status.",
                    true
                );
                return;
            }
            
            // Set pending data
            pendingOrderId = orderId;
            pendingStatus = newStatus;
            
            // Update confirmation modal text
            document.getElementById("statusConfirmText").innerHTML =
                `Change this order from <strong>${escapeHtml(currentStatus)}</strong> to <strong>${escapeHtml(newStatus)}</strong>?`;
            
            updateConfirmationIcon(newStatus);
            
            // Show confirmation modal
            statusConfirmModal.show();
        });
    });
}


/* ============================================================
   CONFIRM STATUS CHANGE
============================================================ */

document.getElementById("confirmStatusBtn").addEventListener("click", function() {
    if (!pendingOrderId || !pendingStatus) {
        showOrderToast("Error", "No order selected.", true);
        return;
    }

    const button = this;
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...`;

    const formData = new FormData();
    formData.append("id", pendingOrderId);
    formData.append("status", pendingStatus);

    fetch("update-order-status.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || "Unable to update status.");
        }

        statusConfirmModal.hide();
        showOrderToast("Status Updated", data.message, false);

        // Store the order ID for later use
        const currentOrderId = pendingOrderId;
        
        // Refresh the modal content
        fetch("get-order.php?id=" + encodeURIComponent(currentOrderId))
            .then(response => response.text())
            .then(html => {
                document.getElementById("orderDetails").innerHTML = html;
                // Re-bind status buttons after content refresh
                setTimeout(function() {
                    bindStatusButtons();
                }, 150);
                
                const orderNumber = document.querySelector("[data-order-number]");
                if (orderNumber) {
                    document.getElementById("modalSubtitle").textContent = "Order #" + orderNumber.dataset.orderNumber;
                }
            });

        // Update table row status
        updateTableStatus(currentOrderId, pendingStatus);

        // Clear pending data
        pendingOrderId = null;
        pendingStatus = null;
    })
    .catch(error => {
        console.error("Status update error:", error);
        statusConfirmModal.hide();
        showOrderToast("Update Failed", error.message || "Unable to update order status.", true);
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = originalText;
    });
});


/* ============================================================
   UPDATE TABLE STATUS
============================================================ */

function updateTableStatus(orderId, newStatus) {
    const viewButton = document.querySelector(`.viewOrderBtn[data-id="${orderId}"]`);
    if (!viewButton) return;
    
    const row = viewButton.closest("tr");
    if (!row) return;
    
    const statusBadge = row.querySelector(".status-badge");
    if (!statusBadge) return;
    
    statusBadge.textContent = newStatus;
    statusBadge.className = "status-badge " + getStatusClass(newStatus);
}


/* ============================================================
   STATUS CLASS
============================================================ */

function getStatusClass(status) {
    const classes = {
        "Pending": "status-pending",
        "Processing": "status-processing",
        "Out for Delivery": "status-delivery",
        "Completed": "status-completed",
        "Cancelled": "status-cancelled"
    };
    return classes[status] || "status-pending";
}


/* ============================================================
   CONFIRM ICON
============================================================ */

function updateConfirmationIcon(status) {
    const icon = document.getElementById("statusConfirmIcon");
    const icons = {
        "Pending": "fa-clock",
        "Processing": "fa-box-open",
        "Out for Delivery": "fa-truck-fast",
        "Completed": "fa-circle-check",
        "Cancelled": "fa-ban"
    };
    icon.innerHTML = `<i class="fa-solid ${icons[status] || "fa-arrows-rotate"}"></i>`;
}


/* ============================================================
   TOAST
============================================================ */

let toastTimer;

function showOrderToast(title, message, error = false) {
    const toast = document.getElementById("orderToast");
    document.getElementById("toastTitle").textContent = title;
    document.getElementById("toastMessage").textContent = message;
    toast.classList.toggle("error", error);
    toast.classList.add("show");
    
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.classList.remove("show");
    }, 3500);
}


/* ============================================================
   ESCAPE HTML
============================================================ */

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Initialize bindings when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initial binding for any existing status buttons (if any)
    setTimeout(function() {
        bindStatusButtons();
    }, 200);
});
</script>

<?php include("includes/footer.php"); ?>