<?php

session_start();

include("../config/database.php");

$query = mysqli_query($conn, "
    SELECT id, title, message, type, reference_id, created_at
    FROM notifications
    WHERE is_read = 0
    ORDER BY created_at DESC
");

if (!$query) {
    echo '<div class="p-3 text-center text-danger">
        Failed to load notifications
    </div>';
    exit;
}

if (mysqli_num_rows($query) == 0) {
    echo '<div class="p-3 text-center text-muted">
        No notifications
    </div>';
    exit;
}

while ($row = mysqli_fetch_assoc($query)) {

    // Default icon
    $icon = "fa-bell";
    $color = "text-dark";

    // Notification type icons
    if ($row['type'] == 'order') {

        $icon = "fa-cart-shopping";
        $color = "text-success";

    } elseif ($row['type'] == 'customer') {

        $icon = "fa-user";
        $color = "text-primary";

    } elseif ($row['type'] == 'product') {

        $icon = "fa-box";
        $color = "text-warning";

    } elseif ($row['type'] == 'system') {

        $icon = "fa-circle-info";
        $color = "text-info";
    }
?>

<div class="notification-item">

    <div class="d-flex">

        <div class="me-3">

            <i class="fa-solid <?= $icon ?> <?= $color ?>"></i>

        </div>

        <div class="flex-grow-1">

            <strong>
                <?= htmlspecialchars($row['title']) ?>
            </strong>

            <div class="small text-muted">

                <?= htmlspecialchars($row['message']) ?>

            </div>

            <div class="small text-secondary mt-1">

                <?= date(
                    "d M Y h:i A",
                    strtotime($row['created_at'])
                ) ?>

            </div>

        </div>

    </div>

</div>

<?php
}
?>

<div class="p-2 text-center border-top">

    <button
        class="btn btn-sm btn-outline-dark"
        id="markAllRead"
        type="button">

        Mark all as read

    </button>

</div>