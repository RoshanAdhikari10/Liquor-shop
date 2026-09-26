<?php
session_start();
include("../config/database.php");

$pageTitle = "Add Category";
$adminName = "Administrator";

include("includes/header.php");
include("includes/sidebar.php");

$message = "";
$messageType = "";

if (isset($_POST['save'])) {

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];

    if (empty($name)) {

        $message = "Category name is required.";
        $messageType = "danger";

    } else {

        // Check duplicate category
        $check = mysqli_query($conn, "SELECT id FROM categories WHERE name='$name'");

        if (mysqli_num_rows($check) > 0) {

            $message = "Category already exists.";
            $messageType = "warning";

        } else {

            $sql = "INSERT INTO categories(name, description, status)
                    VALUES('$name','$description','$status')";

            if (mysqli_query($conn, $sql)) {

                header("Location: categories.php?success=1");
                exit();

            } else {

                $message = "Database Error : " . mysqli_error($conn);
                $messageType = "danger";

            }

        }

    }

}
?>

<div class="main">

<?php include("includes/navbar.php"); ?>

<div class="content">

<div class="container-fluid">

    <div class="header">

        <div>
            <h2>Add Category</h2>
            <p class="text-muted">Create a new product category</p>
        </div>

        <a href="categories.php" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Categories
        </a>

    </div>

    <?php if($message != ""){ ?>

        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <i class="fa <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

    <?php } ?>

    <div class="form-box">

        <form method="POST">

            <div class="mb-3">

                <label class="form-label fw-bold">
                    <i class="fa fa-tag text-primary"></i> Category Name <span class="text-danger">*</span>
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control form-control-lg"
                    placeholder="Enter category name"
                    required>

                <!--  -->

            </div>

            <div class="mb-3">

                <label class="form-label fw-bold">
                    <i class="fa fa-align-left text-primary"></i> Description
                </label>

                <textarea
                    name="description"
                    rows="4"
                    class="form-control"
                    placeholder="Enter category description (optional)"></textarea>

            </div>

            <div class="mb-4">

                <label class="form-label fw-bold">
                    <i class="fa fa-toggle-on text-primary"></i> Status
                </label>

                <select
                    name="status"
                    class="form-select form-select-lg">

                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>

                </select>

                <small class="text-muted">Active categories will be visible to customers</small>

            </div>

            <div class="d-flex gap-2">

                <button
                    type="submit"
                    name="save"
                    class="btn btn-success btn-lg px-4">

                    <i class="fa fa-save"></i> Save Category

                </button>

                <a
                    href="categories.php"
                    class="btn btn-secondary btn-lg px-4">

                    <i class="fa fa-times"></i> Cancel

                </a>

            </div>

        </form>

    </div>

</div>

</div>
</div>

<style>
    .form-box {
        max-width: 700px;
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,.08);
        margin-top: 20px;
    }

    .form-box .form-control,
    .form-box .form-select {
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        transition: all 0.3s ease;
    }

    .form-box .form-control:focus,
    .form-box .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
    }

    .form-box .form-control-lg {
        font-size: 1.1rem;
    }

    .form-box .form-label {
        color: #333;
        margin-bottom: 8px;
    }

    .btn-lg {
        padding: 12px 30px;
        font-size: 1rem;
    }
</style>

<?php include("includes/footer.php"); ?>