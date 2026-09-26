<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login/login.php");
    exit;
}

require "../config/database.php";

$user_id = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - KungTung</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ============================================================
           RESET & BASE
        ============================================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f0f2f5;
            color: #1d2433;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* ============================================================
           CONTAINER
        ============================================================ */
        .profile-container {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
        }

        /* ============================================================
           MAIN CARD
        ============================================================ */
        .profile-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        /* ============================================================
           HEADER
        ============================================================ */
        .profile-header {
            background: linear-gradient(135deg, #1d2433 0%, #2a3346 100%);
            padding: 40px 50px 30px;
            color: white;
        }

        .profile-header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 15px;
        }

        .profile-header-left h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .profile-header-left p {
            margin: 6px 0 0;
            opacity: 0.7;
            font-size: 14px;
        }

        .profile-header-badge {
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
        }

        .profile-header-badge i {
            font-size: 14px;
        }

        /* ============================================================
           PROFILE BODY
        ============================================================ */
        .profile-body {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 40px;
            padding: 40px 50px 50px;
        }

        /* ============================================================
           LEFT SIDEBAR
        ============================================================ */
        .profile-sidebar {
            text-align: center;
        }

        .avatar-wrapper {
            position: relative;
            width: 160px;
            height: 160px;
            margin: 0 auto 20px;
        }

        .avatar-wrapper img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #1d2433;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .avatar-edit-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: #1d2433;
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid white;
        }

        .avatar-edit-btn:hover {
            background: #2a3346;
            transform: scale(1.05);
        }

        .profile-username {
            font-size: 20px;
            font-weight: 700;
            color: #1d2433;
            margin: 0 0 4px;
        }

        .profile-role {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .profile-role.admin {
            background: #d4edda;
            color: #155724;
        }

        .profile-role.customer {
            background: #cce5ff;
            color: #004085;
        }

        /* Upload Form */
        .upload-form {
            margin-top: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 2px dashed #dee2e6;
            transition: all 0.2s;
        }

        .upload-form:hover {
            border-color: #1d2433;
            background: #f1f3f5;
        }

        .upload-form input[type="file"] {
            width: 100%;
            padding: 8px;
            font-size: 12px;
            border: none;
            background: transparent;
            cursor: pointer;
        }

        .upload-form input[type="file"]::file-selector-button {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            background: #1d2433;
            color: white;
            cursor: pointer;
            font-size: 12px;
            margin-right: 8px;
            transition: background 0.2s;
        }

        .upload-form input[type="file"]::file-selector-button:hover {
            background: #2a3346;
        }

        .btn-upload {
            width: 100%;
            margin-top: 10px;
            padding: 10px;
            background: #1d2433;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-upload:hover {
            background: #2a3346;
            transform: translateY(-1px);
        }

        /* ============================================================
           RIGHT CONTENT
        ============================================================ */
        .profile-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        /* Section Titles */
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #1d2433;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #6c757d;
            font-size: 18px;
        }

        .section-divider {
            border: none;
            border-top: 1px solid #e9ecef;
            margin: 5px 0;
        }

        /* ============================================================
           FORM STYLES
        ============================================================ */
        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }

        .form-group label i {
            margin-right: 6px;
            color: #6c757d;
            width: 16px;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s;
            background: #fafbfc;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #1d2433;
            background: white;
            box-shadow: 0 0 0 3px rgba(29, 36, 51, 0.08);
        }

        .form-control::placeholder {
            color: #adb5bd;
        }

        .form-control:disabled {
            background: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* Password Requirements */
        .password-requirements {
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            border: 1px solid #e9ecef;
        }

        .password-requirements p {
            font-size: 13px;
            font-weight: 600;
            color: #495057;
            margin: 0 0 6px;
        }

        .password-requirements ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 8px 16px;
        }

        .password-requirements ul li {
            font-size: 12px;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .password-requirements ul li i {
            font-size: 10px;
            color: #28a745;
        }

        .password-requirements ul li i.fa-circle {
            color: #dee2e6;
        }

        .password-requirements ul li.valid i.fa-circle {
            color: #28a745;
        }

        .password-requirements ul li.valid i.fa-circle {
            display: none;
        }

        .password-requirements ul li.valid i.fa-check-circle {
            display: inline;
        }

        .password-requirements ul li i.fa-check-circle {
            display: none;
            color: #28a745;
        }

        /* ============================================================
           BUTTONS
        ============================================================ */
        .btn-primary {
            padding: 11px 28px;
            background: #1d2433;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background: #2a3346;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(29, 36, 51, 0.15);
        }

        .btn-primary i {
            font-size: 14px;
        }

        .btn-secondary {
            padding: 11px 28px;
            background: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
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

        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .btn-danger {
            padding: 11px 28px;
            background: #dc3545;
            color: white;
            border: none;
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

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.2);
        }

        .btn-outline {
            padding: 11px 28px;
            background: transparent;
            color: #1d2433;
            border: 2px solid #1d2433;
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

        .btn-outline:hover {
            background: #1d2433;
            color: white;
            transform: translateY(-2px);
        }

        .btn-block {
            width: 100%;
            justify-content: center;
        }

        /* ============================================================
           ACTIONS
        ============================================================ */
        .profile-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 5px;
        }

        /* ============================================================
           ALERTS
        ============================================================ */
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #b7d4c7;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert i {
            font-size: 18px;
        }

        /* ============================================================
           RESPONSIVE
        ============================================================ */
        @media (max-width: 992px) {
            .profile-body {
                grid-template-columns: 1fr;
                gap: 30px;
                padding: 30px;
            }

            .profile-header {
                padding: 30px 30px 25px;
            }

            .profile-sidebar {
                max-width: 400px;
                margin: 0 auto;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .profile-header {
                padding: 20px;
            }

            .profile-header-top {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .profile-header-left h1 {
                font-size: 22px;
            }

            .profile-body {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .avatar-wrapper {
                width: 130px;
                height: 130px;
            }

            .profile-actions {
                flex-direction: column;
            }

            .profile-actions .btn-primary,
            .profile-actions .btn-secondary,
            .profile-actions .btn-danger,
            .profile-actions .btn-outline {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .profile-header-left h1 {
                font-size: 19px;
            }

            .avatar-wrapper {
                width: 110px;
                height: 110px;
            }

            .upload-form {
                padding: 15px;
            }

            .form-group label {
                font-size: 12px;
            }

            .form-control {
                font-size: 13px;
                padding: 8px 12px;
            }
        }

        /* ============================================================
           ANIMATIONS
        ============================================================ */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-card {
            animation: fadeInUp 0.5s ease;
        }
    </style>
</head>
<body>

<div class="profile-container">
    <div class="profile-card">

        <!-- ============================================================
             HEADER
        ============================================================ -->
        <div class="profile-header">
            <div class="profile-header-top">
                <div class="profile-header-left">
                    <h1>
                        <i class="fa-regular fa-user" style="margin-right: 10px;"></i>
                        My Account
                    </h1>
                    <p>Manage your profile information and preferences</p>
                </div>
                <div class="profile-header-badge">
                    <i class="fa-regular fa-clock"></i>
                    Member since <?= date("M Y", strtotime($user['created_at'])) ?>
                </div>
            </div>
        </div>

        <!-- ============================================================
             BODY
        ============================================================ -->
        <div class="profile-body">

            <!-- ============================================================
                 LEFT SIDEBAR
            ============================================================ -->
            <div class="profile-sidebar">
                <div class="avatar-wrapper">
                    <img src="<?= !empty($user['profile_image']) 
                        ? "../uploads/profile/".$user['profile_image'] 
                        : "https://ui-avatars.com/api/?name=".urlencode($user['full_name'])."&background=1d2433&color=ffffff&size=160" ?>" 
                         alt="Profile Picture">
                    <button class="avatar-edit-btn" onclick="document.getElementById('fileInput').click();" title="Change Profile Picture">
                        <i class="fa-solid fa-camera"></i>
                    </button>
                </div>

                <div class="profile-username"><?= htmlspecialchars($user['full_name']) ?></div>
                <span class="profile-role <?= $user['role'] ?>">
                    <i class="fa-solid <?= $user['role'] == 'admin' ? 'fa-user-shield' : 'fa-user' ?>"></i>
                    <?= ucfirst($user['role']) ?>
                </span>

                <!-- Upload Form -->
                <form action="upload_profile.php" method="POST" enctype="multipart/form-data" class="upload-form">
                    <input type="file" name="image" id="fileInput" accept=".jpg,.jpeg,.png,.webp" required style="display:none;">
                    <button type="button" class="btn-upload" onclick="document.getElementById('fileInput').click();">
                        <i class="fa-regular fa-image"></i> Choose Photo
                    </button>
                    <button type="submit" class="btn-upload" style="margin-top:5px; background: #28a745;">
                        <i class="fa-solid fa-upload"></i> Upload
                    </button>
                </form>

                <!-- Image Alerts -->
                <?php if(isset($_GET['image'])): ?>
                    <?php if($_GET['image'] == "success"): ?>
                        <div class="alert alert-success" style="margin-top:15px;">
                            <i class="fa-regular fa-circle-check"></i> Profile picture updated successfully!
                        </div>
                    <?php elseif(in_array($_GET['image'], ['type', 'size', 'error'])): ?>
                        <div class="alert alert-error" style="margin-top:15px;">
                            <i class="fa-regular fa-circle-xmark"></i>
                            <?php 
                                $messages = [
                                    'type' => 'Only JPG, JPEG, PNG and WEBP are allowed.',
                                    'size' => 'Image must be under 2MB.',
                                    'error' => 'Upload failed. Please try again.'
                                ];
                                echo $messages[$_GET['image']];
                            ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- ============================================================
                 RIGHT CONTENT
            ============================================================ -->
            <div class="profile-content">

                <!-- ============================================================
                     PERSONAL INFORMATION
                ============================================================ -->
                <div>
                    <div class="section-title">
                        <i class="fa-regular fa-address-card"></i>
                        Personal Information
                    </div>

                    <?php if(isset($_GET['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fa-regular fa-circle-check"></i> Profile updated successfully!
                        </div>
                    <?php endif; ?>

                    <form action="update_profile.php" method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fa-regular fa-user"></i> Full Name</label>
                                <input type="text" name="full_name" class="form-control" 
                                       value="<?= htmlspecialchars($user['full_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fa-regular fa-envelope"></i> Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($user['email']) ?>" disabled>
                                <small style="color:#6c757d;font-size:12px;display:block;margin-top:4px;">
                                    <i class="fa-regular fa-info-circle"></i> Email cannot be changed
                                </small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fa-regular fa-phone"></i> Phone Number</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($user['phone_number']) ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-regular fa-calendar"></i> Date of Birth</label>
                                <input type="date" name="dob" class="form-control" 
                                       value="<?= !empty($user['date_of_birth']) && $user['date_of_birth'] != '0000-00-00' ? $user['date_of_birth'] : '' ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fa-regular fa-location-dot"></i> Address</label>
                            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($user['address']) ?></textarea>
                        </div>

                        <button type="submit" class="btn-primary">
                            <i class="fa-regular fa-floppy-disk"></i> Save Changes
                        </button>
                    </form>
                </div>

                <hr class="section-divider">

                <!-- ============================================================
                     CHANGE PASSWORD
                ============================================================ -->
                <div>
                    <div class="section-title">
                        <i class="fa-regular fa-lock"></i>
                        Change Password
                    </div>

                    <?php if(isset($_GET['password'])): ?>
                        <div class="alert <?= in_array($_GET['password'], ['success']) ? 'alert-success' : 'alert-error' ?>">
                            <i class="fa-regular <?= in_array($_GET['password'], ['success']) ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                            <?php 
                                $messages = [
                                    'success' => 'Password changed successfully!',
                                    'wrong' => 'Current password is incorrect.',
                                    'mismatch' => 'New passwords do not match.',
                                    'short' => 'Password must be at least 8 characters.',
                                    'empty' => 'Please fill in all password fields.',
                                    'error' => 'Something went wrong. Please try again.',
                                    'uppercase' => 'Password must contain at least one uppercase letter.',
                                    'special' => 'Password must contain at least one special character.'
                                ];
                                echo $messages[$_GET['password']] ?? 'An error occurred.';
                            ?>
                        </div>
                    <?php endif; ?>

                    <!-- Password Requirements -->
                    <div class="password-requirements">
                        <p><i class="fa-regular fa-circle-check" style="color:#28a745;"></i> Password must contain:</p>
                        <ul>
                            <li id="req-length"><i class="fa-regular fa-circle"></i> At least 8 characters</li>
                            <li id="req-uppercase"><i class="fa-regular fa-circle"></i> One uppercase letter</li>
                            <li id="req-special"><i class="fa-regular fa-circle"></i> One special character (@, #, $, etc.)</li>
                        </ul>
                    </div>

                    <form action="change_password.php" method="POST" id="passwordForm">
                        <div class="form-group">
                            <label><i class="fa-regular fa-key"></i> Current Password</label>
                            <input type="password" name="current_password" class="form-control" 
                                   placeholder="Enter current password" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fa-regular fa-key"></i> New Password</label>
                                <input type="password" name="new_password" id="new_password" class="form-control" 
                                       placeholder="Enter new password" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fa-regular fa-key"></i> Confirm Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                                       placeholder="Confirm new password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn-secondary" style="background:#1d2433;color:white;border:none;" id="changePasswordBtn">
                            <i class="fa-regular fa-rotate"></i> Change Password
                        </button>
                    </form>
                </div>

                <hr class="section-divider">

                <!-- ============================================================
                     ACTIONS
                ============================================================ -->
                <div>
                    <div class="section-title">
                        <i class="fa-regular fa-cog"></i>
                        Quick Actions
                    </div>

                    <div class="profile-actions">
                        <a href="orders.php" class="btn-secondary">
                            <i class="fa-regular fa-shopping-bag"></i> My Orders
                        </a>
                        <a href="wishlist.php" class="btn-secondary">
                            <i class="fa-regular fa-heart"></i> Wishlist
                        </a>
                        <a href="logout.php" class="btn-danger">
                            <i class="fa-regular fa-sign-out"></i> Logout
                        </a>
                    </div>
                </div>

            </div>
            <!-- End Right Content -->

        </div>
        <!-- End Profile Body -->

    </div>
    <!-- End Profile Card -->
</div>
<!-- End Profile Container -->

<script>
// Auto-hide alerts after 5 seconds
document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    }, 5000);
});

// File input change handler - show selected filename
document.getElementById('fileInput')?.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const uploadBtn = this.closest('.upload-form').querySelector('.btn-upload:first-child');
        uploadBtn.innerHTML = `<i class="fa-regular fa-image"></i> ${file.name}`;
    }
});

// ============================================================
// PASSWORD VALIDATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const form = document.getElementById('passwordForm');
    const submitBtn = document.getElementById('changePasswordBtn');

    // Requirements elements
    const reqLength = document.getElementById('req-length');
    const reqUppercase = document.getElementById('req-uppercase');
    const reqSpecial = document.getElementById('req-special');

    function validatePassword(password) {
        const validations = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };
        return validations;
    }

    function updateRequirements(password) {
        const valid = validatePassword(password);
        
        // Update length requirement
        if (valid.length) {
            reqLength.classList.add('valid');
            reqLength.querySelector('.fa-circle').style.display = 'none';
            reqLength.querySelector('.fa-check-circle')?.remove();
            const icon = document.createElement('i');
            icon.className = 'fa-regular fa-check-circle';
            icon.style.color = '#28a745';
            reqLength.prepend(icon);
        } else {
            reqLength.classList.remove('valid');
            const checkIcon = reqLength.querySelector('.fa-check-circle');
            if (checkIcon) checkIcon.remove();
            const circleIcon = document.createElement('i');
            circleIcon.className = 'fa-regular fa-circle';
            reqLength.prepend(circleIcon);
        }

        // Update uppercase requirement
        if (valid.uppercase) {
            reqUppercase.classList.add('valid');
            reqUppercase.querySelector('.fa-circle').style.display = 'none';
            reqUppercase.querySelector('.fa-check-circle')?.remove();
            const icon = document.createElement('i');
            icon.className = 'fa-regular fa-check-circle';
            icon.style.color = '#28a745';
            reqUppercase.prepend(icon);
        } else {
            reqUppercase.classList.remove('valid');
            const checkIcon = reqUppercase.querySelector('.fa-check-circle');
            if (checkIcon) checkIcon.remove();
            const circleIcon = document.createElement('i');
            circleIcon.className = 'fa-regular fa-circle';
            reqUppercase.prepend(circleIcon);
        }

        // Update special character requirement
        if (valid.special) {
            reqSpecial.classList.add('valid');
            reqSpecial.querySelector('.fa-circle').style.display = 'none';
            reqSpecial.querySelector('.fa-check-circle')?.remove();
            const icon = document.createElement('i');
            icon.className = 'fa-regular fa-check-circle';
            icon.style.color = '#28a745';
            reqSpecial.prepend(icon);
        } else {
            reqSpecial.classList.remove('valid');
            const checkIcon = reqSpecial.querySelector('.fa-check-circle');
            if (checkIcon) checkIcon.remove();
            const circleIcon = document.createElement('i');
            circleIcon.className = 'fa-regular fa-circle';
            reqSpecial.prepend(circleIcon);
        }

        return valid.length && valid.uppercase && valid.special;
    }

    // Real-time validation as user types
    newPassword.addEventListener('input', function() {
        const isValid = updateRequirements(this.value);
        // Also check if passwords match
        if (confirmPassword.value && this.value !== confirmPassword.value) {
            confirmPassword.style.borderColor = '#dc3545';
        } else if (confirmPassword.value) {
            confirmPassword.style.borderColor = '#28a745';
        }
    });

    // Confirm password match validation
    confirmPassword.addEventListener('input', function() {
        if (this.value && this.value !== newPassword.value) {
            this.style.borderColor = '#dc3545';
        } else if (this.value) {
            this.style.borderColor = '#28a745';
        } else {
            this.style.borderColor = '#dee2e6';
        }
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
        const password = newPassword.value;
        const confirm = confirmPassword.value;

        // Validate password requirements
        const valid = validatePassword(password);
        
        if (!valid.length) {
            e.preventDefault();
            alert('Password must be at least 8 characters long.');
            newPassword.focus();
            return;
        }

        if (!valid.uppercase) {
            e.preventDefault();
            alert('Password must contain at least one uppercase letter (A-Z).');
            newPassword.focus();
            return;
        }

        if (!valid.special) {
            e.preventDefault();
            alert('Password must contain at least one special character (!@#$%^&* etc.).');
            newPassword.focus();
            return;
        }

        if (password !== confirm) {
            e.preventDefault();
            alert('New passwords do not match.');
            confirmPassword.focus();
            return;
        }

        if (password.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long.');
            newPassword.focus();
            return;
        }

        // All validations passed
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Changing Password...';
        submitBtn.disabled = true;
    });
});
</script>

</body>
</html>