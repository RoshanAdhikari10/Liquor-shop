<?php
session_start();
include("../config/database.php");

$pageTitle = "Add Product";
$adminName = "Administrator";

include("includes/header.php");
include("includes/sidebar.php");

$message = "";
$type = "";

// Load Categories
$categories = mysqli_query($conn,
"SELECT * FROM categories
WHERE status='Active'
ORDER BY name");

if(isset($_POST['save']))
{

    $name = mysqli_real_escape_string($conn,$_POST['name']);
    $category = (int)$_POST['category'];
    $brand = mysqli_real_escape_string($conn,$_POST['brand']);
    $description = mysqli_real_escape_string($conn,$_POST['description']);
    $abv = $_POST['abv'];
    $status = $_POST['status'];

    $image = "default.png";

    // Upload Image
    if(isset($_FILES['image']) && $_FILES['image']['error']==0)
    {

        $allowed = ['jpg','jpeg','png','webp'];

        $extension = strtolower(
            pathinfo($_FILES['image']['name'],
            PATHINFO_EXTENSION)
        );

        if(in_array($extension,$allowed))
        {

            $image = uniqid().".".$extension;

            move_uploaded_file(
                $_FILES['image']['tmp_name'],
                "../uploads/products/".$image
            );

        }

    }

    // Insert Product

    $productSql = "INSERT INTO products
    (
        category_id,
        name,
        brand,
        description,
        abv,
        image,
        status
    )

    VALUES
    (
        '$category',
        '$name',
        '$brand',
        '$description',
        '$abv',
        '$image',
        '$status'
    )";

    if(mysqli_query($conn,$productSql))
    {

        $product_id = mysqli_insert_id($conn);

        $volumes = $_POST['volume'];
        $prices = $_POST['price'];
        $stocks = $_POST['stock'];

        for($i=0;$i<count($volumes);$i++)
        {

            $volume = mysqli_real_escape_string($conn,$volumes[$i]);
            $price = $prices[$i];
            $stock = $stocks[$i];

            mysqli_query($conn,
            "INSERT INTO product_variants
            (
                product_id,
                volume,
                price,
                stock
            )

            VALUES
            (
                '$product_id',
                '$volume',
                '$price',
                '$stock'
            )");

        }

        header("Location: products.php?success=1");
        exit();

    }
    else
    {

        $message = mysqli_error($conn);
        $type = "danger";

    }

}
?>

<div class="main">

<?php include("includes/navbar.php"); ?>

<div class="content">

<div class="container-fluid">

    <div class="header">

        <div>
            <h2>Add Product</h2>
            <p class="text-muted">Create a new product with multiple variants</p>
        </div>

        <a href="products.php" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Products
        </a>

    </div>

    <?php if($message != ""){ ?>

        <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show" role="alert">
            <i class="fa <?php echo $type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

    <?php } ?>

    <div class="form-container">

        <form method="POST" enctype="multipart/form-data">

            <div class="row">

                <!-- Product Name -->
                <div class="col-md-6 mb-3">

                    <label class="form-label fw-bold">
                        <i class="fa fa-box text-primary"></i> Product Name <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-control form-control-lg"
                        placeholder="Enter product name"
                        required>

                </div>

                <!-- Category -->
                <div class="col-md-6 mb-3">

                    <label class="form-label fw-bold">
                        <i class="fa fa-tag text-primary"></i> Category <span class="text-danger">*</span>
                    </label>

                    <select
                        name="category"
                        class="form-select form-select-lg"
                        required>

                        <option value="">Select Category</option>

                        <?php while($cat=mysqli_fetch_assoc($categories)){ ?>

                        <option value="<?= $cat['id']; ?>">

                            <?= htmlspecialchars($cat['name']); ?>

                        </option>

                        <?php } ?>

                    </select>

                </div>

                <!-- Brand -->
                <div class="col-md-6 mb-3">

                    <label class="form-label fw-bold">
                        <i class="fa fa-building text-primary"></i> Brand
                    </label>

                    <input
                        type="text"
                        name="brand"
                        class="form-control form-control-lg"
                        placeholder="Enter brand name">

                </div>

                <!-- ABV -->
                <div class="col-md-6 mb-3">

                    <label class="form-label fw-bold">
                        <i class="fa fa-percent text-primary"></i> ABV (%)
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        name="abv"
                        class="form-control form-control-lg"
                        placeholder="40">

                </div>

                <!-- Status -->
                <div class="col-md-6 mb-3">

                    <label class="form-label fw-bold">
                        <i class="fa fa-toggle-on text-primary"></i> Status
                    </label>

                    <select
                        name="status"
                        class="form-select form-select-lg">

                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>

                    </select>

                    <small class="text-muted">Active products will be visible to customers</small>

                </div>

                <!-- Description -->
                <div class="col-12 mb-3">

                    <label class="form-label fw-bold">
                        <i class="fa fa-align-left text-primary"></i> Description
                    </label>

                    <textarea
                        name="description"
                        rows="5"
                        class="form-control"
                        placeholder="Enter product description"></textarea>

                </div>

                <!-- Image -->
                <div class="col-12 mb-4">

                    <label class="form-label fw-bold">
                        <i class="fa fa-image text-primary"></i> Product Image
                    </label>

                    <input
                        type="file"
                        name="image"
                        class="form-control form-control-lg"
                        accept=".jpg,.jpeg,.png,.webp">

                    <small class="text-muted">Supported formats: JPG, JPEG, PNG, WEBP</small>

                </div>

            </div>

            <hr>

            <h4>
                <i class="fa fa-cubes text-primary"></i> Product Variants
            </h4>

            <p class="text-muted">
                Each product can have multiple bottle sizes.
            </p>

            <div id="variantContainer">

                <div class="row variantRow mb-3">

                    <div class="col-md-4">

                        <label class="form-label fw-bold">Volume <span class="text-danger">*</span></label>

                        <input
                            type="text"
                            name="volume[]"
                            class="form-control"
                            placeholder="750ml"
                            required>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label fw-bold">Price <span class="text-danger">*</span></label>

                        <input
                            type="number"
                            step="0.01"
                            name="price[]"
                            class="form-control"
                            placeholder="0.00"
                            required>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label fw-bold">Stock <span class="text-danger">*</span></label>

                        <input
                            type="number"
                            name="stock[]"
                            class="form-control"
                            placeholder="0"
                            required>

                    </div>

                    <div class="col-md-2 d-flex align-items-end">

                        <button
                            type="button"
                            class="btn btn-danger removeVariant w-100">

                            <i class="fa fa-trash"></i> Remove

                        </button>

                    </div>

                </div>

            </div>

            <button
                type="button"
                id="addVariant"
                class="btn btn-outline-primary mb-4">

                <i class="fa fa-plus"></i> Add Another Volume

            </button>

            <hr>

            <div class="d-flex gap-2">

                <button
                    type="submit"
                    name="save"
                    class="btn btn-success btn-lg px-4">

                    <i class="fa fa-save"></i> Save Product

                </button>

                <a
                    href="products.php"
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
    .form-container {
        max-width: 1000px;
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,.08);
        margin-top: 20px;
    }

    .form-container .form-control,
    .form-container .form-select {
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        transition: all 0.3s ease;
    }

    .form-container .form-control:focus,
    .form-container .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
    }

    .form-container .form-control-lg {
        font-size: 1rem;
    }

    .form-container .form-label {
        color: #333;
        margin-bottom: 8px;
    }

    .btn-lg {
        padding: 12px 30px;
        font-size: 1rem;
    }

    .variantRow {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        margin-bottom: 15px;
    }

    .variantRow .form-control {
        background: #fff;
    }

    .removeVariant {
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #addVariant {
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 500;
    }

    #addVariant:hover {
        background: #0d6efd;
        color: #fff;
    }
