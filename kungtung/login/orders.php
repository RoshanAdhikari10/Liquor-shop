<?php
session_start();
include("../config/database.php");

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Retained query logic from File 2
$query = mysqli_query($conn, "SELECT * FROM orders WHERE user_id='$user_id' ORDER BY created_at DESC");

// Collect orders for rendering and dynamic status pill generation
$orders = [];
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $orders[] = $row;
    }
}

// Distinct statuses present in user's orders for filter pills
$statusesPresent = array_values(array_unique(array_map(fn($o) => $o['status'], $orders)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders — Kung Tung Liquor Shop</title>

<!-- Google Fonts & FontAwesome -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500;9..144,600;9..144,700&family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">

<style>
:root{
  --bg:#180C12;
  --bg-panel:#241119;
  --bg-panel-2:#2C1420;
  --wine:#6B1029;
  --wine-light:#9B2242;
  --wine-glow:rgba(155,34,66,.35);
  --gold:#C9A961;
  --gold-bright:#E4C888;
  --cream:#F3E9DD;
  --muted:#AE96A0;
  --sage:#8A9678;
  --line:rgba(201,169,97,.18);
  --shadow: 0 30px 60px -20px rgba(0,0,0,.6);
  --ease: cubic-bezier(.22,1,.36,1);
}
*{box-sizing:border-box;}
body{
  margin:0;
  background:var(--bg);
  color:var(--cream);
  font-family:'Sora',sans-serif;
  -webkit-font-smoothing:antialiased;
  min-height:100vh;
}
h1,h2,h3,h4{font-family:'Fraunces',serif;font-weight:500;margin:0;letter-spacing:-.01em;}
p{margin:0;line-height:1.6;}
a{color:inherit;text-decoration:none;}
button{font-family:inherit;cursor:pointer;border:none;background:none;color:inherit;}

.vignette{
  position:fixed;inset:0;pointer-events:none;z-index:0;
  background:radial-gradient(120% 90% at 50% -10%, rgba(155,34,66,.14), transparent 60%),
             radial-gradient(140% 100% at 50% 110%, rgba(0,0,0,.55), transparent 55%);
}

.topbar{
  position:sticky;top:0;z-index:50;
  display:flex;align-items:center;justify-content:space-between;
  padding:20px 5vw;
  background:rgba(20,10,15,.92);
  border-bottom:1px solid var(--line);
  backdrop-filter:blur(14px);
}
.logo{font-family:'Fraunces',serif;font-size:19px;font-weight:600;display:flex;align-items:baseline;gap:6px;}
.logo .amp{color:var(--gold);font-style:italic;font-weight:400;}
.back-link{
  font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--muted);
  display:inline-flex;align-items:center;gap:8px;letter-spacing:.05em;
  transition:color .3s;
}
.back-link:hover{color:var(--gold);}

.wrap{max-width:920px;margin:0 auto;padding:60px 5vw 100px;position:relative;z-index:1;}

.eyebrow{
  font-family:'JetBrains Mono',monospace;font-size:11.5px;letter-spacing:.22em;
  text-transform:uppercase;color:var(--gold);display:inline-flex;align-items:center;gap:10px;
}
.eyebrow::before{content:"";width:22px;height:1px;background:var(--gold);display:inline-block;}
.page-title{font-size:clamp(30px,3.6vw,46px);margin:14px 0 36px;display:flex;align-items:center;gap:16px;}

/* ===== SEARCH + FILTERS ===== */
.controls{
  display:flex;gap:16px;flex-wrap:wrap;align-items:center;justify-content:space-between;
  margin-bottom:34px;
}
.search-box{
  flex:1;min-width:220px;position:relative;
  display:flex;align-items:center;
  border:1px solid var(--line);border-radius:100px;
  padding:12px 20px;background:var(--bg-panel);
  transition:border-color .3s;
}
.search-box:focus-within{border-color:var(--gold);}
.search-box svg{flex-shrink:0;margin-right:10px;opacity:.6;}
.search-box input{
  flex:1;background:transparent;border:none;outline:none;
  color:var(--cream);font-family:'Sora',sans-serif;font-size:14.5px;
}
.search-box input::placeholder{color:var(--muted);}
.search-clear{
  color:var(--muted);font-size:16px;padding:2px 4px;display:none;
}
.search-box.has-val .search-clear{display:block;}
.search-clear:hover{color:var(--gold);}

.filters{display:flex;gap:10px;flex-wrap:wrap;}
.filter-pill{
  padding:9px 18px;border-radius:100px;border:1px solid var(--line);
  font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);
  white-space:nowrap;transition:all .3s var(--ease);
}
.filter-pill:hover{border-color:var(--gold);color:var(--cream);}
.filter-pill.active{background:var(--gold);border-color:var(--gold);color:#1c0e14;font-weight:600;}

.results-count{
  font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--muted);
  margin-bottom:20px;
}

