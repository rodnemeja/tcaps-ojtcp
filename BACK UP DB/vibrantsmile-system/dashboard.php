<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Get user role
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
        $patient = mysqli_fetch_assoc($result);
    }
} elseif($role == "doctor"){
    $sql = "SELECT * FROM doctors WHERE user_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $doctor = mysqli_fetch_assoc($result);
    }
}

// Add Medical History Check after user role check
if($role == "patient"){
    // Check if medical history exists
    $sql = "SELECT * FROM medical_history WHERE patient_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $has_medical_history = mysqli_num_rows($result) > 0;
        $medical_history = mysqli_fetch_assoc($result);
    }
}

// Get appointments based on role
if($role == "patient"){
    $sql = "SELECT a.*, d.specialization, CONCAT(u.first_name, ' ', u.last_name) as doctor_name 
            FROM appointments a 
            JOIN doctors d ON a.doctor_id = d.id 
            JOIN users u ON d.user_id = u.id 
            WHERE a.patient_id = ? 
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    $patient_id = $patient['id'] ?? null;
    if(!$patient_id) {
        // Handle case where patient record doesn't exist
        $error_message = "Patient record not found. Please complete your profile first.";
    } else {
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $patient_id);
            mysqli_stmt_execute($stmt);
            $appointments = mysqli_stmt_get_result($stmt);
            if(!$appointments) {
                $error_message = "Error fetching appointments: " . mysqli_error($conn);
            }
        }
    }
} elseif($role == "doctor"){
    $sql = "SELECT a.*, p.id as patient_id, CONCAT(u.first_name, ' ', u.last_name) as patient_name 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.id 
            JOIN users u ON p.user_id = u.id 
            WHERE a.doctor_id = ? 
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    $doctor_id = $doctor['id'] ?? null;
    if(!$doctor_id) {
        // Handle case where doctor record doesn't exist
        $error_message = "Doctor record not found. Please complete your profile first.";
    } else {
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $doctor_id);
            mysqli_stmt_execute($stmt);
            $appointments = mysqli_stmt_get_result($stmt);
            if(!$appointments) {
                $error_message = "Error fetching appointments: " . mysqli_error($conn);
            }
        }
    }
} else {
    $sql = "SELECT a.*, d.specialization, 
            CONCAT(u1.first_name, ' ', u1.last_name) as doctor_name, 
            CONCAT(u2.first_name, ' ', u2.last_name) as patient_name 
            FROM appointments a 
            JOIN doctors d ON a.doctor_id = d.id 
            JOIN patients p ON a.patient_id = p.id 
            JOIN users u1 ON d.user_id = u1.id 
            JOIN users u2 ON p.user_id = u2.id 
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_execute($stmt);
        $appointments = mysqli_stmt_get_result($stmt);
        if(!$appointments) {
            $error_message = "Error fetching appointments: " . mysqli_error($conn);
        }
    }
}

// Calculate statistics
$upcoming_appointments = 0;
$completed_appointments = 0;
$pending_appointments = 0;
$today_appointments = 0;
$total_appointments = 0;

if(isset($appointments) && $appointments) {
    mysqli_data_seek($appointments, 0); // Reset the pointer to the beginning
    while($appointment = mysqli_fetch_assoc($appointments)) {
        $total_appointments++;
        
        if($appointment['status'] == 'confirmed' && strtotime($appointment['appointment_date']) >= strtotime('today')) {
            $upcoming_appointments++;
        }
        
        if($appointment['status'] == 'completed') {
            $completed_appointments++;
        }
        
        if($appointment['status'] == 'pending') {
            $pending_appointments++;
        }
        
        if($appointment['appointment_date'] == date('Y-m-d')) {
            $today_appointments++;
        }
    }
}

// Get total patients and doctors for admin
if($role == "admin") {
    $sql = "SELECT COUNT(*) as total FROM patients";
    $result = mysqli_query($conn, $sql);
    $total_patients = mysqli_fetch_assoc($result)['total'];
    
    $sql = "SELECT COUNT(*) as total FROM doctors";
    $result = mysqli_query($conn, $sql);
    $total_doctors = mysqli_fetch_assoc($result)['total'];
} elseif($role == "doctor") {
    // Get total patients for the doctor
    $sql = "SELECT COUNT(DISTINCT a.patient_id) as total 
            FROM appointments a 
            WHERE a.doctor_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $doctor['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $total_patients = mysqli_fetch_assoc($result)['total'];
    }
}

// Get recent appointments for the table
$recent_appointments = $appointments ?? null;
if($recent_appointments) {
    mysqli_data_seek($recent_appointments, 0); // Reset the pointer to the beginning
}

