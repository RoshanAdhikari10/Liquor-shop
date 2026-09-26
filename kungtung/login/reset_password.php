<?php
session_start();

if (empty($_SESSION['reset_email']) || empty($_SESSION['reset_verified'])) {
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
<title>Reset Password - Kung Tung Liquor Shop</title>
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
        <h1>Set a <span class="ital">New Password</span></h1>
        <p class="sub">For <strong><?= htmlspecialchars($_SESSION['reset_email']) ?></strong></p>

        <div class="steps">
            <span class="done">1</span>
            <span class="done">2</span>
            <span class="active">3</span>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="update_password.php" method="POST">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" minlength="8" required autofocus>

            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>

            <button type="submit">Update Password</button>
        </form>
    </div>
</div>

</body>
</html>