/* ===== ORDER CARDS ===== */
.order-card{
  background:linear-gradient(160deg, var(--bg-panel), var(--bg-panel-2));
  border:1px solid var(--line);
  border-radius:18px;
  margin-bottom:22px;
  overflow:hidden;
  transition:border-color .35s, box-shadow .35s, opacity .3s, transform .3s;
}
.order-card:hover{border-color:rgba(201,169,97,.5);box-shadow:var(--shadow);}
.order-card.hide{display:none;}

.order-head{
  display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;
  padding:22px 26px;border-bottom:1px solid var(--line);
}
.order-head h5{font-family:'JetBrains Mono',monospace;font-size:15px;color:var(--cream);letter-spacing:.02em;}
.order-head small{font-size:12.5px;color:var(--muted);display:block;margin-top:4px;}

.status-badge{
  font-family:'JetBrains Mono',monospace;font-size:11px;letter-spacing:.08em;text-transform:uppercase;
  padding:6px 14px;border-radius:100px;border:1px solid;
}
.status-Pending{color:#E4C888;border-color:rgba(228,200,136,.4);background:rgba(228,200,136,.08);}
.status-Processing{color:#8CA9D8;border-color:rgba(140,169,216,.4);background:rgba(140,169,216,.08);}
.status-Out-for-Delivery{color:#8AC0C8;border-color:rgba(138,192,200,.4);background:rgba(138,192,200,.08);}
.status-Completed{color:#8A9678;border-color:rgba(138,150,120,.45);background:rgba(138,150,120,.1);}
.status-Cancelled{color:#C86464;border-color:rgba(200,100,100,.4);background:rgba(200,100,100,.08);}

.order-body{padding:24px 26px;}
.order-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-bottom:26px;}
.og-label{font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);margin-bottom:8px;display:block;}
.og-val{font-size:14.5px;color:var(--cream);}
.og-total{font-family:'JetBrains Mono',monospace;font-size:22px;color:var(--gold-bright);}

.progress-track{height:4px;border-radius:100px;background:rgba(243,233,221,.08);overflow:hidden;margin-bottom:12px;}
.progress-fill{height:100%;background:linear-gradient(90deg,var(--wine-light),var(--gold));border-radius:100px;transition:width .6s var(--ease);}
.progress-steps{display:flex;justify-content:space-between;margin-bottom:24px;}
.progress-steps small{font-family:'JetBrains Mono',monospace;font-size:10.5px;color:var(--muted);letter-spacing:.04em;text-transform:uppercase;}
.progress-steps small.done{color:var(--gold-bright);}

.view-btn{
  display:inline-flex;align-items:center;gap:10px;
  padding:13px 26px;border-radius:100px;
  font-size:12px;letter-spacing:.08em;text-transform:uppercase;font-weight:600;
  border:1px solid var(--line);color:var(--cream);
  transition:border-color .3s, color .3s, background .3s;
}
.view-btn:hover{border-color:var(--gold);color:var(--gold-bright);background:rgba(201,169,97,.08);}

.empty-state{
  text-align:center;padding:80px 20px;color:var(--muted);
  border:1px dashed var(--line);border-radius:18px;
}
.empty-state .eyebrow{justify-content:center;margin-bottom:18px;}
.empty-state h3{font-size:22px;margin-bottom:10px;color:var(--cream);}
.empty-state p{margin-bottom:26px;}
.empty-state .btn-primary{
  display:inline-flex;align-items:center;gap:10px;padding:15px 28px;border-radius:100px;
  background:linear-gradient(120deg,var(--gold),var(--gold-bright));color:#1c0e14;
  font-size:12.5px;letter-spacing:.08em;text-transform:uppercase;font-weight:600;
}

/* ===== CONTACT MESSAGE ===== */
.contact-message {
  background:linear-gradient(160deg, var(--bg-panel), var(--bg-panel-2));
  border:1px solid var(--line);
  border-radius:18px;
  padding:30px 36px;
  margin-top:40px;
  text-align:center;
  transition:border-color .35s;
}
.contact-message:hover{border-color:rgba(201,169,97,.5);}
.contact-message h4{
  color:var(--gold-bright);
  font-size:18px;
  margin-bottom:8px;
  letter-spacing:.02em;
}
.contact-message p{
  color:var(--muted);
  font-size:14px;
  line-height:1.8;
}
.contact-message .contact-icon{
  color:var(--gold);
  margin-right:6px;
}
.contact-message .highlight{
  color:var(--cream);
  font-weight:500;
}

/* ===== MODAL ===== */
.modal-overlay{
  position:fixed;inset:0;z-index:1000;
  background:rgba(10,5,8,.75);backdrop-filter:blur(6px);
  display:none;align-items:center;justify-content:center;padding:24px;
}
.modal-overlay.show{display:flex;}
.modal-box{
  background:linear-gradient(160deg, var(--bg-panel), var(--bg-panel-2));
  border:1px solid var(--line);border-radius:18px;
  max-width:760px;width:100%;max-height:85vh;overflow-y:auto;
  box-shadow:var(--shadow);
  animation:modalIn .35s var(--ease);
}
@keyframes modalIn{from{opacity:0;transform:scale(.96) translateY(10px);} to{opacity:1;transform:scale(1) translateY(0);}}
.modal-head{
  display:flex;justify-content:space-between;align-items:center;
  padding:22px 26px;border-bottom:1px solid var(--line);position:sticky;top:0;
  background:var(--bg-panel);z-index:2;
}
.modal-head h5{font-size:19px;}
.modal-close{font-size:20px;color:var(--muted);padding:4px 8px;transition:color .3s;}
.modal-close:hover{color:var(--gold);}
.modal-body{padding:26px;color:var(--muted);font-size:14.5px;}

/* ===== RESPONSIVE ===== */
@media(max-width:700px){
  .order-grid{grid-template-columns:1fr;gap:16px;}
  .controls{flex-direction:column;align-items:stretch;}
  .filters{overflow-x:auto;flex-wrap:nowrap;padding-bottom:4px;}
  .contact-message{padding:24px 18px;}
}
</style>
</head>
<body>

<div class="vignette"></div>

<header class="topbar">
  <div class="logo">KUNG <span class="amp">&amp;</span> TUNG</div>
  <a class="back-link" href="../index.php">&larr; Back to shop</a>
</header>

<div class="wrap">
  <span class="eyebrow">Order History</span>
  <h1 class="page-title"><i class="fa-solid fa-box" style="font-size:0.8em; color:var(--gold);"></i> My Orders</h1>

  <?php if (count($orders) > 0): ?>
  <div class="controls">
    <div class="search-box" id="searchBox">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="#AE96A0" stroke-width="2"/><line x1="21" y1="21" x2="16.65" y2="16.65" stroke="#AE96A0" stroke-width="2" stroke-linecap="round"/></svg>
      <input type="text" id="searchInput" placeholder="Search by order number or city...">
      <button class="search-clear" id="searchClear">✕</button>
    </div>
    <div class="filters" id="filters">
      <button class="filter-pill active" data-status="all">All</button>
      <?php foreach ($statusesPresent as $s): ?>
        <button class="filter-pill" data-status="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></button>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="results-count" id="resultsCount"></div>
  <?php endif; ?>

  <div id="ordersList">
    <?php if (count($orders) === 0): ?>
      <div class="empty-state">
        <span class="eyebrow">Empty</span>
        <h3>No orders yet</h3>
        <p>You haven't placed any orders yet. Once you place an order, it'll show up here.</p>
        <a class="btn-primary" href="../index.php">Browse the Shop</a>
      </div>
    <?php else: ?>
      <?php foreach ($orders as $order):
        $status = $order['status'];
        $statusClass = 'status-' . str_replace(' ', '-', $status);

        switch ($status) {
          case 'Pending': $progress = 20; break;
          case 'Processing': $progress = 45; break;
          case 'Out for Delivery': $progress = 75; break;
          case 'Completed': $progress = 100; break;
          default: $progress = 100;
        }
        $searchBlob = strtolower($order['order_number'] . ' ' . $order['city']);
      ?>
      <div class="order-card" data-status="<?= htmlspecialchars($status) ?>" data-search="<?= htmlspecialchars($searchBlob) ?>">
        <div class="order-head">
          <div>
            <h5><?= htmlspecialchars($order['order_number']) ?></h5>
            <small><?= date("d M Y, h:i A", strtotime($order['created_at'])) ?></small>
          </div>
          <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
        </div>
        <div class="order-body">
          <div class="order-grid">
            <div>
              <span class="og-label">Total</span>
              <div class="og-val og-total">Rs. <?= number_format($order['total'], 2) ?></div>
            </div>
            <div>
              <span class="og-label">Payment</span>
              <div class="og-val"><?= htmlspecialchars($order['payment_method']) ?></div>
            </div>
            <div>
              <span class="og-label">Shipping Address</span>
              <div class="og-val"><?= htmlspecialchars($order['city']) ?></div>
            </div>
          </div>

          <?php if ($status !== 'Cancelled'): ?>
          <div class="progress-track"><div class="progress-fill" style="width:<?= $progress ?>%"></div></div>
          <div class="progress-steps">
            <small class="<?= $progress >= 20 ? 'done' : '' ?>">Pending</small>
            <small class="<?= $progress >= 45 ? 'done' : '' ?>">Processing</small>
            <small class="<?= $progress >= 75 ? 'done' : '' ?>">Out for Delivery</small>
            <small class="<?= $progress >= 100 ? 'done' : '' ?>">Completed</small>
          </div>
          <?php endif; ?>

          <button class="view-btn viewBtn" data-id="<?= (int)$order['id'] ?>">
            <i class="fa fa-eye"></i> View Details
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ===== CONTACT MESSAGE ===== -->
  <div class="contact-message">
    <h4><i class="fa-regular fa-circle-question contact-icon"></i> Need Help?</h4>
    <p>
      For any issues regarding your orders, delivery status, or refund requests, 
      please contact our support team at 
      <span class="highlight"><i class="fa-regular fa-envelope contact-icon"></i> support@kungtung.com</span> 
      or call us at <span class="highlight"><i class="fa-solid fa-phone contact-icon"></i> +977-1-1234567</span>.
      We're here to help!
    </p>
  </div>

  <div class="empty-state" id="noResults" style="display:none;">
    <span class="eyebrow">No matches</span>
    <h3>No orders found</h3>
    <p>Try a different search term or status filter.</p>
  </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="orderModal">
  <div class="modal-box">
    <div class="modal-head">
      <h5>Order Details</h5>
      <button class="modal-close" id="modalClose">✕</button>
    </div>
    <div class="modal-body" id="orderDetails">Loading...</div>
  </div>
</div>

<script>
/* ===== MODAL LOGIC ===== */
const modal = document.getElementById('orderModal');

function openModal(id){
  document.getElementById('orderDetails').innerHTML = 'Loading...';
  modal.classList.add('show');
  fetch('get-order-details.php?id=' + id)
    .then(r => r.text())
    .then(html => { document.getElementById('orderDetails').innerHTML = html; })
    .catch(err => { document.getElementById('orderDetails').innerHTML = 'Failed to load details.'; });
}

function closeModal(){ modal.classList.remove('show'); }

document.getElementById('modalClose').addEventListener('click', closeModal);
modal.addEventListener('click', e => { if(e.target === modal) closeModal(); });

document.addEventListener('click', e => {
  const btn = e.target.closest('.viewBtn');
  if(btn) openModal(btn.dataset.id);
});

/* ===== LIVE SEARCH & FILTER LOGIC ===== */
const searchInput = document.getElementById('searchInput');
const searchBox = document.getElementById('searchBox');
const searchClear = document.getElementById('searchClear');
const filters = document.getElementById('filters');
const cards = Array.from(document.querySelectorAll('.order-card'));
const resultsCount = document.getElementById('resultsCount');
const noResults = document.getElementById('noResults');

let currentStatus = 'all';
let currentSearch = '';

function applyFilters(){
  let visible = 0;
  cards.forEach(card => {
    const matchesStatus = currentStatus === 'all' || card.dataset.status === currentStatus;
    const matchesSearch = !currentSearch || card.dataset.search.includes(currentSearch);
    const show = matchesStatus && matchesSearch;
    card.classList.toggle('hide', !show);
    if(show) visible++;
  });

  if(resultsCount) resultsCount.textContent = visible + (visible === 1 ? ' order found' : ' orders found');
  if(noResults) noResults.style.display = (visible === 0 && cards.length > 0) ? 'block' : 'none';
}

if(searchInput){
  searchInput.addEventListener('input', () => {
    currentSearch = searchInput.value.trim().toLowerCase();
    searchBox.classList.toggle('has-val', currentSearch.length > 0);
    applyFilters();
  });
  searchClear.addEventListener('click', () => {
    searchInput.value = '';
    currentSearch = '';
    searchBox.classList.remove('has-val');
    applyFilters();
  });
}

if(filters){
  filters.addEventListener('click', e => {
    const pill = e.target.closest('.filter-pill');
    if(!pill) return;
    filters.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    pill.classList.add('active');
    currentStatus = pill.dataset.status;
    applyFilters();
  });
}

applyFilters();
</script>

</body>
</html>