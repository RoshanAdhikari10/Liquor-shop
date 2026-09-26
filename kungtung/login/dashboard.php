<?php
// dashboard.php - Shown after a successful login
session_start();

if (empty($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard - Kung Tung Liquor Shop</title>
<link rel="stylesheet" href="style.css">
<style>
    body { align-items: center; height: 100vh; text-align: center; }
</style>
</head>
<body>
<div class="card">
    <h1>Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
    <p>You're logged in to your Kung Tung Liquor Shop account.</p>
    <a class="link" href="logout.php">Log out</a>
</div>
</body>
</html>