</style>

<script>

const maxVariants = 5;

const container = document.getElementById("variantContainer");
const addButton = document.getElementById("addVariant");

addButton.addEventListener("click", function () {

    const total = document.querySelectorAll(".variantRow").length;

    if (total >= maxVariants) {
        alert("Maximum 5 bottle sizes allowed.");
        return;
    }

    const html = `
    <div class="row variantRow mb-3">

        <div class="col-md-4">

            <label class="form-label fw-bold">Volume <span class="text-danger">*</span></label>

            <input
                type="text"
                name="volume[]"
                class="form-control"
                placeholder="750ml"
                required>

        </div>

        <div class="col-md-3">

            <label class="form-label fw-bold">Price <span class="text-danger">*</span></label>

            <input
                type="number"
                step="0.01"
                name="price[]"
                class="form-control"
                placeholder="0.00"
                required>

        </div>

        <div class="col-md-3">

            <label class="form-label fw-bold">Stock <span class="text-danger">*</span></label>

            <input
                type="number"
                name="stock[]"
                class="form-control"
                placeholder="0"
                required>

        </div>

        <div class="col-md-2 d-flex align-items-end">

            <button
                type="button"
                class="btn btn-danger removeVariant w-100">

                <i class="fa fa-trash"></i> Remove

            </button>

        </div>

    </div>
    `;

    container.insertAdjacentHTML("beforeend", html);

});

document.addEventListener("click", function (e) {

    if (e.target.closest(".removeVariant")) {

        const rows = document.querySelectorAll(".variantRow");

        if (rows.length === 1) {

            alert("At least one variant is required.");
            return;

        }

        e.target.closest(".variantRow").remove();

    }

});

</script>

<?php include("includes/footer.php"); ?>