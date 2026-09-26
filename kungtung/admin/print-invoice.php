<?php

session_start();

include("../config/database.php");


if (!isset($_GET['id']) || empty($_GET['id'])) {

    die("Invalid order.");

}


$orderId = (int)$_GET['id'];


/* ============================================================
   ORDER
============================================================ */

$orderStmt = mysqli_prepare(
    $conn,
    "SELECT * FROM orders WHERE id=? LIMIT 1"
);

mysqli_stmt_bind_param(
    $orderStmt,
    "i",
    $orderId
);

mysqli_stmt_execute($orderStmt);

$orderResult =
    mysqli_stmt_get_result($orderStmt);


if (mysqli_num_rows($orderResult) === 0) {

    die("Order not found.");

}


$order = mysqli_fetch_assoc($orderResult);


/* ============================================================
   ITEMS
============================================================ */

$itemStmt = mysqli_prepare(
    $conn,
    "
    SELECT
        oi.*,
        pv.volume
    FROM order_items oi
    LEFT JOIN product_variants pv
        ON oi.variant_id = pv.id
    WHERE oi.order_id=?
    "
);

mysqli_stmt_bind_param(
    $itemStmt,
    "i",
    $orderId
);

mysqli_stmt_execute($itemStmt);

$itemsResult =
    mysqli_stmt_get_result($itemStmt);


$items = [];

while ($item = mysqli_fetch_assoc($itemsResult)) {

    $items[] = $item;

}


/* ============================================================
   STATUS -> visual language
============================================================ */

$statusMap = [
    'Pending'          => ['label' => 'Pending',          'tone' => 'pending'],
    'Processing'       => ['label' => 'Processing',       'tone' => 'processing'],
    'Out for Delivery' => ['label' => 'Out for Delivery', 'tone' => 'transit'],
    'Completed'        => ['label' => 'Completed',        'tone' => 'done'],
    'Cancelled'        => ['label' => 'Cancelled',        'tone' => 'cancelled'],
];

$statusInfo = $statusMap[$order['status']] ?? ['label' => $order['status'], 'tone' => 'pending'];

$itemCount = count($items);

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
    Invoice #<?= htmlspecialchars($order['order_number']) ?> · Kung Tung Liquor Shop
</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500&family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<style>

:root {

    --ink: #1c2130;
    --ink-soft: #2c3245;
    --paper: #f3efe6;
    --card: #fffdfa;
    --line: #e6e0d2;
    --line-strong: #d8d0bd;
    --text: #262b3a;
    --text-muted: #7d8194;
    --gold: #a97a34;
    --gold-deep: #8a611f;
    --gold-light: #e8c98c;
    --cream: #f7f3e9;

    --tone-pending-bg: #fbf1de;
    --tone-pending-fg: #92660f;
    --tone-processing-bg: #e6edf9;
    --tone-processing-fg: #2f4f9e;
    --tone-transit-bg: #e4f1ee;
    --tone-transit-fg: #1f6e5c;
    --tone-done-bg: #e5f3e4;
    --tone-done-fg: #2c7a31;
    --tone-cancelled-bg: #fbe8e6;
    --tone-cancelled-fg: #a3372a;

}

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    background:
        radial-gradient(circle at 12% -10%, #3a4256 0%, transparent 45%),
        var(--paper);

    font-family: 'Inter', Arial, sans-serif;

    color: var(--text);

    -webkit-font-smoothing: antialiased;

}


.mono {
    font-family: 'JetBrains Mono', ui-monospace, monospace;
}


/* ============================================================
   TOP ACTION BAR
============================================================ */

.print-buttons {

    max-width: 840px;

    margin: 18px auto 0;

    padding: 0 18px;

    display: flex;

    justify-content: flex-end;

    gap: 10px;

}


.print-buttons button {

    border: 0;

    border-radius: 999px;

    padding: 11px 22px;

    cursor: pointer;

    font-weight: 600;

    font-size: 13px;

    font-family: 'Inter', sans-serif;

    display: inline-flex;

    align-items: center;

    gap: 7px;

    transition: transform .15s ease, box-shadow .15s ease;

}

.print-buttons button:hover {
    transform: translateY(-1px);
}


