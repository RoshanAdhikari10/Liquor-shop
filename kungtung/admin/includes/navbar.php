<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}

include("../config/database.php");

/*
|--------------------------------------------------------------------------
| Admin Details - Fetch from Database
|--------------------------------------------------------------------------
*/

$adminName = "Administrator";
$adminImage = "../uploads/admin/default.png";


if(isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])){

    $user_id = intval($_SESSION['user_id']);

    $adminQuery = mysqli_query($conn,
        "SELECT full_name, profile_image 
         FROM users 
         WHERE id='$user_id' 
         AND role='admin'"
    );


    if($adminData = mysqli_fetch_assoc($adminQuery)){


        if(!empty($adminData['full_name'])){
            $adminName = $adminData['full_name'];
        }


        if(!empty($adminData['profile_image'])){

            $imagePath = "../uploads/profile/".$adminData['profile_image'];

            if(file_exists($imagePath)){
                $adminImage = $imagePath;
            }
            else{
                $adminImage = "https://ui-avatars.com/api/?name="
                    .urlencode($adminName)
                    ."&background=C89B3C&color=ffffff";
            }

        }
        else{

            $adminImage = "https://ui-avatars.com/api/?name="
                .urlencode($adminName)
                ."&background=C89B3C&color=ffffff";

        }

    }

}

/*
|--------------------------------------------------------------------------
| Notification Count
|--------------------------------------------------------------------------
*/

$countQuery = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM notifications
WHERE is_read=0
");

$notifyCount = mysqli_fetch_assoc($countQuery)['total'];
?>

<style>

/* ================= NAVBAR ================= */

.navbar-admin{
    position:sticky;
    top:0;
    left:0;

    width:100%;

    background:#fff;

    padding:18px 30px;

    display:flex;
    justify-content:space-between;
    align-items:center;

    box-shadow:0 5px 15px rgba(0,0,0,.05);

    z-index:100;
}

.navbar-admin h4{

    margin:0;

    font-size:24px;

    font-weight:700;

}

.admin-actions{

    display:flex;

    align-items:center;

    gap:25px;

}

/* ================= NOTIFICATION ================= */

.notification-area{

    position:relative;

}

.notification-btn{

    width:45px;

    height:45px;

    border:none;

    border-radius:50%;

    background:#f5f5f5;

    cursor:pointer;

    transition:.3s;

    position:relative;

}

.notification-btn:hover{

    background:#ffc107;

    color:#fff;

}

.notification-btn i{

    font-size:18px;

}

.notification-count{

    position:absolute;

    top:-5px;

    right:-5px;

    background:#dc3545;

    color:#fff;

    width:22px;

    height:22px;

    border-radius:50%;

    display:flex;

    justify-content:center;

    align-items:center;

    font-size:11px;

    font-weight:bold;

}

.notification-dropdown{

    position:absolute;

    right:0;

    top:60px;

    width:380px;

    background:#fff;

    border-radius:12px;

    box-shadow:0 15px 40px rgba(0,0,0,.15);

    display:none;

    overflow:hidden;

    z-index:9999;

    max-height:500px;

    overflow-y:auto;

}

.notification-dropdown.show{

    display:block;

}

.notification-item{

    padding:15px;

    border-bottom:1px solid #eee;

    transition:.3s;

}

.notification-item:hover{

    background:#fafafa;

}

/* ================= PROFILE ================= */

.admin-profile{

    display:flex;

    align-items:center;

    gap:12px;

    cursor:pointer;

    position:relative;

    text-decoration:none;
    padding:5px 10px;
    border-radius:8px;
    transition: background 0.3s;
}

.admin-profile:hover{
    background: #f5f5f5;
}

.admin-profile img{

    width:45px;

    height:45px;

    border-radius:50%;

    object-fit:cover;

    border:3px solid #C89B3C;

}

.admin-profile .profile-info{
    display:flex;
    flex-direction:column;
    align-items:flex-end;
}

.admin-profile .welcome-text{
    color:#999;
    font-size:12px;
    margin:0;
    line-height:1.2;
}

.admin-profile .admin-name{
    color:#333;
    font-size:15px;
    font-weight:600;
    margin:0;
    line-height:1.3;
}

.notification-dropdown::-webkit-scrollbar{

    width:6px;

}

