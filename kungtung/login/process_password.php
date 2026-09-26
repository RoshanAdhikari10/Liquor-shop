<?php
// process_password.php
// Validates password and creates the account
// This is the only point where the user is inserted into the database

session_start();
require 'config.php';

function backToPasswordWithError($msg) {
    $_SESSION['error'] = $msg;
    header("Location: password.php");
    exit;
}


// Make sure request is POST and previous registration steps are complete
if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    empty($_SESSION['email_verified']) ||
    empty($_SESSION['details_done'])
) {
    header("Location: index.php");
    exit;
}


// Get passwords
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';


// =========================================================
// PASSWORD VALIDATION
// =========================================================

// Minimum 8 characters
if (strlen($password) < 8) {
    backToPasswordWithError(
        "Password must be at least 8 characters long."
    );
}


// At least one uppercase letter
if (!preg_match('/[A-Z]/', $password)) {
    backToPasswordWithError(
        "Password must contain at least one uppercase letter."
    );
}


// At least one lowercase letter
if (!preg_match('/[a-z]/', $password)) {
    backToPasswordWithError(
        "Password must contain at least one lowercase letter."
    );
}


// At least one number
if (!preg_match('/[0-9]/', $password)) {
    backToPasswordWithError(
        "Password must contain at least one number."
    );
}


// Confirm password
if ($password !== $confirm_password) {
    backToPasswordWithError(
        "Passwords do not match."
    );
}


// =========================================================
// GET REGISTRATION DETAILS FROM SESSION
// =========================================================

$email = $_SESSION['verified_email'] ?? '';
$full_name = $_SESSION['reg_full_name'] ?? '';
$phone_number = $_SESSION['reg_phone_number'] ?? '';
$date_of_birth = $_SESSION['reg_date_of_birth'] ?? '';
$address = $_SESSION['reg_address'] ?? '';


// Make sure required registration data exists
if (
    empty($email) ||
    empty($full_name) ||
    empty($phone_number) ||
    empty($date_of_birth) ||
    empty($address)
) {
    $_SESSION['error'] = "Registration session expired. Please start again.";
    header("Location: index.php");
    exit;
}


// =========================================================
// HASH PASSWORD
// =========================================================

$password_hash = password_hash(
    $password,
    PASSWORD_DEFAULT
);


// =========================================================
// CREATE ACCOUNT
// =========================================================

try {

    // Check if email already exists
    $check = $pdo->prepare(
        "SELECT id FROM users WHERE email = :email LIMIT 1"
    );

    $check->execute([
        ':email' => $email
    ]);

    if ($check->fetch()) {
        backToPasswordWithError(
            "This email is already registered. Please use another email."
        );
    }


    // Insert user
    $stmt = $pdo->prepare(
        "INSERT INTO users
        (
            email,
            phone_number,
            full_name,
            address,
            date_of_birth,
            password_hash
        )
        VALUES
        (
            :email,
            :phone_number,
            :full_name,
            :address,
            :date_of_birth,
            :password_hash
        )"
    );


    $stmt->execute([
        ':email' => $email,
        ':phone_number' => $phone_number,
        ':full_name' => $full_name,
        ':address' => $address,
        ':date_of_birth' => $date_of_birth,
        ':password_hash' => $password_hash
    ]);


} catch (PDOException $e) {

    // Do not expose database error details to the user
    backToPasswordWithError(
        "Something went wrong while creating your account. Please try again later."
    );
}


// =========================================================
// ACCOUNT CREATED
// =========================================================

$createdName = $full_name;


// Clear registration session data
session_unset();
session_destroy();


// Start fresh session
session_start();

$_SESSION['registered_name'] = $createdName;


// Redirect to success page
header("Location: success.php");
exit;

?>