.print-button {

    background: var(--ink);

    color: var(--gold-light);

    box-shadow: 0 8px 20px rgba(28,33,48,.25);

}


.close-button {

    background: transparent;

    color: var(--text-muted);

    border: 1.5px solid var(--line-strong) !important;

}


/* ============================================================
   INVOICE SHEET
============================================================ */

.invoice {

    width: 210mm;

    min-height: 297mm;

    background: var(--card);

    margin: 16px auto 40px;

    box-shadow: 0 20px 60px rgba(28,33,48,.16);

    border-radius: 14px;

    overflow: hidden;

}


/* ---- header band ---- */

.invoice-header {

    background:
        linear-gradient(155deg, #232838 0%, var(--ink) 55%, #171b28 100%);

    color: #f4efe2;

    padding: 15mm 18mm 13mm;

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    position: relative;

    overflow: hidden;

}


.invoice-header::after {

    content: "";

    position: absolute;

    right: -60px;

    top: -60px;

    width: 220px;

    height: 220px;

    border-radius: 50%;

    background: radial-gradient(circle, rgba(233,197,140,.16), transparent 70%);

}


.brand-mark {

    display: flex;

    align-items: center;

    gap: 13px;

}


.brand-glyph {

    width: 46px;

    height: 46px;

    border-radius: 12px;

    background: linear-gradient(150deg, var(--gold-light), var(--gold-deep));

    display: flex;

    align-items: center;

    justify-content: center;

    font-family: 'Fraunces', serif;

    font-weight: 700;

    font-size: 20px;

    color: #241a08;

    flex: none;

}


.shop-name {

    font-family: 'Fraunces', serif;

    font-size: 25px;

    font-weight: 600;

    letter-spacing: .2px;

    margin: 0 0 4px;

    color: #fdf9ef;

}


.shop-tag {

    font-size: 10.5px;

    letter-spacing: 1.6px;

    text-transform: uppercase;

    color: var(--gold-light);

    font-weight: 600;

}


.shop-details {

    margin-top: 12px;

    font-size: 11.5px;

    color: rgba(244,239,226,.62);

    line-height: 1.75;

}


.invoice-title {

    text-align: right;

    position: relative;

    z-index: 1;

}


.invoice-title .eyebrow {

    font-size: 10.5px;

    letter-spacing: 2.5px;

    text-transform: uppercase;

    color: rgba(244,239,226,.55);

    margin-bottom: 6px;

}


.invoice-title h1 {

    margin: 0;

    font-family: 'Fraunces', serif;

    font-size: 30px;

    font-weight: 600;

    letter-spacing: .5px;

    color: #fdf9ef;

}


.invoice-number {

    margin-top: 8px;

    font-size: 12px;

    color: var(--gold-light);

}


.seal {

    margin-top: 16px;

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 7px 14px 7px 10px;

    border-radius: 999px;

    font-size: 11.5px;

    font-weight: 700;

    letter-spacing: .3px;

}

.seal .dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
}

.seal.tone-pending     { background: var(--tone-pending-bg);    color: var(--tone-pending-fg); }
.seal.tone-processing  { background: var(--tone-processing-bg); color: var(--tone-processing-fg); }
.seal.tone-transit     { background: var(--tone-transit-bg);    color: var(--tone-transit-fg); }
.seal.tone-done        { background: var(--tone-done-bg);       color: var(--tone-done-fg); }
.seal.tone-cancelled   { background: var(--tone-cancelled-bg);  color: var(--tone-cancelled-fg); }

.seal.tone-pending .dot     { background: var(--tone-pending-fg); }
.seal.tone-processing .dot  { background: var(--tone-processing-fg); }
.seal.tone-transit .dot     { background: var(--tone-transit-fg); }
.seal.tone-done .dot        { background: var(--tone-done-fg); }
.seal.tone-cancelled .dot   { background: var(--tone-cancelled-fg); }


/* ---- body ---- */

.invoice-body {

    padding: 13mm 18mm 15mm;

}


.invoice-meta {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 30px;

    margin-bottom: 30px;

}


.meta-card {

    background: var(--cream);

    border: 1px solid var(--line);

    border-radius: 12px;

    padding: 16px 18px;

}


