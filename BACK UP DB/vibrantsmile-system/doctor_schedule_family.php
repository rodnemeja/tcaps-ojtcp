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

// Check if a family code was provided
if(!isset($_GET['family_code']) || empty($_GET['family_code'])){
    $_SESSION['error'] = "No family specified";
    header("location: family_profiles.php");
    exit;
}

$family_code = $_GET['family_code'];

// Get family information
$sql = "SELECT * FROM family_codes WHERE code = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "s", $family_code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($row = mysqli_fetch_assoc($result)){
        $family = $row;
    } else {
        $_SESSION['error'] = "Family not found";
        header("location: family_profiles.php");
        exit;
    }
}

// Get family members
$sql = "SELECT p.*, u.first_name, u.last_name, u.email, u.phone
        FROM patients p
        JOIN users u ON p.user_id = u.id
        WHERE p.family_code = ?
        ORDER BY u.first_name, u.last_name";

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

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["schedule_appointments"])){
    // Loop through each family member and check if they have an appointment
    $scheduled_count = 0;
    $error_count = 0;
    
    foreach($family_members as $member){
        $member_id = $member['id'];
        $checkbox_name = "schedule_" . $member_id;
        
        // If this family member is selected
        if(isset($_POST[$checkbox_name]) && $_POST[$checkbox_name] == "1"){
            $date_key = "date_" . $member_id;
            $time_key = "time_" . $member_id;
            $service_key = "service_" . $member_id;
            $notes_key = "notes_" . $member_id;
            
            $appointment_date = $_POST[$date_key];
            $appointment_time = $_POST[$time_key];
            $service_id = $_POST[$service_key];
            $notes = isset($_POST[$notes_key]) ? $_POST[$notes_key] : "";
            
            // Validate inputs
            if(empty($appointment_date) || empty($appointment_time) || empty($service_id)){
                $error_count++;
                continue;
            }
            
            // Validate date and time
            $appointment_datetime = strtotime($appointment_date . ' ' . $appointment_time);
            $current_datetime = strtotime('now');
            $day_of_week = date('N', $appointment_datetime); // 1 (Monday) to 7 (Sunday)
            $appointment_hour = date('H', $appointment_datetime);
            
            if($day_of_week == 6 || $appointment_datetime <= $current_datetime || 
               $appointment_hour < 9 || $appointment_hour >= 17){
                $error_count++;
                continue;
            }
            
            // Get service duration
            $service_duration = 0;
            foreach($services as $service){
                if($service['id'] == $service_id){
                    $service_duration = $service['duration'];
                    break;
                }
            }
            
            // Calculate end time
            $end_time = date('H:i:s', strtotime($appointment_time . ' +' . $service_duration . ' minutes'));
            
            // Check for double booking
            $check_sql = "SELECT COUNT(*) as count FROM appointments 
                         WHERE doctor_id = ? 
                         AND appointment_date = ? 
                         AND status != 'cancelled'
                         AND (
                             appointment_time = ? OR
                             (appointment_time <= ? AND end_time > ?)
                         )";
            
            if($stmt = mysqli_prepare($conn, $check_sql)){
                mysqli_stmt_bind_param($stmt, "issss", 
                    $doctor_id, 
                    $appointment_date, 
                    $appointment_time,
                    $appointment_time,
                    $appointment_time
                );
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                
                if($row['count'] > 0){
                    $error_count++;
                    continue;
                }
                
                // Insert the appointment
                $sql = "INSERT INTO appointments (patient_id, doctor_id, service_id, appointment_date, appointment_time, end_time, status, notes) 
                        VALUES (?, ?, ?, ?, ?, ?, 'approved', ?)";
                
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "iiissss", 
                        $member_id, 
                        $doctor_id, 
                        $service_id,
                        $appointment_date, 
                        $appointment_time,
                        $end_time,
                        $notes
                    );
                    
                    if(mysqli_stmt_execute($stmt)){
                        $scheduled_count++;
                    } else {
                        $error_count++;
                    }
                }
            }
        }
    }
    
    // Set session messages
    if($scheduled_count > 0){
        $_SESSION['success'] = "Successfully scheduled appointments for " . $scheduled_count . " family member(s).";
    }
    
    if($error_count > 0){
        $_SESSION['error'] = "Failed to schedule appointments for " . $error_count . " family member(s).";
    }
    
    // Redirect back to the family profile page
    header("location: doctor_patient_family.php?patient_id=" . $family_members[0]['id']);
    exit;
}

