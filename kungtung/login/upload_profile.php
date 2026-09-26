<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require "../config/database.php"; // Change if needed

$user_id = $_SESSION['user_id'];

if (!isset($_FILES['image']) || $_FILES['image']['error'] != 0) {
    header("Location: profile.php?image=error");
    exit();
}

$allowed = ['jpg', 'jpeg', 'png', 'webp'];

$fileName = $_FILES['image']['name'];
$tmpName  = $_FILES['image']['tmp_name'];
$fileSize = $_FILES['image']['size'];

$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($extension, $allowed)) {
    header("Location: profile.php?image=type");
    exit();
}

if ($fileSize > 2 * 1024 * 1024) {
    header("Location: profile.php?image=size");
    exit();
}

$newName = "user_" . $user_id . "_" . time() . "." . $extension;

$destination = "../uploads/profile/" . $newName;

// Get old image
$stmt = mysqli_prepare($conn, "SELECT profile_image FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (move_uploaded_file($tmpName, $destination)) {

    // Delete old image (except default)
    if (!empty($user['profile_image'])) {

        $oldFile = "../uploads/profile/" . $user['profile_image'];

        if (file_exists($oldFile)) {
            unlink($oldFile);
        }
    }

    // Save filename
    $stmt = mysqli_prepare($conn, "UPDATE users SET profile_image=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "si", $newName, $user_id);
    mysqli_stmt_execute($stmt);

    header("Location: profile.php?image=success");
    exit();

} else {

    header("Location: profile.php?image=error");
    exit();

}
?>