.meta-title {

    font-size: 10.5px;

    text-transform: uppercase;

    letter-spacing: 1.2px;

    color: var(--gold-deep);

    font-weight: 700;

    margin-bottom: 9px;

    display: flex;

    align-items: center;

    gap: 6px;

}


.meta-name {

    font-family: 'Fraunces', serif;

    font-size: 16.5px;

    font-weight: 600;

    margin-bottom: 5px;

    color: var(--ink);

}


.meta-text {

    font-size: 12.5px;

    color: var(--text-muted);

    line-height: 1.75;

}


.meta-text strong {
    color: var(--text);
    font-weight: 600;
}


/* ---- product table ---- */

.section-label {

    font-size: 10.5px;

    text-transform: uppercase;

    letter-spacing: 1.2px;

    color: var(--gold-deep);

    font-weight: 700;

    margin: 0 0 10px 2px;

}


.invoice-table {

    width: 100%;

    border-collapse: collapse;

    border-radius: 10px;

    overflow: hidden;

    box-shadow: 0 0 0 1px var(--line);

}


.invoice-table th {

    background: var(--ink);

    color: var(--gold-light);

    padding: 12px 14px;

    text-align: left;

    font-size: 10.5px;

    text-transform: uppercase;

    letter-spacing: .6px;

    font-weight: 600;

}


.invoice-table td {

    padding: 14px;

    border-bottom: 1px solid var(--line);

    font-size: 13px;

    background: var(--card);

}


.invoice-table tbody tr:last-child td {
    border-bottom: 0;
}


.invoice-table tbody tr:nth-child(even) td {
    background: #fbf9f4;
}


.product-name {

    font-weight: 700;

    font-size: 13.5px;

    color: var(--ink);

}


.volume-chip {

    display: inline-block;

    margin-top: 5px;

    padding: 2px 9px;

    border-radius: 999px;

    background: var(--tone-processing-bg);

    color: var(--tone-processing-fg);

    font-size: 10.5px;

    font-weight: 600;

}


.qty-chip {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-width: 26px;

    padding: 3px 8px;

    border-radius: 7px;

    background: var(--cream);

    border: 1px solid var(--line);

    font-weight: 700;

    font-size: 12px;

}


.text-right {
    text-align: right !important;
}


.amount-cell {

    font-weight: 700;

    color: var(--ink);

}


/* ---- bottom: notes + totals ---- */

.invoice-bottom {

    display: flex;

    justify-content: space-between;

    gap: 28px;

    margin-top: 28px;

    align-items: flex-start;

}


.notes {

    flex: 1;

    background: var(--cream);

    border: 1px dashed var(--line-strong);

    border-radius: 12px;

    padding: 16px 18px;

}


.notes-title {

    font-size: 10.5px;

    text-transform: uppercase;

    letter-spacing: 1.2px;

    color: var(--gold-deep);

    font-weight: 700;

    margin-bottom: 7px;

}


.notes p {

    font-size: 12.5px;

    color: var(--text-muted);

    line-height: 1.7;

    margin: 0;

}


.totals {

    width: 300px;

    flex: none;

    background: var(--card);

    border: 1px solid var(--line);

    border-radius: 12px;

    padding: 18px 20px;

}


.total-row {

    display: flex;

    justify-content: space-between;

    padding: 7px 0;

    font-size: 12.5px;

    color: var(--text-muted);

}


.total-row.final {

    border-top: 2px solid var(--ink);

    margin-top: 8px;

    padding-top: 14px;

    color: var(--ink);

    font-size: 19px;

    font-weight: 800;

    font-family: 'Fraunces', serif;

}


.total-row.final span:last-child {
    color: var(--gold-deep);
}


/* ---- payment strip ---- */

