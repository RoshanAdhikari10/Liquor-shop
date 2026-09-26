<?php
session_start();

header('Content-Type: application/json');

include("../config/database.php");
require_once("../login/email_service.php");

if(!isset($_SESSION['user_id']))
{
    echo json_encode([
        'success'=>false,
        'error'=>'login_required'
    ]);
    exit;
}

$user_id=(int)$_SESSION['user_id'];

$full_name=trim($_POST['full_name'] ?? '');
$phone=trim($_POST['phone'] ?? '');
$email=trim($_POST['email'] ?? '');
$address=trim($_POST['address'] ?? '');
$city=trim($_POST['city'] ?? '');
$notes=trim($_POST['notes'] ?? '');

if(
    $full_name=='' ||
    $phone=='' ||
    $address=='' ||
    $city==''
)
{
    echo json_encode([
        'success'=>false,
        'error'=>'missing_fields'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Cart Items
|--------------------------------------------------------------------------
*/

$cStmt=mysqli_prepare($conn,"

SELECT

ci.product_id,
ci.variant_id,
ci.quantity,

p.name,

pv.volume,
pv.price,
pv.stock

FROM cart_items ci

INNER JOIN products p
ON ci.product_id=p.id

INNER JOIN product_variants pv
ON ci.variant_id=pv.id

WHERE

ci.user_id=?

AND

p.status='Active'

");

mysqli_stmt_bind_param(
    $cStmt,
    "i",
    $user_id
);

mysqli_stmt_execute($cStmt);

$cRes=mysqli_stmt_get_result($cStmt);

$items=[];

while($row=mysqli_fetch_assoc($cRes))
{
    $items[]=$row;
}

if(empty($items))
{
    echo json_encode([
        'success'=>false,
        'error'=>'empty_cart'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Stock Check
|--------------------------------------------------------------------------
*/

foreach($items as $item)
{
    if($item['stock'] < $item['quantity'])
    {
        echo json_encode([
            'success'=>false,
            'error'=>'out_of_stock',
            'product'=>$item['name']
        ]);

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Totals
|--------------------------------------------------------------------------
*/

$subtotal=0;

foreach($items as $item)
{
    $subtotal +=
    $item['price'] * $item['quantity'];
}

$shipping = $subtotal > 0 ? 100 : 0;

$total = $subtotal + $shipping;

$order_number="KT"
.date("ymd")
.strtoupper(substr(bin2hex(random_bytes(3)),0,5));

$order_id = null;

mysqli_begin_transaction($conn);

try
{

    /*
    |--------------------------------------------------------------------------
    | Create Order
    |--------------------------------------------------------------------------
    */

    $oStmt = mysqli_prepare($conn, "

    INSERT INTO orders
    (
        user_id,
        order_number,
        full_name,
        phone,
        email,
        address,
        city,
        notes,
        payment_method,
        subtotal,
        shipping,
        total,
        status
    )

    VALUES

    (
        ?,?,?,?,?,?,?,?,
        'COD',
        ?,?,?,
        'Pending'
    )

    ");

    mysqli_stmt_bind_param(

        $oStmt,

        "isssssssddd",

        $user_id,
        $order_number,
        $full_name,
        $phone,
        $email,
        $address,
        $city,
        $notes,
        $subtotal,
        $shipping,
        $total

    );

    mysqli_stmt_execute($oStmt);

    $order_id = mysqli_insert_id($conn);


    /*
    |--------------------------------------------------------------------------
    | Prepare Statements
    |--------------------------------------------------------------------------
    */

    $itemStmt = mysqli_prepare($conn, "

    INSERT INTO order_items
    (
        order_id,
        product_id,
        variant_id,
        product_name,
        volume,
        price,
        quantity,
        line_total
    )

    VALUES

    (?,?,?,?,?,?,?,?)

    ");

    $stockStmt = mysqli_prepare($conn, "

    UPDATE product_variants

    SET stock = stock - ?

    WHERE id = ?

    ");


    /*
    |--------------------------------------------------------------------------
    | Save Order Items
    |--------------------------------------------------------------------------
    */

    foreach($items as $item)
    {

        $product_id = (int)$item['product_id'];

        $variant_id = (int)$item['variant_id'];

        $product_name = $item['name'];

        $volume = $item['volume'];

        $price = (float)$item['price'];

        $qty = (int)$item['quantity'];

        $lineTotal = $price * $qty;


        mysqli_stmt_bind_param(

            $itemStmt,

            "iisssdid",

            $order_id,
            $product_id,
            $variant_id,
            $product_name,
            $volume,
            $price,
            $qty,
            $lineTotal

        );

        mysqli_stmt_execute($itemStmt);


        mysqli_stmt_bind_param(

            $stockStmt,

            "ii",

            $qty,
            $variant_id

        );

        mysqli_stmt_execute($stockStmt);

    }


    /*
    |--------------------------------------------------------------------------
    | Clear Cart
    |--------------------------------------------------------------------------
    */

    $clearStmt = mysqli_prepare($conn, "

    DELETE FROM cart_items

    WHERE user_id=?

    ");

    mysqli_stmt_bind_param(

        $clearStmt,

        "i",

        $user_id

    );

    mysqli_stmt_execute($clearStmt);


    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */

    mysqli_commit($conn);

    /*
    |--------------------------------------------------------------------------
    | Order Confirmation Email (never let a mail failure break the order)
    |--------------------------------------------------------------------------
    */

    if ($email !== '') {
        try {
            sendOrderConfirmationEmail($email, $order_number, $full_name, $items, $subtotal, $shipping, $total);
        } catch (\Throwable $mailErr) {
            error_log("Order confirmation email failed: " . $mailErr->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Notification (admin panel)
    |--------------------------------------------------------------------------
    */

    $message = "Order $order_number has been placed.";

    $notifStmt = mysqli_prepare($conn, "
        INSERT INTO notifications
        (title,message,type,reference_id)
        VALUES (?,?,?,?)
    ");
    mysqli_stmt_bind_param($notifStmt, "sssi", $notifTitle, $message, $notifType, $order_id);
    $notifTitle = 'New Order';
    $notifType  = 'order';
    mysqli_stmt_execute($notifStmt);

    echo json_encode([

        'success' => true,

        'order_number' => $order_number,

        'total' => $total

    ]);

}
catch(Exception $e)
{

    mysqli_rollback($conn);

    echo json_encode([

        'success' => false,

        'error' => 'db_error',

        'message' => $e->getMessage()

    ]);

}