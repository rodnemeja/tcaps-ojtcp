<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    exit('Not logged in');
}

$role = $_SESSION["role"];
$user_id = $_SESSION["id"];

// Get appointments based on role
if($role == "patient"){
    $sql = "SELECT a.*, d.specialization, s.name as service_name, s.price as service_price,
            CONCAT(u.first_name, ' ', u.last_name) as doctor_name 
            FROM appointments a 
            JOIN doctors d ON a.doctor_id = d.id 
            JOIN users u ON d.user_id = u.id 
            JOIN services s ON a.service_id = s.id 
            WHERE a.patient_id = (SELECT id FROM patients WHERE user_id = ?) 
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
} elseif($role == "doctor"){
    $sql = "SELECT a.*, p.id as patient_id, s.name as service_name, s.price as service_price,
            CONCAT(u.first_name, ' ', u.last_name) as patient_name 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.id 
            JOIN users u ON p.user_id = u.id 
            JOIN services s ON a.service_id = s.id 
            WHERE a.doctor_id = (SELECT id FROM doctors WHERE user_id = ?) 
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
} else{
    $sql = "SELECT a.*, d.specialization, s.name as service_name, s.price as service_price,
            CONCAT(u1.first_name, ' ', u1.last_name) as doctor_name, 
            CONCAT(u2.first_name, ' ', u2.last_name) as patient_name 
            FROM appointments a 
            JOIN doctors d ON a.doctor_id = d.id 
            JOIN patients p ON a.patient_id = p.id 
            JOIN users u1 ON d.user_id = u1.id 
            JOIN users u2 ON p.user_id = u2.id 
            JOIN services s ON a.service_id = s.id 
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
}

if($stmt = mysqli_prepare($conn, $sql)){
    if($role != "admin"){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    }
    mysqli_stmt_execute($stmt);
    $appointments_result = mysqli_stmt_get_result($stmt);
    $appointments = mysqli_fetch_all($appointments_result, MYSQLI_ASSOC);
}

// Output the appointments list HTML
foreach($appointments as $appointment): ?>
    <div class="col-md-6 mb-3">
        <div class="card appointment-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="card-title mb-0">
                        <?php echo $role == "patient" ? htmlspecialchars($appointment['doctor_name']) : htmlspecialchars($appointment['patient_name']); ?>
                    </h6>
                    <span class="badge bg-<?php 
                        echo $appointment['status'] == 'confirmed' ? 'success' : 
                            ($appointment['status'] == 'pending' ? 'warning' : 
                            ($appointment['status'] == 'cancelled' ? 'danger' : 'info')); 
                    ?>">
                        <?php echo ucfirst($appointment['status']); ?>
                    </span>
                </div>
                <p class="card-text mb-1">
                    <i class="fas fa-calendar me-2"></i>
                    <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?>
                </p>
                <p class="card-text mb-1">
                    <i class="fas fa-clock me-2"></i>
                    <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>
                </p>
                <p class="card-text mb-1">
                    <i class="fas fa-stethoscope me-2"></i>
                    <?php echo htmlspecialchars($appointment['service_name']); ?>
                </p>
                <p class="card-text mb-2">
                    <i class="fas fa-money-bill me-2"></i>
                    ₱<?php echo number_format($appointment['service_price'], 2); ?>
                </p>
                <?php if($appointment['notes']): ?>
                <p class="card-text mb-2">
                    <i class="fas fa-sticky-note me-2"></i>
                    <?php echo htmlspecialchars($appointment['notes']); ?>
                </p>
                <?php endif; ?>
                
                <?php if($role == "patient" && $appointment['status'] != 'cancelled'): ?>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-warning btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#rescheduleModal<?php echo $appointment['id']; ?>">
                        <i class="fas fa-calendar-alt me-1"></i> Reschedule
                    </button>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" name="update_status" class="btn btn-danger btn-sm">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <?php if($role != "patient" && $appointment['status'] == 'pending'): ?>
                <div class="d-flex gap-2">
                    <form method="post" class="d-inline">
                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" name="update_status" class="btn btn-success btn-sm">
                            <i class="fas fa-check me-1"></i> Confirm
                        </button>
                    </form>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" name="update_status" class="btn btn-danger btn-sm">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?> 