<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

$staff = array();
$edit_mode = false;

// Get staff data if in edit mode
if(isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $sql = "SELECT u.*, d.specialization, d.license_number, d.status 
            FROM users u 
            LEFT JOIN doctors d ON u.id = d.user_id 
            WHERE u.id = ? AND u.role IN ('doctor', 'staff', 'admin')";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)) {
            $staff = $row;
        }
    }
}

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    $active = isset($_POST['active']) ? 1 : 0;
    $error = "";

    // Validate unique username
    $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        $id = $edit_mode ? $_GET['edit'] : 0;
        mysqli_stmt_bind_param($stmt, "si", $username, $id);
        mysqli_stmt_execute($stmt);
        if(mysqli_stmt_get_result($stmt)->num_rows > 0) {
            $error = "Username already exists.";
        }
    }

    // Validate unique email
    if(empty($error)) {
        $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            $id = $edit_mode ? $_GET['edit'] : 0;
            mysqli_stmt_bind_param($stmt, "si", $email, $id);
            mysqli_stmt_execute($stmt);
            if(mysqli_stmt_get_result($stmt)->num_rows > 0) {
                $error = "Email already exists.";
            }
        }
    }

    if(empty($error)) {
        if($edit_mode) {
            // Update user information
            $sql = "UPDATE users SET 
                    username = ?, 
                    email = ?, 
                    first_name = ?, 
                    middle_name = ?, 
                    last_name = ?, 
                    phone = ?, 
                    role = ?,
                    active = ? 
                    WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssssssii", 
                    $username, 
                    $email, 
                    $first_name, 
                    $middle_name, 
                    $last_name, 
                    $phone, 
                    $role, 
                    $active, 
                    $_GET['edit']
                );
                if(!mysqli_stmt_execute($stmt)) {
                    $error = "Error updating user information: " . mysqli_error($conn);
                }
            }

            // First, check if doctor record exists
            $check_sql = "SELECT user_id FROM doctors WHERE user_id = ?";
            if($check_stmt = mysqli_prepare($conn, $check_sql)) {
                mysqli_stmt_bind_param($check_stmt, "i", $_GET['edit']);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                $doctor_exists = mysqli_num_rows($check_result) > 0;
                mysqli_stmt_close($check_stmt);
            }

            // Handle doctor information based on role
            if($role === 'doctor') {
                $specialization = $_POST['specialization'];
                $license_number = $_POST['license_number'];
                $status = $_POST['status'];

                if($doctor_exists) {
                    // Update existing doctor record
                    $sql = "UPDATE doctors SET 
                            specialization = ?,
                            license_number = ?,
                            status = ?
                            WHERE user_id = ?";
                    if($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "sssi", $specialization, $license_number, $status, $_GET['edit']);
                        if(!mysqli_stmt_execute($stmt)) {
                            $error = "Error updating doctor information: " . mysqli_error($conn);
                        }
                    }
                } else {
                    // Insert new doctor record
                    $sql = "INSERT INTO doctors (user_id, specialization, license_number, status) 
                            VALUES (?, ?, ?, ?)";
                    if($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "isss", $_GET['edit'], $specialization, $license_number, $status);
                        if(!mysqli_stmt_execute($stmt)) {
                            $error = "Error adding doctor information: " . mysqli_error($conn);
                        }
                    }
                }
            } else if($doctor_exists) {
                // Remove doctor information if role is changed to staff
                $sql = "DELETE FROM doctors WHERE user_id = ?";
                if($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "i", $_GET['edit']);
                    if(!mysqli_stmt_execute($stmt)) {
                        $error = "Error removing doctor information: " . mysqli_error($conn);
                    }
                }
            }

            // Update password if provided
            if(!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                if($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "si", $hashed_password, $_GET['edit']);
                    if(!mysqli_stmt_execute($stmt)) {
                        $error = "Error updating password: " . mysqli_error($conn);
                    }
                }
            }

            // Update user active status
            $sql = "UPDATE users SET active = ? WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ii", $active, $_GET['edit']);
                if(!mysqli_stmt_execute($stmt)) {
                    $error = "Error updating user status: " . mysqli_error($conn);
                }
            }
        } else {
            // Insert new user
            $sql = "INSERT INTO users (username, password, email, first_name, middle_name, last_name, phone, role, active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            if($stmt = mysqli_prepare($conn, $sql)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                mysqli_stmt_bind_param($stmt, "ssssssssi", 
                    $username, 
                    $hashed_password, 
                    $email, 
                    $first_name, 
                    $middle_name, 
                    $last_name, 
                    $phone, 
                    $role, 
                    $active
                );
                if(mysqli_stmt_execute($stmt)) {
                    $user_id = mysqli_insert_id($conn);
                    
                    // If role is doctor, insert into doctors table
                    if($role === 'doctor') {
                        $specialization = $_POST['specialization'] ?? '';
                        $license_number = $_POST['license_number'] ?? '';
                        $status = $_POST['status'] ?? 'active';
                        
                        $sql = "INSERT INTO doctors (user_id, specialization, license_number, status) 
                               VALUES (?, ?, ?, ?)";
                        if($stmt = mysqli_prepare($conn, $sql)) {
                            mysqli_stmt_bind_param($stmt, "isss", $user_id, $specialization, $license_number, $status);
                            mysqli_stmt_execute($stmt);
                        }
                    }
                    
                    // Redirect to staff list
                    header("Location: staff.php");
                    exit();
                } else {
                    $error = "Error creating user: " . mysqli_error($conn);
                }
            } else {
                $error = "Error preparing statement: " . mysqli_error($conn);
            }
        }

        if(empty($error)) {
            $_SESSION['success_message'] = $edit_mode ? "Staff member updated successfully!" : "New staff member added successfully!";
            header("location: staff.php");
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
    <title><?php echo $edit_mode ? 'Edit' : 'New'; ?> Staff Member - Dental Clinic Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Add SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fc;
            overflow-x: hidden;
        }
        .sidebar {
            min-height: 100vh;
            background: #4e73df;
            color: white;
            padding: 1rem;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
            width: 250px;
            overflow-y: auto;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 0.75rem 1rem;
            border-radius: 0.35rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
        }
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 0.5rem;
        }
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
            transform: translateX(5px);
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,.1);
            color: white;
            font-weight: 500;
        }
        .main-content {
            padding: 2rem;
            margin-left: 250px;
            transition: all 0.3s ease;
            min-height: 100vh;
            position: relative;
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
        /* Form Styles */
        .form-control {
            border-radius: 0.35rem;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d3e2;
            transition: all 0.2s ease-in-out;
        }
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        .form-label {
            font-weight: 600;
            color: #4e73df;
            margin-bottom: 0.5rem;
        }
        .form-select {
            border-radius: 0.35rem;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d3e2;
        }
        .form-select:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 0.35rem;
            transition: all 0.2s ease-in-out;
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background-color: #858796;
            border-color: #858796;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 0.35rem;
            transition: all 0.2s ease-in-out;
        }
        .btn-secondary:hover {
            background-color: #6b6d7d;
            border-color: #656776;
            transform: translateY(-1px);
        }
        .form-check-input:checked {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .form-check-input:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        .invalid-feedback {
            font-size: 0.875rem;
            color: #e74a3b;
            margin-top: 0.25rem;
        }
        .form-floating {
            position: relative;
            margin-bottom: 1rem;
        }
        .form-floating > .form-control {
            height: calc(3.5rem + 2px);
            padding: 1rem 0.75rem;
        }
        .form-floating > label {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            padding: 1rem 0.75rem;
            overflow: hidden;
            text-align: start;
            text-overflow: ellipsis;
            white-space: nowrap;
            pointer-events: none;
            border: 1px solid transparent;
            transform-origin: 0 0;
            transition: opacity .1s ease-in-out,transform .1s ease-in-out;
        }
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            transform: scale(.85) translateY(-0.5rem) translateX(0.15rem);
        }
        .form-floating > .form-control:-webkit-autofill ~ label {
            transform: scale(.85) translateY(-0.5rem) translateX(0.15rem);
        }
        .input-group-text {
            background-color: #f8f9fc;
            border: 1px solid #d1d3e2;
            color: #858796;
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #4e73df;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e3e6f0;
        }
        /* Password field styling */
        .password-field {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            top: 0.7rem;
            right: 0.75rem;
            border: none;
            background: transparent;
            color: #858796;
            z-index: 5;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
        }
        .password-toggle:hover {
            color: #4e73df;
        }
        .password-toggle:focus {
            outline: none;
            box-shadow: none;
        }
        /* Logo container styling */
        .logo-container {
            padding: 1rem;
            margin-bottom: 1rem;
            text-align: center;
            border-radius: 0.5rem;
        }
        .logo-container img {
            max-width: 180px;
            height: auto;
            margin-bottom: 0.5rem;
            transition: transform 0.3s ease;
        }
        .logo-container img:hover {
            transform: scale(1.05);
        }
        .logo-container h4 {
            margin: 0;
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }
        .logo-container .admin-label {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.9);
            padding: 0.2rem 0.8rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            margin-top: 0.3rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        /* Mobile responsiveness */
        @media (min-width: 992px) {
            .sidebar {
                transform: none !important;
                display: block !important;
                width: 250px !important;
                position: fixed !important;
                height: 100vh !important;
                z-index: 1000 !important;
            }
            
            .main-content {
                margin-left: 250px !important;
                width: calc(100% - 250px) !important;
            }
        }
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .navbar-toggler {
                display: block;
                position: fixed;
                top: 1rem;
                left: 1rem;
                z-index: 1001;
                background: #4e73df;
                border: none;
                padding: 0.5rem;
                border-radius: 0.35rem;
                color: white;
            }
            .navbar-toggler:focus {
                box-shadow: none;
            }
            .sidebar-backdrop {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }
            .sidebar-backdrop.show {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Navigation Toggle -->
    <button class="navbar-toggler d-lg-none" type="button" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 px-0 sidebar" id="sidebar">
                <div class="logo-container">
                    <img src="../assets/images/logo_vibrant.png" alt="Vibrant SmileDental Logo" class="img-fluid">
                    <h4>Dental Clinic</h4>
                    <span class="admin-label">Admin</span>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">
                            <i class="fas fa-calendar me-2"></i> Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="patients.php">
                            <i class="fas fa-users me-2"></i> Patients
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="family_profiles.php">
                            <i class="fas fa-user-friends me-2"></i> Family Profiles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="staff.php">
                            <i class="fas fa-user-md me-2"></i> Staff
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">
                            <i class="fas fa-list me-2"></i> Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="invoices.php">
                            <i class="fas fa-file-invoice-dollar me-2"></i> Invoices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php">
                            <i class="fas fa-exchange-alt me-2"></i> Staff Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo $edit_mode ? 'Edit' : 'New'; ?> Staff Member</h2>
                    <a href="staff.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Staff
                    </a>
                </div>

                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . ($edit_mode ? "?edit=" . $_GET['edit'] : ""); ?>" method="post">
                            <h5 class="section-title">Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" name="username" class="form-control" id="username" placeholder="Username" required 
                                                value="<?php echo isset($staff['username']) ? htmlspecialchars($staff['username']) : ''; ?>">
                                        <label for="username">Username</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="email" name="email" class="form-control" id="email" placeholder="Email" required 
                                                value="<?php echo isset($staff['email']) ? htmlspecialchars($staff['email']) : ''; ?>">
                                        <label for="email">Email</label>
                                    </div>
                                </div>
                            </div>

                            <h5 class="section-title mt-4">Personal Information</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-floating">
                                        <input type="text" name="first_name" class="form-control" id="first_name" placeholder="First Name" required 
                                                value="<?php echo isset($staff['first_name']) ? htmlspecialchars($staff['first_name']) : ''; ?>">
                                        <label for="first_name">First Name</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-floating">
                                        <input type="text" name="middle_name" class="form-control" id="middle_name" placeholder="Middle Name" 
                                                value="<?php echo isset($staff['middle_name']) ? htmlspecialchars($staff['middle_name']) : ''; ?>">
                                        <label for="middle_name">Middle Name</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-floating">
                                        <input type="text" name="last_name" class="form-control" id="last_name" placeholder="Last Name" required 
                                                value="<?php echo isset($staff['last_name']) ? htmlspecialchars($staff['last_name']) : ''; ?>">
                                        <label for="last_name">Last Name</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="tel" name="phone" class="form-control" id="phone" placeholder="Phone (e.g., 09XXXXXXXXX)" required 
                                                maxlength="11" pattern="09[0-9]{9}" title="Please enter a valid Philippine mobile number starting with 09 followed by 9 digits"
                                                value="<?php echo isset($staff['phone']) ? htmlspecialchars($staff['phone']) : ''; ?>">
                                        <label for="phone">Phone</label>
                                        <div class="form-text small text-muted">Philippine format: 09XXXXXXXXX (11 digits only)</div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select name="role" class="form-select" id="role" required onchange="toggleDoctorFields()">
                                            <option value="">Select Role</option>
                                            <option value="staff" <?php echo isset($staff['role']) && $staff['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                            <option value="admin" <?php echo isset($staff['role']) && $staff['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            <option value="doctor" <?php echo isset($staff['role']) && $staff['role'] === 'doctor' ? 'selected' : ''; ?>>Doctor</option>
                                        </select>
                                        <label for="role">Role</label>
                                    </div>
                                </div>
                            </div>

                            <h5 class="section-title mt-4">Security</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating password-field">
                                        <input type="password" name="password" class="form-control" id="password" placeholder="Password" 
                                                <?php echo $edit_mode ? '' : 'required'; ?>>
                                        <label for="password">Password <?php echo $edit_mode ? '(leave blank to keep current)' : ''; ?></label>
                                        <button type="button" class="btn password-toggle" onclick="togglePasswordVisibility()">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <div id="passwordStrength" class="mt-1"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch mt-4">
                                        <input type="checkbox" class="form-check-input" id="active" name="active" value="1" 
                                                <?php echo (!isset($staff['active']) || $staff['active'] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="active">Active Status</label>
                                    </div>
                                </div>
                            </div>

                            <div id="doctorFields" style="display: <?php echo isset($staff['role']) && $staff['role'] === 'doctor' ? 'block' : 'none'; ?>">
                                <h5 class="section-title mt-4">Doctor Information</h5>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="form-floating">
                                            <input type="text" name="specialization" class="form-control" id="specialization" placeholder="Specialization" 
                                                    value="<?php echo isset($staff['specialization']) ? htmlspecialchars($staff['specialization']) : ''; ?>">
                                            <label for="specialization">Specialization</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-floating">
                                            <input type="text" name="license_number" class="form-control" id="license_number" placeholder="License Number" 
                                                    pattern="DN-\d{7}" title="Format: DN-XXXXXXX (e.g., DN-7788990)"
                                                    maxlength="10" oninput="formatLicenseNumber(this)"
                                                    value="<?php echo isset($staff['license_number']) ? htmlspecialchars($staff['license_number']) : ''; ?>">
                                            <label for="license_number">License Number</label>
                                            <div class="form-text">Format: DN-XXXXXXX (e.g., DN-7788990)</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-floating">
                                            <select name="status" class="form-select" id="status">
                                                <option value="active" <?php echo isset($staff['status']) && $staff['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="inactive" <?php echo isset($staff['status']) && $staff['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            </select>
                                            <label for="status">Doctor Status</label>
                                            <div class="form-text">Controls doctor's availability for appointments</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-4">
                                <a href="staff.php" class="btn btn-secondary me-2">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Staff Member
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    backdrop.classList.toggle('show');
                });
            }
            
            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    backdrop.classList.remove('show');
                });
            }
        });

        function toggleDoctorFields() {
            var role = document.querySelector('select[name="role"]').value;
            var doctorFields = document.getElementById('doctorFields');
            doctorFields.style.display = role === 'doctor' ? 'block' : 'none';
        }

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.classList.remove('fa-eye');
                toggleButton.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleButton.classList.remove('fa-eye-slash');
                toggleButton.classList.add('fa-eye');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Password strength indicator
            const passwordInput = document.getElementById('password');
            const strengthIndicator = document.getElementById('passwordStrength');
            
            if (passwordInput && strengthIndicator) {
                passwordInput.addEventListener('input', function() {
                    const value = this.value;
                    let strength = 0;
                    
                    if (value.length > 0) {
                        // Length check
                        if (value.length >= 8) strength++;
                        
                        // Character type checks
                        if (/[A-Z]/.test(value)) strength++;
                        if (/[a-z]/.test(value)) strength++;
                        if (/[0-9]/.test(value)) strength++;
                        if (/[^A-Za-z0-9]/.test(value)) strength++;
                        
                        const strengthClass = ['danger', 'warning', 'warning', 'success', 'success'];
                        const strengthText = ['Very Weak', 'Weak', 'Medium', 'Strong', 'Very Strong'];
                        
                        strengthIndicator.innerHTML = `<span class="badge bg-${strengthClass[strength - 1]}">${strengthText[strength - 1]}</span>`;
                    } else {
                        strengthIndicator.innerHTML = '';
                    }
                });
            }
            
            // Form validation
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input[required], select[required]');
            
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateInput(this);
                });
            });
            
            function validateInput(input) {
                const value = input.value.trim();
                
                if (!value) {
                    input.classList.add('is-invalid');
                    return false;
                }
                
                // Email validation
                if (input.type === 'email' && !isValidEmail(value)) {
                    input.classList.add('is-invalid');
                    return false;
                }
                
                // Phone validation
                if (input.type === 'tel' && !isValidPhone(value)) {
                    input.classList.add('is-invalid');
                    return false;
                }
                
                input.classList.remove('is-invalid');
                return true;
            }
            
            function isValidEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }
            
            function isValidPhone(phone) {
                // Philippine mobile number format: 09XXXXXXXXX (exactly 11 digits starting with 09)
                const cleaned = phone.replace(/[\s-]/g, '');
                return /^09\d{9}$/.test(cleaned) && cleaned.length === 11;
            }
            
            form.addEventListener('submit', function(event) {
                let isValid = true;
                
                inputs.forEach(input => {
                    if (!validateInput(input)) {
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    event.preventDefault();
                }
            });
        });
        
        function formatLicenseNumber(input) {
            let value = input.value.replace(/[^\d]/g, '');
            
            if (value.length > 0) {
                if (value.length > 7) {
                    value = value.substring(0, 7);
                }
                
                input.value = 'DN-' + value;
            } else if (input.value !== 'DN-') {
                input.value = '';
            }
        }
    </script>
</body>
</html> 