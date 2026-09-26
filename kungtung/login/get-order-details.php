<?php
session_start();
include("../config/database.php");

if (!isset($_SESSION['user_id'])) {
    exit("Login required.");
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    exit("Invalid request.");
}

$order_id = (int) $_GET['id'];

/* ---------------- Order ---------------- */

$stmt = mysqli_prepare($conn, "
SELECT *
FROM orders
WHERE id=? AND user_id=?
LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    echo "<p style='padding:20px;color:var(--muted);'>Order not found.</p>";
    exit;
}

/* ---------------- Order Items ---------------- */

$itemStmt = mysqli_prepare($conn, "
SELECT
    oi.*,
    pv.volume
FROM order_items oi
LEFT JOIN product_variants pv
ON oi.variant_id = pv.id
WHERE oi.order_id=?
ORDER BY oi.id ASC
");
mysqli_stmt_bind_param($itemStmt, "i", $order_id);
mysqli_stmt_execute($itemStmt);
$itemsResult = mysqli_stmt_get_result($itemStmt);

$items = [];
while ($row = mysqli_fetch_assoc($itemsResult)) {
    $items[] = $row;
}

$status = $order['status'];
$statusClass = 'status-' . str_replace(' ', '-', $status);

switch ($status) {
    case 'Pending':          $progress = 20; break;
    case 'Processing':       $progress = 45; break;
    case 'Out for Delivery': $progress = 75; break;
    case 'Completed':        $progress = 100; break;
    default:                 $progress = 100; // Cancelled
}
?>
<style>
  .od-wrap{font-family:'Sora',sans-serif;color:var(--cream);}

  .od-status-row{
    display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;
    margin-bottom:22px;
  }
  .od-order-no{font-family:'JetBrains Mono',monospace;font-size:16px;color:var(--gold-bright);letter-spacing:.02em;}
  .od-order-date{font-size:12.5px;color:var(--muted);margin-top:4px;}

  .od-progress-track{height:4px;border-radius:100px;background:rgba(243,233,221,.08);overflow:hidden;margin-bottom:10px;}
  .od-progress-fill{height:100%;background:linear-gradient(90deg,var(--wine-light),var(--gold));border-radius:100px;transition:width .6s var(--ease);}
  .od-progress-steps{display:flex;justify-content:space-between;margin-bottom:28px;}
  .od-progress-steps span{font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--muted);letter-spacing:.04em;text-transform:uppercase;}
  .od-progress-steps span.done{color:var(--gold-bright);}

  .od-cancelled-note{
    display:flex;align-items:center;gap:10px;
    background:rgba(200,100,100,.08);border:1px solid rgba(200,100,100,.35);
    color:#C86464;padding:12px 16px;border-radius:12px;font-size:13px;margin-bottom:24px;
  }

  .od-section{margin-bottom:28px;}
  .od-section-title{
    font-family:'JetBrains Mono',monospace;font-size:11px;letter-spacing:.12em;text-transform:uppercase;
    color:var(--gold);margin:0 0 14px;display:flex;align-items:center;gap:8px;
  }

  /* Shipping + payment two-column info card */
  .od-info-grid{
    display:grid;grid-template-columns:1fr 1fr;gap:18px;
    padding:18px;border:1px solid var(--line);border-radius:14px;
    background:rgba(255,255,255,.02);
  }
  .od-info-item small{
    display:block;font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:.08em;
    text-transform:uppercase;color:var(--muted);margin-bottom:6px;
  }
  .od-info-item div{font-size:14px;color:var(--cream);line-height:1.5;word-break:break-word;}
  .od-info-item.full{grid-column:1/-1;}

  /* Item rows (replaces the old Bootstrap table) */
  .od-item{
    display:flex;align-items:center;gap:14px;
    padding:14px 0;border-bottom:1px solid var(--line);
  }
  .od-item:last-child{border-bottom:none;}
  .od-item-badge{
    width:30px;height:30px;border-radius:50%;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
    background:rgba(201,169,97,.1);border:1px solid var(--line);
    font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--gold);
  }
  .od-item-info{flex:1;min-width:0;}
  .od-item-name{font-size:14.5px;color:var(--cream);margin-bottom:4px;
    overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
  .od-item-meta{font-size:12px;color:var(--muted);display:flex;gap:8px;flex-wrap:wrap;}
  .od-item-meta .dot{opacity:.5;}
  .od-item-price{font-family:'JetBrains Mono',monospace;font-size:14px;color:var(--gold-bright);white-space:nowrap;text-align:right;}
  .od-item-price small{display:block;font-size:11px;color:var(--muted);font-weight:400;margin-top:2px;}

  /* Totals */
  .od-totals{padding:18px;border:1px solid var(--line);border-radius:14px;background:rgba(255,255,255,.02);}
  .od-totals-row{display:flex;justify-content:space-between;font-size:13.5px;color:var(--muted);padding:6px 0;}
  .od-totals-row.grand{
    border-top:1px solid var(--line);margin-top:8px;padding-top:14px;
    font-family:'JetBrains Mono',monospace;font-size:18px;color:var(--gold-bright);font-weight:600;
  }
  .od-totals-row.grand span:first-child{color:var(--cream);font-family:'Sora',sans-serif;font-size:14px;font-weight:400;}

  @media(max-width:520px){
    .od-info-grid{grid-template-columns:1fr;}
    .od-item-badge{width:26px;height:26px;font-size:11px;}
    .od-item-meta{gap:6px;}
  }
</style>

<div class="od-wrap">

  <div class="od-status-row">
    <div>
      <div class="od-order-no"><?= htmlspecialchars($order['order_number']) ?></div>
      <div class="od-order-date"><?= date("d M Y, h:i A", strtotime($order['created_at'])) ?></div>
    </div>
    <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
  </div>

  <?php if ($status === 'Cancelled'): ?>
    <div class="od-cancelled-note">
      <i class="fa-solid fa-circle-xmark"></i> This order was cancelled.
    </div>
  <?php else: ?>
    <div class="od-progress-track"><div class="od-progress-fill" style="width:<?= $progress ?>%"></div></div>
    <div class="od-progress-steps">
      <span class="<?= $progress >= 20 ? 'done' : '' ?>">Pending</span>
      <span class="<?= $progress >= 45 ? 'done' : '' ?>">Processing</span>
      <span class="<?= $progress >= 75 ? 'done' : '' ?>">Out for Delivery</span>
      <span class="<?= $progress >= 100 ? 'done' : '' ?>">Completed</span>
    </div>
  <?php endif; ?>

  <div class="od-section">
    <h6 class="od-section-title"><i class="fa-solid fa-location-dot"></i> Shipping &amp; Payment</h6>
    <div class="od-info-grid">
      <div class="od-info-item">
        <small>Name</small>
        <div><?= htmlspecialchars($order['full_name']) ?></div>
      </div>
      <div class="od-info-item">
        <small>Phone</small>
        <div><?= htmlspecialchars($order['phone']) ?></div>
      </div>
      <div class="od-info-item">
        <small>Email</small>
        <div><?= htmlspecialchars($order['email']) ?></div>
      </div>
      <div class="od-info-item">
        <small>Payment Method</small>
        <div><?= htmlspecialchars($order['payment_method']) ?></div>
      </div>
      <div class="od-info-item full">
        <small>Delivery Address</small>
        <div><?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?></div>
      </div>
      <?php if (!empty($order['notes'])): ?>
      <div class="od-info-item full">
        <small>Order Notes</small>
        <div><?= nl2br(htmlspecialchars($order['notes'])) ?></div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (count($items) > 0): ?>
  <div class="od-section">
    <h6 class="od-section-title"><i class="fa-solid fa-cart-shopping"></i> Ordered Products (<?= count($items) ?>)</h6>
    <div>
      <?php $count = 1; foreach ($items as $item): ?>
      <div class="od-item">
        <div class="od-item-badge"><?= $count++ ?></div>
        <div class="od-item-info">
          <div class="od-item-name"><?= htmlspecialchars($item['product_name']) ?></div>
          <div class="od-item-meta">
            <?php if (!empty($item['volume'])): ?>
              <span><?= htmlspecialchars($item['volume']) ?></span>
              <span class="dot">&middot;</span>
            <?php endif; ?>
            <span>Qty <?= (int) $item['quantity'] ?></span>
            <span class="dot">&middot;</span>
            <span>Rs. <?= number_format($item['price'], 2) ?> each</span>
          </div>
        </div>
        <div class="od-item-price">
          Rs. <?= number_format($item['line_total'], 2) ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="od-section" style="margin-bottom:0;">
    <h6 class="od-section-title"><i class="fa-solid fa-receipt"></i> Order Summary</h6>
    <div class="od-totals">
      <div class="od-totals-row">
        <span>Subtotal</span>
        <span>Rs. <?= number_format($order['subtotal'], 2) ?></span>
      </div>
      <div class="od-totals-row">
        <span>Shipping</span>
        <span><?= (float) $order['shipping'] > 0 ? 'Rs. ' . number_format($order['shipping'], 2) : 'Free' ?></span>
      </div>
      <div class="od-totals-row grand">
        <span>Grand Total</span>
        <span>Rs. <?= number_format($order['total'], 2) ?></span>
      </div>
    </div>
  </div>

</div>