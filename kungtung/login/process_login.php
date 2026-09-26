<?php
// process_login.php - Verifies email + password, routes by role
session_start();
require 'config.php';

function backToLoginWithError($msg) {
    $_SESSION['error'] = $msg;
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    backToLoginWithError("Please enter both email and password.");
}

$normalized_email = strtolower($email);

$stmt = $pdo->prepare("SELECT id, full_name, password_hash, role FROM users WHERE email = :email");
$stmt->execute([':email' => $normalized_email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Same generic error for "no such user" and "wrong password" so we don't
// reveal which emails are registered
if (!$user || !password_verify($password, $user['password_hash'])) {
    backToLoginWithError("Incorrect email or password.");
}

// Success - start a logged-in session
session_regenerate_id(true);
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['role']      = $user['role'];
$_SESSION['logged_in'] = true;

if ($user['role'] === 'admin') {
    header("Location: ../admin/index.php");
} else {
    header("Location: ../index.php");
}
exit;
?>
