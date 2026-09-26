<?php
// config.php - Database connection settings (XAMPP defaults)

$host = "localhost";
$dbname = "kungtung";
$username = "root";   // default XAMPP MySQL username
$password = "";        // default XAMPP MySQL password (empty)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
