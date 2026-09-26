<?php
// send_reset_otp.php - Validates email exists, generates OTP, emails it, moves to verify step
session_start();
require 'config.php';
require 'email_service.php';

function backToForgotWithError($msg) {
    $_SESSION['error'] = $msg;
    header("Location: forgot_password.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: forgot_password.php");
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    backToForgotWithError("Please enter a valid email address.");
}

$normalized_email = strtolower($email);

// Must exist as a registered user
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute([':email' => $normalized_email]);
$user = $stmt->fetch();

if (!$user) {
    // Don't reveal whether the email exists — generic message, but don't send anything
    backToForgotWithError("If that email is registered, a reset code has been sent.");
}

// --- Generate 6-digit OTP ---
$otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

$emailResult = sendOtpEmail($normalized_email, $otp);

if (!$emailResult['success']) {
    error_log("Reset OTP email send failed for $normalized_email: " . $emailResult['error']);
    backToForgotWithError("We couldn't send the reset code right now. Please try again.");
}

$_SESSION['reset_otp']          = $otp;
$_SESSION['reset_email']        = $normalized_email;
$_SESSION['reset_otp_expiry']   = time() + 300; // 5 minutes
$_SESSION['reset_otp_attempts'] = 0;
$_SESSION['reset_verified']     = false;

header("Location: verify_reset_otp.php");
exit;
?>