// Page title
$page_title = "Schedule Family Appointments";
$current_page = "family_profiles";
include "includes/header.php";
?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="family_profiles.php">Family Profiles</a></li>
            <li class="breadcrumb-item active" aria-current="page">Schedule Family Appointments</li>
        </ol>
    </nav>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Schedule Appointments for <?php echo htmlspecialchars($family['name']); ?> Family</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> You can schedule appointments for multiple family members at once. Select which family members you want to schedule and provide the appointment details for each.
                    </div>
                    
                    <form method="post" id="familyScheduleForm">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAll">
                                            </div>
                                        </th>
                                        <th>Family Member</th>
                                        <th>Service</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($family_members as $member): ?>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input member-checkbox" type="checkbox" name="schedule_<?php echo $member['id']; ?>" value="1" id="check_<?php echo $member['id']; ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <label for="check_<?php echo $member['id']; ?>" class="mb-0">
                                                <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                            </label>
                                            <div class="small text-muted">
                                                Age: <?php echo calculateAge($member['date_of_birth']); ?> | 
                                                <?php echo ucfirst($member['gender']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <select class="form-select" name="service_<?php echo $member['id']; ?>" disabled>
                                                <option value="">Select service</option>
                                                <?php foreach($services as $service): ?>
                                                <option value="<?php echo $service['id']; ?>">
                                                    <?php echo htmlspecialchars($service['name']); ?> 
                                                    (<?php echo $service['duration']; ?> min - ₱<?php echo number_format($service['price'], 2); ?>)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="date" class="form-control" name="date_<?php echo $member['id']; ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" disabled>
                                        </td>
                                        <td>
                                            <select class="form-select" name="time_<?php echo $member['id']; ?>" disabled>
                                                <option value="">Select time</option>
                                                <?php
                                                $start = strtotime('09:00');
                                                $end = strtotime('17:00');
                                                $interval = 30 * 60; // 30 minutes
                                                
                                                for($time = $start; $time < $end; $time += $interval){
                                                    echo '<option value="' . date('H:i:s', $time) . '">' . 
                                                         date('g:i A', $time) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="notes_<?php echo $member['id']; ?>" placeholder="Optional notes" disabled>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" name="schedule_appointments" class="btn btn-primary">
                                <i class="fas fa-calendar-plus me-2"></i> Schedule Selected Appointments
                            </button>
                            <a href="doctor_patient_family.php?patient_id=<?php echo $family_members[0]['id']; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Family Profile
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkbox functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const memberCheckboxes = document.querySelectorAll('.member-checkbox');
    
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        
        memberCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
            toggleFormFields(checkbox);
        });
    });
    
    // Enable/disable form fields based on checkbox state
    memberCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            toggleFormFields(this);
            
            // Update "Select All" checkbox
            const allChecked = [...memberCheckboxes].every(cb => cb.checked);
            const someChecked = [...memberCheckboxes].some(cb => cb.checked);
            
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = someChecked && !allChecked;
        });
    });
    
    function toggleFormFields(checkbox) {
        const memberId = checkbox.id.split('_')[1];
        const row = checkbox.closest('tr');
        const formFields = row.querySelectorAll('select, input[type="date"], input[type="text"]');
        
        formFields.forEach(field => {
            field.disabled = !checkbox.checked;
            if(checkbox.checked && field.hasAttribute('required')) {
                field.setAttribute('required', 'required');
            } else {
                field.removeAttribute('required');
            }
        });
    }
    
    // Form validation before submit
    document.getElementById('familyScheduleForm').addEventListener('submit', function(e) {
        let isValid = false;
        
        // Check if at least one member is selected
        memberCheckboxes.forEach(checkbox => {
            if(checkbox.checked) {
                isValid = true;
            }
        });
        
        if(!isValid) {
            e.preventDefault();
            alert('Please select at least one family member to schedule an appointment.');
            return;
        }
        
        // Validate selected members have all required fields
        let allFieldsValid = true;
        
        memberCheckboxes.forEach(checkbox => {
            if(checkbox.checked) {
                const memberId = checkbox.id.split('_')[1];
                const serviceField = document.querySelector(`select[name="service_${memberId}"]`);
                const dateField = document.querySelector(`input[name="date_${memberId}"]`);
                const timeField = document.querySelector(`select[name="time_${memberId}"]`);
                
                if(!serviceField.value || !dateField.value || !timeField.value) {
                    allFieldsValid = false;
                }
            }
        });
        
        if(!allFieldsValid) {
            e.preventDefault();
            alert('Please fill in all required fields (service, date, time) for selected family members.');
        }
    });
});
</script>

<?php include "includes/footer.php"; ?> 