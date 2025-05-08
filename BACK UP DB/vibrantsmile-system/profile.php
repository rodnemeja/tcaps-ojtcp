<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

$role = $_SESSION["role"];
$user_id = $_SESSION["id"];

// Get user information
$sql = "SELECT * FROM users WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
}

// Get additional information based on role
if($role == "patient"){
    $sql = "SELECT * FROM patients WHERE user_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $profile = mysqli_fetch_assoc($result);
    }

    // Check if medical history exists
    $sql = "SELECT * FROM medical_history WHERE patient_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $has_medical_history = mysqli_num_rows($result) > 0;
        $medical_history = mysqli_fetch_assoc($result);
    }
} elseif($role == "doctor"){
    $sql = "SELECT * FROM doctors WHERE user_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $profile = mysqli_fetch_assoc($result);
    }
}

// Handle profile update
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])){
    $first_name = trim($_POST["first_name"]);
    $middle_name = trim($_POST["middle_name"]);
    $last_name = trim($_POST["last_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    
    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Update users table
        $sql = "UPDATE users SET 
                first_name = ?, 
                middle_name = ?, 
                last_name = ?, 
                email = ?, 
                phone = ? 
                WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssi", 
            $first_name, 
            $middle_name, 
            $last_name, 
            $email, 
            $phone,
            $user_id
        );
        
        if(!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error updating user data: " . mysqli_error($conn));
        }

                // Update role-specific information
                if($role == "patient"){
            $date_of_birth = $_POST["date_of_birth"];
            $gender = $_POST["gender"];
                    $address = trim($_POST["address"]);
                    $region = trim($_POST["region"]);
                    $city = trim($_POST["city"]);
                    $barangay = trim($_POST["barangay"]);
                    $zipcode = trim($_POST["zipcode"]);
            
            // Calculate age
            $birth_date = new DateTime($date_of_birth);
            $today = new DateTime();
            $age = $birth_date->diff($today)->y;

            $sql = "UPDATE patients SET 
                    date_of_birth = ?, 
                    age = ?, 
                    gender = ?, 
                    address = ?,
                    region = ?,
                    city = ?,
                    barangay = ?,
                    zipcode = ?
                    WHERE user_id = ?";
            
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sissssssi", 
                $date_of_birth,
                $age,
                $gender,
                $address,
                $region,
                $city,
                $barangay,
                $zipcode,
                $user_id
            );
            
            if(!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error updating patient data: " . mysqli_error($conn));
            }
        }

        mysqli_commit($conn);
        $_SESSION['success_message'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}

// Handle password change
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_password"])){
    $current_password = trim($_POST["current_password"]);
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    
    $password_err = "";
    
    // Verify current password
    $sql = "SELECT password FROM users WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user_data = mysqli_fetch_assoc($result);
        
        if(!password_verify($current_password, $user_data['password'])){
            $password_err = "Current password is incorrect.";
        }
    }
    
    // Validate new password
    if(empty($new_password)){
        $password_err = "Please enter a new password.";
    } elseif(strlen($new_password) < 6){
        $password_err = "Password must have at least 6 characters.";
    }
    
    // Validate confirm password
    if(empty($confirm_password)){
        $password_err = "Please confirm the password.";
    } else{
        if(empty($password_err) && ($new_password != $confirm_password)){
            $password_err = "Password did not match.";
        }
    }
    
    // Update password if no errors
    if(empty($password_err)){
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
            mysqli_stmt_execute($stmt);
            
            // Redirect to prevent form resubmission
            header("location: profile.php?password_changed=1");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Dental Clinic Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fc;
        }
        .sidebar {
            min-height: 100vh;
            background: #4e73df;
            color: white;
            padding: 1rem;
            position: fixed;
            width: 16.66%; /* col-lg-2 equivalent */
            overflow-y: auto;
            z-index: 100;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 0.75rem 1rem;
            border-radius: 0.35rem;
            margin-bottom: 0.5rem;
        }
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,.1);
            color: white;
        }
        .main-content {
            padding: 2rem;
            margin-left: 16.66%; /* Match sidebar width */
        }
        @media (max-width: 991.98px) {
            .sidebar {
                width: 25%; /* col-md-3 equivalent */
            }
            .main-content {
                margin-left: 25%;
            }
        }
        @media (max-width: 767.98px) {
            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }
            .main-content {
                margin-left: 0;
            }
        }
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        .profile-section {
            margin-bottom: 2rem;
        }
        
        /* User online status styles */
        .user-status-container {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 8px 12px;
            margin: 10px auto;
            max-width: 90%;
        }
        .online-indicator {
            width: 10px;
            height: 10px;
            background-color: #4cd137; /* Bright green */
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            position: relative;
        }
        .online-indicator::after {
            content: '';
            position: absolute;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background-color: rgba(76, 209, 55, 0.3);
            left: -2px;
            top: -2px;
            animation: pulse 2s infinite;
        }
        .user-fullname {
            color: white;
            font-size: 0.95rem;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .user-role {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
            text-align: center;
            font-weight: 400;
            background-color: rgba(0, 0, 0, 0.15);
            border-radius: 10px;
            padding: 2px 8px;
            display: inline-block;
            margin: 4px auto 0;
        }
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.8;
            }
            70% {
                transform: scale(1.2);
                opacity: 0.4;
            }
            100% {
                transform: scale(1);
                opacity: 0.8;
            }
        }
        .profile-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            object-fit: cover;
        }
        .profile-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .profile-section {
            padding: 2rem;
        }
        .profile-section-title {
            color: #0d6efd;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        .info-label {
            color: #6c757d;
            font-weight: 500;
        }
        .info-value {
            color: #212529;
            font-weight: 500;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e3e6f0;
            padding: 0.75rem 1rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }
        .btn-primary {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
        }
        .btn-secondary {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background: none;
            border-bottom: 1px solid #e3e6f0;
            padding: 1.5rem 2rem;
        }
        .card-header h5 {
            color: #0d6efd;
            font-weight: 600;
            margin: 0;
        }
        .card-body {
            padding: 2rem;
        }
        .info-section {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .info-group {
            margin-bottom: 1.5rem;
        }
        .info-group:last-child {
            margin-bottom: 0;
        }
        .info-label {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .info-value {
            font-size: 1rem;
            color: #212529;
            font-weight: 500;
        }
        .edit-btn {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .edit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.2);
        }
        .cancel-btn {
            background: #e3e6f0;
            border: none;
            color: #495057;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .cancel-btn:hover {
            background: #d1d5db;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="text-center mb-4">
                    <img src="assets/images/logo_vibrant.png" alt="Dental Clinic Logo" class="img-fluid mb-3" style="max-width: 180px; height: auto; transition: transform 0.3s ease;">
                    <h4 class="text-white">Dental Clinic</h4>
                    <?php if($role == "patient"): ?>
                    <div class="user-status-container mt-2">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="online-indicator"></div>
                            <span class="user-fullname"><?php echo htmlspecialchars($user["first_name"] . " " . $user["last_name"]); ?></span>
                        </div>
                        <div class="user-role">Patient</div>
                    </div>
                    <?php elseif($role == "doctor"): ?>
                    <div class="user-status-container mt-2">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="online-indicator"></div>
                            <span class="user-fullname"><?php echo htmlspecialchars($user["first_name"] . " " . $user["last_name"]); ?></span>
                        </div>
                        <div class="user-role">Doctor</div>
                    </div>
                    <?php endif; ?>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-2"></i> Dashboard
                        </a>
                    </li>
                    <?php if($role == "patient"): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">
                            <i class="fas fa-calendar-alt me-2"></i> My Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="patient_medical_history.php">
                            <i class="fas fa-notes-medical me-2"></i> Medical History
                            <?php if(!$has_medical_history): ?>
                            <span class="badge bg-danger rounded-pill ms-2">Required</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="family_profile.php">
                            <i class="fas fa-users me-2"></i> Family Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="messaging.php">
                            <i class="fas fa-comments me-2"></i> Messages
                            <span class="badge bg-danger rounded-pill ms-2" id="unreadMessagesCount">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
                            <i class="fas fa-user me-2"></i> My Profile
                        </a>
                    </li>
                    <?php elseif($role == "doctor"): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">
                            <i class="fas fa-calendar-check me-2"></i> Patient Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isset($current_page) && $current_page === 'family_profiles' ? 'active' : ''; ?>" href="./family_profiles.php">
                            <i class="fas fa-users me-2"></i> Family Profiles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="doctor_messaging.php">
                            <i class="fas fa-comments me-2"></i> Messages
                            <span class="badge bg-danger rounded-pill ms-2" id="unreadMessagesCount">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
                            <i class="fas fa-user-md me-2"></i> Doctor Profile
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">
                            <i class="fas fa-calendar me-2"></i> All Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="doctors.php">
                            <i class="fas fa-user-md me-2"></i> Manage Doctors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="patients.php">
                            <i class="fas fa-users me-2"></i> Manage Patients
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item mt-4">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Profile Settings</h2>
                    <div class="text-end">
                        <span class="badge bg-primary"><?php echo ucfirst($role); ?></span>
                    </div>
                </div>

                <?php if(isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if(isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Profile Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Profile Information</h5>
                            <button class="btn edit-btn" id="editProfileBtn">
                                <i class="fas fa-edit me-2"></i>Edit Profile
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="profileForm" method="POST" action="" style="display: none;">
                            <div class="info-section">
                                <h6 class="mb-4">Personal Details</h6>
                            <div class="row mb-3">
                                    <div class="col-md-4">
                                    <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="first_name" 
                                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" name="middle_name" 
                                               value="<?php echo htmlspecialchars($user['middle_name']); ?>">
                                </div>
                                    <div class="col-md-4">
                                    <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" name="last_name" 
                                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                    </div>
                                </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if($role == "patient"): ?>
                            <div class="info-section">
                                <h6 class="mb-4">Patient Information</h6>
                            <div class="row mb-3">
                                    <div class="col-md-4">
                                    <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" name="date_of_birth" 
                                               value="<?php echo $profile['date_of_birth']; ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Age</label>
                                        <input type="text" class="form-control" value="<?php echo $profile['age']; ?>" readonly>
                                </div>
                                    <div class="col-md-4">
                                    <label class="form-label">Gender</label>
                                        <select class="form-select" name="gender" required>
                                            <option value="Male" <?php echo $profile['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo $profile['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo $profile['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                    <input type="text" class="form-control" name="address" 
                                           value="<?php echo htmlspecialchars($profile['address']); ?>" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">Region</label>
                                        <input type="text" class="form-control" name="region" 
                                               value="<?php echo htmlspecialchars($profile['region']); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                    <label class="form-label">City</label>
                                        <input type="text" class="form-control" name="city" 
                                               value="<?php echo htmlspecialchars($profile['city']); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Barangay</label>
                                        <input type="text" class="form-control" name="barangay" 
                                               value="<?php echo htmlspecialchars($profile['barangay']); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Zipcode</label>
                                        <input type="text" class="form-control" name="zipcode" 
                                               value="<?php echo htmlspecialchars($profile['zipcode']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="text-end mt-4">
                                <button type="button" class="btn cancel-btn me-2" id="cancelEditBtn">Cancel</button>
                                <button type="submit" name="update_profile" class="btn edit-btn">Save Changes</button>
                            </div>
                        </form>

                        <div id="profileDetails">
                            <div class="info-section">
                                <h6 class="mb-4">Personal Details</h6>
                            <div class="row mb-3">
                                    <div class="col-md-4">
                                        <div class="info-group">
                                            <div class="info-label">First Name</div>
                                            <div class="info-value"><?php echo htmlspecialchars($user['first_name']); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-group">
                                            <div class="info-label">Middle Name</div>
                                            <div class="info-value"><?php echo htmlspecialchars($user['middle_name']); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-group">
                                            <div class="info-label">Last Name</div>
                                            <div class="info-value"><?php echo htmlspecialchars($user['last_name']); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                <div class="col-md-6">
                                        <div class="info-group">
                                            <div class="info-label">Email</div>
                                            <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                                        </div>
                                </div>
                                <div class="col-md-6">
                                        <div class="info-group">
                                            <div class="info-label">Phone</div>
                                            <div class="info-value"><?php echo htmlspecialchars($user['phone']); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if($role == "patient"): ?>
                            <div class="info-section">
                                <h6 class="mb-4">Patient Information</h6>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <div class="info-group">
                                            <div class="info-label">Date of Birth</div>
                                            <div class="info-value"><?php echo date('F d, Y', strtotime($profile['date_of_birth'])); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-group">
                                            <div class="info-label">Age</div>
                                            <div class="info-value"><?php echo $profile['age']; ?> years old</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-group">
                                            <div class="info-label">Gender</div>
                                            <div class="info-value"><?php echo htmlspecialchars($profile['gender']); ?></div>
                                        </div>
                                    </div>
                                </div>

                            <div class="mb-3">
                                    <div class="info-group">
                                        <div class="info-label">Address</div>
                                        <div class="info-value"><?php echo htmlspecialchars($profile['address']); ?></div>
                            </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="info-group">
                                            <div class="info-label">Region</div>
                                            <div class="info-value"><?php echo htmlspecialchars($profile['region']); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-group">
                                            <div class="info-label">City</div>
                                            <div class="info-value"><?php echo htmlspecialchars($profile['city']); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-group">
                                            <div class="info-label">Barangay</div>
                                            <div class="info-value"><?php echo htmlspecialchars($profile['barangay']); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-group">
                                            <div class="info-label">Zipcode</div>
                                            <div class="info-value"><?php echo htmlspecialchars($profile['zipcode']); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if($role == "patient"): ?>
                <!-- Link to Family Profile -->
                <div class="card mb-4">
                    <div class="card-header">Family Profile</div>
                    <div class="card-body">
                        <p>Manage your family members' information and view their appointments.</p>
                        <a href="family_profile.php" class="btn btn-primary">Manage Family Profile</a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Change Password -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" id="passwordForm">
                            <?php if(isset($password_err)): ?>
                            <div class="alert alert-danger"><?php echo $password_err; ?></div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6">
                            </div>
                            <button type="submit" name="change_password" class="btn btn-warning">
                                <i class="fas fa-key me-2"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Show success message for profile update
            <?php if(isset($_SESSION['success_message'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo $_SESSION['success_message']; ?>',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['success_message']); endif; ?>

            // Show success message for password change
            <?php if(isset($_GET['password_changed'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Password Changed!',
                text: 'Your password has been updated successfully.',
                timer: 3000,
                showConfirmButton: false
            });
            <?php endif; ?>

            // Show error message if exists
            <?php if(isset($error)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?php echo $error; ?>'
            });
            <?php endif; ?>

            // Profile form validation and submission
            $('#profileForm').on('submit', function(e) {
                const requiredFields = $(this).find('[required]');
                let hasError = false;
                
                requiredFields.each(function() {
                    if (!$(this).val()) {
                        hasError = true;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });
                
                if (hasError) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please fill in all required fields'
                    });
                } else {
                    // Show loading state
                    Swal.fire({
                        title: 'Updating Profile',
                        text: 'Please wait...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        allowEnterKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                }
            });
            
            // Password form validation and submission
            $('#passwordForm').on('submit', function(e) {
                const newPassword = $('input[name="new_password"]').val();
                const confirmPassword = $('input[name="confirm_password"]').val();
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Password Mismatch',
                        text: 'New password and confirmation do not match'
                    });
                } else {
                    // Show loading state
                    Swal.fire({
                        title: 'Changing Password',
                        text: 'Please wait...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        allowEnterKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                }
            });
            
            // Phone number validation
            $('input[name="phone"]').on('input', function() {
                this.value = this.value.replace(/[^0-9+\-\s]/g, '');
            });

            const editBtn = document.getElementById('editProfileBtn');
            const cancelBtn = document.getElementById('cancelEditBtn');
            const profileForm = document.getElementById('profileForm');
            const profileDetails = document.getElementById('profileDetails');

            editBtn.addEventListener('click', function() {
                profileForm.style.display = 'block';
                profileDetails.style.display = 'none';
                editBtn.style.display = 'none';
            });

            cancelBtn.addEventListener('click', function() {
                profileForm.style.display = 'none';
                profileDetails.style.display = 'block';
                editBtn.style.display = 'block';
            });

            // Auto-calculate age when date of birth changes
            const dobInput = document.querySelector('input[name="date_of_birth"]');
            const ageInput = document.querySelector('input[readonly]');
            
            dobInput.addEventListener('change', function() {
                const birthDate = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                
                ageInput.value = age;
            });
        });
    </script>
</body>
</html> 