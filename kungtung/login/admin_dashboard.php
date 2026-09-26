<?php
// admin_dashboard.php - Shown after an admin logs in
session_start();
require 'config.php';

if (empty($_SESSION['logged_in']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

// Pull the customer list (everyone except other admins)
$stmt = $pdo->query(
    "SELECT full_name, email, phone_number, date_of_birth, created_at
     FROM users WHERE role = 'customer' ORDER BY created_at DESC"
);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - Kung Tung Liquor Shop</title>
<link rel="stylesheet" href="style.css">
<style>
    body { align-items: flex-start; }
    .card { max-width: 800px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
    th, td { text-align: left; padding: 8px; border-bottom: 1px solid #444; }
    th { color: #d4af37; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; }
</style>
</head>
<body>
<div class="card">
    <div class="top-bar">
        <h1 style="margin:0;">Admin Dashboard</h1>
        <a class="link" href="logout.php">Log out</a>
    </div>
    <p class="sub">Logged in as <?= htmlspecialchars($_SESSION['user_name']) ?> (admin)</p>

    <table>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>DOB</th>
            <th>Registered</th>
        </tr>
        <?php if (empty($customers)): ?>
        <tr><td colspan="5">No customers registered yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($customers as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['full_name']) ?></td>
            <td><?= htmlspecialchars($c['email']) ?></td>
            <td><?= htmlspecialchars($c['phone_number']) ?></td>
            <td><?= htmlspecialchars($c['date_of_birth']) ?></td>
            <td><?= htmlspecialchars($c['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