.payment {

    margin-top: 24px;

    padding: 15px 18px;

    background: linear-gradient(120deg, #232838, var(--ink));

    color: #f4efe2;

    border-radius: 12px;

    font-size: 12.5px;

    display: flex;

    align-items: center;

    gap: 10px;

}


.payment .badge {

    font-family: 'JetBrains Mono', monospace;

    background: rgba(232,201,140,.15);

    color: var(--gold-light);

    padding: 4px 10px;

    border-radius: 6px;

    font-size: 11px;

    font-weight: 600;

    letter-spacing: .3px;

    flex: none;

}


/* ---- footer ---- */

.footer {

    margin-top: 34px;

    padding-top: 16px;

    border-top: 1px solid var(--line);

    text-align: center;

    font-size: 10.5px;

    color: var(--text-muted);

    line-height: 1.8;

}


.footer strong {
    color: var(--ink);
}


/* ============================================================
   PRINT
============================================================ */

@media print {

    @page {

        size: A4;

        margin: 0;

    }


    body {

        background: white;

    }


    .invoice {

        width: 210mm;

        min-height: 297mm;

        margin: 0;

        box-shadow: none;

        border-radius: 0;

    }


    .invoice-table th,
    .invoice-header,
    .payment {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }


    .print-buttons {

        display: none;

    }

}


/* ============================================================
   RESPONSIVE (screen / mobile view)
============================================================ */

@media (max-width: 900px) {

    .invoice {

        width: 100%;

        min-height: 0;

        margin: 14px auto 30px;

        border-radius: 0;

    }

    .print-buttons {
        max-width: 100%;
    }

}


@media (max-width: 640px) {

    .invoice-header {

        flex-direction: column;

        gap: 22px;

        padding: 26px 20px;

    }

    .invoice-title {
        text-align: left;
        width: 100%;
    }

    .invoice-body {
        padding: 22px 16px 26px;
    }

    .invoice-meta {
        grid-template-columns: 1fr;
        gap: 14px;
    }

    /* card-style table on small screens */

    .invoice-table thead {
        display: none;
    }

    .invoice-table, .invoice-table tbody, .invoice-table tr, .invoice-table td {
        display: block;
        width: 100%;
    }

    .invoice-table {
        box-shadow: none;
    }

    .invoice-table tr {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 12px;
        margin-bottom: 12px;
        padding: 12px 14px;
    }

    .invoice-table tbody tr:nth-child(even) td {
        background: transparent;
    }

    .invoice-table td {
        border-bottom: 0;
        padding: 4px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }

    .invoice-table td:first-child {
        display: block;
    }

    .invoice-table td::before {
        content: attr(data-label);
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--text-muted);
        font-weight: 600;
    }

    .invoice-table td.text-right {
        text-align: left !important;
    }

    .invoice-bottom {
        flex-direction: column;
    }

    .totals {
        width: 100%;
    }

}

</style>

</head>


<body>


<div class="print-buttons">

    <button
        class="close-button"
        onclick="window.close()">

        Close

    </button>


    <button
        class="print-button"
        onclick="window.print()">

        🖨 Print / Save PDF

    </button>

</div>


