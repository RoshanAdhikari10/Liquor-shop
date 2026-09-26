<?php
session_start();
require "../config/database.php";

header("Content-Type: application/json");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status"=>"login"]);
    exit;
}

$user_id = $_SESSION['user_id'];

$product_id = intval($_POST['product_id'] ?? 0);
$action = $_POST['action'] ?? '';

$stmt = mysqli_prepare($conn,"
SELECT c.quantity,p.stock
FROM cart c
JOIN products p ON c.product_id=p.id
WHERE c.user_id=? AND c.product_id=?");

mysqli_stmt_bind_param($stmt,"ii",$user_id,$product_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result)==0){
    echo json_encode(["status"=>"error"]);
    exit;
}

$row = mysqli_fetch_assoc($result);

$qty = $row['quantity'];

if($action=="plus"){

    if($qty < $row['stock']){
        $qty++;
    }

}else{

    $qty--;

}

if($qty<=0){

    $stmt=mysqli_prepare($conn,
    "DELETE FROM cart WHERE user_id=? AND product_id=?");

    mysqli_stmt_bind_param($stmt,"ii",$user_id,$product_id);

    mysqli_stmt_execute($stmt);

}else{

    $stmt=mysqli_prepare($conn,
    "UPDATE cart SET quantity=? WHERE user_id=? AND product_id=?");

    mysqli_stmt_bind_param($stmt,"iii",$qty,$user_id,$product_id);

    mysqli_stmt_execute($stmt);

}

echo json_encode(["status"=>"success"]);