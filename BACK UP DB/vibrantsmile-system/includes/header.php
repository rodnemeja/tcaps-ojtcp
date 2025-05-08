<?php
require_once __DIR__ . "/../config/database.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Dental Clinic Management System</title>
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css' rel='stylesheet' />
    
    <!-- Custom CSS -->
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
            width: 16.66%;
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
            margin-left: 16.66%;
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
        .user-status-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 10px;
            margin-top: 10px;
        }
        .user-fullname {
            font-weight: 600;
            margin-left: 8px;
        }
        .user-role {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-top: 4px;
        }
        .online-indicator {
            width: 10px;
            height: 10px;
            background-color: #1cc88a;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
            position: relative;
        }
        .online-indicator::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background-color: #1cc88a;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            100% {
                transform: scale(2);
                opacity: 0;
            }
        }
        @media (max-width: 991.98px) {
            .sidebar {
                width: 25%;
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="text-center mb-4">
                    <img src="assets/images/logo_vibrant.png" alt="Dental Clinic Logo" class="img-fluid mb-3" style="max-width: 180px; height: auto;">
                    <h4 class="text-white">Dental Clinic</h4>
                    <?php if(isset($_SESSION["role"])): ?>
                    <div class="user-status-container">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="online-indicator"></div>
                            <span class="user-fullname">
                                <?php 
                                // Get user's full name from database
                                $user_id = $_SESSION['id'];
                                $sql = "SELECT first_name, last_name FROM users WHERE id = ?";
                                $stmt = mysqli_prepare($conn, $sql);
                                mysqli_stmt_bind_param($stmt, "i", $user_id);
                                mysqli_stmt_execute($stmt);
                                $result = mysqli_stmt_get_result($stmt);
                                $user = mysqli_fetch_assoc($result);
                                
                                if($user) {
                                    echo htmlspecialchars($user['first_name'] . " " . $user['last_name']);
                                } else {
                                    echo "User";
                                }
                                ?>
                            </span>
                        </div>
                        <div class="user-role"><?php echo ucfirst($_SESSION["role"]); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo isset($current_page) && $current_page === 'dashboard' ? 'active' : ''; ?>" href="./dashboard.php">
                            <i class="fas fa-home me-2"></i> Dashboard
                        </a>
                    </li>
                    <?php if($_SESSION["role"] == "patient"): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isset($current_page) && $current_page === 'appointments' ? 'active' : ''; ?>" href="./appointments.php">
                            <i class="fas fa-calendar-alt me-2"></i> My Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isset($current_page) && $current_page === 'patient_medical_history' ? 'active' : ''; ?>" href="./patient_medical_history.php">
                            <i class="fas fa-notes-medical me-2"></i> Medical History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isset($current_page) && $current_page === 'family_profile' ? 'active' : ''; ?>" href="./family_profile.php">
                            <i class="fas fa-users me-2"></i> Family Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isset($current_page) && $current_page === 'messaging' ? 'active' : ''; ?>" href="./messaging.php">
                            <i class="fas fa-comments me-2"></i> Messages
                            <span class="badge bg-danger rounded-pill ms-2" id="unreadMessagesCount">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isset($current_page) && $current_page === 'profile' ? 'active' : ''; ?>" href="./profile.php">
                            <i class="fas fa-user me-2"></i> My Profile
                        </a>
                    </li>
                    <?php elseif($_SESSION["role"] == "doctor"): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isset($current_page) && $current_page === 'appointments' ? 'active' : ''; ?>" href="./appointments.php">
                            <i class="fas fa-calendar-check me-2"></i> Patient Appointments
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo isset($current_page) && $current_page === 'family_profiles' ? 'active' : ''; ?>" href="./family_profiles.php">
                            <i class="fas fa-users me-2"></i> Family Profiles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./doctor_messaging.php">
                            <i class="fas fa-comments me-2"></i> Messages
                            <span class="badge bg-danger rounded-pill ms-2" id="unreadMessagesCount">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isset($current_page) && $current_page === 'profile' ? 'active' : ''; ?>" href="./profile.php">
                            <i class="fas fa-user-md me-2"></i> Doctor Profile
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isset($current_page) && $current_page === 'appointments' ? 'active' : ''; ?>" href="./appointments.php">
                            <i class="fas fa-calendar me-2"></i> All Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isset($current_page) && $current_page === 'doctors' ? 'active' : ''; ?>" href="./doctors.php">
                            <i class="fas fa-user-md me-2"></i> Manage Doctors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isset($current_page) && $current_page === 'patients' ? 'active' : ''; ?>" href="./patients.php">
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