<div class="invoice">


    <!-- ========================================================
         HEADER
    ========================================================= -->

    <div class="invoice-header">

        <div>

            <div class="brand-mark">

                <div class="brand-glyph">KT</div>

                <div>
                    <div class="shop-name">Kung Tung Liquor Shop</div>
                    <div class="shop-tag">Fine Spirits &amp; Beverages</div>
                </div>

            </div>

            <div class="shop-details">

                Pokhara, Nepal<br>

                Phone: +977-XXXXXXXXXX<br>

                Email: admin@kungtungliquor.com

            </div>

        </div>


        <div class="invoice-title">

            <div class="eyebrow">Customer Invoice</div>

            <h1>Invoice</h1>

            <div class="invoice-number mono">

                #<?= htmlspecialchars(
                    $order['order_number']
                ) ?>

            </div>

            <div class="seal tone-<?= htmlspecialchars($statusInfo['tone']) ?>">
                <span class="dot"></span>
                <?= htmlspecialchars($statusInfo['label']) ?>
            </div>

        </div>

    </div>


    <div class="invoice-body">

        <!-- ====================================================
             CUSTOMER + ORDER
        ===================================================== -->

        <div class="invoice-meta">


            <div class="meta-card">

                <div class="meta-title">
                    👤 Bill To
                </div>

                <div class="meta-name">

                    <?= htmlspecialchars(
                        $order['full_name']
                    ) ?>

                </div>

                <div class="meta-text">

                    <?= htmlspecialchars(
                        $order['phone']
                    ) ?><br>

                    <?= !empty($order['email'])
                        ? htmlspecialchars($order['email'])
                        : ''
                    ?>

                    <br>

                    <?= htmlspecialchars(
                        $order['address']
                    ) ?>

                    <br>

                    <?= htmlspecialchars(
                        $order['city']
                    ) ?>

                </div>

            </div>


            <div class="meta-card">

                <div class="meta-title">
                    🧾 Order Information
                </div>

                <div class="meta-text">

                    <strong>Order Date</strong><br>

                    <?= date(
                        "d M Y, h:i A",
                        strtotime($order['created_at'])
                    ) ?>

                    <br><br>

                    <strong>Payment Method</strong><br>

                    <?= htmlspecialchars(
                        $order['payment_method']
                    ) ?>

                    <br><br>

                    <strong>Items</strong><br>

                    <?= $itemCount ?> product<?= $itemCount === 1 ? '' : 's' ?>

                </div>

            </div>


        </div>


        <!-- ====================================================
             PRODUCTS
        ===================================================== -->

        <div class="section-label">Order Summary</div>

        <table class="invoice-table">

            <thead>

                <tr>

                    <th style="width:44%;">
                        Product
                    </th>

                    <th>
                        Price
                    </th>

                    <th>
                        Qty
                    </th>

                    <th class="text-right">
                        Amount
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php foreach ($items as $item): ?>

                    <tr>

                        <td data-label="Product">

                            <div class="product-name">

                                <?= htmlspecialchars(
                                    $item['product_name']
                                ) ?>

                            </div>

                            <?php if (!empty($item['volume'])): ?>

                                <span class="volume-chip">

                                    <?= htmlspecialchars(
                                        $item['volume']
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </td>


                        <td data-label="Price">

                            Rs.
                            <?= number_format(
                                $item['price'],
                                2
                            ) ?>

                        </td>


                        <td data-label="Qty">

                            <span class="qty-chip">
                                <?= (int)$item['quantity'] ?>
                            </span>

                        </td>


                        <td data-label="Amount" class="text-right amount-cell">

                            Rs.
                            <?= number_format(
                                $item['line_total'],
                                2
                            ) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>


        <!-- ====================================================
             TOTALS
        ===================================================== -->

        <div class="invoice-bottom">


            <div class="notes">

                <?php if (!empty($order['notes'])): ?>

                    <div class="notes-title">
                        Customer Note
                    </div>

                    <p>

                        <?= nl2br(
                            htmlspecialchars(
                                $order['notes']
                            )
                        ) ?>

                    </p>

                <?php else: ?>

                    <div class="notes-title">
                        Thank You
                    </div>

                    <p>
                        Thank you for shopping with Kung Tung Liquor Shop.
                        We hope you enjoy your order — cheers!
                    </p>

                <?php endif; ?>

            </div>


            <div class="totals">

                <div class="total-row">

                    <span>Subtotal</span>

                    <span>

                        Rs.
                        <?= number_format(
                            $order['subtotal'],
                            2
                        ) ?>

                    </span>

                </div>


                <div class="total-row">

                    <span>Shipping</span>

                    <span>

                        Rs.
                        <?= number_format(
                            $order['shipping'],
                            2
                        ) ?>

                    </span>

                </div>


                <div class="total-row final">

                    <span>Total</span>

                    <span>

                        Rs.
                        <?= number_format(
                            $order['total'],
                            2
                        ) ?>

                    </span>

                </div>

            </div>


        </div>


        <!-- ====================================================
             PAYMENT
        ===================================================== -->

        <div class="payment">

            <span class="badge">
                <?= htmlspecialchars(
                    strtoupper($order['payment_method'])
                ) ?>
            </span>

            <span>

                <?php if (
                    strtoupper($order['payment_method'])
                    === 'COD'
                ): ?>

                    Please collect the invoice total from the customer upon delivery.

                <?php else: ?>

                    Payment received via <?= htmlspecialchars($order['payment_method']) ?>.

                <?php endif; ?>

            </span>

        </div>


        <!-- ====================================================
             FOOTER
        ===================================================== -->

        <div class="footer">

            This is a computer-generated invoice. No signature is required.

            <br>

            <strong>Kung Tung Liquor Shop</strong> · Pokhara, Nepal

        </div>

    </div>


</div>


<script>

window.addEventListener("load", function() {

    /*
       Uncomment the next line if you want the
       print dialog to automatically open.
    */

    // window.print();

});

</script>


</body>

</html>