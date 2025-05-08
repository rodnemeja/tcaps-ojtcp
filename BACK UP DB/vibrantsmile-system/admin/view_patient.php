<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Check if patient ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid patient ID.";
    header("location: patients.php");
    exit;
}

// First, get patient details
$sql = "SELECT p.*, u.email, u.phone, u.first_name, u.middle_name, u.last_name, u.active, u.username, u.profile_picture 
        FROM patients p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ? AND u.role = 'patient'";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_GET['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if($patient = mysqli_fetch_assoc($result)) {
        // Get medical history
        $medical_history = null;
        $sql_medical = "SELECT * FROM medical_history WHERE patient_id = ?";
        if($stmt_medical = mysqli_prepare($conn, $sql_medical)) {
            mysqli_stmt_bind_param($stmt_medical, "i", $_GET['id']);
            mysqli_stmt_execute($stmt_medical);
            $result_medical = mysqli_stmt_get_result($stmt_medical);
            if($row_medical = mysqli_fetch_assoc($result_medical)) {
                $medical_history = $row_medical;
            }
            mysqli_stmt_close($stmt_medical);
        }

        // Get appointment history
        $appointment_history_sql = "SELECT 
            a.*,
            CONCAT(du.first_name, ' ', du.last_name) as doctor_name,
            GROUP_CONCAT(DISTINCT s.name SEPARATOR ', ') as services
        FROM appointments a
        LEFT JOIN doctors d ON a.doctor_id = d.id
        LEFT JOIN users du ON d.user_id = du.id
        LEFT JOIN appointment_services aps ON a.id = aps.appointment_id
        LEFT JOIN services s ON aps.service_id = s.id
        WHERE a.patient_id = ?
        GROUP BY a.id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

        $stmt = mysqli_prepare($conn, $appointment_history_sql);
        mysqli_stmt_bind_param($stmt, "i", $_GET['id']);
        mysqli_stmt_execute($stmt);
        $appointment_history_result = mysqli_stmt_get_result($stmt);

        // Debug: Print the query results
        if(mysqli_num_rows($appointment_history_result) > 0) {
            while($row = mysqli_fetch_assoc($appointment_history_result)) {
                error_log("Appointment ID: " . $row['id'] . ", Services: " . $row['services']);
            }
            // Reset the pointer
            mysqli_data_seek($appointment_history_result, 0);
        }
    } else {
        $_SESSION['error_message'] = "Patient not found.";
        header("location: patients.php");
        exit;
    }
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error_message'] = "Error fetching patient details.";
    header("location: patients.php");
    exit;
}

// Debug information
if(isset($_SESSION['error_message'])) {
    echo "<div class='alert alert-danger'>" . $_SESSION['error_message'] . "</div>";
    unset($_SESSION['error_message']);
}

$page_title = "View Patient Details";
$current_page = "patients";
require_once "includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Patient Details</h2>
    <a href="patients.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Patients
    </a>
</div>

