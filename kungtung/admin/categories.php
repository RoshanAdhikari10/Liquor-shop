<?php
session_start();
include("../config/database.php");

$pageTitle = "Categories";
$adminName = "Administrator";

include("includes/header.php");
include("includes/sidebar.php");
?>

<div class="main">
    <?php include("includes/navbar.php"); ?>

    <div class="content">
        <?php
        $result = mysqli_query($conn, "
            SELECT c.*, 
                   (SELECT COUNT(*) FROM products WHERE category_id = c.id AND status = 'Active') as product_count
            FROM categories c
            ORDER BY c.id DESC
        ");
        ?>

        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <div class="header-icon">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div>
                    <h1>Categories</h1>
                    <p class="header-subtitle">Manage and organize your product categories</p>
                </div>
            </div>
            <div class="header-actions">
                <button class="btn-refresh" onclick="location.reload()">
                    <i class="fa-solid fa-rotate"></i>
                </button>
                <a href="categories-add.php" class="btn-primary-custom">
                    <i class="fa-solid fa-plus"></i>
                    Add New Category
                </a>
            </div>
        </div>

        <!-- Stats Row -->
        <?php
        $totalCategories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM categories"))['total'];
        $activeCategories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM categories WHERE status = 'Active'"))['total'];
        $inactiveCategories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM categories WHERE status = 'Inactive'"))['total'];
        ?>

        <div class="stats-row">
            <div class="stat-mini">
                <div class="stat-mini-icon" style="background: #e3f2fd; color: #0d6efd;">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div>
                    <div class="stat-mini-value"><?= $totalCategories ?></div>
                    <div class="stat-mini-label">Total Categories</div>
                </div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="background: #d4edda; color: #155724;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <div class="stat-mini-value"><?= $activeCategories ?></div>
                    <div class="stat-mini-label">Active</div>
                </div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="background: #f8d7da; color: #721c24;">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>
                <div>
                    <div class="stat-mini-value"><?= $inactiveCategories ?></div>
                    <div class="stat-mini-label">Inactive</div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if(isset($_GET['success'])): ?>
            <div class="alert-custom success">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <strong>Success!</strong>
                    <span>Category added successfully.</span>
                </div>
                <button onclick="this.parentElement.remove()" class="alert-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['updated'])): ?>
            <div class="alert-custom info">
                <i class="fa-solid fa-circle-info"></i>
                <div>
                    <strong>Updated!</strong>
                    <span>Category updated successfully.</span>
                </div>
                <button onclick="this.parentElement.remove()" class="alert-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['deleted'])): ?>
            <div class="alert-custom warning">
                <i class="fa-solid fa-trash-can"></i>
                <div>
                    <strong>Deleted!</strong>
                    <span>Category has been removed.</span>
                </div>
                <button onclick="this.parentElement.remove()" class="alert-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- Main Table -->
        <div class="table-container-modern">
            <div class="table-toolbar">
                <div class="table-info">
                    <i class="fa-regular fa-list"></i>
                    <span>Showing <strong><?= mysqli_num_rows($result) ?></strong> categories</span>
                </div>
                <div class="search-wrapper-modern">
                    <i class="fa-solid fa-search search-icon-modern"></i>
                    <input 
                        type="text" 
                        id="search" 
                        class="search-input-modern" 
                        placeholder="Search categories..."
                    >
                </div>
            </div>

            <div class="table-responsive-modern">
                <table class="table-modern" id="categoryTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Products</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>
                                        <span class="id-badge">#<?= $row['id'] ?></span>
                                    </td>
                                    <td>
                                        <div class="category-name">
                                            <div class="category-icon">
                                                <?php 
                                                $colors = ['#e3f2fd', '#d4edda', '#fff3cd', '#f8d7da', '#d1ecf1', '#f5c6cb'];
                                                $color = $colors[$row['id'] % count($colors)];
                                                ?>
                                                <span style="background: <?= $color ?>; color: #1d2433; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                                                    <?= strtoupper(substr($row['name'], 0, 2)) ?>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="name-text"><?= htmlspecialchars($row['name']) ?></div>
                                                <div class="name-slug">slug: <?= strtolower(str_replace(' ', '-', htmlspecialchars($row['name']))) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="description-text">
                                            <?php 
                                            $desc = htmlspecialchars($row['description'] ?? 'No description');
                                            echo strlen($desc) > 60 ? substr($desc, 0, 60) . '...' : $desc;
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="product-count">
                                            <?= $row['product_count'] ?? 0 ?>
                                            <span class="count-label">products</span>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($row['status'] == "Active"): ?>
                                            <span class="status-badge-modern active">
                                                <span class="status-dot"></span>
                                                Active
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge-modern inactive">
                                                <span class="status-dot"></span>
                                                Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="date-text">
                                            <i class="fa-regular fa-calendar"></i>
                                            <?= date("d M Y", strtotime($row['created_at'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="categories-edit.php?id=<?= $row['id'] ?>" 
                                               class="action-btn edit-btn" 
                                               title="Edit Category">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                            <button class="action-btn delete-btn" 
                                                    onclick="confirmDelete(<?= $row['id'] ?>, '<?= addslashes($row['name']) ?>')"
                                                    title="Delete Category">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <i class="fa-regular fa-folder-open"></i>
                                    <h5>No categories found</h5>
                                    <p>Get started by creating your first category.</p>
                                    <a href="categories-add.php" class="btn-primary-custom btn-sm">
                                        <i class="fa-solid fa-plus"></i>
                                        Add Category
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-modern">
        <div class="modal-modern-header">
            <div class="modal-modern-icon danger">
                <i class="fa-solid fa-trash-can"></i>
            </div>
            <h3>Delete Category</h3>
            <p>Are you sure you want to delete <strong id="deleteCategoryName"></strong>? This action cannot be undone.</p>
        </div>
        <div class="modal-modern-footer">
            <button onclick="closeDeleteModal()" class="btn-cancel">
                <i class="fa-regular fa-xmark"></i>
                Cancel
            </button>
            <a href="#" id="deleteConfirmLink" class="btn-delete-confirm">
                <i class="fa-regular fa-trash-can"></i>
                Delete Category
            </a>
        </div>
    </div>
</div>

<style>
/* ============================================================
   MAIN CONTENT
============================================================ */

.content {
    padding: 20px 30px 30px;
}

/* Page Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 20px 25px;
    border-radius: 16px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    border: 1px solid #f0f2f5;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 15px;
}

.header-icon {
    width: 48px;
    height: 48px;
    background: #eef2ff;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4f46e5;
    font-size: 22px;
}

.page-header h1 {
    font-size: 24px;
    font-weight: 700;
    margin: 0;
    color: #1d2433;
}

.header-subtitle {
    margin: 2px 0 0;
    color: #6c757d;
    font-size: 13px;
}

.header-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.btn-refresh {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    border: 1px solid #e9ecef;
    background: white;
    color: #495057;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-refresh:hover {
    background: #f8f9fa;
    transform: rotate(45deg);
}

.btn-primary-custom {
    background: #1d2433;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-primary-custom:hover {
    background: #30394d;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(29,36,51,0.15);
}

/* Stats Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-mini {
    background: white;
    padding: 15px 20px;
    border-radius: 12px;
    border: 1px solid #f0f2f5;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-mini-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.stat-mini-value {
    font-size: 20px;
    font-weight: 700;
    color: #1d2433;
    line-height: 1;
}

.stat-mini-label {
    font-size: 12px;
    color: #6c757d;
    margin-top: 2px;
}

/* Alerts */
.alert-custom {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 20px;
    position: relative;
}

.alert-custom.success {
    background: #d4edda;
    border: 1px solid #b7d4c7;
    color: #155724;
}

.alert-custom.info {
    background: #cce5ff;
    border: 1px solid #b8d4f0;
    color: #004085;
}

.alert-custom.warning {
    background: #fff3cd;
    border: 1px solid #f0e3b8;
    color: #856404;
}

.alert-custom i {
    font-size: 18px;
}

.alert-custom strong {
    margin-right: 4px;
}

.alert-close {
    background: none;
    border: none;
    color: inherit;
    cursor: pointer;
    margin-left: auto;
    font-size: 16px;
    opacity: 0.5;
    transition: opacity 0.2s;
}

.alert-close:hover {
    opacity: 1;
}

/* Table Container */
.table-container-modern {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    border: 1px solid #f0f2f5;
}

.table-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    flex-wrap: wrap;
    gap: 15px;
}

.table-info {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6c757d;
    font-size: 14px;
}

.table-info strong {
    color: #1d2433;
}

.search-wrapper-modern {
    position: relative;
    width: 280px;
}

.search-icon-modern {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

.search-input-modern {
    width: 100%;
    padding: 9px 12px 9px 38px;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.2s;
    background: #f8f9fa;
}

.search-input-modern:focus {
    outline: none;
    border-color: #1d2433;
    background: white;
    box-shadow: 0 0 0 3px rgba(29,36,51,0.08);
}

/* Modern Table */
.table-responsive-modern {
    overflow-x: auto;
}

.table-modern {
    width: 100%;
    border-collapse: collapse;
}

.table-modern thead th {
    background: #f8f9fa;
    padding: 12px 15px;
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #495057;
    border-bottom: 2px solid #e9ecef;
}

.table-modern tbody td {
    padding: 14px 15px;
    border-bottom: 1px solid #f0f2f5;
    vertical-align: middle;
}

.table-modern tbody tr:hover {
    background: #fafbfc;
}

.table-modern tbody tr:last-child td {
    border-bottom: none;
}

/* ID Badge */
.id-badge {
    font-weight: 700;
    color: #1d2433;
    font-size: 14px;
}

/* Category Name */
.category-name {
    display: flex;
    align-items: center;
    gap: 12px;
}

.category-icon span {
    font-weight: 700;
    font-size: 14px;
    text-transform: uppercase;
}

.name-text {
    font-weight: 600;
    color: #1d2433;
}

.name-slug {
    font-size: 11px;
    color: #6c757d;
    margin-top: 1px;
}

/* Description */
.description-text {
    color: #495057;
    font-size: 13px;
    line-height: 1.4;
    max-width: 220px;
}

/* Product Count */
.product-count {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-weight: 700;
    color: #1d2433;
}

.count-label {
    font-weight: 400;
    color: #6c757d;
    font-size: 12px;
}

/* Status Badge Modern */
.status-badge-modern {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px 4px 8px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge-modern.active {
    background: #d4edda;
    color: #155724;
}

.status-badge-modern.inactive {
    background: #f8d7da;
    color: #721c24;
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
}

.status-badge-modern.active .status-dot {
    background: #155724;
}

.status-badge-modern.inactive .status-dot {
    background: #721c24;
}

/* Date Text */
.date-text {
    color: #6c757d;
    font-size: 13px;
}

.date-text i {
    margin-right: 4px;
}

/* Action Buttons */
.action-group {
    display: flex;
    gap: 6px;
    justify-content: center;
}

.action-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.edit-btn {
    background: #e3f2fd;
    color: #0d6efd;
}

.edit-btn:hover {
    background: #0d6efd;
    color: white;
    transform: translateY(-2px);
}

.delete-btn {
    background: #f8d7da;
    color: #721c24;
    border: none;
}

.delete-btn:hover {
    background: #721c24;
    color: white;
    transform: translateY(-2px);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state i {
    font-size: 48px;
    color: #dee2e6;
    margin-bottom: 15px;
}

.empty-state h5 {
    color: #1d2433;
    margin: 0 0 5px;
}

.empty-state p {
    color: #6c757d;
    margin: 0 0 20px;
}

/* Delete Modal */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}

.modal-overlay.show {
    display: flex;
}

.modal-modern {
    background: white;
    border-radius: 20px;
    padding: 30px;
    max-width: 420px;
    width: 90%;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-30px) scale(0.95);
        opacity: 0;
    }
    to {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}

.modal-modern-header {
    text-align: center;
    margin-bottom: 25px;
}

.modal-modern-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 24px;
}

.modal-modern-icon.danger {
    background: #f8d7da;
    color: #721c24;
}

.modal-modern-header h3 {
    margin: 0 0 8px;
    color: #1d2433;
}

.modal-modern-header p {
    color: #6c757d;
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
}

.modal-modern-footer {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.btn-cancel, .btn-delete-confirm {
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-cancel {
    background: #f8f9fa;
    color: #495057;
}

.btn-cancel:hover {
    background: #e9ecef;
}

.btn-delete-confirm {
    background: #dc3545;
    color: white;
}

.btn-delete-confirm:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(220,53,69,0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .content {
        padding: 15px;
    }
    
    .page-header {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
    
    .header-actions {
        flex-wrap: wrap;
    }
    
    .btn-primary-custom {
        flex: 1;
        justify-content: center;
    }
    
    .table-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-wrapper-modern {
        width: 100%;
    }
    
    .stats-row {
        grid-template-columns: 1fr 1fr;
    }
    
    .modal-modern {
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .stats-row {
        grid-template-columns: 1fr;
    }
    
    .table-modern {
        font-size: 13px;
    }
    
    .table-modern thead th,
    .table-modern tbody td {
        padding: 10px 12px;
    }
}

/* Toast Notification (optional) */
.toast-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    padding: 15px 20px;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 10000;
    transform: translateX(120%);
    transition: transform 0.3s ease;
}

.toast-notification.show {
    transform: translateX(0);
}

.toast-notification i {
    font-size: 20px;
}
</style>

<script>
// Search functionality
document.getElementById("search").addEventListener("keyup", function() {
    let filter = this.value.toLowerCase().trim();
    let rows = document.querySelectorAll("#categoryTable tbody tr");
    
    rows.forEach(function(row) {
        // Skip empty state row
        if (row.querySelector('.empty-state')) {
            row.style.display = "none";
            return;
        }
        
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
});

// Delete Confirmation
function confirmDelete(id, name) {
    document.getElementById('deleteCategoryName').textContent = name;
    document.getElementById('deleteConfirmLink').href = 'categories-delete.php?id=' + id;
    document.getElementById('deleteModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    document.body.style.overflow = '';
}

// Close modal on overlay click
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDeleteModal();
    }
});

// Auto-hide alerts after 5 seconds
document.querySelectorAll('.alert-custom').forEach(function(alert) {
    setTimeout(function() {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.remove();
        }, 500);
    }, 5000);
});
</script>

<?php include("includes/footer.php"); ?>