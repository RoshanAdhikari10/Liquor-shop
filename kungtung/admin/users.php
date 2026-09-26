<?php
session_start();
include("../config/database.php");

$pageTitle = "Users";
$adminName = "Administrator";

include("includes/header.php");
include("includes/sidebar.php");
?>

<div class="main">
    <?php include("includes/navbar.php"); ?>

    <div class="content">
        <?php
        // Statistics (no status column)
        $totalUsers = 0;
        $totalAdmins = 0;
        $totalCustomers = 0;

        $totalUsersResult = mysqli_query($conn, "SELECT COUNT(*) total FROM users");
        if ($totalUsersResult) {
            $totalUsers = mysqli_fetch_assoc($totalUsersResult)['total'];
        }

        $totalAdminsResult = mysqli_query($conn, "SELECT COUNT(*) total FROM users WHERE role='admin'");
        if ($totalAdminsResult) {
            $totalAdmins = mysqli_fetch_assoc($totalAdminsResult)['total'];
        }

        $totalCustomersResult = mysqli_query($conn, "SELECT COUNT(*) total FROM users WHERE role='customer'");
        if ($totalCustomersResult) {
            $totalCustomers = mysqli_fetch_assoc($totalCustomersResult)['total'];
        }

        $search = "";
        $sql = "SELECT * FROM users";

        if(isset($_GET['search']) && $_GET['search'] != "") {
            $search = mysqli_real_escape_string($conn, $_GET['search']);
            $sql .= " WHERE full_name LIKE '%$search%' OR email LIKE '%$search%' OR phone_number LIKE '%$search%'";
        }

        $sql .= " ORDER BY id DESC";
        $result = mysqli_query($conn, $sql);
        ?>

        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <div class="header-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <h1>Users</h1>
                    <p class="header-subtitle">Manage all registered users and their roles</p>
                </div>
            </div>
            <div class="header-actions">
                <button class="btn-refresh" onclick="location.reload()" title="Refresh">
                    <i class="fa-solid fa-rotate"></i>
                </button>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-mini">
                <div class="stat-mini-icon" style="background: #e3f2fd; color: #0d6efd;">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <div class="stat-mini-value"><?= $totalUsers ?></div>
                    <div class="stat-mini-label">Total Users</div>
                </div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="background: #d4edda; color: #155724;">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div>
                    <div class="stat-mini-value"><?= $totalAdmins ?></div>
                    <div class="stat-mini-label">Administrators</div>
                </div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="background: #cce5ff; color: #004085;">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div>
                    <div class="stat-mini-value"><?= $totalCustomers ?></div>
                    <div class="stat-mini-label">Customers</div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert-custom success">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <strong>Success!</strong>
                    <span><?= $_SESSION['success'] ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="alert-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert-custom error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div>
                    <strong>Error!</strong>
                    <span><?= $_SESSION['error'] ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="alert-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Main Table -->
        <div class="table-container-modern">
            <div class="table-toolbar">
                <div class="table-info">
                    <i class="fa-regular fa-list"></i>
                    <span>Showing <strong><?= mysqli_num_rows($result) ?></strong> users</span>
                </div>
                <form method="GET" class="search-wrapper-modern">
                    <i class="fa-solid fa-search search-icon-modern"></i>
                    <input 
                        type="text" 
                        name="search" 
                        class="search-input-modern" 
                        placeholder="Search users by name, email or phone..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                    <?php if(!empty($search)): ?>
                        <a href="users.php" class="search-clear" title="Clear search">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-responsive-modern">
                <table class="table-modern" id="userTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) > 0): ?>
                            <?php while($user = mysqli_fetch_assoc($result)): ?>
                                <?php
                                if(!empty($user['profile_image']) && file_exists("../uploads/profile/".$user['profile_image'])) {
                                    $image = "../uploads/profile/".$user['profile_image'];
                                } else {
                                    $image = "https://ui-avatars.com/api/?name=".urlencode($user['full_name'])."&background=1d2433&color=ffffff&size=60";
                                }
                                ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <img src="<?= $image ?>" alt="<?= htmlspecialchars($user['full_name']) ?>">
                                            </div>
                                            <div>
                                                <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                                                <div class="user-id">ID: #<?= $user['id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-email">
                                            <i class="fa-regular fa-envelope"></i>
                                            <?= htmlspecialchars($user['email']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-phone">
                                            <i class="fa-regular fa-phone"></i>
                                            <?= htmlspecialchars($user['phone_number'] ?? '-') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($user['role'] == "admin"): ?>
                                            <span class="role-badge admin">
                                                <i class="fa-solid fa-user-shield"></i>
                                                Admin
                                            </span>
                                        <?php else: ?>
                                            <span class="role-badge customer">
                                                <i class="fa-solid fa-user"></i>
                                                Customer
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="date-text">
                                            <i class="fa-regular fa-calendar"></i>
                                            <?= date("d M Y", strtotime($user['created_at'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <button class="action-btn view-btn viewBtn" 
                                                    data-id="<?= $user['id'] ?>"
                                                    title="View Details">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                            
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fa-regular fa-users"></i>
                                    <h5>No users found</h5>
                                    <p><?= !empty($search) ? 'No users match your search criteria.' : 'No users registered yet.' ?></p>
                                    <?php if(!empty($search)): ?>
                                        <a href="users.php" class="btn-primary-custom btn-sm">
                                            <i class="fa-solid fa-arrow-left"></i>
                                            Clear Search
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal-overlay" id="userModal">
    <div class="modal-modern modal-lg">
        <div class="modal-modern-header">
            <div class="modal-header-content">
                <div class="modal-header-icon">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div>
                    <h3>User Details</h3>
                    <p id="modalUserSubtitle">View complete user information</p>
                </div>
            </div>
            <button onclick="closeUserModal()" class="modal-close-btn">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-modern-body" id="userDetails">
            <div class="loading-state">
                <div class="loading-spinner">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                </div>
                <p>Loading user details...</p>
            </div>
        </div>
    </div>
</div>

<!-- Role Change Confirmation Modal -->
<!-- <div class="modal-overlay" id="roleModal">
    <div class="modal-modern">
        <div class="modal-modern-header">
            <div class="modal-modern-icon warning">
                <i class="fa-solid fa-arrows-rotate"></i>
            </div>
            <h3>Change User Role</h3>
            <p>Are you sure you want to change the role of <strong id="roleUserName"></strong>?</p>
            <div class="role-change-info">
                <span id="roleChangeText">This will change them from <strong>Admin</strong> to <strong>Customer</strong></span>
            </div>
        </div>
        <div class="modal-modern-footer">
            <button onclick="closeRoleModal()" class="btn-cancel">
                <i class="fa-regular fa-xmark"></i>
                Cancel
            </button>
            <button class="btn-role-confirm" id="confirmRoleBtn">
                <i class="fa-regular fa-arrows-rotate"></i>
                Change Role
            </button>
        </div>
    </div>
</div> -->

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

.alert-custom.error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
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
    width: 320px;
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
    padding: 9px 40px 9px 38px;
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

.search-clear {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    text-decoration: none;
    font-size: 14px;
}

.search-clear:hover {
    color: #dc3545;
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

.table-modern thead th.text-center {
    text-align: center;
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

/* User Info */
.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    border: 2px solid #e9ecef;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-name {
    font-weight: 600;
    color: #1d2433;
    font-size: 14px;
}

.user-id {
    font-size: 11px;
    color: #6c757d;
    margin-top: 1px;
}

/* User Email & Phone */
.user-email, .user-phone {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #495057;
    font-size: 13px;
}

.user-email i, .user-phone i {
    color: #6c757d;
    font-size: 12px;
    width: 16px;
}

/* Role Badges */
.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.role-badge.admin {
    background: #d4edda;
    color: #155724;
}

.role-badge.customer {
    background: #cce5ff;
    color: #004085;
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

.view-btn {
    background: #e3f2fd;
    color: #0d6efd;
}

.view-btn:hover {
    background: #0d6efd;
    color: white;
    transform: translateY(-2px);
}

.role-btn {
    background: #fff3cd;
    color: #856404;
}

.role-btn:hover {
    background: #856404;
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

.btn-primary-custom.btn-sm {
    padding: 8px 16px;
    font-size: 13px;
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

/* Modal Overlay */
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
    max-width: 600px;
    width: 90%;
    animation: modalSlideIn 0.3s ease;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.modal-modern.modal-lg {
    max-width: 700px;
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

/* Modal Header */
.modal-modern-header {
    padding: 20px 25px;
    border-bottom: 1px solid #f0f2f5;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.modal-header-content {
    display: flex;
    align-items: center;
    gap: 15px;
}

.modal-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #eef2ff;
    color: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.modal-header-content h3 {
    margin: 0;
    font-size: 18px;
    color: #1d2433;
}

.modal-header-content p {
    margin: 2px 0 0;
    color: #6c757d;
    font-size: 13px;
}

.modal-close-btn {
    background: none;
    border: none;
    font-size: 20px;
    color: #6c757d;
    cursor: pointer;
    padding: 5px;
    border-radius: 8px;
    transition: all 0.2s;
}

.modal-close-btn:hover {
    background: #f8f9fa;
    color: #1d2433;
}

/* Modal Body */
.modal-modern-body {
    padding: 25px;
    overflow-y: auto;
    flex: 1;
}

.loading-state {
    text-align: center;
    padding: 40px 0;
}

.loading-spinner {
    font-size: 32px;
    color: #1d2433;
    margin-bottom: 15px;
}

.loading-state p {
    color: #6c757d;
    margin: 0;
}

/* Role Modal */
.modal-modern-icon.warning {
    background: #fff3cd;
    color: #856404;
}

.role-change-info {
    background: #f8f9fa;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 13px;
    color: #495057;
    margin-top: 10px;
}

.modal-modern-footer {
    padding: 15px 25px 25px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    border-top: 1px solid #f0f2f5;
}

.btn-cancel, .btn-role-confirm {
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
}

.btn-cancel {
    background: #f8f9fa;
    color: #495057;
}

.btn-cancel:hover {
    background: #e9ecef;
}

.btn-role-confirm {
    background: #856404;
    color: white;
}

.btn-role-confirm:hover {
    background: #6d5304;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(133,100,4,0.3);
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
        width: 95%;
    }
    
    .modal-modern-header {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    
    .modal-close-btn {
        align-self: flex-end;
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
    
    .user-avatar {
        width: 36px;
        height: 36px;
    }
}
</style>

<script>
// User Modal
let currentUserId = null;

function openUserModal(id) {
    currentUserId = id;
    const modal = document.getElementById('userModal');
    const details = document.getElementById('userDetails');
    
    details.innerHTML = `
        <div class="loading-state">
            <div class="loading-spinner">
                <i class="fa-solid fa-spinner fa-spin"></i>
            </div>
            <p>Loading user details...</p>
        </div>
    `;
    
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    document.getElementById('modalUserSubtitle').textContent = 'Loading user information...';
    
    fetch("get-user.php?id=" + id)
        .then(response => response.text())
        .then(html => {
            details.innerHTML = html;
            document.getElementById('modalUserSubtitle').textContent = 'View complete user information';
        })
        .catch(error => {
            details.innerHTML = `
                <div class="alert-custom error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div>
                        <strong>Error!</strong>
                        <span>Failed to load user details. Please try again.</span>
                    </div>
                </div>
            `;
        });
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('show');
    document.body.style.overflow = '';
    currentUserId = null;
}

// View button click
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.viewBtn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            openUserModal(id);
        });
    });
    
    // Close modal on overlay click
    document.getElementById('userModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeUserModal();
        }
    });
    
    // Close with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeUserModal();
            closeRoleModal();
        }
    });
});

