<?php
session_start();

header('Content-Type: application/json');

include("../config/database.php");

if(!isset($_SESSION['user_id']))
{
    echo json_encode([
        'success' => false,
        'error' => 'login_required'
    ]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$product_id = (int)($_POST['product_id'] ?? 0);

$variant_id = (int)($_POST['variant_id'] ?? 0);

$qty = max(1, (int)($_POST['qty'] ?? 1));

if($product_id <= 0 || $variant_id <= 0)
{
    echo json_encode([
        'success' => false,
        'error' => 'invalid_product'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Verify Variant
|--------------------------------------------------------------------------
*/

$check = mysqli_prepare($conn, "

SELECT

    pv.id,
    pv.stock

FROM product_variants pv

INNER JOIN products p
ON pv.product_id = p.id

WHERE

pv.id = ?

AND

pv.product_id = ?

AND

p.status='Active'

");

mysqli_stmt_bind_param(
    $check,
    "ii",
    $variant_id,
    $product_id
);

mysqli_stmt_execute($check);

$result = mysqli_stmt_get_result($check);

if(mysqli_num_rows($result)==0)
{
    echo json_encode([
        'success'=>false,
        'error'=>'variant_not_found'
    ]);
    exit;
}

$variant = mysqli_fetch_assoc($result);

/*
|--------------------------------------------------------------------------
| Already In Cart?
|--------------------------------------------------------------------------
*/

$existing = mysqli_prepare($conn, "

SELECT

id,
quantity

FROM cart_items

WHERE

user_id=?

AND product_id=?

AND variant_id=?

");

mysqli_stmt_bind_param(
    $existing,
    "iii",
    $user_id,
    $product_id,
    $variant_id
);

mysqli_stmt_execute($existing);

$existingResult = mysqli_stmt_get_result($existing);

if(mysqli_num_rows($existingResult))
{

    mysqli_query($conn, "

    UPDATE cart_items

    SET quantity = quantity + $qty

    WHERE

    user_id=$user_id

    AND product_id=$product_id

    AND variant_id=$variant_id

    ");

}
else
{

    $insert = mysqli_prepare($conn, "

    INSERT INTO cart_items

    (

    user_id,

    product_id,

    variant_id,

    quantity

    )

    VALUES

    (?,?,?,?)

    ");

    mysqli_stmt_bind_param(

        $insert,

        "iiii",

        $user_id,

        $product_id,

        $variant_id,

        $qty

    );

    mysqli_stmt_execute($insert);

}

echo json_encode([

    'success'=>true

]);