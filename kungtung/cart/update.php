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

$qty = (int)($_POST['qty'] ?? 1);

if($product_id <= 0 || $variant_id <= 0)
{
    echo json_encode([
        'success' => false,
        'error' => 'invalid_product'
    ]);
    exit;
}

if($qty <= 0)
{

    $stmt = mysqli_prepare($conn,"
        DELETE FROM cart_items
        WHERE
            user_id=?
            AND product_id=?
            AND variant_id=?
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "iii",
        $user_id,
        $product_id,
        $variant_id
    );

    mysqli_stmt_execute($stmt);

}
else
{

    $stmt = mysqli_prepare($conn,"
        UPDATE cart_items
        SET quantity=?
        WHERE
            user_id=?
            AND product_id=?
            AND variant_id=?
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "iiii",
        $qty,
        $user_id,
        $product_id,
        $variant_id
    );

    mysqli_stmt_execute($stmt);

}

echo json_encode([
    'success' => true
]);