<?php
// details.php - Step 3: Full Name, Phone Number, Date of Birth, Address
session_start();

if (empty($_SESSION['email_verified'])) {
    header("Location: index.php");
    exit;
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

// Max date a person can select so they are at least 18 today
$maxDob = date('Y-m-d', strtotime('-18 years'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Details - Kung Tung Liquor Shop</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="card">
    <h1>Your Details</h1>
    <p class="sub">Must be 18 or older to continue</p>

    <div class="steps">
        <span class="done">1</span>
        <span class="done">2</span>
        <span class="active">3</span>
        <span>4</span>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="process_details.php" method="POST">
        <label for="full_name">Full Name</label>
        <input type="text" id="full_name" name="full_name" required autofocus>

        <label for="phone_number">Phone Number</label>
        <input type="tel" id="phone_number" name="phone_number"
               placeholder="98XXXXXXXX" pattern="[0-9+\-\s]{7,20}" required>

        <label for="date_of_birth">Date of Birth</label>
        <input type="date" id="date_of_birth" name="date_of_birth" max="<?= $maxDob ?>" required>

        <label for="address">Address</label>
        <input type="text" id="address" name="address" required>

        <button type="submit">Continue</button>
    </form>

    <p class="notice">By continuing you confirm you are 18 years of age or older.</p>
</div>
</body>
</html>
