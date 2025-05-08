<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Check if a family code was provided
if(!isset($_GET['code']) || empty($_GET['code'])){
    $_SESSION['error_message'] = "No family specified";
    header("location: family_profiles.php");
    exit;
}

$family_code = $_GET['code'];
$error = '';
$success = '';

// Get family information
$sql = "SELECT * FROM family_codes WHERE code = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "s", $family_code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($row = mysqli_fetch_assoc($result)){
        $family = $row;
    } else {
        $_SESSION['error_message'] = "Family not found";
        header("location: family_profiles.php");
        exit;
    }
}

// Get family members
$sql = "SELECT p.id, p.user_id, p.date_of_birth, p.gender, p.family_role,
        u.first_name, u.middle_name, u.last_name, u.email, u.phone
        FROM patients p
        JOIN users u ON p.user_id = u.id
        WHERE p.family_code = ?
        ORDER BY u.last_name, u.first_name";

$family_members = [];
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "s", $family_code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
        $family_members[] = $row;
    }
}

// Get services
$services_sql = "SELECT * FROM services ORDER BY name";
$services_result = mysqli_query($conn, $services_sql);
$services = mysqli_fetch_all($services_result, MYSQLI_ASSOC);

// Get doctors
$doctors_sql = "SELECT d.id, d.specialization, d.bio, u.first_name, u.last_name
               FROM doctors d
               JOIN users u ON d.user_id = u.id
               WHERE d.status = 'active'
               ORDER BY u.first_name, u.last_name";
