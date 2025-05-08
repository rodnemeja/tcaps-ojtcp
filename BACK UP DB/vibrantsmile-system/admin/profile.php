<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../login.php");
    exit;
}

$page_title = "Admin Profile";
$current_page = "profile";
require_once "includes/header.php";

// Initialize variables
$username = $email = $first_name = $middle_name = $last_name = $phone = "";
$username_err = $email_err = $first_name_err = $middle_name_err = $last_name_err = $phone_err = $profile_picture_err = "";
$password_err = $current_password_err = $new_password_err = $confirm_password_err = "";
$success_message = $error_message = "";
$password_success = $password_error = "";

// Get user data
$sql = "SELECT username, email, first_name, middle_name, last_name, phone, profile_picture FROM users WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    if(mysqli_stmt_execute($stmt)){
        mysqli_stmt_bind_result($stmt, $username, $email, $first_name, $middle_name, $last_name, $phone, $profile_picture);
        mysqli_stmt_fetch($stmt);
    }
    mysqli_stmt_close($stmt);
}

// Process form submission for profile update
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])){
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $param_username, $_SESSION["id"]);
            $param_username = trim($_POST["username"]);
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                $error_message = "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } else{
        $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $param_email, $_SESSION["id"]);
            $param_email = trim($_POST["email"]);
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "This email is already registered.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                $error_message = "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate other fields
    $first_name = trim($_POST["first_name"]);
    $middle_name = trim($_POST["middle_name"]);
    $last_name = trim($_POST["last_name"]);
    $phone = trim($_POST["phone"]);
    
    // Handle profile picture upload
    if(isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0){
        $allowed = ["jpg", "jpeg", "png", "gif"];
        $filename = $_FILES["profile_picture"]["name"];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($filetype), $allowed)){
            // Create uploads directory if it doesn't exist
            $upload_dir = "../uploads/profile_pictures/";
            if(!file_exists($upload_dir)){
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $new_filename = uniqid() . "." . $filetype;
            $target_file = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)){
                // Delete old profile picture if exists
                if($profile_picture && file_exists("../" . $profile_picture)){
                    unlink("../" . $profile_picture);
                }
                $profile_picture = "uploads/profile_pictures/" . $new_filename;
            } else{
                $profile_picture_err = "Sorry, there was an error uploading your file.";
            }
        } else{
            $profile_picture_err = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        }
    }
    
    // Check input errors before updating in database
    if(empty($username_err) && empty($email_err) && empty($profile_picture_err)){
        $sql = "UPDATE users SET username = ?, email = ?, first_name = ?, middle_name = ?, last_name = ?, phone = ?";
        $params = [$username, $email, $first_name, $middle_name, $last_name, $phone];
        $types = "ssssss";
        
        if($profile_picture){
            $sql .= ", profile_picture = ?";
            $params[] = $profile_picture;
            $types .= "s";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $_SESSION["id"];
        $types .= "i";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            if(mysqli_stmt_execute($stmt)){
                $success_message = "Profile updated successfully!";
            } else{
                $error_message = "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Process form submission for password change
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_password"])) {
    // Validate current password
    if(empty(trim($_POST["current_password"]))) {
        $current_password_err = "Please enter current password.";
    } else {
        // Verify current password
        $sql = "SELECT password FROM users WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
            if(mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)) {
                        if(!password_verify($_POST["current_password"], $hashed_password)) {
                            $current_password_err = "Current password is incorrect.";
                        }
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate new password
    if(empty(trim($_POST["new_password"]))) {
        $new_password_err = "Please enter new password.";
    } elseif(strlen(trim($_POST["new_password"])) < 6) {
        $new_password_err = "Password must have at least 6 characters.";
    }

    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";
    } else {
        if(empty($new_password_err) && trim($_POST["new_password"]) != trim($_POST["confirm_password"])) {
            $confirm_password_err = "Passwords did not match.";
        }
    }

    // Check input errors before updating in database
    if(empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err)) {
        // Update password
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Hash the new password
            $param_password = password_hash(trim($_POST["new_password"]), PASSWORD_DEFAULT);
            mysqli_stmt_bind_param($stmt, "si", $param_password, $_SESSION["id"]);
            if(mysqli_stmt_execute($stmt)) {
                $password_success = "Password has been changed successfully!";
            } else {
                $password_error = "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get active tab from URL or set default
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">My Profile</h1>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_tab == 'profile') ? 'active' : ''; ?>" href="#profile-tab" data-bs-toggle="tab">Profile Information</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_tab == 'password') ? 'active' : ''; ?>" href="#password-tab" data-bs-toggle="tab">Change Password</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Profile Information Tab -->
                        <div class="tab-pane fade <?php echo ($active_tab == 'profile') ? 'show active' : ''; ?>" id="profile-tab">
                            <?php if($success_message): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($error_message): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" id="profile-form" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="text-center mb-4">
                                            <div class="profile-image-container mb-3">
                                                <?php if($profile_picture): ?>
                                                    <img src="../<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="profile-image rounded-circle">
                                                <?php else: ?>
                                                    <img src="../assets/img/default-profile.png" alt="Default Profile Picture" class="profile-image rounded-circle">
                                                <?php endif; ?>
                                                <div class="profile-image-overlay">
                                                    <label for="profile_picture_input" class="upload-btn">
                                                        <i class="fas fa-camera"></i>
                                                    </label>
                                                </div>
                                            </div>
                                            <input type="file" name="profile_picture" id="profile_picture_input" class="form-control d-none <?php echo (!empty($profile_picture_err)) ? 'is-invalid' : ''; ?>">
                                            <div class="form-text">Click on the camera icon to change your profile picture</div>
                                            <?php if (!empty($profile_picture_err)): ?>
                                                <div class="invalid-feedback d-block"><?php echo $profile_picture_err; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="username" class="form-label required-field">Username</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                    <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" required>
                                                    <?php if (!empty($username_err)): ?>
                                                        <div class="invalid-feedback"><?php echo $username_err; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="email" class="form-label required-field">Email</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                                    <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>" required>
                                                    <?php if (!empty($email_err)): ?>
                                                        <div class="invalid-feedback"><?php echo $email_err; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label for="first_name" class="form-label required-field">First Name</label>
                                                <input type="text" name="first_name" id="first_name" class="form-control" value="<?php echo $first_name; ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="middle_name" class="form-label">Middle Name</label>
                                                <input type="text" name="middle_name" id="middle_name" class="form-control" value="<?php echo $middle_name; ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="last_name" class="form-label required-field">Last Name</label>
                                                <input type="text" name="last_name" id="last_name" class="form-control" value="<?php echo $last_name; ?>" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                <input type="text" name="phone" id="phone" class="form-control" value="<?php echo $phone; ?>">
                                            </div>
                                        </div>
                                        <input type="hidden" name="update_profile" value="1">
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Update Profile
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <!-- Change Password Tab -->
                        <div class="tab-pane fade <?php echo ($active_tab == 'password') ? 'show active' : ''; ?>" id="password-tab">
                            <?php if($password_success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i><?php echo $password_success; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($password_error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $password_error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="password-form" class="needs-validation" novalidate>
                                <div class="row justify-content-center">
                                    <div class="col-lg-8">
                                        <div class="card border-left-primary shadow mb-4">
                                            <div class="card-body">
                                                <div class="text-center mb-4">
                                                    <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                                                    <h4>Change Your Password</h4>
                                                    <p class="text-muted">Ensure your account is secure with a strong password</p>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="current_password" class="form-label required-field">Current Password</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                                        <input type="password" name="current_password" id="current_password" class="form-control <?php echo (!empty($current_password_err)) ? 'is-invalid' : ''; ?>" required>
                                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if (!empty($current_password_err)): ?>
                                                            <div class="invalid-feedback"><?php echo $current_password_err; ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="new_password" class="form-label required-field">New Password</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                        <input type="password" name="new_password" id="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" required>
                                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if (!empty($new_password_err)): ?>
                                                            <div class="invalid-feedback"><?php echo $new_password_err; ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="password-strength mt-2" id="password-strength"></div>
                                                </div>
                                                
                                                <div class="mb-4">
                                                    <label for="confirm_password" class="form-label required-field">Confirm Password</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-check"></i></span>
                                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" required>
                                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if (!empty($confirm_password_err)): ?>
                                                            <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <input type="hidden" name="change_password" value="1">
                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-key me-2"></i>Change Password
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationModalLabel">Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4" id="modal-icon-container">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                </div>
                <p class="text-center" id="modal-message"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Profile styles */
.profile-image-container {
    position: relative;
    width: 200px;
    height: 200px;
    margin: 0 auto;
    overflow: hidden;
    border-radius: 50%;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    background-color: #f8f9fc;
}

.profile-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-image-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: rgba(0,0,0,0.5);
    overflow: hidden;
    width: 100%;
    height: 0;
    transition: .5s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-image-container:hover .profile-image-overlay {
    height: 40px;
}

.upload-btn {
    color: white;
    font-size: 20px;
    cursor: pointer;
    margin: 0;
    padding: 10px;
}

.upload-btn:hover {
    color: #4e73df;
}

/* Password strength meter */
.password-strength {
    height: 5px;
    transition: all 0.3s ease;
    border-radius: 3px;
}

.strength-weak {
    background-color: #e74a3b;
    width: 30%;
}

.strength-medium {
    background-color: #f6c23e;
    width: 60%;
}

.strength-strong {
    background-color: #1cc88a;
    width: 100%;
}

/* Tab styling */
.nav-tabs .nav-link {
    color: #5a5c69;
    font-weight: 500;
    border: none;
    padding: 0.75rem 1rem;
}

.nav-tabs .nav-link.active {
    color: #4e73df;
    background-color: transparent;
    border-bottom: 2px solid #4e73df;
}

.nav-tabs .nav-link:hover:not(.active) {
    border-bottom: 2px solid #eaecf4;
}

/* Input group styling */
.input-group-text {
    background-color: #f8f9fc;
    border-right: none;
}

.form-control {
    border-left: none;
}

.input-group .form-control:focus {
    border-color: #d1d3e2;
    box-shadow: none;
}

.input-group .form-control:focus + .input-group-text {
    border-color: #d1d3e2;
}

/* Required field indicator */
.required-field::after {
    content: " *";
    color: #e74a3b;
    font-weight: bold;
}

/* Modal animations */
.modal.fade .modal-dialog {
    transition: transform 0.3s ease-out;
}

.modal.show .modal-dialog {
    transform: none;
}

.notification-icon-success {
    color: #1cc88a;
    animation: pulse 1.5s infinite;
}

.notification-icon-error {
    color: #e74a3b;
}

@keyframes pulse {
    0% {
        transform: scale(0.95);
        opacity: 0.8;
    }
    70% {
        transform: scale(1);
        opacity: 1;
    }
    100% {
        transform: scale(0.95);
        opacity: 0.8;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if there's a success or error message
    const successMessage = "<?php echo $success_message; ?>";
    const errorMessage = "<?php echo $error_message; ?>";
    const passwordSuccess = "<?php echo $password_success; ?>";
    const passwordError = "<?php echo $password_error; ?>";
    
    // Show notification modal if there's a message
    if (successMessage) {
        showNotificationModal('success', successMessage);
    } else if (errorMessage) {
        showNotificationModal('error', errorMessage);
    } else if (passwordSuccess) {
        showNotificationModal('success', passwordSuccess);
    } else if (passwordError) {
        showNotificationModal('error', passwordError);
    }
    
    // Function to show notification modal
    function showNotificationModal(type, message) {
        const modal = new bootstrap.Modal(document.getElementById('notificationModal'));
        const iconContainer = document.getElementById('modal-icon-container');
        const messageElement = document.getElementById('modal-message');
        
        // Set icon based on type
        if (type === 'success') {
            iconContainer.innerHTML = '<i class="fas fa-check-circle fa-4x notification-icon-success mb-3"></i>';
            document.getElementById('notificationModalLabel').textContent = 'Success';
        } else {
            iconContainer.innerHTML = '<i class="fas fa-exclamation-circle fa-4x notification-icon-error mb-3"></i>';
            document.getElementById('notificationModalLabel').textContent = 'Error';
        }
        
        // Set message
        messageElement.textContent = message;
        
        // Show modal
        modal.show();
    }
    
    // Tab navigation via URL
    const tabLinks = document.querySelectorAll('.nav-link');
    tabLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Update URL with tab parameter without reloading the page
            const tab = this.getAttribute('href').substring(1).replace('-tab', '');
            history.replaceState(null, null, '?tab=' + tab);
        });
    });
    
    // Toggle password visibility
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Password strength meter
    const passwordInput = document.getElementById('new_password');
    const strengthMeter = document.getElementById('password-strength');
    
    if (passwordInput && strengthMeter) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Check password length
            if (password.length >= 8) strength += 1;
            
            // Check for mixed case
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
            
            // Check for numbers
            if (password.match(/\d/)) strength += 1;
            
            // Check for special characters
            if (password.match(/[^a-zA-Z\d]/)) strength += 1;
            
            // Update strength meter
            strengthMeter.className = 'password-strength mt-2';
            if (password.length === 0) {
                strengthMeter.style.width = '0';
            } else if (strength < 2) {
                strengthMeter.classList.add('strength-weak');
            } else if (strength < 4) {
                strengthMeter.classList.add('strength-medium');
            } else {
                strengthMeter.classList.add('strength-strong');
            }
        });
    }
    
    // Form validation
    const profileForm = document.getElementById('profile-form');
    const passwordForm = document.getElementById('password-form');
    
    if (profileForm) {
        profileForm.addEventListener('submit', function(event) {
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            this.classList.add('was-validated');
        });
    }
    
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(event) {
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            this.classList.add('was-validated');
        });
    }
});
</script>

<?php require_once "includes/footer.php"; ?> 