<?php
session_start();
$name = $_SESSION['registered_name'] ?? 'Customer';
unset($_SESSION['registered_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Account Created - Kung Tung Liquor Shop</title>
<link rel="stylesheet" href="style.css">
<style>
    body { align-items: center; height: 100vh; text-align: center; }
</style>
</head>
<body>
<div class="card">
    <h1>Account Created!</h1>
    <p>Welcome, <?= htmlspecialchars($name) ?> — your Kung Tung Liquor Shop account is ready.</p>
    <a class="link" href="login.php">Log in now</a>
</div>
</body>
</html>
