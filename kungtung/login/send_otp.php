<?php
// send_otp.php - Validates email, generates OTP, emails it, moves to verify step
session_start();
require 'config.php';
require 'email_service.php';

function backToIndexWithError($msg) {
    $_SESSION['error'] = $msg;
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    backToIndexWithError("Please enter a valid email address.");
}

$normalized_email = strtolower($email);

// Reject if this email already has a completed account
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute([':email' => $normalized_email]);
if ($stmt->fetch()) {
    $_SESSION['error'] = "This email is already registered. Please log in instead.";
    header("Location: login.php");
    exit;
}

// --- Generate 6-digit OTP ---
$otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// --- Send the OTP via email (SMTP) ---
$emailResult = sendOtpEmail($normalized_email, $otp);

if (!$emailResult['success']) {
    // Don't leak raw SMTP errors to the user; log them for yourself instead
    error_log("OTP email send failed for $normalized_email: " . $emailResult['error']);
    backToIndexWithError("We couldn't send the OTP right now. Please check the address and try again.");
}

$_SESSION['otp']          = $otp;
$_SESSION['otp_email']    = $normalized_email;
$_SESSION['otp_expiry']   = time() + 300; // 5 minutes
$_SESSION['otp_attempts'] = 0;

header("Location: verify_otp.php");
exit;
?>