// Role Change
let roleData = null;

function changeRole(id, newRole, userName) {
    const currentRole = newRole === 'admin' ? 'Customer' : 'Admin';
    const newRoleDisplay = newRole === 'admin' ? 'Admin' : 'Customer';
    
    document.getElementById('roleUserName').textContent = userName;
    document.getElementById('roleChangeText').innerHTML = 
        `This will change them from <strong>${currentRole}</strong> to <strong>${newRoleDisplay}</strong>`;
    
    roleData = { id, newRole };
    
    document.getElementById('roleModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeRoleModal() {
    document.getElementById('roleModal').classList.remove('show');
    document.body.style.overflow = '';
    roleData = null;
}

document.getElementById('confirmRoleBtn').addEventListener('click', function() {
    if (!roleData) return;
    
    const button = this;
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';
    
    const formData = new FormData();
    formData.append('id', roleData.id);
    formData.append('role', roleData.newRole);
    
    fetch('update-role.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeRoleModal();
            showToast('success', 'Role Updated', data.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('error', 'Error', data.message);
        }
    })
    .catch(error => {
        showToast('error', 'Error', 'Failed to update role. Please try again.');
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = originalText;
    });
});

// Toast Notification
function showToast(type, title, message) {
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <i class="fa-solid ${type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'}"></i>
        <div>
            <strong>${title}</strong>
            <span>${message}</span>
        </div>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Search functionality with form submit
document.querySelector('.search-wrapper-modern')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const input = this.querySelector('.search-input-modern');
    if (input.value.trim()) {
        window.location.href = 'users.php?search=' + encodeURIComponent(input.value.trim());
    } else {
        window.location.href = 'users.php';
    }
});

// Auto-hide alerts
document.querySelectorAll('.alert-custom').forEach(alert => {
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    }, 5000);
});

// Auto-submit search on Enter
document.querySelector('.search-input-modern')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const form = this.closest('form');
        if (form) form.submit();
    }
});
</script>

<?php include("includes/footer.php"); ?>