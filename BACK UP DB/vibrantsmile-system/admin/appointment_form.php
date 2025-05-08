<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

$appointment = array();
$edit_mode = false;
$error = '';

// Get appointment data if in edit mode
if(isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $sql = "SELECT a.*, p.user_id as patient_user_id 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.id 
            WHERE a.id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)) {
            $appointment = $row;
            
            // Get selected services for this appointment
            $sql = "SELECT service_id FROM appointment_services WHERE appointment_id = ?";
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $appointment['services'] = array();
                while($row = mysqli_fetch_assoc($result)) {
                    $appointment['services'][] = $row['service_id'];
                }
            }
        }
    }
}

// Check if this is a walk-in patient appointment
$walk_in_mode = false;
$preselected_service = null;
$is_minor = false;
$requires_consent = false;

if(isset($_SESSION['walk_in_patient_id'])) {
    $walk_in_mode = true;
    
    // Pre-fill patient information
    if(isset($_GET['patient_id']) && is_numeric($_GET['patient_id'])) {
        $appointment['patient_id'] = $_GET['patient_id'];
    } else {
        $appointment['patient_id'] = $_SESSION['walk_in_patient_id'];
    }
    
    // Set default status to "approved" for walk-ins
    $appointment['status'] = 'approved';
    
    // Check for pre-selected service
    if(isset($_SESSION['walk_in_service']) && !empty($_SESSION['walk_in_service'])) {
        $service_mapping = [
            'check_up' => 'Regular Check-up',
            'cleaning' => 'Teeth Cleaning',
            'extraction' => 'Tooth Extraction',
            'filling' => 'Dental Filling',
            'root_canal' => 'Root Canal Treatment',
            'other' => ''
        ];
        
        $service_name = $service_mapping[$_SESSION['walk_in_service']] ?? '';
        if(!empty($service_name)) {
            $preselected_service = $service_name;
        }
    }
    
    // Check age-related warnings
    if(isset($_SESSION['walk_in_patient_age'])) {
        $patient_age = $_SESSION['walk_in_patient_age'];
        
        if($patient_age < 18) {
            $is_minor = true;
            $requires_consent = true;
        }
        
        if(isset($_SESSION['walk_in_service'])) {
            $service = $_SESSION['walk_in_service'];
            
            if(($service === 'extraction' && $patient_age < 7) || 
               ($service === 'root_canal' && $patient_age < 7)) {
                $requires_specialist = true;
            }
        }
    }
}

// Clear walk-in session variables after using them
if($walk_in_mode) {
    unset($_SESSION['walk_in_patient_id']);
    unset($_SESSION['walk_in_patient_name']);
    unset($_SESSION['walk_in_patient_age']);
    unset($_SESSION['walk_in_service']);
}

// Get all patients
$sql = "SELECT p.id, u.first_name, u.middle_name, u.last_name, u.phone, 
        p.family_code, 
        (SELECT name FROM family_codes WHERE code = p.family_code) as family_name
        FROM patients p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY family_name, u.last_name, u.first_name";
$patients = mysqli_query($conn, $sql);
if (!$patients) {
    die("Error fetching patients: " . mysqli_error($conn));
}

// Get all doctors
$sql = "SELECT d.id, u.first_name, u.middle_name, u.last_name, d.specialization 
        FROM doctors d 
        JOIN users u ON d.user_id = u.id 
        ORDER BY u.last_name, u.first_name";
$doctors = mysqli_query($conn, $sql);
if (!$doctors) {
    die("Error fetching doctors: " . mysqli_error($conn));
}

// Get all services
$sql = "SELECT * FROM services ORDER BY name";
$services = mysqli_query($conn, $sql);
if (!$services) {
    die("Error fetching services: " . mysqli_error($conn));
}