<div class="row">
    <!-- Patient Information -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-circle me-2"></i>Personal Information
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-4">
                    <?php 
                    // Set default profile picture if none exists
                    $profile_pic = $patient['profile_picture'] ? '../' . $patient['profile_picture'] : '../assets/img/default-profile.png';
                    ?>
                    <div class="patient-profile-container me-3">
                        <img src="<?php echo $profile_pic; ?>" alt="Patient Profile" class="patient-profile-img rounded-circle">
                    </div>
                    <div>
                        <h4 class="mb-1">
                            <?php 
                            $full_name = $patient['last_name'] . ', ' . $patient['first_name'];
                            if (!empty($patient['middle_name'])) {
                                $full_name .= ' ' . $patient['middle_name'];
                            }
                            echo htmlspecialchars($full_name);
                            ?>
                        </h4>
                        <div class="text-muted">@<?php echo htmlspecialchars($patient['username']); ?></div>
                        <span class="badge bg-<?php echo $patient['active'] ? 'success' : 'danger'; ?> mt-1">
                            <?php echo $patient['active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                        <?php if(isset($patient['is_minor']) && $patient['is_minor'] == 1): ?>
                        <span class="badge bg-warning text-dark ms-2 mt-1" data-bs-toggle="tooltip" title="Minor patient (under 18)">
                            <i class="fas fa-child"></i> Minor
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Email</div>
                    <div class="col-sm-8"><?php echo htmlspecialchars($patient['email']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Phone</div>
                    <div class="col-sm-8"><?php echo htmlspecialchars($patient['phone']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Address</div>
                    <div class="col-sm-8"><?php echo htmlspecialchars($patient['address']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Date of Birth</div>
                    <div class="col-sm-8"><?php echo date('F d, Y', strtotime($patient['date_of_birth'])); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Gender</div>
                    <div class="col-sm-8"><?php echo ucfirst($patient['gender']); ?></div>
                </div>
                
                <?php if(isset($patient['is_minor']) && $patient['is_minor'] == 1): ?>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Age Status</div>
                    <div class="col-sm-8">
                        <span class="badge bg-warning text-dark">
                            <i class="fas fa-child me-1"></i> Minor (Under 18)
                        </span>
                    </div>
                </div>
                
                <div class="mt-4 mb-3">
                    <h6 class="text-primary">
                        <i class="fas fa-user-shield me-2"></i>Guardian Information
                    </h6>
                    <hr>
                </div>
                
                <?php if(!empty($patient['guardian_name'])): ?>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Guardian Name</div>
                    <div class="col-sm-8"><?php echo htmlspecialchars($patient['guardian_name']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($patient['guardian_relationship'])): ?>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Relationship</div>
                    <div class="col-sm-8"><?php echo htmlspecialchars($patient['guardian_relationship']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($patient['guardian_phone'])): ?>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Guardian Phone</div>
                    <div class="col-sm-8"><?php echo htmlspecialchars($patient['guardian_phone']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($patient['guardian_email'])): ?>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Guardian Email</div>
                    <div class="col-sm-8"><?php echo htmlspecialchars($patient['guardian_email']); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Consent Status</div>
                    <div class="col-sm-8">
                        <?php if($patient['guardian_consent'] == 1): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check me-1"></i> Consent Provided
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger">
                                <i class="fas fa-times me-1"></i> No Consent Record
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Blood Type</div>
                    <div class="col-sm-8">
                        <?php 
                        $blood_type = isset($patient['blood_type']) && !empty($patient['blood_type']) 
                            ? htmlspecialchars($patient['blood_type']) 
                            : '<span class="text-muted">Not specified</span>';
                        echo $blood_type;
                        ?>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Region</div>
                    <div class="col-sm-8"><?php echo htmlspecialchars($patient['region']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">City</div>
                    <div class="col-sm-8"><?php echo htmlspecialchars($patient['city']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Barangay</div>
                    <div class="col-sm-8"><?php echo htmlspecialchars($patient['barangay']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Zipcode</div>
                    <div class="col-sm-8"><?php echo htmlspecialchars($patient['zipcode']); ?></div>
                </div>
                <div class="row">
                    <div class="col-sm-4 text-muted">Medical History</div>
                    <div class="col-sm-8">
                        <?php if($medical_history): ?>
                            <?php 
                            // Decrypt the medical history data
                            $decrypted_data = json_decode(decryptMedicalData($medical_history['encrypted_data']), true);
                            $additional_notes = json_decode($medical_history['additional_notes'], true);
                            ?>
                            
                            <?php if($medical_history['has_allergies']): ?>
                                <div class="mb-3">
                                    <strong>Allergies:</strong> <?php echo htmlspecialchars($medical_history['allergies_details']); ?>
                                </div>
                            <?php endif; ?>

                            <?php if($medical_history['has_medications']): ?>
                                <div class="mb-3">
                                    <strong>Current Medications:</strong> <?php echo htmlspecialchars($medical_history['medications_details']); ?>
                                </div>
                            <?php endif; ?>

                            <?php if(!empty($medical_history['medical_conditions'])): ?>
                                <div class="mb-3">
                                    <strong>Medical Conditions:</strong>
                                    <ul class="list-unstyled">
                                        <?php foreach(explode(', ', $medical_history['medical_conditions']) as $condition): ?>
                                            <li><?php echo ucwords(str_replace('_', ' ', $condition)); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if(!empty($medical_history['other_conditions_details'])): ?>
                                <div class="mb-3">
                                    <strong>Other Illnesses:</strong>
                                    <?php echo htmlspecialchars($medical_history['other_conditions_details']); ?>
                                </div>
                            <?php endif; ?>

                            <?php if(isset($additional_notes['pregnancy_status'])): ?>
                                <div class="mb-3">
                                    <strong>Pregnancy Status:</strong> 
                                    <?php echo ucfirst($additional_notes['pregnancy_status']); ?>
                                </div>
                            <?php endif; ?>

                            <?php if(isset($additional_notes['hospitalization']) && $additional_notes['hospitalization'] === 'yes'): ?>
                                <div class="mb-3">
                                    <strong>Previous Hospitalization:</strong> Yes
                                    <?php if(!empty($additional_notes['hospitalization_cause'])): ?>
                                        <br><small>Cause: <?php echo htmlspecialchars($additional_notes['hospitalization_cause']); ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if(!empty($additional_notes['dental_health_status'])): ?>
                                <div class="mb-3">
                                    <strong>Dental Health Status:</strong>
                                    <?php echo htmlspecialchars($additional_notes['dental_health_status']); ?>
                                </div>
                            <?php endif; ?>

                            <?php if(!empty($additional_notes['oral_prophylaxis'])): ?>
                                <div class="mb-3">
                                    <strong>Oral Prophylaxis:</strong>
                                    <?php echo htmlspecialchars($additional_notes['oral_prophylaxis']); ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted">No medical history recorded.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointments History -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-check me-2"></i>Appointment History
                </h5>
            </div>
            <div class="card-body">
                <?php if(mysqli_num_rows($appointment_history_result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Doctor</th>
                                    <th>Services</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($appointment = mysqli_fetch_assoc($appointment_history_result)): ?>
                                    <?php 
                                    // Debug: Log the appointment data
                                    error_log("Displaying appointment ID: " . $appointment['id']);
                                    error_log("Services for appointment: " . $appointment['services']);
                                    ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Not assigned'); ?></td>
                                        <td>
                                            <?php 
                                            if (!empty($appointment['services'])) {
                                                echo htmlspecialchars($appointment['services']);
                                            } else {
                                                // Check if there's a service_id in the appointments table
                                                if (!empty($appointment['service_id'])) {
                                                    // Fetch the service name directly
                                                    $service_sql = "SELECT name FROM services WHERE id = ?";
                                                    $service_stmt = mysqli_prepare($conn, $service_sql);
                                                    mysqli_stmt_bind_param($service_stmt, "i", $appointment['service_id']);
                                                    mysqli_stmt_execute($service_stmt);
                                                    $service_result = mysqli_stmt_get_result($service_stmt);
                                                    if ($service = mysqli_fetch_assoc($service_result)) {
                                                        echo htmlspecialchars($service['name']);
                                                    } else {
                                                        echo 'No services selected';
                                                    }
                                                } else {
                                                    echo 'No services selected';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $appointment['status'] === 'completed' ? 'success' : 
                                                    ($appointment['status'] === 'cancelled' ? 'danger' : 
                                                    ($appointment['status'] === 'pending' ? 'warning' : 'info')); 
                                            ?>">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">No appointment history found</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-tasks me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex gap-2">
                    <a href="edit_patient.php?id=<?php echo $patient['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Edit Patient
                    </a>
                    <!-- <button onclick="toggleStatus(<?php echo $patient['id']; ?>, <?php echo $patient['active']; ?>)" 
                            class="btn btn-<?php echo $patient['active'] ? 'danger' : 'success'; ?>">
                        <i class="fas fa-<?php echo $patient['active'] ? 'ban' : 'check'; ?> me-2"></i>
                        <?php echo $patient['active'] ? 'Deactivate' : 'Activate'; ?> Patient
                    </button> -->
                    <a href="appointment_form.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-info">
                        <i class="fas fa-calendar-plus me-2"></i>New Appointment
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Patient Profile Image */
.patient-profile-container {
    position: relative;
}

.patient-profile-img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border: 3px solid #e3e6f0;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function toggleStatus(id, currentStatus) {
        const action = currentStatus ? 'deactivate' : 'activate';
        Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to ${action} this patient?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4e73df',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, proceed!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `patients.php?toggle_status=${id}`;
            }
        });
    }
</script>

<?php require_once "includes/footer.php"; ?> 