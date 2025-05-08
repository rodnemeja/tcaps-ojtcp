<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

$error = '';
$success = '';

// Get all patients
$sql = "SELECT p.id, u.full_name, u.phone, u.email 
        FROM patients p 
        JOIN users u ON p.user_id = u.id 
        WHERE u.role = 'patient'
        ORDER BY u.full_name";
$patients = mysqli_query($conn, $sql);

// Get all doctors
$sql = "SELECT d.id, u.full_name, d.specialization 
        FROM doctors d 
        JOIN users u ON d.user_id = u.id 
        ORDER BY u.full_name";
$doctors = mysqli_query($conn, $sql);

// Get all services
$sql = "SELECT * FROM services ORDER BY name";
$services = mysqli_query($conn, $sql);

// Get tomorrow's date
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];
    $selected_services = isset($_POST['services']) ? $_POST['services'] : array();

    // Check if appointment date is today
    if (strtotime($appointment_date) <= strtotime(date('Y-m-d'))) {
        $_SESSION['error'] = "Appointments cannot be scheduled for today. Please select a future date.";
    } else {
        // Validate required fields
        if(empty($patient_id) || empty($appointment_date) || empty($appointment_time) || empty($status)) {
            $error = "Please fill in all required fields.";
        } else {
            // Insert appointment
            $sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status, notes) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "iissss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $status, $notes);
                if(mysqli_stmt_execute($stmt)) {
                    $appointment_id = mysqli_insert_id($conn);
                    
                    // Insert selected services
                    if(!empty($selected_services)) {
                        $sql = "INSERT INTO appointment_services (appointment_id, service_id) VALUES (?, ?)";
                        if($stmt = mysqli_prepare($conn, $sql)) {
                            foreach($selected_services as $service_id) {
                                mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $service_id);
                                if(!mysqli_stmt_execute($stmt)) {
                                    $error = "Error adding services: " . mysqli_error($conn);
                                    break;
                                }
                            }
                        }
                    }
                    
                    if(empty($error)) {
                        $_SESSION['success_swal'] = true;
                        header("Location: appointments.php");
                        exit();
                    }
                } else {
                    $error = "Error creating appointment: " . mysqli_error($conn);
                }
            }
        }
    }
}

$page_title = "New Appointment";
$current_page = "appointments";
require_once "includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>New Appointment</h2>
    <a href="appointments.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Appointments
    </a>
</div>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-warning"><?php echo $_SESSION['error']; ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success_swal'])): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Appointment Created!',
        text: 'The appointment has been successfully scheduled.',
        confirmButtonColor: '#1cc88a',
        willClose: () => {
            window.location.href = 'appointments.php';
        }
    });
</script>
<?php unset($_SESSION['success_swal']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['warning'])): ?>
    <script>
        Swal.fire({
            icon: 'warning',
            title: 'Warning',
            text: '<?php echo $_SESSION['warning']; ?>',
            confirmButtonColor: '#3085d6'
        });
    </script>
    <?php unset($_SESSION['warning']); ?>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Patient <span class="text-danger">*</span></label>
                    <select name="patient_id" class="form-select select2" required>
                        <option value="">Select Patient</option>
                        <?php while($patient = mysqli_fetch_assoc($patients)): ?>
                            <option value="<?php echo $patient['id']; ?>">
                                <?php echo htmlspecialchars($patient['full_name'] . ' - ' . $patient['phone'] . ' (' . $patient['email'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Doctor</label>
                    <select name="doctor_id" class="form-select">
                        <option value="">Select Doctor</option>
                        <?php while($doctor = mysqli_fetch_assoc($doctors)): ?>
                            <option value="<?php echo $doctor['id']; ?>">
                                <?php echo htmlspecialchars($doctor['full_name'] . ' (' . $doctor['specialization'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Appointment Date <span class="text-danger">*</span></label>
                    <input type="date" name="appointment_date" id="appointment_date" class="form-control" required 
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    <small class="text-muted">Appointments must be scheduled at least one day in advance.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Appointment Time <span class="text-danger">*</span></label>
                    <input type="time" name="appointment_time" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Services</label>
                <div class="row">
                    <?php 
                    mysqli_data_seek($services, 0); // Reset the services result pointer
                    while($service = mysqli_fetch_assoc($services)): 
                    ?>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="services[]" 
                                       value="<?php echo $service['id']; ?>" id="service<?php echo $service['id']; ?>">
                                <label class="form-check-label" for="service<?php echo $service['id']; ?>">
                                    <?php echo htmlspecialchars($service['name']); ?> (₱<?php echo number_format($service['cost'], 2); ?>)
                                </label>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Create Appointment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Select2 for better dropdown experience -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select Patient'
    });

    // Date validation
    const appointmentDateInput = document.getElementById('appointment_date');
    const appointmentTimeInput = document.querySelector('input[name="appointment_time"]');
    
    function validateDateTime() {
        const selectedDate = new Date(appointmentDateInput.value);
        const today = new Date();
        
        // Reset time parts for date comparison
        selectedDate.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate.getTime() <= today.getTime()) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Date Selection',
                html: '<div class="text-center">' +
                      '<p class="mb-2">Same-day appointments are not allowed.</p>' +
                      '<p class="mb-2">Please select a future date for your appointment.</p>' +
                      '<p class="text-muted small">Next available date: Starting tomorrow</p>' +
                      '</div>',
                confirmButtonText: 'Select New Date',
                confirmButtonColor: '#4e73df',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                appointmentDateInput.value = '';
                appointmentTimeInput.value = '';
            });
        }
    }

    // Add event listeners
    appointmentDateInput.addEventListener('change', validateDateTime);
});
</script>

<!-- Add this CSS for better notification styling -->
<style>
.my-swal {
    z-index: 9999;
}
.my-swal .swal2-html-container {
    margin: 1em 1.6em 0.3em;
}
.my-swal .swal2-title {
    color: #2c3e50;
    font-size: 1.4rem;
}
.my-swal p {
    margin-bottom: 0.5rem;
    color: #2c3e50;
}
.my-swal .text-muted {
    color: #6c757d !important;
    font-size: 0.9rem;
}
</style>

<?php require_once "includes/footer.php"; ?> 