<?php
// update_password.php - Validates new password and updates DB
session_start();
require 'config.php';

function backToResetWithError($msg) {
    $_SESSION['error'] = $msg;
    header("Location: reset_password.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_SESSION['reset_email'])
    || empty($_SESSION['reset_verified'])) {
    header("Location: forgot_password.php");
    exit;
}

$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

if (strlen($password) < 8) {
    backToResetWithError("Password must be at least 8 characters.");
}

if ($password !== $confirm) {
    backToResetWithError("Passwords do not match.");
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password_hash = :password WHERE email = :email");
$stmt->execute([
    ':password' => $hashed,
    ':email'    => $_SESSION['reset_email']
]);

// Clean up session
unset($_SESSION['reset_email'], $_SESSION['reset_verified']);

$_SESSION['success'] = "Password updated successfully. Please log in.";
header("Location: login.php");
exit;
?>