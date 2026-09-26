<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require "../config/database.php";   // Change path if your database.php is elsewhere

$user_id = $_SESSION['user_id'];

$full_name   = trim($_POST['full_name']);
$email       = trim($_POST['email']);
$phone       = trim($_POST['phone']);
$address     = trim($_POST['address']);

if (empty($full_name) || empty($email) || empty($phone) || empty($address)) {
    die("All fields are required.");
}

// Check if another user is already using this email
$check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
mysqli_stmt_bind_param($check, "si", $email, $user_id);
mysqli_stmt_execute($check);

$result = mysqli_stmt_get_result($check);

if (mysqli_num_rows($result) > 0) {
    die("Email is already in use by another account.");
}

// Update profile
$stmt = mysqli_prepare($conn, "UPDATE users SET full_name=?, email=?, phone_number=?, address=? WHERE id=?");
mysqli_stmt_bind_param($stmt, "ssssi", $full_name, $email, $phone, $address, $user_id);

if (mysqli_stmt_execute($stmt)) {

    // Update session name
    $_SESSION['user_name'] = $full_name;

    header("Location: profile.php?success=1");
    exit();

} else {

    die("Something went wrong.");

}
?>