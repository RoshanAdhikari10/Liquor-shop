<?php
// login.php - Log in with email + password
session_start();
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Kung Tung Liquor Shop</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Sora:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div>
    <div class="brand-mark">KUNG <span class="amp">&amp;</span> TUNG</div>

    <div class="card">
        <span class="eyebrow">Welcome Back</span>
        <h1>Login to your <span class="ital">Account</span></h1>
        <p class="sub">Pick up right where you left off.</p>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="process_login.php" method="POST">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                   placeholder="you@example.com" required autofocus>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <a class="forgot-link" href="forgot_password.php">Forgot password?</a>

            <button type="submit">Login</button>
        </form>

        <p class="notice">Don't have an account? <a class="link" href="index.php">Register</a></p>
    </div>
</div>

</body>
</html>