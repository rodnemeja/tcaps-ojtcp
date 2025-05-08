<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

$patient = array();
$edit_mode = false;

// Get patient data if in edit mode
if(isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $sql = "SELECT p.*, u.username, u.email, u.full_name, u.phone 
            FROM patients p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)) {
            $patient = $row;
        }
    }
}

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];
    $medical_history = $_POST['medical_history'];
    $password = $_POST['password'];

    if($edit_mode) {
        // Update user information
        $sql = "UPDATE users SET 
                username = ?, 
                email = ?, 
                full_name = ?, 
                phone = ? 
                WHERE id = (SELECT user_id FROM patients WHERE id = ?)";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssi", $username, $email, $full_name, $phone, $_GET['edit']);
            mysqli_stmt_execute($stmt);
        }

        // Update patient information
        $sql = "UPDATE patients SET 
                date_of_birth = ?, 
                gender = ?, 
                address = ?, 
                medical_history = ? 
                WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssi", $date_of_birth, $gender, $address, $medical_history, $_GET['edit']);
            mysqli_stmt_execute($stmt);
        }

        // Update password if provided
        if(!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ? WHERE id = (SELECT user_id FROM patients WHERE id = ?)";
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "si", $hashed_password, $_GET['edit']);
                mysqli_stmt_execute($stmt);
            }
        }
    } else {
        // Insert new user
        $sql = "INSERT INTO users (username, password, email, full_name, phone, role) 
                VALUES (?, ?, ?, ?, ?, ?)";
        if($stmt = mysqli_prepare($conn, $sql)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = "patient";
            mysqli_stmt_bind_param($stmt, "ssssss", $username, $hashed_password, $email, $full_name, $phone, $role);
            mysqli_stmt_execute($stmt);
            $user_id = mysqli_insert_id($conn);

            // Insert patient information
            $sql = "INSERT INTO patients (user_id, date_of_birth, gender, address, medical_history) 
                    VALUES (?, ?, ?, ?, ?)";
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "issss", $user_id, $date_of_birth, $gender, $address, $medical_history);
                mysqli_stmt_execute($stmt);
            }
        }
    }

    header("location: patients.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit' : 'New'; ?> Patient - Dental Clinic Management System</title>
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="text-center mb-4">
                    <i class="fas fa-tooth fa-3x mb-3"></i>
                    <h4>Dental Clinic</h4>
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
                        <a class="nav-link active" href="patients.php">
                            <i class="fas fa-users me-2"></i> Patients
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="staff.php">
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
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo $edit_mode ? 'Edit' : 'New'; ?> Patient</h2>
                    <a href="patients.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Patients
                    </a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . ($edit_mode ? "?edit=" . $_GET['edit'] : ""); ?>" method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" required 
                                           value="<?php echo isset($patient['username']) ? htmlspecialchars($patient['username']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" required 
                                           value="<?php echo isset($patient['email']) ? htmlspecialchars($patient['email']) : ''; ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="full_name" class="form-control" required 
                                           value="<?php echo isset($patient['full_name']) ? htmlspecialchars($patient['full_name']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="form-control" required 
                                           value="<?php echo isset($patient['phone']) ? htmlspecialchars($patient['phone']) : ''; ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="form-control" 
                                           value="<?php echo isset($patient['date_of_birth']) ? $patient['date_of_birth'] : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gender</label>
                                    <select name="gender" class="form-select">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?php echo isset($patient['gender']) && $patient['gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo isset($patient['gender']) && $patient['gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo isset($patient['gender']) && $patient['gender'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo isset($patient['address']) ? htmlspecialchars($patient['address']) : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Medical History</label>
                                <textarea name="medical_history" class="form-control" rows="4"><?php echo isset($patient['medical_history']) ? htmlspecialchars($patient['medical_history']) : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><?php echo $edit_mode ? 'New Password (leave blank to keep current)' : 'Password'; ?></label>
                                <input type="password" name="password" class="form-control" <?php echo $edit_mode ? '' : 'required'; ?>>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i><?php echo $edit_mode ? 'Update' : 'Create'; ?> Patient
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 