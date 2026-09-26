<?php
session_start();

include("../config/database.php");

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$role = isset($_POST['role']) ? trim($_POST['role']) : "";

if ($id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid user."
    ]);
    exit();
}

if (!in_array($role, ["admin", "customer"])) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid role."
    ]);
    exit();
}

// Check user exists
$check = mysqli_query($conn, "SELECT id FROM users WHERE id='$id'");

if (mysqli_num_rows($check) == 0) {
    echo json_encode([
        "success" => false,
        "message" => "User not found."
    ]);
    exit();
}

// Update role
$update = mysqli_query($conn, "UPDATE users SET role='$role' WHERE id='$id'");

if ($update) {

    echo json_encode([
        "success" => true,
        "message" => "User role updated successfully."
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => mysqli_error($conn)
    ]);

}
?>