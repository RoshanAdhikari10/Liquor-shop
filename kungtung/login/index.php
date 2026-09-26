<?php
// index.php - Step 1: Enter email address
session_start();
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - Kung Tung Liquor Shop</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Sora:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div>
    <div class="brand-mark">KUNG <span class="amp">&amp;</span> TUNG</div>

    <div class="card">
        <span class="eyebrow">Create Account</span>
        <h1>Join <span class="ital">Kung Tung</span></h1>
        <p class="sub">Step 1 of 4 — let's start with your email.</p>

        <div class="steps">
            <span class="active">1</span>
            <span>2</span>
            <span>3</span>
            <span>4</span>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="send_otp.php" method="POST">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                   placeholder="you@example.com" required autofocus>

            <button type="submit">Send OTP</button>
        </form>

        <p class="notice">We'll email you a one-time code to verify your address.<br>
        Already have an account? <a class="link" href="login.php">Login</a></p>
    </div>
</div>

</body>
</html>