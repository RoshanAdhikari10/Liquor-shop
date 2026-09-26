<?php
session_start();

if (empty($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Reset Code - Kung Tung Liquor Shop</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Sora:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div>
    <div class="brand-mark">KUNG <span class="amp">&amp;</span> TUNG</div>

    <div class="card">
        <span class="eyebrow">Reset Access</span>
        <h1>Verify Your <span class="ital">Code</span></h1>
        <p class="sub">Code sent to <strong><?= htmlspecialchars($_SESSION['reset_email']) ?></strong></p>

        <div class="steps">
            <span class="done">1</span>
            <span class="active">2</span>
            <span>3</span>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="check_reset_otp.php" method="POST">
            <label for="otp">Enter 6-digit OTP</label>
            <input type="text" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}"
                   maxlength="6" required autofocus>

            <button type="submit">Verify</button>
        </form>

        <p class="notice">
            Didn't get a code?
            <a class="link" href="#" onclick="return resend(event)">Resend Code</a>
        </p>
    </div>
</div>

<form id="resendForm" action="resend_reset_otp.php" method="POST" style="display:none;"></form>
<script>
function resend(e) {
    e.preventDefault();
    document.getElementById('resendForm').submit();
}
</script>
</body>
</html>