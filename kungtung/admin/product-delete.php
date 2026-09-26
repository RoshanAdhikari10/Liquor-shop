<?php
session_start();

include("../config/database.php");

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$id = (int)$_GET['id'];

// Get product
$result = mysqli_query($conn, "SELECT * FROM products WHERE id='$id'");

if (mysqli_num_rows($result) == 0) {
    header("Location: products.php");
    exit();
}

$product = mysqli_fetch_assoc($result);

// Delete image if not default
if (
    !empty($product['image']) &&
    $product['image'] != "default.png" &&
    file_exists("../uploads/products/" . $product['image'])
) {
    unlink("../uploads/products/" . $product['image']);
}

// Delete database record
$delete = mysqli_query($conn, "DELETE FROM products WHERE id='$id'");

if ($delete) {

    header("Location: products.php?deleted=1");
    exit();

} else {

    header("Location: products.php?error=1");
    exit();

}
?>