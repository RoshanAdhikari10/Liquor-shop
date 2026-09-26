<?php
// verify_otp.php - Step 2: Enter and verify OTP
session_start();

if (empty($_SESSION['otp_email'])) {
    header("Location: index.php");
    exit;
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify OTP - Kung Tung Liquor Shop</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="card">
    <h1>Verify Your Email</h1>
    <p class="sub">Code sent to <?= htmlspecialchars($_SESSION['otp_email']) ?></p>

    <div class="steps">
        <span class="done">1</span>
        <span class="active">2</span>
        <span>3</span>
        <span>4</span>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="check_otp.php" method="POST">
        <label for="otp">Enter 6-digit OTP</label>
        <input type="text" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}"
               maxlength="6" required autofocus>

        <button type="submit">Verify</button>
    </form>

    <p class="notice">
        Didn't get a code?
        <a class="link" href="#" onclick="return resend(event)">Resend OTP</a>
    </p>
</div>

<form id="resendForm" action="resend_otp.php" method="POST" style="display:none;"></form>
<script>
function resend(e) {
    e.preventDefault();
    document.getElementById('resendForm').submit();
}
</script>
</body>
</html>
