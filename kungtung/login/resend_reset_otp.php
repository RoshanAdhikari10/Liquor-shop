<?php
// resend_reset_otp.php - Regenerates a reset OTP for the email already in session
session_start();
require 'email_service.php';

if (empty($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

$emailResult = sendOtpEmail($_SESSION['reset_email'], $otp);
if (!$emailResult['success']) {
    error_log("Reset OTP resend failed for {$_SESSION['reset_email']}: " . $emailResult['error']);
    $_SESSION['error'] = "We couldn't resend the code right now. Please try again shortly.";
    header("Location: verify_reset_otp.php");
    exit;
}

$_SESSION['reset_otp'] = $otp;
$_SESSION['reset_otp_expiry'] = time() + 300;
$_SESSION['reset_otp_attempts'] = 0;

header("Location: verify_reset_otp.php");
exit;
?>