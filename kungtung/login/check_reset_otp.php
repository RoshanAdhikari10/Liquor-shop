<?php
// check_reset_otp.php - Verifies the entered reset OTP
session_start();

function backToVerifyWithError($msg) {
    $_SESSION['error'] = $msg;
    header("Location: verify_reset_otp.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$entered = trim($_POST['otp'] ?? '');

$_SESSION['reset_otp_attempts'] = ($_SESSION['reset_otp_attempts'] ?? 0) + 1;
if ($_SESSION['reset_otp_attempts'] > 5) {
    unset($_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['reset_otp_expiry']);
    $_SESSION['error'] = "Too many attempts. Please start over.";
    header("Location: forgot_password.php");
    exit;
}

if (empty($_SESSION['reset_otp']) || time() > ($_SESSION['reset_otp_expiry'] ?? 0)) {
    backToVerifyWithError("Your code has expired. Please request a new one.");
}

if (!hash_equals($_SESSION['reset_otp'], $entered)) {
    backToVerifyWithError("Incorrect code. Please try again.");
}

// Success - allowed to reset password
$_SESSION['reset_verified'] = true;
unset($_SESSION['reset_otp'], $_SESSION['reset_otp_expiry'], $_SESSION['reset_otp_attempts']);

header("Location: reset_password.php");
exit;
?>