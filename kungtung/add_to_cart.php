<?php
session_start();
require "../config/database.php";   // Change path if needed

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "login"
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

$product_id = intval($_POST['product_id'] ?? 0);
$qty = intval($_POST['qty'] ?? 1);

if ($product_id <= 0) {
    echo json_encode([
        "status"=>"error",
        "message"=>"Invalid product"
    ]);
    exit;
}

/* Check product */

$stmt = mysqli_prepare($conn,"SELECT stock FROM products WHERE id=? AND status='Active'");
mysqli_stmt_bind_param($stmt,"i",$product_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result)==0){

    echo json_encode([
        "status"=>"error",
        "message"=>"Product not found"
    ]);
    exit;

}

$product=mysqli_fetch_assoc($result);

/* Already in cart? */

$stmt=mysqli_prepare($conn,"SELECT quantity FROM cart WHERE user_id=? AND product_id=?");
mysqli_stmt_bind_param($stmt,"ii",$user_id,$product_id);
mysqli_stmt_execute($stmt);

$result=mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result)>0){

    $cart=mysqli_fetch_assoc($result);

    $newQty=$cart['quantity']+$qty;

    if($newQty>$product['stock']){
        $newQty=$product['stock'];
    }

    $stmt=mysqli_prepare($conn,"UPDATE cart SET quantity=? WHERE user_id=? AND product_id=?");
    mysqli_stmt_bind_param($stmt,"iii",$newQty,$user_id,$product_id);
    mysqli_stmt_execute($stmt);

}else{

    if($qty>$product['stock']){
        $qty=$product['stock'];
    }

    $stmt=mysqli_prepare($conn,"INSERT INTO cart(user_id,product_id,quantity) VALUES(?,?,?)");
    mysqli_stmt_bind_param($stmt,"iii",$user_id,$product_id,$qty);
    mysqli_stmt_execute($stmt);

}

/* Cart badge */

$stmt=mysqli_prepare($conn,"SELECT SUM(quantity) total FROM cart WHERE user_id=?");
mysqli_stmt_bind_param($stmt,"i",$user_id);
mysqli_stmt_execute($stmt);

$result=mysqli_stmt_get_result($stmt);

$row=mysqli_fetch_assoc($result);

echo json_encode([
    "status"=>"success",
    "count"=>$row['total']??0
]);