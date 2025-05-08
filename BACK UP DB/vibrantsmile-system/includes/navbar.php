<?php
if(!isset($role)) {
    $role = $_SESSION["role"] ?? '';
}
if(!isset($has_medical_history) && $role == "patient") {
    // Check if medical history exists
    $sql = "SELECT * FROM medical_history WHERE patient_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $has_medical_history = mysqli_num_rows($result) > 0;
    }
}
?>
<!-- Sidebar -->
<div class="col-md-3 col-lg-2 px-0 sidebar">
    <div class="text-center mb-4">
        <i class="fas fa-tooth fa-3x mb-3"></i>
        <h4>Dental Clinic</h4>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="./dashboard.php">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
        </li>
        <?php if($role == "patient"): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>" href="./appointments.php">
                <i class="fas fa-calendar-alt me-2"></i> My Appointments
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>" href="./messages.php">
                <i class="fas fa-envelope me-2"></i> Messages
                <?php 
                // Check for unread messages
                $unread_count = 0;
                $unread_sql = "SELECT COUNT(*) as count FROM messages 
                              WHERE to_user_id = ? AND to_user_role = 'patient' AND is_read = 0";
                if($stmt = mysqli_prepare($conn, $unread_sql)){
                    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
                    mysqli_stmt_execute($stmt);
                    $unread_result = mysqli_stmt_get_result($stmt);
                    $unread_row = mysqli_fetch_assoc($unread_result);
                    $unread_count = $unread_row['count'];
                }
                if($unread_count > 0): 
                ?>
                <span class="badge bg-danger ms-2"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" id="medicalHistoryBtn">
                <i class="fas fa-notes-medical me-2"></i> Medical History
                <?php if(!$has_medical_history): ?>
                    <span class="badge bg-warning text-dark ms-2">Required</span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="./profile.php">
                <i class="fas fa-user me-2"></i> My Profile
            </a>
        </li>
        <?php elseif($role == "doctor"): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>" href="./appointments.php">
                <i class="fas fa-calendar-check me-2"></i> Patient Appointments
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="./profile.php">
                <i class="fas fa-user-md me-2"></i> Doctor Profile
            </a>
        </li>
        <?php else: ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>" href="./appointments.php">
                <i class="fas fa-calendar me-2"></i> All Appointments
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'doctors.php' ? 'active' : ''; ?>" href="./doctors.php">
                <i class="fas fa-user-md me-2"></i> Manage Doctors
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'patients.php' ? 'active' : ''; ?>" href="./patients.php">
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