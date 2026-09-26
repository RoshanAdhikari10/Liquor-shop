<?php
session_start();
header('Content-Type: application/json');
include("../config/database.php");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'login_required']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "DELETE FROM cart_items WHERE user_id=?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

echo json_encode(['success' => true]);