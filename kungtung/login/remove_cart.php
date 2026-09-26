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

$stmt = mysqli_prepare($conn,
"DELETE FROM cart
WHERE user_id=?
AND product_id=?");

mysqli_stmt_bind_param($stmt,"ii",$user_id,$product_id);

mysqli_stmt_execute($stmt);

echo json_encode(["status"=>"success"]);