function approveAppointment($conn, $appointment_id){
    $sql = "UPDATE appointments SET status = 'approved' WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $appointment_id);
        mysqli_stmt_execute($stmt);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Dental Clinic Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css' rel='stylesheet' />
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
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        .appointment-card {
            transition: transform 0.2s;
        }
        .appointment-card:hover {
            transform: translateY(-5px);
        }
        /* Calendar Styles */
        #calendar {
            min-height: 600px;
            margin: 20px 0;
        }
        .fc {
            background: white;
            padding: 20px;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .fc-toolbar {
            margin-bottom: 1.5em !important;
        }
        .fc-button {
            background-color: #4e73df !important;
            border-color: #4e73df !important;
        }
        .fc-button:hover {
            background-color: #2e59d9 !important;
            border-color: #2653d4 !important;
        }
        .fc-event {
            cursor: pointer;
            padding: 2px 5px;
        }
        .appointment-type-label {
            font-size: 0.8rem;
            font-weight: bold;
            padding: 2px 5px;
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
                        <a class="nav-link active" href="./dashboard.php">
                            <i class="fas fa-home me-2"></i> Dashboard
                        </a>
                    </li>
                    <?php if($role == "patient"): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="./appointments.php">
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
                        <a class="nav-link" href="./messaging.php">
                            <i class="fas fa-comments me-2"></i> Messages
                            <span class="badge bg-danger rounded-pill ms-2" id="unreadMessagesCount">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./profile.php">
                            <i class="fas fa-user me-2"></i> My Profile
                        </a>
                    </li>
                    <?php elseif($role == "doctor"): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="./appointments.php">
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
                            <span class="badge bg-danger rounded-pill ms-2" id="doctorUnreadMessagesCount">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./profile.php">
                            <i class="fas fa-user-md me-2"></i> Doctor Profile
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="./appointments.php">
                            <i class="fas fa-calendar me-2"></i> All Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./doctors.php">
                            <i class="fas fa-user-md me-2"></i> Manage Doctors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./patients.php">
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
                    <h2>Dashboard</h2>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <?php if($role == "admin"): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Patients</h5>
                                <h2 class="mb-0"><?php echo $total_patients; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Doctors</h5>
                                <h2 class="mb-0"><?php echo $total_doctors; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Appointments</h5>
                                <h2 class="mb-0"><?php echo $total_appointments; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Pending Appointments</h5>
                                <h2 class="mb-0"><?php echo $pending_appointments; ?></h2>
                            </div>
                        </div>
                    </div>
                    <?php elseif($role == "doctor"): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Today's Appointments</h5>
                                <h2 class="mb-0"><?php echo $today_appointments; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Patients</h5>
                                <h2 class="mb-0"><?php echo $total_patients; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Pending Appointments</h5>
                                <h2 class="mb-0"><?php echo $pending_appointments; ?></h2>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Upcoming Appointments</h5>
                                <h2 class="mb-0"><?php echo $upcoming_appointments; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Completed Appointments</h5>
                                <h2 class="mb-0"><?php echo $completed_appointments; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Pending Appointments</h5>
                                <h2 class="mb-0"><?php echo $pending_appointments; ?></h2>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Appointments -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Appointments</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <?php if($role == "patient"): ?>
                                        <th>Doctor</th>
                                        <?php else: ?>
                                        <th>Patient</th>
                                        <?php endif; ?>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($appointment = mysqli_fetch_assoc($recent_appointments)): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                        <td>
                            <?php
                                            if($role == "patient") {
                                                echo htmlspecialchars($appointment['doctor_name']);
                                            } else {
                                                echo htmlspecialchars($appointment['patient_name']);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $appointment['status'] == 'confirmed' ? 'success' : 
                                                    ($appointment['status'] == 'pending' ? 'warning' : 
                                                    ($appointment['status'] == 'cancelled' ? 'danger' : 'info')); 
                                            ?>">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="appointments.php" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                            <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Calendar Section -->
                <?php if($role == "patient" || $role == "doctor"): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Appointment Calendar</h5>
                    </div>
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($role == "doctor"): ?>
                <!-- Family Profiling Section for Doctors -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="m-0 font-weight-bold">Recent Family Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get recent family activity where multiple family members have appointments
                        $sql = "SELECT p.family_code, COUNT(DISTINCT a.patient_id) as member_count, 
                                MAX(a.appointment_date) as latest_date
                                FROM appointments a
                                JOIN patients p ON a.patient_id = p.id
                                WHERE a.doctor_id = ? AND p.family_code IS NOT NULL
                                GROUP BY p.family_code
                                HAVING COUNT(DISTINCT a.patient_id) > 1
                                ORDER BY latest_date DESC
                                LIMIT 5";
                                
                        $family_activity = [];
                        if($stmt = mysqli_prepare($conn, $sql)){
                            mysqli_stmt_bind_param($stmt, "i", $doctor['id']);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            while($row = mysqli_fetch_assoc($result)){
                                $family_activity[] = $row;
                            }
                        }
                                
                        if(empty($family_activity)):
                        ?>
                        <div class="text-center py-3">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="mb-0">No recent family activity found.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Family Code</th>
                                        <th>Members Seen</th>
                                        <th>Latest Visit</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($family_activity as $family): 
                                        // Get a patient from this family to link to
                                        $patient_sql = "SELECT p.id 
                                                        FROM patients p 
                                                        JOIN appointments a ON p.id = a.patient_id 
                                                        WHERE p.family_code = ? AND a.doctor_id = ? 
                                                        LIMIT 1";
                                        $patient_id = null;
                                        if($stmt = mysqli_prepare($conn, $patient_sql)){
                                            mysqli_stmt_bind_param($stmt, "si", $family['family_code'], $doctor['id']);
                                            mysqli_stmt_execute($stmt);
                                            $patient_result = mysqli_stmt_get_result($stmt);
                                            if($patient_row = mysqli_fetch_assoc($patient_result)){
                                                $patient_id = $patient_row['id'];
                                            }
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($family['family_code']); ?></td>
                                        <td><?php echo $family['member_count']; ?> members</td>
                                        <td><?php echo date('M d, Y', strtotime($family['latest_date'])); ?></td>
                                        <td>
                                            <?php if($patient_id): ?>
                                            <a href="doctor_patient_family.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-users me-1"></i> View Family
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add required JavaScript libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize FullCalendar
            $('#calendar').fullCalendar({
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay'
                },
                defaultView: 'month',
                editable: false,
                selectable: false,
                selectHelper: false,
                height: 'auto',
                contentHeight: 600,
                events: 'get_appointments_calendar.php',
                eventColor: function(event) {
                    return event.color;
                },
                eventRender: function(event, element) {
                    element.find('.fc-title').append('<br><small>' + 
                        (event.doctor ? event.doctor : event.patient) + '</small>');
                },
                eventClick: function(event) {
                    // Show appointment details in a modal
                    Swal.fire({
                        title: 'Appointment Details',
                        html: `
                            <div class="text-start">
                                <p><strong>Date:</strong> ${moment(event.start).format('MMMM D, YYYY')}</p>
                                <p><strong>Time:</strong> ${moment(event.start).format('h:mm A')}</p>
                                <p><strong>${event.doctor ? 'Doctor' : 'Patient'}:</strong> ${event.doctor ? event.doctor : event.patient}</p>
                                <p><strong>Status:</strong> <span class="badge bg-${
                                    event.status === 'approved' ? 'success' : 
                                    (event.status === 'pending' ? 'warning' : 
                                    (event.status === 'cancelled' ? 'danger' : 
                                    (event.status === 'completed' ? 'info' : 'secondary')))
                                }">${event.status}</span></p>
                            </div>
                        `,
                        icon: 'info',
                        confirmButtonColor: '#4e73df'
                    });
                }
            });

            // Approve appointment
            $('.approve-btn').click(function() {
                const appointmentId = $(this).data('id');
                if(confirm('Are you sure you want to approve this appointment?')) {
                    $.post('approve_appointment.php', {id: appointmentId}, function(response) {
                        if(response.success) {
                            location.reload();
                        } else {
                            alert('Error approving appointment');
                        }
                    });
                }
            });

            // View appointment details
            $('.view-btn').click(function() {
                const appointmentId = $(this).data('id');
                $.get('get_appointment.php', {id: appointmentId}, function(response) {
                    // Handle the response and show in modal
                    $('#appointmentModal').modal('show');
                });
            });

            // Check if medical history exists before allowing appointment booking
            $('.book-appointment-btn').click(function(e) {
                <?php if($role == "patient" && !$has_medical_history): ?>
                e.preventDefault();
                Swal.fire({
                    title: 'Medical History Required',
                    text: 'Before booking an appointment, you need to complete your medical history form. Would you like to do this now?',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, complete now',
                    cancelButtonText: 'No, later',
                    confirmButtonColor: '#4e73df'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#medicalHistoryModal').modal('show');
                    }
                });
                <?php endif; ?>
            });

            // Toggle allergies details
            $('input[name="has_allergies"]').change(function() {
                $('#allergiesDetails').toggleClass('d-none', $(this).val() !== '1');
            });

            // Toggle medications details
            $('input[name="has_medications"]').change(function() {
                $('#medicationsDetails').toggleClass('d-none', $(this).val() !== '1');
            });

            // Toggle other conditions details
            $('input[name="medical_conditions[]"][value="other"]').change(function() {
                $('#otherConditionsDetails').toggleClass('d-none', !$(this).is(':checked'));
            });

            // Handle medical history form submission
            $('#saveMedicalHistory').click(function() {
                const form = $('#medicalHistoryForm');
                const formData = new FormData(form[0]);

                $.ajax({
                    url: 'save_medical_history.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if(response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Medical History Saved',
                                text: 'You can now proceed with booking your appointment.',
                                confirmButtonColor: '#4e73df'
                            }).then(() => {
                                $('#medicalHistoryModal').modal('hide');
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Failed to save medical history. Please try again.',
                                confirmButtonColor: '#4e73df'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while saving your medical history. Please try again.',
                            confirmButtonColor: '#4e73df'
                        });
                    }
                });
            });

            // Medical History Button Click Handler
            $('#medicalHistoryBtn').click(function(e) {
                e.preventDefault();
                $('#medicalHistoryModal').modal('show');
            });

            // Check for unread messages
            function checkUnreadMessages() {
                <?php if($role == "patient"): ?>
                $.ajax({
                    url: 'messaging.php',
                    type: 'POST',
                    data: {
                        action: 'check_new_messages'
                    },
                    success: function(response) {
                        if (response.success) {
                            let totalUnread = 0;
                            for (const userId in response.data) {
                                totalUnread += response.data[userId].count;
                            }
                            $('#unreadMessagesCount').text(totalUnread);
                        }
                    }
                });
                <?php elseif($role == "doctor"): ?>
                $.ajax({
                    url: 'doctor_messaging.php',
                    type: 'POST',
                    data: {
                        action: 'check_new_messages'
                    },
                    success: function(response) {
                        if (response.success) {
                            let totalUnread = 0;
                            for (const userId in response.data) {
                                totalUnread += response.data[userId].count;
                            }
                            $('#doctorUnreadMessagesCount').text(totalUnread);
                        }
                    }
                });
                <?php endif; ?>
            }

            // Check for unread messages every 10 seconds
            setInterval(checkUnreadMessages, 10000);
            // Check immediately when page loads
            checkUnreadMessages();
        });

        // Medical history button click event
        document.addEventListener('DOMContentLoaded', function() {
            const medicalHistoryBtn = document.getElementById('medicalHistoryBtn');
            if(medicalHistoryBtn) {
                medicalHistoryBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = 'patient_medical_history.php';
                });
            }
        });
    </script>
