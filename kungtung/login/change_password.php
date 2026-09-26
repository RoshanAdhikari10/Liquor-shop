<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require "../config/database.php"; // Change if your path is different

$user_id = $_SESSION['user_id'];

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    header("Location: profile.php?password=empty");
    exit();
}

if ($new_password !== $confirm_password) {
    header("Location: profile.php?password=mismatch");
    exit();
}

if (strlen($new_password) < 6) {
    header("Location: profile.php?password=short");
    exit();
}

// Get current password hash
$stmt = mysqli_prepare($conn, "SELECT password_hash FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user || !password_verify($current_password, $user['password_hash'])) {
    header("Location: profile.php?password=wrong");
    exit();
}

// Hash new password
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Update password
$stmt = mysqli_prepare($conn, "UPDATE users SET password_hash=? WHERE id=?");
mysqli_stmt_bind_param($stmt, "si", $new_hash, $user_id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: profile.php?password=success");
    exit();
} else {
    header("Location: profile.php?password=error");
    exit();
}
?>