// Function to check for appointment conflicts
function checkAppointmentConflict($conn, $doctor_id, $date, $time, $end_time, $appointment_id = null) {
    $sql = "SELECT COUNT(*) as count FROM appointments 
            WHERE doctor_id = ? 
            AND appointment_date = ? 
            AND status != 'cancelled' 
            AND (
                (appointment_time <= ? AND end_time > ?) OR
                (appointment_time < ? AND end_time >= ?) OR
                (appointment_time >= ? AND appointment_time < ?)
            )";
    
    $params = [$doctor_id, $date, $time, $time, $end_time, $end_time, $time, $end_time];
    $types = "isssssss";
    
    // If editing an existing appointment, exclude the current appointment
    if($appointment_id) {
        $sql .= " AND id != ?";
        $params[] = $appointment_id;
        $types .= "i";
    }
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0;
    }
    
    return false; // If query fails, assume no conflict
}

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_id = $_POST['patient_id'];
    $doctor_id = !empty($_POST['doctor_id']) ? $_POST['doctor_id'] : null;
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];
    $service_id = $_POST['service_id'];

    // Validate required fields
    if(empty($patient_id) || empty($appointment_date) || empty($appointment_time) || empty($status) || empty($service_id)) {
        $error = "Please fill in all required fields.";
    } else {
        // Validate appointment date and time
        $appointment_datetime = strtotime($appointment_date . ' ' . $appointment_time);
        $current_datetime = strtotime('now');
        $day_of_week = date('N', $appointment_datetime); // 1 (Monday) to 7 (Sunday)
        
        if($appointment_datetime < $current_datetime) {
            $error = "You cannot schedule appointments in the past.";
        } else if($day_of_week == 6) { // 6 is Saturday
            $error = "Appointments are not available on Saturdays.";
        } else {
            // Get service duration for end time calculation
            $duration_sql = "SELECT duration FROM services WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $duration_sql)) {
                mysqli_stmt_bind_param($stmt, "i", $service_id);
                mysqli_stmt_execute($stmt);
                $duration_result = mysqli_stmt_get_result($stmt);
                if($duration_row = mysqli_fetch_assoc($duration_result)) {
                    // Calculate end time
                    $duration = intval($duration_row['duration']); // Convert to integer minutes
                    $end_time = date('H:i:s', strtotime($appointment_time . ' +' . $duration . ' minutes'));
                    
                    // Check for appointment conflicts
                    if (!empty($doctor_id) && checkAppointmentConflict($conn, $doctor_id, $appointment_date, $appointment_time, $end_time, $edit_mode ? $_GET['edit'] : null)) {
                        $error = "This time slot conflicts with an existing appointment for the selected doctor. Please choose a different time.";
                    } else if($edit_mode) {
            // Update appointment
            $sql = "UPDATE appointments SET 
                    patient_id = ?, 
                    doctor_id = ?, 
                    appointment_date = ?, 
                    appointment_time = ?, 
                    status = ?, 
                                notes = ?,
                                service_id = ?,
                                end_time = ?
                    WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)) {
                            mysqli_stmt_bind_param($stmt, "iissssisi", $patient_id, $doctor_id, $appointment_date, $appointment_time, $status, $notes, $service_id, $end_time, $_GET['edit']);
                if(mysqli_stmt_execute($stmt)) {
                    $appointment_id = $_GET['edit'];
                } else {
                    $error = "Error updating appointment: " . mysqli_error($conn);
                }
            }
        } else {
            // Insert new appointment
                        $sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status, notes, service_id, end_time) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            if($stmt = mysqli_prepare($conn, $sql)) {
                            mysqli_stmt_bind_param($stmt, "iissssss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $status, $notes, $service_id, $end_time);
                if(mysqli_stmt_execute($stmt)) {
                    $appointment_id = mysqli_insert_id($conn);
                } else {
                    $error = "Error creating appointment: " . mysqli_error($conn);
                }
            }
        }

                    // If no errors occurred, redirect to appointments page
                    if(empty($error)) {
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: '" . ($edit_mode ? 'Appointment updated successfully!' : 'Appointment created successfully!') . "',
                                    confirmButtonColor: '#4e73df'
                                }).then((result) => {
                                    window.location.href = 'appointments.php';
                                });
                            });
                        </script>";
                        // Don't exit immediately to allow the script to render
                    }
                } else {
                    $error = "Error: Could not retrieve service duration.";
                }
            } else {
                $error = "Error preparing service duration query: " . mysqli_error($conn);
            }
        }
    }
}

