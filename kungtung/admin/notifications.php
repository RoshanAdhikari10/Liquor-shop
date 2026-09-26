<?php

include("../config/database.php");

$query = mysqli_query($conn,"
    SELECT *
    FROM notifications
    ORDER BY created_at DESC
    LIMIT 10
");


if(mysqli_num_rows($query)>0){

    while($row=mysqli_fetch_assoc($query)){


?>

<div class="notification-item">


    <h6>
        <?= htmlspecialchars($row['title']); ?>
    </h6>


    <p>
        <?= htmlspecialchars($row['message']); ?>
    </p>


    <small>
        <?= $row['created_at']; ?>
    </small>


</div>


<?php

    }

}
else{

    echo "
    <div style='padding:20px;text-align:center'>
        No notifications
    </div>";

}

?>