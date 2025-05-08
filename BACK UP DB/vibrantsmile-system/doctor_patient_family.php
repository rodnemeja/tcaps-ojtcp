<?php
session_start();
require_once "config/database.php";
require_once "includes/functions.php";

// Check if user is logged in and is a doctor
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "doctor"){
    header("location: index.php");
    exit;
}

// Get doctor information
$user_id = $_SESSION["id"];
$doctor_id = 0;

$sql = "SELECT id FROM doctors WHERE user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($row = mysqli_fetch_assoc($result)){
        $doctor_id = $row['id'];
    }
}

// Check if a patient ID was provided
if(!isset($_GET['patient_id']) || !is_numeric($_GET['patient_id'])){
    $_SESSION['error'] = "No patient specified";
    header("location: appointments.php");
    exit;
}

$patient_id = $_GET['patient_id'];

// Get patient information
$sql = "SELECT p.*, u.first_name, u.middle_name, u.last_name, u.email, u.phone
        FROM patients p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($row = mysqli_fetch_assoc($result)){
        $patient = $row;
    } else {
        $_SESSION['error'] = "Patient not found";
        header("location: appointments.php");
        exit;
    }
}

// Get family information if the patient is in a family
$family_info = null;
$family_members = [];

if($patient['family_code']) {
    $family_info = getFamilyCodeInfo($patient['family_code'], $conn);
    $family_members = getFamilyMembersByCode($patient['family_code'], $conn, $patient_id);
}

// Get appointment history for all family members
$family_appointments = [];

if($patient['family_code']) {
    $sql = "SELECT a.*, s.name as service_name, u.first_name, u.last_name,
            du.first_name as doctor_first_name, du.last_name as doctor_last_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN users u ON p.user_id = u.id
            JOIN services s ON a.service_id = s.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN users du ON d.user_id = du.id
            WHERE p.family_code = ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
            LIMIT 20";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $patient['family_code']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $family_appointments[] = $row;
        }
    }
}

// Get medical histories for all family members
$family_medical_histories = [];

if($patient['family_code']) {
    $sql = "SELECT m.*, u.first_name, u.last_name
            FROM medical_history m
            JOIN patients p ON m.patient_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE p.family_code = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $patient['family_code']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $family_medical_histories[] = $row;
        }
    }
}

