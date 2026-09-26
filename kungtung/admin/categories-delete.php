<?php
session_start();
include("../config/database.php");

include("includes/header.php");
include("includes/sidebar.php");
include("includes/navbar.php");


if (!isset($_GET['id'])) {
    header("Location: categories.php");
    exit();
}

$id = (int)$_GET['id'];

// Check if category exists
$category = mysqli_query($conn, "SELECT * FROM categories WHERE id='$id'");

if (mysqli_num_rows($category) == 0) {
    header("Location: categories.php");
    exit();
}

// Check whether products use this category
$checkProducts = mysqli_query($conn,
"SELECT COUNT(*) AS total
FROM products
WHERE category_id='$id'");

$product = mysqli_fetch_assoc($checkProducts);

if ($product['total'] > 0) {

    echo "
    <script>
        alert('Cannot delete this category because products are assigned to it.');
        window.location='categories.php';
    </script>";

    exit();

}

// Delete category
$delete = mysqli_query($conn,
"DELETE FROM categories WHERE id='$id'");

if ($delete) {

    echo "
    <script>
        alert('Category deleted successfully.');
        window.location='categories.php';
    </script>";

} else {

    echo "
    <script>
        alert('Failed to delete category.');
        window.location='categories.php';
    </script>";

}
?>