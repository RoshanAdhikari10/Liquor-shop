<?php
session_start();
require "../config/database.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT
            c.product_id,
            c.quantity,
            p.name,
            p.price,
            p.image,
            p.stock
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$cart = [];

while($row = mysqli_fetch_assoc($result)){

    $cart[] = [
        "id"=>$row["product_id"],
        "qty"=>$row["quantity"],
        "name"=>$row["name"],
        "price"=>$row["price"],
        "image"=>$row["image"],
        "stock"=>$row["stock"]
    ];

}

echo json_encode($cart);