.notification-dropdown::-webkit-scrollbar-thumb{

    background:#ccc;

    border-radius:20px;

}

/* ======================
NOTIFICATION
====================== */

.notification-area{
    position:relative;
}

.notification-btn{
    width:45px;
    height:45px;
    border:none;
    background:#f5f5f5;
    border-radius:50%;
    cursor:pointer;
    transition:.3s;
    position:relative;
}

.notification-btn:hover{
    background:#C89B3C;
    color:#fff;
}

.notification-btn i{
    font-size:18px;
}

.notification-count{
    position:absolute;
    top:-5px;
    right:-5px;
    width:20px;
    height:20px;
    border-radius:50%;
    background:#dc3545;
    color:#fff;
    display:flex;
    justify-content:center;
    align-items:center;
    font-size:11px;
    font-weight:bold;
}

.notification-dropdown{
    position:absolute;
    top:60px;
    right:0;
    width:360px;
    max-height:420px;
    overflow-y:auto;
    background:#fff;
    border-radius:12px;
    box-shadow:0 10px 25px rgba(0,0,0,.15);
    display:none;
    z-index:9999;
}

.notification-dropdown.show{
    display:block;
}

.notification-item{
    padding:15px;
    border-bottom:1px solid #eee;
}

.notification-item:hover{
    background:#f8f9fa;
}

</style>

<div class="navbar-admin">

    <h4>
        <?= isset($pageTitle) ? $pageTitle : "Dashboard"; ?>
    </h4>

    <div class="admin-actions">

        <!-- Notifications -->
        <div class="notification-area">

            <button
                class="notification-btn"
                id="notificationBtn"
                type="button">

                <i class="fa-solid fa-bell"></i>

                <?php if ($notifyCount > 0) { ?>

                    <span
                        class="notification-count"
                        id="notificationCount">

                        <?= $notifyCount ?>

                    </span>

                <?php } ?>

            </button>

            <div
                class="notification-dropdown"
                id="notificationDropdown">

                <div style="padding:30px;text-align:center;">
                    Loading...
                </div>

            </div>

        </div>


        <!-- Profile -->
        <a
            href="profile.php"
            class="admin-profile">

            <div class="profile-info">

                <p class="welcome-text">
                    Welcome, Admin
                </p>

                <p class="admin-name">
                    <?= htmlspecialchars($adminName); ?>
                </p>

            </div>

            <img
                src="<?= $adminImage ?>"
                alt="Admin Profile">

        </a>

    </div>

</div>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const notificationBtn =
        document.getElementById("notificationBtn");

    const notificationDropdown =
        document.getElementById("notificationDropdown");


    notificationBtn.addEventListener("click", function () {

        notificationDropdown.classList.toggle("show");


        // Only load when opening
        if (notificationDropdown.classList.contains("show")) {

            notificationDropdown.innerHTML = `
                <div style="padding:30px;text-align:center;">
                    Loading...
                </div>
            `;


            fetch("get_notifications.php")

                .then(response => response.text())

                .then(html => {

                    notificationDropdown.innerHTML = html;


                    // Mark all as read button now exists
                    const markAllRead =
                        document.getElementById("markAllRead");


                    if (markAllRead) {

                        markAllRead.addEventListener(
                            "click",
                            function (event) {

                                event.stopPropagation();


                                fetch("mark_notifications_read.php", {
                                    method: "POST"
                                })

                                .then(response => response.json())

                                .then(data => {

                                    if (data.success) {

                                        // Remove notification badge
                                        const count =
                                            document.getElementById(
                                                "notificationCount"
                                            );

                                        if (count) {
                                            count.remove();
                                        }


                                        // Show empty state
                                        notificationDropdown.innerHTML = `

                                            <div class="p-3 text-center text-muted">
                                                No notifications
                                            </div>

                                        `;

                                    }

                                })

                                .catch(error => {

                                    console.error(
                                        "Mark read error:",
                                        error
                                    );

                                });

                            }
                        );

                    }

                })

                .catch(error => {

                    console.error(
                        "Notification loading error:",
                        error
                    );

                    notificationDropdown.innerHTML = `

                        <div class="p-3 text-center text-danger">
                            Failed to load notifications
                        </div>

                    `;

                });

        }

    });

});

</script>