<?php
// process_details.php - Validates name/phone/DOB/address and enforces 18+ rule
session_start();

function backToDetailsWithError($msg) {
    $_SESSION['error'] = $msg;
    header("Location: details.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['email_verified'])) {
    header("Location: index.php");
    exit;
}

$full_name     = trim($_POST['full_name'] ?? '');
$phone_number  = trim($_POST['phone_number'] ?? '');
$date_of_birth = trim($_POST['date_of_birth'] ?? '');
$address       = trim($_POST['address'] ?? '');

if ($full_name === '' || $phone_number === '' || $date_of_birth === '' || $address === '') {
    backToDetailsWithError("All fields are required.");
}

if (!preg_match('/^[a-zA-Z\s\.\-\']{2,100}$/', $full_name)) {
    backToDetailsWithError("Please enter a valid full name.");
}

if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone_number)) {
    backToDetailsWithError("Please enter a valid phone number.");
}

// Normalize (strip spaces/dashes) before storing
$normalized_phone = preg_replace('/[\s\-]/', '', $phone_number);

$dob = DateTime::createFromFormat('Y-m-d', $date_of_birth);
if (!$dob || $dob->format('Y-m-d') !== $date_of_birth) {
    backToDetailsWithError("Please enter a valid date of birth.");
}

// --- Age check: must be 18 or older ---
$today = new DateTime();
$age = $today->diff($dob)->y;

if ($age < 18) {
    backToDetailsWithError("Registration denied: You must be 18 years or older to register.");
}

// Stash details in session until the account is actually created
$_SESSION['reg_full_name']     = $full_name;
$_SESSION['reg_phone_number']  = $normalized_phone;
$_SESSION['reg_date_of_birth'] = $date_of_birth;
$_SESSION['reg_address']       = $address;
$_SESSION['details_done']      = true;

header("Location: password.php");
exit;
?>
