<?php
include("../config/database.php");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("<div class='alert-custom error'>
            <i class='fa-solid fa-circle-exclamation'></i>
            <div>
                <strong>Error!</strong>
                <span>Invalid user ID.</span>
            </div>
        </div>");
}

$id = (int)$_GET['id'];

$query = mysqli_query($conn, "SELECT * FROM users WHERE id='$id'");

if (mysqli_num_rows($query) == 0) {
    die("<div class='alert-custom error'>
            <i class='fa-solid fa-circle-exclamation'></i>
            <div>
                <strong>Error!</strong>
                <span>User not found.</span>
            </div>
        </div>");
}

$user = mysqli_fetch_assoc($query);

/*
|--------------------------------------------------------------------------
| Profile Image
|--------------------------------------------------------------------------
*/

if (!empty($user['profile_image']) && file_exists("../uploads/profile/" . $user['profile_image'])) {
    $image = "../uploads/profile/" . $user['profile_image'];
} else {
    $image = "https://ui-avatars.com/api/?name=" . 
             urlencode($user['full_name']) . 
             "&background=1d2433&color=ffffff&size=250";
}
?>

<!-- User Profile Container -->
<div class="user-profile-container">
    
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar-wrapper">
            <div class="profile-avatar">
                <img src="<?= $image ?>" alt="<?= htmlspecialchars($user['full_name']) ?>">
                <div class="profile-status <?= $user['role'] ?>">
                    <span class="status-dot"></span>
                </div>
            </div>
        </div>
        
        <div class="profile-info-header">
            <h2 class="profile-name"><?= htmlspecialchars($user['full_name']) ?></h2>
            <div class="profile-badges">
                <?php if ($user['role'] == "admin"): ?>
                    <span class="role-badge admin">
                        <i class="fa-solid fa-user-shield"></i>
                        Administrator
                    </span>
                <?php else: ?>
                    <span class="role-badge customer">
                        <i class="fa-solid fa-user"></i>
                        Customer
                    </span>
                <?php endif; ?>
                <span class="role-badge joined">
                    <i class="fa-regular fa-clock"></i>
                    Member since <?= date("M Y", strtotime($user['created_at'])) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Profile Details -->
    <div class="profile-details">
        <div class="details-grid">
            <!-- Email -->
            <div class="detail-item">
                <div class="detail-icon" style="background: #e3f2fd; color: #0d6efd;">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Email Address</div>
                    <div class="detail-value"><?= htmlspecialchars($user['email']) ?></div>
                </div>
            </div>

            <!-- Phone -->
            <div class="detail-item">
                <div class="detail-icon" style="background: #d4edda; color: #155724;">
                    <i class="fa-solid fa-phone"></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Phone Number</div>
                    <div class="detail-value"><?= htmlspecialchars($user['phone_number']) ?></div>
                </div>
            </div>

            <!-- Address -->
            <div class="detail-item">
                <div class="detail-icon" style="background: #fff3cd; color: #856404;">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Address</div>
                    <div class="detail-value"><?= htmlspecialchars($user['address'] ?: 'Not provided') ?></div>
                </div>
            </div>

            <!-- Date of Birth -->
            <div class="detail-item">
                <div class="detail-icon" style="background: #d1ecf1; color: #0c5460;">
                    <i class="fa-solid fa-cake-candles"></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Date of Birth</div>
                    <div class="detail-value">
                        <?php
                        if (!empty($user['date_of_birth']) && $user['date_of_birth'] != "0000-00-00") {
                            echo date("d M Y", strtotime($user['date_of_birth']));
                        } else {
                            echo '<span class="text-muted">Not provided</span>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Joined Date -->
            <div class="detail-item">
                <div class="detail-icon" style="background: #f8d7da; color: #721c24;">
                    <i class="fa-solid fa-calendar"></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Joined</div>
                    <div class="detail-value"><?= date("d M Y, h:i A", strtotime($user['created_at'])) ?></div>
                </div>
            </div>

            <!-- User ID -->
            <div class="detail-item">
                <div class="detail-icon" style="background: #e8d5b7; color: #6d4c2a;">
                    <i class="fa-solid fa-id-badge"></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">User ID</div>
                    <div class="detail-value">#<?= $user['id'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="profile-actions">
        <?php if ($user['role'] == "customer"): ?>
            <button class="action-btn-primary roleBtn" 
                    data-id="<?= $user['id'] ?>" 
                    data-role="admin">
                <i class="fa-solid fa-user-shield"></i>
                Make Administrator
                <span class="action-btn-badge">Promote</span>
            </button>
        <?php else: ?>
            <button class="action-btn-danger roleBtn" 
                    data-id="<?= $user['id'] ?>" 
                    data-role="customer">
                <i class="fa-solid fa-user-minus"></i>
                Remove Administrator
                <span class="action-btn-badge">Demote</span>
            </button>
        <?php endif; ?>
    </div>

</div>

<style>
/* ============================================================
   USER PROFILE STYLES
============================================================ */

.user-profile-container {
    padding: 5px 0;
}

/* Alert Styles */
.alert-custom {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 15px;
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

/* Profile Header */
.profile-header {
    display: flex;
    align-items: center;
    gap: 30px;
    padding: 20px 25px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 16px;
    margin-bottom: 25px;
    border: 1px solid #f0f2f5;
}

.profile-avatar-wrapper {
    position: relative;
    flex-shrink: 0;
}

.profile-avatar {
    position: relative;
    width: 120px;
    height: 120px;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #1d2433;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.profile-status {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 3px solid white;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-status.admin {
    background: #28a745;
}

.profile-status.customer {
    background: #0d6efd;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: white;
    display: inline-block;
}

.profile-info-header {
    flex: 1;
}

.profile-name {
    font-size: 26px;
    font-weight: 700;
    color: #1d2433;
    margin: 0 0 8px 0;
}

.profile-badges {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 13px;
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

.role-badge.joined {
    background: #f8f9fa;
    color: #495057;
}

/* Profile Details */
.profile-details {
    background: white;
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 25px;
    border: 1px solid #f0f2f5;
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.detail-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 12px 16px;
    border-radius: 12px;
    background: #fafbfc;
    transition: all 0.2s;
}

.detail-item:hover {
    background: #f0f2f5;
    transform: translateX(5px);
}

.detail-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}

.detail-content {
    flex: 1;
    min-width: 0;
}

.detail-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    margin-bottom: 2px;
}

.detail-value {
    font-size: 14px;
    font-weight: 600;
    color: #1d2433;
    word-break: break-word;
}

.detail-value .text-muted {
    font-weight: 400;
    color: #6c757d;
}

/* Profile Actions */
.profile-actions {
    display: flex;
    gap: 12px;
    padding-top: 5px;
}

.action-btn-primary,
.action-btn-danger {
    padding: 10px 24px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.action-btn-primary {
    background: #1d2433;
    color: white;
}

.action-btn-primary:hover {
    background: #30394d;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(29,36,51,0.2);
}

.action-btn-danger {
    background: #dc3545;
    color: white;
}

.action-btn-danger:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(220,53,69,0.2);
}

.action-btn-badge {
    background: rgba(255,255,255,0.2);
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Responsive */
@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
        padding: 20px;
        gap: 15px;
    }
    
    .profile-name {
        font-size: 22px;
    }
    
    .profile-badges {
        justify-content: center;
    }
    
    .details-grid {
        grid-template-columns: 1fr;
    }
    
    .detail-item:hover {
        transform: translateX(0);
    }
    
    .profile-actions {
        flex-direction: column;
    }
    
    .action-btn-primary,
    .action-btn-danger {
        justify-content: center;
        width: 100%;
    }
}

@media (max-width: 480px) {
    .profile-avatar {
        width: 90px;
        height: 90px;
    }
    
    .profile-name {
        font-size: 19px;
    }
    
    .role-badge {
        font-size: 11px;
        padding: 4px 10px;
    }
    
    .detail-item {
        padding: 10px 12px;
    }
    
    .detail-label {
        font-size: 10px;
    }
    
    .detail-value {
        font-size: 13px;
    }
}

/* Loading Animation for Actions */
.action-btn-primary.loading,
.action-btn-danger.loading {
    opacity: 0.7;
    cursor: not-allowed;
    pointer-events: none;
}

.action-btn-primary.loading::after,
.action-btn-danger.loading::after {
    content: '';
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    display: inline-block;
    margin-left: 8px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
// Role button functionality with loading state
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.roleBtn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const role = this.dataset.role;
            
            // Add loading state
            this.classList.add('loading');
            this.disabled = true;
            
            const formData = new FormData();
            formData.append('id', id);
            formData.append('role', role);
            
            fetch('update-role.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message and reload
                    showToast('success', 'Role Updated', data.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showToast('error', 'Error', data.message);
                    this.classList.remove('loading');
                    this.disabled = false;
                }
            })
            .catch(error => {
                showToast('error', 'Error', 'Failed to update role. Please try again.');
                this.classList.remove('loading');
                this.disabled = false;
            });
        });
    });
});

// Toast notification function
function showToast(type, title, message) {
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }
    
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

// Add toast styles if not already present
const toastStyles = document.createElement('style');
toastStyles.textContent = `
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
        z-index: 100000;
        transform: translateX(120%);
        transition: transform 0.3s ease;
        max-width: 400px;
        border-left: 4px solid #28a745;
    }
    
    .toast-notification.show {
        transform: translateX(0);
    }
    
    .toast-notification.error {
        border-left-color: #dc3545;
    }
    
    .toast-notification i {
        font-size: 20px;
    }
    
    .toast-notification.success i {
        color: #28a745;
    }
    
    .toast-notification.error i {
        color: #dc3545;
    }
    
    .toast-notification strong {
        display: block;
        font-size: 14px;
        color: #1d2433;
    }
    
    .toast-notification span {
        font-size: 13px;
        color: #6c757d;
    }
    
    @media (max-width: 480px) {
        .toast-notification {
            top: 10px;
            right: 10px;
            left: 10px;
            max-width: none;
            padding: 12px 16px;
        }
    }
`;
document.head.appendChild(toastStyles);
</script>