// Page title
$page_title = "Patient Family Information";
$current_page = "appointments";
include "includes/header.php";
?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="appointments.php">Appointments</a></li>
            <li class="breadcrumb-item active" aria-current="page">Patient Family Information</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-4">
            <!-- Patient Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Patient Information</h5>
                </div>
                <div class="card-body">
                    <h3><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h3>
                    <p><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($patient['email']); ?></p>
                    <p><i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($patient['phone']); ?></p>
                    <p><i class="fas fa-birthday-cake me-2"></i> <?php echo date('F j, Y', strtotime($patient['date_of_birth'])); ?> (Age: <?php echo calculateAge($patient['date_of_birth']); ?>)</p>
                    <p><i class="fas fa-venus-mars me-2"></i> <?php echo ucfirst($patient['gender']); ?></p>
                    <p><i class="fas fa-map-marker-alt me-2"></i> <?php echo htmlspecialchars($patient['address']); ?></p>
                    
                    <div class="mt-3">
                        <a href="appointments.php" class="btn btn-outline-primary">
                            <i class="fas fa-calendar-alt me-2"></i> View Appointments
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <?php if(!$patient['family_code']): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> This patient is not part of any family group.
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Family Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Family Information: <?php echo htmlspecialchars($family_info['name']); ?></h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-users me-2"></i> Family Code: <?php echo htmlspecialchars($patient['family_code']); ?>
                    </div>
                    
                    <div class="mb-3">
                        <a href="doctor_schedule_family.php?family_code=<?php echo htmlspecialchars($patient['family_code']); ?>" class="btn btn-success">
                            <i class="fas fa-calendar-plus me-2"></i> Schedule Family Appointments
                        </a>
                    </div>
                    
                    <h6>Family Members</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Gender</th>
                                    <th>Age</th>
                                    <th>Contact</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Current patient -->
                                <tr class="table-primary">
                                    <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                                    <td><?php echo ucfirst($patient['gender']); ?></td>
                                    <td><?php echo calculateAge($patient['date_of_birth']); ?></td>
                                    <td>
                                        <small class="d-block"><?php echo htmlspecialchars($patient['email']); ?></small>
                                        <small class="d-block"><?php echo htmlspecialchars($patient['phone']); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                        echo !empty($patient['family_role']) 
                                            ? htmlspecialchars($patient['family_role']) 
                                            : '<span class="text-muted">Not specified</span>'; 
                                        ?>
                                    </td>
                                    <td>
                                        <a href="patient_medical_history.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-notes-medical"></i> Medical History
                                        </a>
                                    </td>
                                </tr>
                                
                                <!-- Other family members -->
                                <?php foreach($family_members as $member): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($member['gender'])); ?></td>
                                    <td><?php echo calculateAge($member['date_of_birth']); ?></td>
                                    <td>
                                        <small class="d-block"><?php echo htmlspecialchars($member['email']); ?></small>
                                        <small class="d-block"><?php echo htmlspecialchars($member['phone']); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                        echo !empty($member['family_role']) 
                                            ? htmlspecialchars($member['family_role']) 
                                            : '<span class="text-muted">Not specified</span>'; 
                                        ?>
                                    </td>
                                    <td>
                                        <a href="patient_medical_history.php?patient_id=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-notes-medical"></i> Medical History
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Family Medical History -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Family Medical History</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($family_medical_histories)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i> No medical history records found for this family.
                    </div>
                    <?php else: ?>
                    <div class="accordion" id="accordionMedicalHistory">
                        <?php foreach($family_medical_histories as $index => $history): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                    <?php echo htmlspecialchars($history['first_name'] . ' ' . $history['last_name']); ?>'s Medical History
                                </button>
                            </h2>
                            <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#accordionMedicalHistory">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Allergies</h6>
                                            <?php if($history['has_allergies']): ?>
                                            <p><?php echo nl2br(htmlspecialchars($history['allergies_details'])); ?></p>
                                            <?php else: ?>
                                            <p class="text-muted">No allergies reported</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Medications</h6>
                                            <?php if($history['has_medications']): ?>
                                            <p><?php echo nl2br(htmlspecialchars($history['medications_details'])); ?></p>
                                            <?php else: ?>
                                            <p class="text-muted">No medications reported</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <h6>Medical Conditions</h6>
                                    <?php if($history['medical_conditions']): ?>
                                    <p><?php echo nl2br(htmlspecialchars($history['medical_conditions'])); ?></p>
                                    <?php else: ?>
                                    <p class="text-muted">No medical conditions reported</p>
                                    <?php endif; ?>
                                    
                                    <?php if($history['other_conditions_details']): ?>
                                    <h6>Other Conditions</h6>
                                    <p><?php echo nl2br(htmlspecialchars($history['other_conditions_details'])); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if($history['additional_notes']): ?>
                                    <h6>Additional Notes</h6>
                                    <?php 
                                    $notes = json_decode($history['additional_notes'], true);
                                    if($notes): 
                                    ?>
                                    <ul>
                                        <?php foreach($notes as $key => $value): ?>
                                        <?php if($value): ?>
                                        <li><strong><?php echo str_replace('_', ' ', ucfirst($key)); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php else: ?>
                                    <p><?php echo nl2br(htmlspecialchars($history['additional_notes'])); ?></p>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Family Appointment History -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Family Appointment History</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($family_appointments)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i> No appointment history found for this family.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Service</th>
                                    <th>Doctor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($family_appointments as $appointment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo ($appointment['status'] == 'pending' ? 'warning' : 
                                            ($appointment['status'] == 'scheduled' ? 'info' : 
                                            ($appointment['status'] == 'approved' ? 'primary' : 
                                            ($appointment['status'] == 'completed' ? 'success' : 'danger')))); 
                                        ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
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

<!-- Schedule Appointment Modal -->
<div class="modal fade" id="scheduleAppointmentModal" tabindex="-1" aria-labelledby="scheduleAppointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleAppointmentModalLabel">Schedule Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="doctorScheduleForm" method="post" action="appointments.php">
                    <input type="hidden" name="schedule_for_patient" id="patientIdInput" value="">
                    <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Select Service</label>
                        <select class="form-select" name="service_id" required>
                            <option value="">Choose a service...</option>
                            <?php 
                            // Get services
                            $sql = "SELECT * FROM services ORDER BY name";
                            $services_result = mysqli_query($conn, $sql);
                            while($service = mysqli_fetch_assoc($services_result)):
                            ?>
                            <option value="<?php echo $service['id']; ?>">
                                <?php echo htmlspecialchars($service['name']); ?> 
                                (<?php echo $service['duration']; ?> min - ₱<?php echo number_format($service['price'], 2); ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Appointment Date</label>
                        <input type="date" class="form-control" name="appointment_date" required 
                               min="<?php echo date('Y-m-d'); ?>">
                        <div class="form-text">Appointments are not available on Saturdays.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Appointment Time</label>
                        <select class="form-select" name="appointment_time" required>
                            <option value="">Select time...</option>
                            <?php
                            $start = strtotime('09:00');
                            $end = strtotime('17:00');
                            $interval = 30 * 60; // 30 minutes
                            
                            for ($time = $start; $time < $end; $time += $interval) {
                                echo '<option value="' . date('H:i:s', $time) . '">' . 
                                     date('g:i A', $time) . '</option>';
                            }
                            ?>
                        </select>
                        <div class="form-text">Available hours: 9:00 AM to 5:00 PM</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="doctorScheduleForm" class="btn btn-primary">Schedule Appointment</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle schedule appointment buttons
    const scheduleButtons = document.querySelectorAll('.schedule-appointment');
    const patientIdInput = document.getElementById('patientIdInput');
    const modal = new bootstrap.Modal(document.getElementById('scheduleAppointmentModal'));
    
    scheduleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const patientId = this.getAttribute('data-patient-id');
            patientIdInput.value = patientId;
            modal.show();
        });
    });
});
</script>

<?php include "includes/footer.php"; ?> 