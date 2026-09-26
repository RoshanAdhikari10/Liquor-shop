<?php

session_start();
include("../config/database.php");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    exit("
        <div class='alert alert-danger'>
            <i class='fa-solid fa-circle-exclamation me-2'></i>
            Invalid order.
        </div>
    ");
}

$order_id = (int)$_GET['id'];

$orderStmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($orderStmt, "i", $order_id);
mysqli_stmt_execute($orderStmt);
$orderResult = mysqli_stmt_get_result($orderStmt);

if (mysqli_num_rows($orderResult) === 0) {
    exit("
        <div class='alert alert-danger'>
            <i class='fa-solid fa-circle-exclamation me-2'></i>
            Order not found.
        </div>
    ");
}

$order = mysqli_fetch_assoc($orderResult);

/* ============================================================
   ORDER ITEMS
============================================================ */

$itemStmt = mysqli_prepare($conn, "
    SELECT oi.*, pv.volume 
    FROM order_items oi 
    LEFT JOIN product_variants pv ON oi.variant_id = pv.id 
    WHERE oi.order_id=?
");

mysqli_stmt_bind_param($itemStmt, "i", $order_id);
mysqli_stmt_execute($itemStmt);
$itemsResult = mysqli_stmt_get_result($itemStmt);

$items = [];
while ($item = mysqli_fetch_assoc($itemsResult)) {
    $items[] = $item;
}

/* ============================================================
   STATUS
============================================================ */

$currentStatus = $order['status'];

$statusSteps = [
    ['name' => 'Pending', 'icon' => 'fa-clock'],
    ['name' => 'Processing', 'icon' => 'fa-box-open'],
    ['name' => 'Out for Delivery', 'icon' => 'fa-truck-fast'],
    ['name' => 'Completed', 'icon' => 'fa-circle-check']
];

$statusIndex = array_search($currentStatus, array_column($statusSteps, 'name'));
if ($statusIndex === false) {
    $statusIndex = -1;
}

/* ============================================================
   PROGRESS WIDTH
============================================================ */

if ($statusIndex <= 0) {
    $progressWidth = 0;
} else {
    $progressWidth = ($statusIndex / (count($statusSteps) - 1)) * 82;
}

?>

<!-- ============================================================
     ORDER HERO
============================================================ -->

<div class="order-hero">
    <div class="order-hero-top">
        <div>
            <h2 class="order-number" data-order-number="<?= htmlspecialchars($order['order_number']) ?>">
                #<?= htmlspecialchars($order['order_number']) ?>
            </h2>
            <p class="order-date">
                <i class="fa-regular fa-calendar me-1"></i>
                <?= date("d M Y, h:i A", strtotime($order['created_at'])) ?>
            </p>
        </div>

        <div class="order-hero-actions">
            <a href="print-invoice.php?id=<?= $order['id'] ?>" target="_blank" class="order-action-btn invoice-btn">
                <i class="fa-solid fa-print"></i>
                Print Invoice
            </a>
        </div>
    </div>
</div>

<!-- ============================================================
     STATUS PROGRESS
============================================================ -->

<div class="order-progress">
    <div class="section-title">
        <h5>
            <i class="fa-solid fa-route me-2"></i>
            Order Progress
        </h5>
        <span>
            Current:
            <strong><?= htmlspecialchars($currentStatus) ?></strong>
        </span>
    </div>

    <?php if ($currentStatus !== "Cancelled"): ?>
        <div class="progress-track">
            <div class="progress-line"></div>
            <div class="progress-line-active" style="width: <?= $progressWidth ?>%;"></div>

            <?php foreach ($statusSteps as $index => $step): ?>
                <?php
                $isCompleted = $index < $statusIndex;
                $isCurrent = $index === $statusIndex;
                $classes = '';
                if ($isCompleted) $classes .= ' completed';
                if ($isCurrent) $classes .= ' current';
                ?>

                <div class="progress-step <?= $classes ?>">
                    <div class="progress-circle">
                        <?php if ($isCompleted): ?>
                            <i class="fa-solid fa-check"></i>
                        <?php else: ?>
                            <i class="fa-solid <?= $step['icon'] ?>"></i>
                        <?php endif; ?>
                    </div>

                    <!-- ADDED: data-order-id attribute for better identification -->
                    <button
                        type="button"
                        class="statusChangeBtn"
                        data-order-id="<?= $order['id'] ?>"
                        data-id="<?= $order['id'] ?>"
                        data-status="<?= htmlspecialchars($step['name']) ?>"
                        data-current="<?= htmlspecialchars($currentStatus) ?>"
                    >
                        <?= htmlspecialchars($step['name']) ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Cancel option -->
        <div class="mt-4 text-end">
            <button
                type="button"
                class="btn btn-sm btn-outline-danger statusChangeBtn"
                data-order-id="<?= $order['id'] ?>"
                data-id="<?= $order['id'] ?>"
                data-status="Cancelled"
                data-current="<?= htmlspecialchars($currentStatus) ?>"
            >
                <i class="fa-solid fa-ban me-1"></i>
                Cancel Order
            </button>
        </div>

    <?php else: ?>
        <div class="cancelled-progress">
            <i class="fa-solid fa-circle-xmark"></i>
            This order has been cancelled.
        </div>
    <?php endif; ?>
</div>

<!-- ============================================================
     CUSTOMER + ORDER INFORMATION
============================================================ -->

<div class="order-info-grid">

    <!-- CUSTOMER -->
    <div class="order-card">
        <div class="order-card-header">
            <i class="fa-solid fa-user"></i>
            <h6>Customer Information</h6>
        </div>

        <div class="order-card-body">
            <div class="info-row">
                <span class="info-label">Name</span>
                <span class="info-value"><?= htmlspecialchars($order['full_name']) ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Phone</span>
                <span class="info-value"><?= htmlspecialchars($order['phone']) ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Email</span>
                <span class="info-value">
                    <?= !empty($order['email']) ? htmlspecialchars($order['email']) : '-' ?>
                </span>
            </div>

            <div class="info-row">
                <span class="info-label">City</span>
                <span class="info-value"><?= htmlspecialchars($order['city']) ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Address</span>
                <span class="info-value address">
                    <?= nl2br(htmlspecialchars($order['address'])) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- ORDER -->
    <div class="order-card">
        <div class="order-card-header">
            <i class="fa-solid fa-credit-card"></i>
            <h6>Order Information</h6>
        </div>

        <div class="order-card-body">
            <div class="info-row">
                <span class="info-label">Payment</span>
                <span class="info-value"><?= htmlspecialchars($order['payment_method']) ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Status</span>
                <span class="info-value"><?= htmlspecialchars($currentStatus) ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Order ID</span>
                <span class="info-value">#<?= $order['id'] ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Order Date</span>
                <span class="info-value"><?= date("d M Y", strtotime($order['created_at'])) ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Time</span>
                <span class="info-value"><?= date("h:i A", strtotime($order['created_at'])) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     PRODUCTS
============================================================ -->

<div class="products-card">
    <div class="products-header">
        <h6>
            <i class="fa-solid fa-box-open me-2"></i>
            Ordered Products
        </h6>
        <span>
            <?= count($items) ?>
            <?= count($items) == 1 ? 'item' : 'items' ?>
        </span>
    </div>

    <div style="overflow-x:auto;">
        <table class="products-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Volume</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="product-name">
                                <?= htmlspecialchars($item['product_name']) ?>
                            </div>
                        </td>

                        <td>
                            <span class="product-volume">
                                <?= !empty($item['volume']) ? htmlspecialchars($item['volume']) : '-' ?>
                            </span>
                        </td>

                        <td>Rs. <?= number_format($item['price'], 2) ?></td>

                        <td>
                            <span class="quantity-badge">
                                <?= (int)$item['quantity'] ?>
                            </span>
                        </td>

                        <td>
                            <strong>Rs. <?= number_format($item['line_total'], 2) ?></strong>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     NOTES
============================================================ -->

<?php if (!empty($order['notes'])): ?>
    <div class="order-notes">
        <strong>
            <i class="fa-solid fa-note-sticky me-1"></i>
            Customer Note
        </strong>
        <p><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
    </div>
<?php endif; ?>

<!-- ============================================================
     TOTALS
============================================================ -->

<div class="order-total-box">
    <div class="total-card">
        <div class="total-row">
            <span>Subtotal</span>
            <span>Rs. <?= number_format($order['subtotal'], 2) ?></span>
        </div>

        <div class="total-row">
            <span>Shipping</span>
            <span>Rs. <?= number_format($order['shipping'], 2) ?></span>
        </div>

        <div class="total-row grand-total">
            <span>Total</span>
            <span>Rs. <?= number_format($order['total'], 2) ?></span>
        </div>
    </div>
</div>

<!-- ============================================================
     ADD THIS SCRIPT AT THE END TO RE-BIND EVENTS
============================================================ -->

<script>
// This ensures events are bound when content is loaded via AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Re-bind status buttons when this content is loaded
    if (typeof window.bindStatusButtons === 'function') {
        // Small delay to ensure DOM is ready
        setTimeout(function() {
            window.bindStatusButtons();
        }, 100);
    }
});

// Also expose a function to manually trigger binding
if (typeof window.bindStatusButtons === 'function') {
    // Wait for parent script to be ready
    setTimeout(function() {
        window.bindStatusButtons();
    }, 200);
}
</script>