$doctors_result = mysqli_query($conn, $doctors_sql);
$doctors = mysqli_fetch_all($doctors_result, MYSQLI_ASSOC);

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['schedule_appointments'])){
    // Validate input
    $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    $appointment_date = isset($_POST['appointment_date']) ? trim($_POST['appointment_date']) : '';
    $appointment_time = isset($_POST['appointment_time']) ? trim($_POST['appointment_time']) : '';
    $patient_ids = isset($_POST['patient_ids']) ? $_POST['patient_ids'] : [];
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    $input_errors = [];
    
    if(empty($doctor_id)) {
        $input_errors[] = "Please select a doctor";
    }
    
    if(empty($service_id)) {
        $input_errors[] = "Please select a service";
    }
    
    if(empty($appointment_date)) {
        $input_errors[] = "Please select an appointment date";
    }
    
    if(empty($appointment_time)) {
        $input_errors[] = "Please select an appointment time";
    }
    
    if(empty($patient_ids)) {
        $input_errors[] = "Please select at least one family member";
    }
    
    if(empty($input_errors)){
        // Create appointment datetime
        $appointment_datetime = date('Y-m-d H:i:s', strtotime($appointment_date . ' ' . $appointment_time));
        
        // Get service duration for sequential scheduling
        $service_duration = 30; // Default 30 minutes
        foreach($services as $service){
            if($service['id'] == $service_id){
                $service_duration = $service['duration'];
                break;
            }
        }
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            $success_count = 0;
            $current_time = $appointment_datetime;
            
            foreach($patient_ids as $patient_id){
                $patient_id = intval($patient_id);
                
                // Create appointment
                $insert_sql = "INSERT INTO appointments (patient_id, doctor_id, service_id, appointment_datetime, 
                               status, notes, created_at) 
                               VALUES (?, ?, ?, ?, 'pending', ?, NOW())";
                               
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, "iiiss", $patient_id, $doctor_id, $service_id, $current_time, $notes);
                
                if(mysqli_stmt_execute($insert_stmt)){
                    $success_count++;
                    
                    // Add service duration minutes to current time for next appointment
                    $current_time = date('Y-m-d H:i:s', strtotime($current_time . ' + ' . $service_duration . ' minutes'));
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            if($success_count > 0){
                $_SESSION['success_message'] = "Successfully scheduled " . $success_count . " appointment" . 
                                              ($success_count > 1 ? "s" : "") . " for the family";
                header("location: view_family.php?code=" . $family_code);
                exit;
            } else {
                $error = "No appointments were scheduled";
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    } else {
        $error = implode('<br>', $input_errors);
    }
}

$page_title = "Schedule Family Appointments";
$current_page = "family_profiles";
require_once "includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Schedule Appointments for <?php echo htmlspecialchars($family['name']); ?> Family</h2>
    <a href="view_family.php?code=<?php echo $family_code; ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Family
    </a>
</div>

<?php if(!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if(empty($family_members)): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i> No family members found for this family.
</div>
<?php else: ?>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Schedule Group Appointments</h6>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?code=" . $family_code); ?>" id="scheduleForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="doctor_id" class="form-label">Doctor</label>
                            <select class="form-select" name="doctor_id" id="doctor_id" required>
                                <option value="">-- Select Doctor --</option>
                                <?php foreach($doctors as $doctor): ?>
                                <option value="<?php echo $doctor['id']; ?>">
                                    Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?> 
                                    (<?php echo htmlspecialchars($doctor['specialization']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="service_id" class="form-label">Service</label>
                            <select class="form-select" name="service_id" id="service_id" required>
                                <option value="">-- Select Service --</option>
                                <?php foreach($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>" data-duration="<?php echo $service['duration']; ?>">
                                    <?php echo htmlspecialchars($service['name']); ?> 
                                    (<?php echo $service['duration']; ?> min - $<?php echo number_format($service['price'], 2); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="appointment_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="appointment_date" name="appointment_date" required
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="appointment_time" class="form-label">Starting Time</label>
                            <input type="time" class="form-control" id="appointment_time" name="appointment_time" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Select Family Members</label>
                        <div id="scheduleInfo" class="alert alert-info mb-3" style="display: none;">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="scheduleDurationInfo"></span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th width="50px">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAll">
                                            </div>
                                        </th>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Role</th>
                                        <th>Appointment Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($family_members as $index => $member): ?>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input patient-select" type="checkbox" 
                                                       name="patient_ids[]" value="<?php echo $member['id']; ?>"
                                                       data-index="<?php echo $index; ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($member['first_name'] . ' ' . 
                                                ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . 
                                                $member['last_name']); 
                                            ?>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($member['email']); ?></div>
                                            <div><?php echo htmlspecialchars($member['phone']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($member['family_role'] ?: 'Not specified'); ?></td>
                                        <td>
                                            <span class="appointment-time" id="time-<?php echo $index; ?>">-</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="view_family.php?code=<?php echo $family_code; ?>" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" name="schedule_appointments" class="btn btn-primary" id="scheduleButton" disabled>
                            <i class="fas fa-calendar-plus me-2"></i>Schedule Appointments
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Schedule Information</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> When scheduling for multiple family members, appointments will be scheduled sequentially based on the service duration.
                </div>
                
                <div class="mb-3">
                    <h6>Family: <?php echo htmlspecialchars($family['name']); ?></h6>
                    <p class="text-muted">Code: <?php echo htmlspecialchars($family['code']); ?></p>
                </div>
                
                <div class="mb-3">
                    <h6>Members: <?php echo count($family_members); ?></h6>
                    <div class="small">
                        <?php 
                        $member_names = array_map(function($member) {
                            return $member['first_name'] . ' ' . $member['last_name'];
                        }, $family_members);
                        echo htmlspecialchars(implode(', ', $member_names)); 
                        ?>
                    </div>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i> Make sure to check doctor availability before scheduling.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const patientCheckboxes = document.querySelectorAll('.patient-select');
    const scheduleButton = document.getElementById('scheduleButton');
    
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        patientCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        updateScheduleButton();
        updateAppointmentTimes();
    });
    
    patientCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateScheduleButton();
            updateSelectAllCheckbox();
            updateAppointmentTimes();
        });
    });
    
    function updateScheduleButton() {
        const anyChecked = Array.from(patientCheckboxes).some(checkbox => checkbox.checked);
        scheduleButton.disabled = !anyChecked;
    }
    
    function updateSelectAllCheckbox() {
        const allChecked = Array.from(patientCheckboxes).every(checkbox => checkbox.checked);
        const someChecked = Array.from(patientCheckboxes).some(checkbox => checkbox.checked);
        
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = !allChecked && someChecked;
    }
    
    // Appointment time calculations
    const appointmentDateInput = document.getElementById('appointment_date');
    const appointmentTimeInput = document.getElementById('appointment_time');
    const serviceSelect = document.getElementById('service_id');
    const scheduleInfo = document.getElementById('scheduleInfo');
    const scheduleDurationInfo = document.getElementById('scheduleDurationInfo');
    
    appointmentDateInput.addEventListener('change', updateAppointmentTimes);
    appointmentTimeInput.addEventListener('change', updateAppointmentTimes);
    serviceSelect.addEventListener('change', updateAppointmentTimes);
    
    function updateAppointmentTimes() {
        const appointmentDate = appointmentDateInput.value;
        const appointmentTime = appointmentTimeInput.value;
        const serviceOption = serviceSelect.options[serviceSelect.selectedIndex];
        
        // Reset all appointment times
        document.querySelectorAll('.appointment-time').forEach(span => {
            span.textContent = '-';
        });
        
        if(!appointmentDate || !appointmentTime || !serviceOption || serviceOption.value === '') {
            scheduleInfo.style.display = 'none';
            return;
        }
        
        const serviceDuration = parseInt(serviceOption.dataset.duration || 30);
        const checkedPatients = document.querySelectorAll('.patient-select:checked');
        
        if(checkedPatients.length === 0) {
            scheduleInfo.style.display = 'none';
            return;
        }
        
        // Calculate total duration
        const totalDuration = serviceDuration * checkedPatients.length;
        const startTime = new Date(`${appointmentDate}T${appointmentTime}`);
        let currentTime = new Date(startTime);
        
        // Update schedule info
        const endTime = new Date(startTime.getTime() + totalDuration * 60000);
        scheduleDurationInfo.textContent = `Total duration: ${totalDuration} minutes (${formatTime(startTime)} - ${formatTime(endTime)})`;
        scheduleInfo.style.display = 'block';
        
        // Update individual appointment times
        checkedPatients.forEach(checkbox => {
            const index = checkbox.dataset.index;
            const timeSpan = document.getElementById(`time-${index}`);
            
            if(timeSpan) {
                timeSpan.textContent = formatTime(currentTime);
                // Add service duration for next appointment
                currentTime = new Date(currentTime.getTime() + serviceDuration * 60000);
            }
        });
    }
    
    function formatTime(date) {
        return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
    
    // Form validation
    document.getElementById('scheduleForm').addEventListener('submit', function(event) {
        const checkedBoxes = document.querySelectorAll('.patient-select:checked');
        
        if(checkedBoxes.length === 0) {
            event.preventDefault();
            alert('Please select at least one family member for the appointment');
            return;
        }
        
        const appointmentDate = appointmentDateInput.value;
        const appointmentTime = appointmentTimeInput.value;
        const doctorId = document.getElementById('doctor_id').value;
        const serviceId = document.getElementById('service_id').value;
        
        if(!appointmentDate || !appointmentTime || !doctorId || !serviceId) {
            event.preventDefault();
            alert('Please fill in all required appointment details');
            return;
        }
    });
});
</script>
<?php endif; ?>

<?php require_once "includes/footer.php"; ?> 