// Set the current page for the header
$current_page = 'appointments';
$page_title = $edit_mode ? 'Edit Appointment' : 'New Appointment';

// Include header
include 'includes/header.php';
?>

<!-- Begin Page Content -->
    <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo $edit_mode ? 'Edit' : 'New'; ?> Appointment</h2>
                    <a href="appointments.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Appointments
                    </a>
                </div>

                <?php if(!empty($error)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?php echo $error; ?>',
                confirmButtonColor: '#4e73df'
            });
        });
    </script>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo $edit_mode ? 'Edit Appointment' : 'New Appointment'; ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if($walk_in_mode): ?>
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Walk-in Patient:</strong> This appointment is being created for a walk-in patient.
                                <?php if($preselected_service): ?>
                                    <span class="ms-2">Requested service: <strong><?php echo htmlspecialchars($preselected_service); ?></strong></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if($requires_consent): ?>
                                <div class="alert alert-warning mb-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Consent Required:</strong> Patient is a minor (under 18). Parental/guardian consent forms should be completed before procedure.
                                </div>
                            <?php endif; ?>
                            
                            <?php if($requires_specialist): ?>
                                <div class="alert alert-danger mb-3">
                                    <i class="fas fa-user-md me-2"></i>
                                    <strong>Specialist Recommended:</strong> Based on patient age and planned procedure, consultation with a pediatric dental specialist is recommended.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="alert alert-info mb-4">
                            <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Appointment Guidelines</h5>
                            <ul class="mb-0">
                                <li>Appointments are not available on Saturdays</li>
                                <li>Business hours are from 9:00 AM to 5:00 PM</li>
                                <li>A service must be selected for each appointment</li>
                                <li>The selected service will determine the appointment duration</li>
                            </ul>
                        </div>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . ($edit_mode ? "?edit=" . $_GET['edit'] : ""); ?>" method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Patient <span class="text-danger">*</span></label>
                                    <div class="input-group mb-2">
                                    </div>
                                    <select class="form-select" name="patient_id" id="patientSelect" required>
                                        <option value="">Select Patient</option>
                                        <?php 
                                        $current_family = '';
                                        while($patient = mysqli_fetch_assoc($patients)): 
                                            $patient_name = $patient['last_name'] . ', ' . $patient['first_name'];
                                            if (!empty($patient['middle_name'])) {
                                                $patient_name .= ' ' . $patient['middle_name'];
                                            }
                                            
                                            // Add family group headers
                                            if (!empty($patient['family_code']) && $current_family != $patient['family_code']) {
                                                $current_family = $patient['family_code'];
                                                echo '<optgroup label="' . htmlspecialchars($patient['family_name'] . ' Family (' . $patient['family_code'] . ')') . '">';
                                            } else if (empty($patient['family_code']) && !empty($current_family)) {
                                                echo '</optgroup>';
                                                $current_family = '';
                                            }
                                        ?>
                                            <option 
                                                value="<?php echo $patient['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars(strtolower($patient_name)); ?>" 
                                                data-phone="<?php echo htmlspecialchars(strtolower($patient['phone'])); ?>"
                                                data-family="<?php echo htmlspecialchars(strtolower($patient['family_name'] ?? '')); ?>"
                                                <?php echo (isset($appointment['patient_id']) && $appointment['patient_id'] == $patient['id']) ? 'selected' : ''; ?>
                                            >
                                                <?php echo htmlspecialchars($patient_name); ?>
                                                <?php if (!empty($patient['family_code'])): ?>
                                                <span class="text-muted"> - <?php echo htmlspecialchars($patient['family_name']); ?> Family</span>
                                                <?php endif; ?>
                                                (<?php echo $patient['phone']; ?>)
                                            </option>
                                        <?php endwhile; 
                                        // Close any open optgroup
                                        if (!empty($current_family)) {
                                            echo '</optgroup>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Doctor</label>
                                    <select class="form-select" name="doctor_id">
                                        <option value="">Select Doctor</option>
                                        <?php while($doctor = mysqli_fetch_assoc($doctors)): 
                                            $doctor_name = $doctor['last_name'] . ', ' . $doctor['first_name'];
                                            if (!empty($doctor['middle_name'])) {
                                                $doctor_name .= ' ' . $doctor['middle_name'];
                                            }
                                        ?>
                                            <option value="<?php echo $doctor['id']; ?>" <?php echo (isset($appointment['doctor_id']) && $appointment['doctor_id'] == $doctor['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($doctor_name . ' (' . $doctor['specialization'] . ')'); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Appointment Date <span class="text-danger">*</span></label>
                                    <input type="date" name="appointment_date" class="form-control" required 
                                           value="<?php echo isset($appointment['appointment_date']) ? $appointment['appointment_date'] : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Appointment Time <span class="text-danger">*</span></label>
                        <select name="appointment_time" class="form-select" required>
                            <option value="">Select time...</option>
                            <?php
                            $start = strtotime('09:00');
                            $end = strtotime('17:00');
                            $interval = 30 * 60; // 30 minutes
                            
                            for ($time = $start; $time < $end; $time += $interval) {
                                $formatted_time = date('H:i:s', $time);
                                $display_time = date('g:i A', $time);
                                $selected = (isset($appointment['appointment_time']) && $formatted_time == $appointment['appointment_time']) ? 'selected' : '';
                                echo "<option value=\"{$formatted_time}\" {$selected}>{$display_time}</option>";
                            }
                            ?>
                        </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="services" class="form-label">Services <span class="text-danger">*</span></label>
                                <select class="form-select" id="services" name="service_id" required>
                                    <option value="">Select a service...</option>
                                    <?php foreach($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>" 
                                            <?php 
                                                $is_selected = false;
                                                
                                                // Check if service was selected in edit mode
                                                if(isset($appointment['service_id']) && $appointment['service_id'] == $service['id']) {
                                                    $is_selected = true;
                                                }
                                                
                                                // Check if service matches walk-in preselected service
                                                if($walk_in_mode && $preselected_service && $service['name'] == $preselected_service) {
                                                    $is_selected = true;
                                                }
                                                
                                                echo $is_selected ? 'selected' : '';
                                            ?>>
                                            <?php echo htmlspecialchars($service['name']); ?> 
                                            (₱<?php echo number_format($service['price'], 2); ?>, <?php echo $service['duration']; ?> min)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Select a service for this appointment</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select" required>
                                        <option value="pending" <?php echo isset($appointment['status']) && $appointment['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo isset($appointment['status']) && $appointment['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="completed" <?php echo isset($appointment['status']) && $appointment['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo isset($appointment['status']) && $appointment['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="3"><?php echo isset($appointment['notes']) ? htmlspecialchars($appointment['notes']) : ''; ?></textarea>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i><?php echo $edit_mode ? 'Update' : 'Create'; ?> Appointment
                                </button>
                            </div>
                        </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Date validation
        $('input[name="appointment_date"]').on('change', function() {
            const selectedDate = new Date($(this).val());
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Check if date is in the past
            if (selectedDate < today) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date',
                    text: 'You cannot schedule appointments in the past.',
                    confirmButtonColor: '#4e73df'
                });
                $(this).val('');
                return;
            }
            
            // Check if it's a Saturday (6) or Sunday (0)
            const dayOfWeek = selectedDate.getDay();
            if (dayOfWeek === 6) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Day',
                    text: 'Appointments are not available on Saturdays.',
                    confirmButtonColor: '#4e73df'
                });
                $(this).val('');
            }
            
            // If time is already selected, check availability
            const timeSelect = $('select[name="appointment_time"]');
            if (timeSelect.val()) {
                checkTimeAvailability();
            }
        });
        
        // Time availability check when time or doctor changes
        $('select[name="appointment_time"], select[name="doctor_id"], select[name="service_id"]').on('change', function() {
            checkTimeAvailability();
        });
        
        // Function to check if time slot is available
        function checkTimeAvailability() {
            const doctorId = $('select[name="doctor_id"]').val();
            const appointmentDate = $('input[name="appointment_date"]').val();
            const appointmentTime = $('select[name="appointment_time"]').val();
            const serviceId = $('select[name="service_id"]').val();
            
            // Only check if doctor, date, time and service are selected
            if (!doctorId || !appointmentDate || !appointmentTime || !serviceId) {
                return;
            }
            
            // Get appointment ID if in edit mode
            const urlParams = new URLSearchParams(window.location.search);
            const appointmentId = urlParams.get('edit');
            
            // Show loading indicator
            const timeSelect = $('select[name="appointment_time"]');
            timeSelect.addClass('border-warning');
            
            // AJAX request to check availability
            $.ajax({
                url: 'check_appointment_availability.php',
                type: 'POST',
                data: {
                    doctor_id: doctorId,
                    appointment_date: appointmentDate,
                    appointment_time: appointmentTime,
                    service_id: serviceId,
                    appointment_id: appointmentId || ''
                },
                dataType: 'json',
                success: function(response) {
                    timeSelect.removeClass('border-warning');
                    
                    if (response.conflict) {
                        timeSelect.addClass('border-danger');
                        Swal.fire({
                            icon: 'warning',
                            title: 'Time Slot Not Available',
                            text: 'This time slot is already booked for the selected doctor. Please choose a different time or doctor.',
                            confirmButtonColor: '#4e73df'
                        });
                    } else {
                        // Time slot is available
                        timeSelect.removeClass('border-danger').addClass('border-success');
                        
                        // Optional: Show a quick toast notification for availability
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        });
                        
                        Toast.fire({
                            icon: 'success',
                            title: 'Time slot is available!'
                        });
                        
                        // Remove success border after a delay
                        setTimeout(() => {
                            timeSelect.removeClass('border-success');
                        }, 3000);
                    }
                },
                error: function() {
                    timeSelect.removeClass('border-warning border-danger border-success');
                    console.error('Error checking appointment availability');
                }
            });
        }
        
        // Service selection validation - ensure a service is selected
        $('button[type="submit"]').on('click', function(e) {
            if (!$('select[name="service_id"]').val()) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Service Required',
                    text: 'Please select a service for this appointment.',
                    confirmButtonColor: '#4e73df'
                });
            }
        });
        
        // Patient search functionality
        const patientSearch = $('#patientSearch');
        const patientSelect = $('#patientSelect');
        const clearPatientSearch = $('#clearPatientSearch');
        
        // Store the original options for reset
        const originalOptions = patientSelect.html();
        
        patientSearch.on('input', function() {
            const searchTerm = $(this).val().toLowerCase().trim();
            
            if (searchTerm === '') {
                // Reset to original state if search field is cleared
                patientSelect.html(originalOptions);
                return;
            }
            
            // First try exact family code search
            let familyMatches = false;
            
            // Filter options based on search term
            $('#patientSelect option').each(function() {
                const $option = $(this);
                const patientName = $option.data('name') || '';
                const patientPhone = $option.data('phone') || '';
                const familyName = $option.data('family') || '';
                
                // Check if search term matches any criteria
                const matches = patientName.includes(searchTerm) || 
                               patientPhone.includes(searchTerm) || 
                               familyName.includes(searchTerm);
                
                if ($option.val() === '') {
                    // Always keep the default option visible
                    $option.show();
                } else if (matches) {
                    $option.show();
                    // If matches family, flag to keep optgroup visible
                    if (familyName.includes(searchTerm)) {
                        familyMatches = $option.closest('optgroup').attr('label') || '';
                    }
                } else {
                    $option.hide();
                }
            });
            
            // Handle optgroups visibility
            $('#patientSelect optgroup').each(function() {
                const $group = $(this);
                const groupLabel = $group.attr('label').toLowerCase();
                
                // Show group if label matches search or if any visible options
                if (groupLabel.includes(searchTerm) || familyMatches === groupLabel || $group.find('option:visible').length > 0) {
                    $group.show();
                    // Also show all options in matching family group
                    if (groupLabel.includes(searchTerm)) {
                        $group.find('option').show();
                    }
                } else {
                    $group.hide();
                }
            });
        });
        
        // Clear search function
        clearPatientSearch.on('click', function() {
            patientSearch.val('');
            patientSelect.html(originalOptions);
        });
        
        // Add a visual indicator for patients with family connections
        $('#patientSelect option').each(function() {
            const $option = $(this);
            if ($option.data('family')) {
                $option.css('background-color', '#f0f8ff'); // Light blue background for family members
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?> 