</body>
</html> 

<!-- Medical History Modal -->
<div class="modal fade" id="medicalHistoryModal" tabindex="-1" aria-labelledby="medicalHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="medicalHistoryModalLabel">Medical History Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="medicalHistoryForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Do you have any allergies?</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="has_allergies" value="1" required>
                                <label class="form-check-label">Yes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="has_allergies" value="0">
                                <label class="form-check-label">No</label>
                            </div>
                            <div id="allergiesDetails" class="mt-2 d-none">
                                <textarea class="form-control" name="allergies_details" rows="2" placeholder="Please specify your allergies"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Are you taking any medications?</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="has_medications" value="1" required>
                                <label class="form-check-label">Yes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="has_medications" value="0">
                                <label class="form-check-label">No</label>
                            </div>
                            <div id="medicationsDetails" class="mt-2 d-none">
                                <textarea class="form-control" name="medications_details" rows="2" placeholder="Please list your current medications"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label">Medical Conditions (Check all that apply)</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="medical_conditions[]" value="diabetes">
                                        <label class="form-check-label">Diabetes</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="medical_conditions[]" value="heart_disease">
                                        <label class="form-check-label">Heart Disease</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="medical_conditions[]" value="hypertension">
                                        <label class="form-check-label">Hypertension</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="medical_conditions[]" value="asthma">
                                        <label class="form-check-label">Asthma</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="medical_conditions[]" value="thyroid">
                                        <label class="form-check-label">Thyroid Disease</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="medical_conditions[]" value="other">
                                        <label class="form-check-label">Other</label>
                                    </div>
                                </div>
                            </div>
                            <div id="otherConditionsDetails" class="mt-2 d-none">
                                <textarea class="form-control" name="other_conditions_details" rows="2" placeholder="Please specify other medical conditions"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Additional Notes</label>
                        <textarea class="form-control" name="additional_notes" rows="3" placeholder="Any additional information you'd like to share"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveMedicalHistory">Save Medical History</button>
            </div>
        </div>
    </div>
</div> 
</div> 
</div> 