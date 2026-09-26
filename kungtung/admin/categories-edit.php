<?php
session_start();
include("../config/database.php");

$pageTitle = "Edit Category";
$adminName = "Administrator";

include("includes/header.php");
include("includes/sidebar.php");

if (!isset($_GET['id'])) {
    header("Location: categories.php");
    exit();
}

$id = intval($_GET['id']);

$result = mysqli_query($conn, "SELECT * FROM categories WHERE id='$id'");

if (mysqli_num_rows($result) == 0) {
    die("Category not found.");
}

$category = mysqli_fetch_assoc($result);

$message = "";
$messageType = "";

if (isset($_POST['update'])) {

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];

    if ($name == "") {

        $message = "Category name is required.";
        $messageType = "danger";

    } else {

        $check = mysqli_query($conn,
        "SELECT id FROM categories
         WHERE name='$name'
         AND id != '$id'");

        if (mysqli_num_rows($check) > 0) {

            $message = "Another category with this name already exists.";
            $messageType = "warning";

        } else {

            $sql = "UPDATE categories SET

                    name='$name',
                    description='$description',
                    status='$status'

                    WHERE id='$id'";

            if (mysqli_query($conn,$sql)) {

                header("Location: categories.php?updated=1");
                exit();

            } else {

                $message = mysqli_error($conn);
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
            <h2>Edit Category</h2>
            <p class="text-muted">Update category information</p>
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
                    value="<?php echo htmlspecialchars($category['name']); ?>"
                    required>

                <small class="text-muted">Example: Electronics, Clothing, Books, etc.</small>

            </div>

            <div class="mb-3">

                <label class="form-label fw-bold">
                    <i class="fa fa-align-left text-primary"></i> Description
                </label>

                <textarea
                    name="description"
                    rows="4"
                    class="form-control"
                    placeholder="Enter category description (optional)"><?php echo htmlspecialchars($category['description']); ?></textarea>

            </div>

            <div class="mb-4">

                <label class="form-label fw-bold">
                    <i class="fa fa-toggle-on text-primary"></i> Status
                </label>

                <select
                    name="status"
                    class="form-select form-select-lg">

                    <option value="Active"
                    <?php if($category['status']=="Active") echo "selected"; ?>>

                    Active

                    </option>

                    <option value="Inactive"
                    <?php if($category['status']=="Inactive") echo "selected"; ?>>

                    Inactive

                    </option>

                </select>

                <small class="text-muted">Active categories will be visible to customers</small>

            </div>

            <div class="d-flex gap-2">

                <button
                    type="submit"
                    name="update"
                    class="btn btn-success btn-lg px-4">

                    <i class="fa fa-save"></i> Update Category

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