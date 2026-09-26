<?php
session_start();
include("../config/database.php");

// Make sure the admin is logged in
// if (!isset($_SESSION['admin_id'])) {
//     header("Location: login.php");
//     exit();
// }

if (!isset($_GET['id']) || !isset($_GET['role'])) {
    header("Location: users.php");
    exit();
}

$userId = (int)$_GET['id'];
$newRole = $_GET['role'];

// Only allow valid roles
if ($newRole != "admin" && $newRole != "customer") {
    header("Location: users.php");
    exit();
}

// Prevent changing your own role
if (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] == $userId) {
    $_SESSION['error'] = "You cannot change your own role.";
    header("Location: users.php");
    exit();
}

// Check if user exists
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = "User not found.";
    header("Location: users.php");
    exit();
}

// Update role
$stmt = mysqli_prepare($conn, "UPDATE users SET role=? WHERE id=?");
mysqli_stmt_bind_param($stmt, "si", $newRole, $userId);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] = "User role updated successfully.";
} else {
    $_SESSION['error'] = "Failed to update role.";
}

header("Location: users.php");
exit();
?>