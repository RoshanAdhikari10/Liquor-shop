<?php
// resend_otp.php - Regenerates an OTP for the email already in session
session_start();
require 'email_service.php';

if (empty($_SESSION['otp_email'])) {
    header("Location: index.php");
    exit;
}

$otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

$emailResult = sendOtpEmail($_SESSION['otp_email'], $otp);
if (!$emailResult['success']) {
    error_log("OTP email resend failed for {$_SESSION['otp_email']}: " . $emailResult['error']);
    $_SESSION['error'] = "We couldn't resend the OTP right now. Please try again shortly.";
    header("Location: verify_otp.php");
    exit;
}

$_SESSION['otp'] = $otp;
$_SESSION['otp_expiry'] = time() + 300;
$_SESSION['otp_attempts'] = 0;

header("Location: verify_otp.php");
exit;
?>
