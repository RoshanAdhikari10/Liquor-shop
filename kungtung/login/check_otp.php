<?php
// check_otp.php - Verifies the entered OTP against the session
session_start();

function backToVerifyWithError($msg) {
    $_SESSION['error'] = $msg;
    header("Location: verify_otp.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['otp_email'])) {
    header("Location: index.php");
    exit;
}

$entered = trim($_POST['otp'] ?? '');

// Limit brute-force attempts
$_SESSION['otp_attempts'] = ($_SESSION['otp_attempts'] ?? 0) + 1;
if ($_SESSION['otp_attempts'] > 5) {
    unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_expiry']);
    $_SESSION['error'] = "Too many attempts. Please start over.";
    header("Location: index.php");
    exit;
}

if (empty($_SESSION['otp']) || time() > ($_SESSION['otp_expiry'] ?? 0)) {
    backToVerifyWithError("Your OTP has expired. Please request a new one.");
}

if (!hash_equals($_SESSION['otp'], $entered)) {
    backToVerifyWithError("Incorrect OTP. Please try again.");
}

// Success - email is verified
$_SESSION['email_verified'] = true;
$_SESSION['verified_email'] = $_SESSION['otp_email'];
unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['otp_attempts']);

header("Location: details.php");
exit;
?>
