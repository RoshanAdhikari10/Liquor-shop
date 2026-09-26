<?php

session_start();

include("../config/database.php");

$query = "
    UPDATE notifications
    SET is_read = 1
    WHERE is_read = 0
";

if (mysqli_query($conn, $query)) {

    echo json_encode([
        "success" => true
    ]);

} else {

    echo json_encode([
        "success" => false
    ]);

}
?>