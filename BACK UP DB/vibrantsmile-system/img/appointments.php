<?php
// Add debug code at the top of the file
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("POST request received");
    error_log("POST data: " . print_r($_POST, true));
}

session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

$role = $_SESSION["role"];
$user_id = $_SESSION["id"];

// Get user information
$user_sql = "SELECT * FROM users WHERE id = ?";
if($stmt = mysqli_prepare($conn, $user_sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $user_result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($user_result);
}

// Get available services
$services_sql = "SELECT * FROM services ORDER BY name";
$services_result = mysqli_query($conn, $services_sql);
$services = mysqli_fetch_all($services_result, MYSQLI_ASSOC);

// Get available doctors with their names from users table
$doctors_sql = "SELECT d.id, u.first_name, u.last_name, d.specialization 
                FROM doctors d 
                JOIN users u ON d.user_id = u.id 
                WHERE u.active = 1
                ORDER BY u.first_name, u.last_name";
$doctors_result = mysqli_query($conn, $doctors_sql);
if (!$doctors_result) {
    die("Error fetching doctors: " . mysqli_error($conn));
}
$doctors = mysqli_fetch_all($doctors_result, MYSQLI_ASSOC);

// Add debug information
echo "<script>
    console.log('Doctors count: " . count($doctors) . "');
    console.log('Doctors data:', " . json_encode($doctors) . ");
</script>";

// Get patient ID for the logged-in user
$patient_sql = "SELECT id FROM patients WHERE user_id = ?";
if($stmt = mysqli_prepare($conn, $patient_sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $patient_result = mysqli_stmt_get_result($stmt);
    $patient = mysqli_fetch_assoc($patient_result);
}

// Handle appointment creation
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["new_appointment"])){
    $doctor_id = $_POST["doctor_id"];
    $service_id = $_POST["service_id"];
    $appointment_date = $_POST["appointment_date"];
    $appointment_time = $_POST["appointment_time"];
    $patient_id = $patient['id'];

    // Debug information
    error_log("New appointment submission - Patient ID: " . $patient_id . ", Doctor ID: " . $doctor_id . ", Service ID: " . $service_id);

    // Check if patient already has 3 appointments on this date
    $check_daily_limit = "SELECT COUNT(*) as count FROM appointments 
                         WHERE patient_id = ? 
                         AND appointment_date = ? 
                         AND status != 'cancelled'";
    
    if($stmt = mysqli_prepare($conn, $check_daily_limit)){
        mysqli_stmt_bind_param($stmt, "is", $patient_id, $appointment_date);
            mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $daily_count = mysqli_fetch_assoc($result);
        
        if($daily_count['count'] >= 3){
            echo "<script>
                Swal.fire({
                    title: 'Daily Booking Limit Reached',
                    text: 'You can only book up to 3 appointments per day. Please choose a different date.',
                    icon: 'warning',
                    confirmButtonColor: '#4e73df'
                });
            </script>";
        } else {
            // Validate appointment date and time
            $appointment_datetime = strtotime($appointment_date . ' ' . $appointment_time);
            $current_datetime = strtotime('now');
            $day_of_week = date('N', $appointment_datetime);
            $appointment_hour = date('H', $appointment_datetime);

            if ($day_of_week == 6) {
                echo "<script>
                    Swal.fire({
                        title: 'Invalid Day',
                        text: 'Appointments are not available on Saturdays.',
                        icon: 'error',
                        confirmButtonColor: '#4e73df'
                    });
                </script>";
            } elseif ($appointment_datetime <= $current_datetime) {
                echo "<script>
                    Swal.fire({
                        title: 'Invalid Date',
                        text: 'You cannot book appointments for past dates or the current day.',
                        icon: 'error',
                        confirmButtonColor: '#4e73df'
                    });
                </script>";
            } elseif ($appointment_hour < 9 || $appointment_hour >= 17) {
                echo "<script>
                    Swal.fire({
                        title: 'Invalid Time',
                        text: 'Appointments are only available between 9:00 AM and 5:00 PM.',
                        icon: 'error',
                        confirmButtonColor: '#4e73df'
                    });
                </script>";
            } else {
                // Get service duration
                $service_duration = 0;
                foreach ($services as $service) {
                    if ($service['id'] == $service_id) {
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
                
                if ($stmt = mysqli_prepare($conn, $check_sql)) {
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

                    if ($row['count'] > 0) {
                        echo "<script>
                            Swal.fire({
                                title: 'Time Slot Unavailable',
                                text: 'This time slot is already booked by another patient. Please choose another time.',
                                icon: 'warning',
                                confirmButtonColor: '#4e73df'
                            });
                        </script>";
                    } else {
                        // Insert the appointment
                        $sql = "INSERT INTO appointments (patient_id, doctor_id, service_id, appointment_date, appointment_time, end_time, status) 
                                VALUES (?, ?, ?, ?, ?, ?, 'pending')";
                        
                        if ($stmt = mysqli_prepare($conn, $sql)) {
                            mysqli_stmt_bind_param($stmt, "iiisss", 
                                $patient_id, 
                                $doctor_id, 
                                $service_id,
                                $appointment_date, 
                                $appointment_time,
                                $end_time
                            );
                            
                            if (mysqli_stmt_execute($stmt)) {
                                // Get service and doctor details for the success message
                                $service_name = "";
                                $doctor_name = "";
                                foreach ($services as $service) {
                                    if ($service['id'] == $service_id) {
                                        $service_name = $service['name'];
                                        break;
                                    }
                                }
                                foreach ($doctors as $doctor) {
                                    if ($doctor['id'] == $doctor_id) {
                                        $doctor_name = "Dr. " . $doctor['first_name'] . " " . $doctor['last_name'];
                                        break;
                                    }
                                }

                                echo "<script>
                                    Swal.fire({
                                        title: 'Appointment Request Submitted!',
                                        html: `
                                            <div class='text-start'>
                                                <p><strong>Service:</strong> " . $service_name . "</p>
                                                <p><strong>Doctor:</strong> " . $doctor_name . "</p>
                                                <p><strong>Date:</strong> " . date('F j, Y', strtotime($appointment_date)) . "</p>
                                                <p><strong>Time:</strong> " . date('g:i A', strtotime($appointment_time)) . "</p>
                                                <p class='mt-3'>Your appointment request has been submitted successfully!</p>
                                            </div>
                                        `,
                                        icon: 'success',
                                        confirmButtonColor: '#4e73df'
                                    }).then((result) => {
                                        window.location.href = 'appointments.php';
                                    });
                                </script>";
                            } else {
                                echo "<script>
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'There was an error submitting your appointment request. Please try again.',
                                        icon: 'error',
                                        confirmButtonColor: '#4e73df'
                                    });
                                </script>";
                            }
                        }
                    }
                }
            }
        }
    }
}

// Handle appointment status update
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_status"])){
    $appointment_id = $_POST["appointment_id"];
    $status = $_POST["status"];
    
    // Get appointment details including patient email
    $appointment_sql = "SELECT a.*, p.id as patient_id, s.name as service_name, s.price as service_price, s.duration, s.duration_format,
            CONCAT(u1.first_name, ' ', u1.last_name) as doctor_name,
            CONCAT(u2.first_name, ' ', u2.last_name) as patient_name,
            u2.email as patient_email
            FROM appointments a 
            JOIN doctors d ON a.doctor_id = d.id 
            JOIN patients p ON a.patient_id = p.id 
            JOIN users u1 ON d.user_id = u1.id 
            JOIN users u2 ON p.user_id = u2.id 
            JOIN services s ON a.service_id = s.id 
            WHERE a.id = ?";
            
    if($stmt = mysqli_prepare($conn, $appointment_sql)){
        mysqli_stmt_bind_param($stmt, "i", $appointment_id);
        mysqli_stmt_execute($stmt);
        $appointment_result = mysqli_stmt_get_result($stmt);
        $appointment = mysqli_fetch_assoc($appointment_result);
        
        // Update appointment status
        $update_sql = "UPDATE appointments SET status = ? WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $update_sql)){
        mysqli_stmt_bind_param($stmt, "si", $status, $appointment_id);
        if(mysqli_stmt_execute($stmt)){
                // Send email notification
                require_once "config/mail.php";
                
                $subject = ($status == 'approved') ? 'Appointment Approved - Dental Clinic' : 'Appointment Cancelled - Dental Clinic';
                $email_body = getAppointmentEmailBody($appointment, $status);
                
                if(sendAppointmentEmail($appointment['patient_email'], $subject, $email_body)){
                    $status_text = ($status == 'approved') ? 'approved' : 'cancelled';
            echo "<script>
                Swal.fire({
                    title: 'Status Updated',
                            text: 'Appointment has been " . $status_text . " successfully and email notification sent!',
                    icon: 'success',
                    confirmButtonColor: '#4e73df'
                }).then((result) => {
                    window.location.href = 'appointments.php';
                });
            </script>";
                } else {
                    $status_text = ($status == 'approved') ? 'approved' : 'cancelled';
                    echo "<script>
                        Swal.fire({
                            title: 'Status Updated',
                            text: 'Appointment has been " . $status_text . " successfully but email notification failed to send.',
                            icon: 'warning',
                            confirmButtonColor: '#4e73df'
                        }).then((result) => {
                            window.location.href = 'appointments.php';
                        });
                    </script>";
                }
        } else {
            echo "<script>
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to update appointment status. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#4e73df'
                });
            </script>";
            }
        }
    }
}

// Get appointments based on role
if($role == "patient"){
    $sql = "SELECT a.*, d.specialization, s.name as service_name, s.price as service_price, s.duration, s.duration_format,
            CONCAT(u.first_name, ' ', u.last_name) as doctor_name 
            FROM appointments a 
            JOIN doctors d ON a.doctor_id = d.id 
            JOIN users u ON d.user_id = u.id 
            JOIN services s ON a.service_id = s.id 
            WHERE a.patient_id = (SELECT id FROM patients WHERE user_id = ?) 
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
} elseif($role == "doctor"){
    $sql = "SELECT a.*, p.id as patient_id, s.name as service_name, s.price as service_price, s.duration, s.duration_format,
            CONCAT(u.first_name, ' ', u.last_name) as patient_name 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.id 
            JOIN users u ON p.user_id = u.id 
            JOIN services s ON a.service_id = s.id 
            WHERE a.doctor_id = (SELECT id FROM doctors WHERE user_id = ?) 
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
} else{
    $sql = "SELECT a.*, d.specialization, s.name as service_name, s.price as service_price, s.duration, s.duration_format,
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

// Add error checking
if(!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Clear any existing appointments array
$appointments = array();

if($stmt = mysqli_prepare($conn, $sql)){
    if($role != "admin"){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    }
    if(!mysqli_stmt_execute($stmt)) {
        die("Error executing query: " . mysqli_stmt_error($stmt));
    }
    $appointments_result = mysqli_stmt_get_result($stmt);
    if(!$appointments_result) {
        die("Error getting result: " . mysqli_error($conn));
    }
    // Store appointments in an array for reuse
    while($row = mysqli_fetch_assoc($appointments_result)) {
        $appointments[] = $row;
    }
} else {
    die("Error preparing statement: " . mysqli_error($conn));
}

// Add this after the appointments query to verify the data
echo "<script>
    console.log('Appointments count: " . count($appointments) . "');
    console.log('Database connection status: " . ($conn ? 'Connected' : 'Disconnected') . "');
</script>";

// Handle AJAX request for fetching appointments
if (isset($_GET['fetch_appointments'])) {
    // Don't output the entire page, just the appointments section
    if(empty($appointments)): ?>
        <div class="col-12 text-center py-5">
            <div class="empty-state">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No Appointments Found</h4>
                <?php if($role == "patient"): ?>
                <p class="text-muted mb-3">You don't have any appointments scheduled yet.</p>
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAppointmentModal">
                    <i class="fas fa-plus me-2"></i> Schedule Your First Appointment
                </a>
                <?php else: ?>
                <p class="text-muted">There are no appointments available at this time.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <?php foreach($appointments as $appointment): ?>
        <div class="col-md-6 mb-3">
            <div class="card appointment-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0">
                            <?php echo $role == "patient" ? htmlspecialchars($appointment['doctor_name']) : htmlspecialchars($appointment['patient_name']); ?>
                        </h6>
                        <span class="badge bg-<?php 
                            switch($appointment['status']) {
                                case 'approved':
                                    echo 'success';
                                    break;
                                case 'pending':
                                    echo 'warning';
                                    break;
                                case 'cancelled':
                                    echo 'danger';
                                    break;
                                case 'completed':
                                    echo 'info';
                                    break;
                                default:
                                    echo 'secondary';
                            }
                        ?>" data-status="<?php echo $appointment['status']; ?>">
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
                    <p class="card-text mb-1">
                        <i class="fas fa-clock me-2"></i>
                        <?php echo htmlspecialchars($appointment['duration_format']); ?>
                    </p>
                    <p class="card-text mb-2">
                        <i class="fas fa-money-bill me-2"></i>
                        ₱<?php echo number_format($appointment['service_price'], 2); ?>
                    </p>
                    <?php if(isset($appointment['notes']) && $appointment['notes']): ?>
                    <p class="card-text mb-2">
                        <i class="fas fa-sticky-note me-2"></i>
                        <?php echo htmlspecialchars($appointment['notes']); ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php if($role == "patient" && $appointment['status'] != 'cancelled' && $appointment['status'] != 'completed'): ?>
                    <div class="btn-group action-buttons w-100">
                        <a href="#" class="btn btn-sm btn-primary view-btn" data-id="<?php echo $appointment['id']; ?>" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $appointment['id']; ?>">
                            <i class="fas fa-eye"></i>
                            <span>View</span>
                        </a>
                        <a href="#" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#rescheduleModal<?php echo $appointment['id']; ?>">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Reschedule</span>
                        </a>
                        <a href="#" class="btn btn-sm btn-danger cancel-btn" data-id="<?php echo $appointment['id']; ?>">
                            <i class="fas fa-times-circle"></i>
                            <span>Cancel</span>
                        </a>
                    </div>
                    <?php elseif($role == "patient" && $appointment['status'] == 'completed'): ?>
                    <div class="btn-group action-buttons w-100">
                        <a href="#" class="btn btn-sm btn-primary view-btn" data-id="<?php echo $appointment['id']; ?>" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $appointment['id']; ?>">
                            <i class="fas fa-eye"></i>
                            <span>View</span>
                        </a>
                        <a href="patient/invoice_details.php?appointment_id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Invoice</span>
                        </a>
                    </div>
                    <?php elseif($role == "patient" && $appointment['status'] == 'cancelled'): ?>
                    <div class="btn-group action-buttons w-100">
                        <a href="#" class="btn btn-sm btn-primary view-btn" data-id="<?php echo $appointment['id']; ?>" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $appointment['id']; ?>">
                            <i class="fas fa-eye"></i>
                            <span>View</span>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($role != "patient" && $appointment['status'] == 'pending'): ?>
                    <div class="d-flex gap-2 mt-3">
                        <form method="post" class="d-inline flex-grow-1">
                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" name="update_status" class="btn btn-outline-success btn-sm w-100 d-flex align-items-center justify-content-center" style="height: 31px;">
                                <i class="fas fa-check me-2"></i>
                                <span>Approve</span>
                            </button>
                        </form>
                        <button type="button" class="btn btn-outline-primary btn-sm flex-grow-1 d-flex align-items-center justify-content-center" 
                                data-bs-toggle="modal" 
                                data-bs-target="#doctorRescheduleModal<?php echo $appointment['id']; ?>"
                                style="height: 31px;">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <span>Reschedule</span>
                        </button>
                        <form method="post" class="d-inline flex-grow-1">
                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                            <input type="hidden" name="status" value="cancelled">
                            <button type="submit" name="update_status" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center" style="height: 31px;">
                                <i class="fas fa-times me-2"></i>
                                <span>Cancel</span>
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($role != "patient" && $appointment['status'] == 'approved'): ?>
                    <div class="d-flex gap-2 mt-3">
                        <button type="button" class="btn btn-outline-primary btn-sm flex-grow-1 d-flex align-items-center justify-content-center" 
                                data-bs-toggle="modal" 
                                data-bs-target="#doctorRescheduleModal<?php echo $appointment['id']; ?>"
                                style="height: 31px;">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <span>Reschedule</span>
                        </button>
                        <form method="post" class="d-inline flex-grow-1">
                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                            <input type="hidden" name="status" value="cancelled">
                            <button type="submit" name="update_status" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center" style="height: 31px;">
                                <i class="fas fa-times me-2"></i>
                                <span>Cancel</span>
                            </button>
                        </form>
                        <form method="post" class="d-inline flex-grow-1">
                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" name="update_status" class="btn btn-outline-info btn-sm w-100 d-flex align-items-center justify-content-center" style="height: 31px;">
                                <i class="fas fa-check-double me-2"></i>
                                <span>Complete</span>
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; 
    
    // Stop execution here to return only the appointments HTML
    exit;
}

// Handle new appointment submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_appointment'])) {
    $doctor_id = $_POST['doctor_id'];
    $service_id = $_POST['service_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $patient_id = $patient['id'];

    // Debug information
    error_log("New appointment submission - Patient ID: " . $patient_id . ", Doctor ID: " . $doctor_id . ", Service ID: " . $service_id);

    // Check if patient already has 3 appointments on this date
    $check_daily_limit = "SELECT COUNT(*) as count FROM appointments 
                         WHERE patient_id = ? 
                         AND appointment_date = ? 
                         AND status != 'cancelled'";
    
    if($stmt = mysqli_prepare($conn, $check_daily_limit)){
        mysqli_stmt_bind_param($stmt, "is", $patient_id, $appointment_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $daily_count = mysqli_fetch_assoc($result);
        
        if($daily_count['count'] >= 3){
            echo "<script>
                Swal.fire({
                    title: 'Daily Booking Limit Reached',
                    text: 'You can only book up to 3 appointments per day. Please choose a different date.',
                    icon: 'warning',
                    confirmButtonColor: '#4e73df'
                });
            </script>";
        } else {
            // Validate appointment date and time
            $appointment_datetime = strtotime($appointment_date . ' ' . $appointment_time);
            $current_datetime = strtotime('now');
            $day_of_week = date('N', $appointment_datetime);
            $appointment_hour = date('H', $appointment_datetime);

            if ($day_of_week == 6) {
                echo "<script>
                    Swal.fire({
                        title: 'Invalid Day',
                        text: 'Appointments are not available on Saturdays.',
                        icon: 'error',
                        confirmButtonColor: '#4e73df'
                    });
                </script>";
            } elseif ($appointment_datetime <= $current_datetime) {
                echo "<script>
                    Swal.fire({
                        title: 'Invalid Date',
                        text: 'You cannot book appointments for past dates or the current day.',
                        icon: 'error',
                        confirmButtonColor: '#4e73df'
                    });
                </script>";
            } elseif ($appointment_hour < 9 || $appointment_hour >= 17) {
                echo "<script>
                    Swal.fire({
                        title: 'Invalid Time',
                        text: 'Appointments are only available between 9:00 AM and 5:00 PM.',
                        icon: 'error',
                        confirmButtonColor: '#4e73df'
                    });
                </script>";
            } else {
                // Get service duration
                $service_duration = 0;
                foreach ($services as $service) {
                    if ($service['id'] == $service_id) {
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
                
                if ($stmt = mysqli_prepare($conn, $check_sql)) {
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

                    if ($row['count'] > 0) {
                        echo "<script>
                            Swal.fire({
                                title: 'Time Slot Unavailable',
                                text: 'This time slot is already booked by another patient. Please choose another time.',
                                icon: 'warning',
                                confirmButtonColor: '#4e73df'
                            });
                        </script>";
                    } else {
                        // Insert the appointment
                        $sql = "INSERT INTO appointments (patient_id, doctor_id, service_id, appointment_date, appointment_time, end_time, status) 
                                VALUES (?, ?, ?, ?, ?, ?, 'pending')";
                        
                        if ($stmt = mysqli_prepare($conn, $sql)) {
                            mysqli_stmt_bind_param($stmt, "iiisss", 
                                $patient_id, 
                                $doctor_id, 
                                $service_id,
                                $appointment_date, 
                                $appointment_time,
                                $end_time
                            );
                            
                            if (mysqli_stmt_execute($stmt)) {
                                // Get service and doctor details for the success message
                                $service_name = "";
                                $doctor_name = "";
                                foreach ($services as $service) {
                                    if ($service['id'] == $service_id) {
                                        $service_name = $service['name'];
                                        break;
                                    }
                                }
                                foreach ($doctors as $doctor) {
                                    if ($doctor['id'] == $doctor_id) {
                                        $doctor_name = "Dr. " . $doctor['first_name'] . " " . $doctor['last_name'];
                                        break;
                                    }
                                }

                                echo "<script>
                                    Swal.fire({
                                        title: 'Appointment Request Submitted!',
                                        html: `
                                            <div class='text-start'>
                                                <p><strong>Service:</strong> " . $service_name . "</p>
                                                <p><strong>Doctor:</strong> " . $doctor_name . "</p>
                                                <p><strong>Date:</strong> " . date('F j, Y', strtotime($appointment_date)) . "</p>
                                                <p><strong>Time:</strong> " . date('g:i A', strtotime($appointment_time)) . "</p>
                                                <p class='mt-3'>Your appointment request has been submitted successfully!</p>
                                            </div>
                                        `,
                                        icon: 'success',
                                        confirmButtonColor: '#4e73df'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                </script>";
                            } else {
                                echo "<script>
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'There was an error submitting your appointment request. Please try again.',
                                        icon: 'error',
                                        confirmButtonColor: '#4e73df'
                                    });
                                </script>";
                            }
                        }
                    }
                }
            }
        }
    }
}

// Handle appointment rescheduling
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reschedule_appointment"])){
    $appointment_id = $_POST["appointment_id"];
    $new_date = $_POST["new_date"];
    $new_time = $_POST["new_time"];
    $doctor_id = $_POST["doctor_id"];
    $service_id = $_POST["service_id"];
    
    // For debugging
    error_log("Reschedule request: appointment_id=$appointment_id, new_date=$new_date, new_time=$new_time, doctor_id=$doctor_id, service_id=$service_id");
    
    // Validate the date and time
    $new_datetime = strtotime($new_date . ' ' . $new_time);
    $current_datetime = strtotime('now');
    $day_of_week = date('N', $new_datetime);
    $new_hour = date('H', $new_datetime);

    if ($day_of_week == 6) {
        echo json_encode(['error' => 'Appointments are not available on Saturdays.']);
        exit;
    } elseif ($new_datetime <= $current_datetime) {
        echo json_encode(['error' => 'You cannot reschedule to past dates or the current day.']);
        exit;
    } elseif ($new_hour < 9 || $new_hour >= 17) {
        echo json_encode(['error' => 'Appointments are only available between 9:00 AM and 5:00 PM.']);
        exit;
    }
    
    // Check reschedule count
    $check_count_sql = "SELECT reschedule_count FROM appointments WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $check_count_sql)){
        mysqli_stmt_bind_param($stmt, "i", $appointment_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $reschedule_data = mysqli_fetch_assoc($result);
        
        if($reschedule_data['reschedule_count'] >= 3){
            echo json_encode(['error' => 'You have reached the maximum number of reschedules (3) for this appointment.']);
            exit;
        }
        
        // Get service duration
        $service_sql = "SELECT duration FROM services WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $service_sql)){
            mysqli_stmt_bind_param($stmt, "i", $service_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $service = mysqli_fetch_assoc($result);
            
            // Calculate end time
            $end_time = date('H:i:s', strtotime($new_time . ' +' . $service['duration'] . ' minutes'));
            
            error_log("Service duration: " . $service['duration'] . ", Calculated end time: $end_time");
            
            // Check for double booking
            $check_sql = "SELECT COUNT(*) as count FROM appointments 
                         WHERE doctor_id = ? 
                         AND appointment_date = ? 
                         AND status != 'cancelled'
                         AND id != ?
                         AND (
                             (appointment_time <= ? AND end_time > ?) OR
                             (appointment_time < ? AND end_time >= ?) OR
                             (appointment_time >= ? AND appointment_time < ?)
                         )";
            
            error_log("Double booking check SQL: $check_sql");
            
            if($stmt = mysqli_prepare($conn, $check_sql)){
                mysqli_stmt_bind_param($stmt, "issssssss", 
                    $doctor_id,
                    $new_date, 
                    $appointment_id,
                    $new_time,
                    $new_time,
                    $end_time,
                    $end_time,
                    $new_time,
                    $end_time
                );
                
                error_log("Double booking check params: doctor_id=$doctor_id, new_date=$new_date, appointment_id=$appointment_id, new_time=$new_time, end_time=$end_time");
                
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                
                if($row['count'] > 0){
                    echo json_encode(['error' => 'This time slot is already booked. Please select another time.']);
                    exit;
                }
                
                // Update appointment
                $update_sql = "UPDATE appointments SET 
                             appointment_date = ?, 
                             appointment_time = ?, 
                             end_time = ?,
                             status = 'pending',
                             reschedule_count = reschedule_count + 1
                           WHERE id = ?";
                           
                error_log("Update SQL: $update_sql with params: new_date=$new_date, new_time=$new_time, end_time=$end_time, appointment_id=$appointment_id");
                           
                if($stmt = mysqli_prepare($conn, $update_sql)){
                    mysqli_stmt_bind_param($stmt, "sssi", 
                        $new_date, 
                        $new_time, 
                        $end_time, 
                        $appointment_id
                    );
                    
                    if(mysqli_stmt_execute($stmt)){
                        error_log("Reschedule successful for appointment ID: $appointment_id");
                        echo json_encode(['success' => true, 'message' => 'Appointment rescheduled successfully!']);
        } else {
                        $error = mysqli_error($conn);
                        error_log("MySQL error during reschedule: $error");
                        echo json_encode(['error' => 'Failed to update appointment: ' . $error]);
                    }
                } else {
                    $error = mysqli_error($conn);
                    error_log("MySQL prepare error: $error");
                    echo json_encode(['error' => 'Database error: ' . $error]);
                }
            } else {
                echo json_encode(['error' => 'Failed to check for double booking: ' . mysqli_error($conn)]);
            }
        } else {
            echo json_encode(['error' => 'Failed to check reschedule count: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['error' => 'Failed to check reschedule count: ' . mysqli_error($conn)]);
    }
    exit;
}

// Handle doctor reschedule suggestions
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["doctor_reschedule"])){
    $appointment_id = $_POST["appointment_id"];
    $suggested_date = $_POST["suggested_date"];
    $suggested_time = $_POST["suggested_time"];
    $notes = mysqli_real_escape_string($conn, $_POST["notes"] ?? '');
    
    // Validate the date and time
    $suggested_datetime = strtotime($suggested_date . ' ' . $suggested_time);
            $current_datetime = strtotime('now');
    $day_of_week = date('N', $suggested_datetime);
    $suggested_hour = date('H', $suggested_datetime);
            
            if ($day_of_week == 6) {
                echo "<script>
                    Swal.fire({
                        title: 'Invalid Day',
                        text: 'Appointments are not available on Saturdays.',
                        icon: 'error',
                        confirmButtonColor: '#4e73df'
                    });
                </script>";
        exit;
    } elseif ($suggested_datetime <= $current_datetime) {
                echo "<script>
                    Swal.fire({
                        title: 'Invalid Date',
                text: 'You cannot suggest past dates or the current day.',
                        icon: 'error',
                        confirmButtonColor: '#4e73df'
                    });
                </script>";
        exit;
    } elseif ($suggested_hour < 9 || $suggested_hour >= 17) {
                echo "<script>
                    Swal.fire({
                        title: 'Invalid Time',
                        text: 'Appointments are only available between 9:00 AM and 5:00 PM.',
                        icon: 'error',
                        confirmButtonColor: '#4e73df'
                    });
                </script>";
        exit;
    }
    
    // Get doctor ID
    $doctor_sql = "SELECT id FROM doctors WHERE user_id = ?";
    if($stmt = mysqli_prepare($conn, $doctor_sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $doctor_data = mysqli_fetch_assoc($result);
        $doctor_id = $doctor_data['id'] ?? null;
        
        if(!$doctor_id) {
            echo "<script>
                Swal.fire({
                    title: 'Error',
                    text: 'Doctor record not found.',
                    icon: 'error',
                    confirmButtonColor: '#4e73df'
                });
            </script>";
            exit;
            } else {
            // Create reschedule_suggestions table if it doesn't exist
            $check_table_sql = "SHOW TABLES LIKE 'reschedule_suggestions'";
            $table_exists = mysqli_query($conn, $check_table_sql)->num_rows > 0;
            
            if(!$table_exists) {
                $create_table_sql = "CREATE TABLE reschedule_suggestions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    appointment_id INT NOT NULL,
                    doctor_id INT NOT NULL,
                    patient_id INT NOT NULL,
                    suggested_date DATE NOT NULL,
                    suggested_time TIME NOT NULL,
                    notes TEXT,
                    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
                    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
                    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
                )";
                mysqli_query($conn, $create_table_sql);
            }

            // Check if double booking would occur
            $check_double_booking = "SELECT COUNT(*) as count FROM appointments 
                                     WHERE doctor_id = ? 
                                     AND appointment_date = ? 
                                     AND status != 'cancelled'
                                     AND (
                                         appointment_time = ? OR
                                         (appointment_time <= ? AND end_time > ?)
                                     )";
            
            if($stmt = mysqli_prepare($conn, $check_double_booking)){
                mysqli_stmt_bind_param($stmt, "issss", 
                    $doctor_id, 
                    $suggested_date, 
                    $suggested_time,
                    $suggested_time,
                    $suggested_time
                );
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                
                if($row['count'] > 0){
                    echo "<script>
                        Swal.fire({
                            title: 'Time Slot Unavailable',
                            text: 'This time slot is already booked. Please select another time.',
                            icon: 'warning',
                            confirmButtonColor: '#4e73df'
                        });
                    </script>";
                    exit;
                }
            }

            // Get appointment and patient details
            $check_sql = "SELECT a.*, p.id as patient_id 
                          FROM appointments a 
                          JOIN patients p ON a.patient_id = p.id 
                          WHERE a.id = ? AND a.doctor_id = ?";
            if($stmt = mysqli_prepare($conn, $check_sql)){
                mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $doctor_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if(mysqli_num_rows($result) > 0){
                    $appointment = mysqli_fetch_assoc($result);
                    
                    // Insert reschedule suggestion
                    $insert_sql = "INSERT INTO reschedule_suggestions 
                                  (appointment_id, doctor_id, patient_id, suggested_date, suggested_time, notes) 
                                  VALUES (?, ?, ?, ?, ?, ?)";
                                  
                    if($stmt = mysqli_prepare($conn, $insert_sql)){
                        mysqli_stmt_bind_param($stmt, "iiisss", 
                            $appointment_id, 
                            $doctor_id, 
                            $appointment['patient_id'],
                            $suggested_date, 
                            $suggested_time, 
                            $notes
                        );
                        
                        if(mysqli_stmt_execute($stmt)){
                            // Success - send email notification if available
                            $patient_sql = "SELECT u.email 
                                            FROM patients p 
                                            JOIN users u ON p.user_id = u.id 
                                            WHERE p.id = ?";
                            if($stmt = mysqli_prepare($conn, $patient_sql)){
                                mysqli_stmt_bind_param($stmt, "i", $appointment['patient_id']);
                                mysqli_stmt_execute($stmt);
                                $patient_result = mysqli_stmt_get_result($stmt);
                                $patient_data = mysqli_fetch_assoc($patient_result);
                                
                                // If email functions exist, send notification
                                if(function_exists('sendAppointmentEmail') && isset($patient_data['email'])){
                                    require_once "config/mail.php";
                                    $subject = 'Appointment Reschedule Suggestion - Dental Clinic';
                                    $email_body = "Your doctor has suggested to reschedule your appointment.<br><br>
                                                   Please log in to view and respond to this suggestion.";
                                    sendAppointmentEmail($patient_data['email'], $subject, $email_body);
                                }
                            }
                            
                            echo "<script>
                                Swal.fire({
                                    title: 'Success',
                                    text: 'Reschedule suggestion sent to patient.',
                                    icon: 'success',
                                    confirmButtonColor: '#4e73df'
                                }).then(() => {
                                    window.location.reload();
                                });
                            </script>";
                        } else {
                            echo "<script>
                                Swal.fire({
                                    title: 'Error',
                                    text: 'Failed to send reschedule suggestion: " . mysqli_error($conn) . "',
                                    icon: 'error',
                                    confirmButtonColor: '#4e73df'
                                });
                            </script>";
                        }
                    }
                } else {
                    echo "<script>
                        Swal.fire({
                            title: 'Error',
                            text: 'You are not authorized to reschedule this appointment.',
                            icon: 'error',
                            confirmButtonColor: '#4e73df'
                        });
                    </script>";
                }
            }
        }
    }
    exit;
}

// Handle patient reschedule form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reschedule_appointment"])){
    $appointment_id = $_POST["appointment_id"];
    $new_date = $_POST["new_date"];
    $new_time = $_POST["new_time"];
    $doctor_id = $_POST["doctor_id"];
    $service_id = $_POST["service_id"];
    
    // Validate the date and time
    $new_datetime = strtotime($new_date . ' ' . $new_time);
    $current_datetime = strtotime('now');
    $day_of_week = date('N', $new_datetime);
    $new_hour = date('H', $new_datetime);

    if ($day_of_week == 6) {
        echo json_encode(['error' => 'Appointments are not available on Saturdays.']);
        exit;
    } elseif ($new_datetime <= $current_datetime) {
        echo json_encode(['error' => 'You cannot reschedule to past dates or the current day.']);
        exit;
    } elseif ($new_hour < 9 || $new_hour >= 17) {
        echo json_encode(['error' => 'Appointments are only available between 9:00 AM and 5:00 PM.']);
        exit;
    }
    
    // Check reschedule count
    $check_count_sql = "SELECT reschedule_count FROM appointments WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $check_count_sql)){
                    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $reschedule_data = mysqli_fetch_assoc($result);
        
        if($reschedule_data['reschedule_count'] >= 3){
            echo json_encode(['error' => 'You have reached the maximum number of reschedules (3) for this appointment.']);
            exit;
        }
        
        // Get service duration
        $service_sql = "SELECT duration FROM services WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $service_sql)){
            mysqli_stmt_bind_param($stmt, "i", $service_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $service = mysqli_fetch_assoc($result);
                    
                    // Calculate end time
                    $end_time = date('H:i:s', strtotime($new_time . ' +' . $service['duration'] . ' minutes'));
                    
                    // Check for double booking
                    $check_sql = "SELECT COUNT(*) as count FROM appointments 
                             WHERE doctor_id = ? 
                             AND appointment_date = ? 
                             AND status != 'cancelled'
                             AND id != ?
                         AND (
                             appointment_time = ? OR
                             (appointment_time <= ? AND end_time > ?)
                         )";
                    
                    if($stmt = mysqli_prepare($conn, $check_sql)){
                mysqli_stmt_bind_param($stmt, "isssss", 
                    $doctor_id,
                            $new_date, 
                            $appointment_id,
                    $new_time,
                    $new_time,
                            $new_time
                        );
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        $row = mysqli_fetch_assoc($result);
                        
                        if($row['count'] > 0){
                    echo json_encode(['error' => 'This time slot is already booked. Please select another time.']);
                    exit;
                }
                
                // Update appointment
                            $update_sql = "UPDATE appointments SET 
                                     appointment_date = ?, 
                                     appointment_time = ?, 
                                     end_time = ?,
                                     status = 'pending',
                                     reschedule_count = reschedule_count + 1
                                     WHERE id = ?";
                           
                error_log("Update SQL: $update_sql with params: new_date=$new_date, new_time=$new_time, end_time=$end_time, appointment_id=$appointment_id");
                           
                            if($stmt = mysqli_prepare($conn, $update_sql)){
                    mysqli_stmt_bind_param($stmt, "sssi", 
                        $new_date, 
                        $new_time, 
                        $end_time, 
                        $appointment_id
                    );
                    
                                if(mysqli_stmt_execute($stmt)){
                        error_log("Reschedule successful for appointment ID: $appointment_id");
                        echo json_encode(['success' => true, 'message' => 'Appointment rescheduled successfully!']);
                    } else {
                        $error = mysqli_error($conn);
                        error_log("MySQL error during reschedule: $error");
                        echo json_encode(['error' => 'Failed to update appointment: ' . $error]);
                    }
                } else {
                    $error = mysqli_error($conn);
                    error_log("MySQL prepare error: $error");
                    echo json_encode(['error' => 'Database error: ' . $error]);
                }
            } else {
                echo json_encode(['error' => 'Failed to check for double booking: ' . mysqli_error($conn)]);
            }
        } else {
            echo json_encode(['error' => 'Failed to get service information: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['error' => 'Failed to check reschedule count: ' . mysqli_error($conn)]);
    }
    exit;
}

// Handle patient responses to reschedule suggestions
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["respond_reschedule"])){
    $suggestion_id = $_POST["suggestion_id"];
    $response = $_POST["response"]; // 'accept' or 'decline'
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    // Function to handle success response
    function respond_success($message, $is_ajax) {
        if($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $message]);
            exit;
        } else {
                                    echo "<script>
                                        Swal.fire({
                    title: 'Success',
                    text: '$message',
                                            icon: 'success',
                                            confirmButtonColor: '#4e73df'
                }).then(() => {
                                            window.location.href = 'appointments.php';
                                        });
                                    </script>";
            exit;
        }
    }
    
    // Function to handle error response
    function respond_error($message, $is_ajax) {
        if($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $message]);
            exit;
                                } else {
                                    echo "<script>
                                        Swal.fire({
                    title: 'Error',
                    text: '$message',
                                            icon: 'error',
                                            confirmButtonColor: '#4e73df'
                                        });
                                    </script>";
            exit;
        }
    }
    
    // Get the suggestion details
    $get_sql = "SELECT rs.*, a.service_id, a.patient_id, a.doctor_id, a.appointment_date, a.appointment_time,
                       s.duration, d.user_id as doctor_user_id
                FROM reschedule_suggestions rs
                JOIN appointments a ON rs.appointment_id = a.id
                JOIN services s ON a.service_id = s.id
                JOIN doctors d ON a.doctor_id = d.id
                WHERE rs.id = ?";
    
    if($stmt = mysqli_prepare($conn, $get_sql)){
        mysqli_stmt_bind_param($stmt, "i", $suggestion_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if($row = mysqli_fetch_assoc($result)){
            $appointment_id = $row['appointment_id'];
            
            // Check if response is valid
            if($response !== 'accept' && $response !== 'decline'){
                respond_error('Invalid response. Please try again.', $is_ajax);
            }
            
            // Check if the suggestion is still pending
            if($row['status'] !== 'pending'){
                respond_error('This reschedule suggestion has already been processed.', $is_ajax);
            }
            
            // Check if the appointment still exists and is not cancelled
            $check_appointment_sql = "SELECT status FROM appointments WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $check_appointment_sql)){
                mysqli_stmt_bind_param($stmt, "i", $appointment_id);
                mysqli_stmt_execute($stmt);
                $appointment_result = mysqli_stmt_get_result($stmt);
                $appointment_data = mysqli_fetch_assoc($appointment_result);
                
                if(!$appointment_data || $appointment_data['status'] === 'cancelled'){
                    respond_error('The appointment associated with this suggestion no longer exists or has been cancelled.', $is_ajax);
                }
            }
            
            if($response === 'accept'){
                // Check if the suggested time is still available
                $suggested_date = $row['suggested_date'];
                $suggested_time = $row['suggested_time'];
                $doctor_id = $row['doctor_id'];
                $duration = $row['duration'];
                
                // Calculate end time
                $end_time = date('H:i:s', strtotime($suggested_time . ' +' . $duration . ' minutes'));
                
                // Check for double booking
                $check_sql = "SELECT COUNT(*) as count FROM appointments 
                              WHERE doctor_id = ? 
                              AND appointment_date = ? 
                              AND status != 'cancelled'
                              AND id != ?
                              AND (
                                  appointment_time = ? OR
                                  (appointment_time <= ? AND end_time > ?)
                              )";
                
                if($stmt = mysqli_prepare($conn, $check_sql)){
                    mysqli_stmt_bind_param($stmt, "isssss", 
                        $doctor_id,
                        $suggested_date, 
                        $appointment_id,
                        $suggested_time,
                        $suggested_time,
                        $suggested_time
                    );
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $check_row = mysqli_fetch_assoc($result);
                    
                    if($check_row['count'] > 0){
                        respond_error('This time slot is no longer available. Please contact the clinic for assistance.', $is_ajax);
                    }
                }
                
                // Update the appointment with new date/time
                $update_sql = "UPDATE appointments 
                               SET appointment_date = ?, 
                                     appointment_time = ?, 
                                     end_time = ?,
                                   status = 'approved',
                                     reschedule_count = reschedule_count + 1
                                     WHERE id = ?";
                              
                            if($stmt = mysqli_prepare($conn, $update_sql)){
                    mysqli_stmt_bind_param($stmt, "sssi", 
                        $suggested_date, 
                        $suggested_time, 
                        $end_time,
                        $appointment_id
                    );
                    
                                if(mysqli_stmt_execute($stmt)){
                        // Update the suggestion status
                        $update_suggestion = "UPDATE reschedule_suggestions SET status = 'accepted' WHERE id = ?";
                        $stmt = mysqli_prepare($conn, $update_suggestion);
                        mysqli_stmt_bind_param($stmt, "i", $suggestion_id);
                        
                        if(!mysqli_stmt_execute($stmt)) {
                            error_log("Error updating reschedule suggestion status: " . mysqli_error($conn));
                        }
                        
                        // Send email notification to doctor if email functions are available
                        if(function_exists('sendAppointmentEmail')){
                            require_once "config/mail.php";
                            
                            // Get doctor's email
                            $doctor_email_sql = "SELECT u.email 
                                                FROM users u 
                                                WHERE u.id = ?";
                            if($stmt = mysqli_prepare($conn, $doctor_email_sql)){
                                mysqli_stmt_bind_param($stmt, "i", $row['doctor_user_id']);
                                mysqli_stmt_execute($stmt);
                                $doctor_email_result = mysqli_stmt_get_result($stmt);
                                $doctor_email_data = mysqli_fetch_assoc($doctor_email_result);
                                
                                if(isset($doctor_email_data['email'])){
                                    $subject = 'Reschedule Suggestion Accepted - Dental Clinic';
                                    $email_body = "The patient has accepted your suggested reschedule.<br><br>
                                                   New appointment details:<br>
                                                   Date: " . date('F j, Y', strtotime($suggested_date)) . "<br>
                                                   Time: " . date('g:i A', strtotime($suggested_time)) . "<br><br>
                                                   Please check your dashboard for more details.";
                                    sendAppointmentEmail($doctor_email_data['email'], $subject, $email_body);
                                }
                            }
                        }
                        
                                    echo "<script>
                                        Swal.fire({
                                title: 'Success',
                                text: 'You have accepted the reschedule suggestion. Your appointment has been updated.',
                                            icon: 'success',
                                            confirmButtonColor: '#4e73df'
                    }).then(() => {
                        window.location.href = 'appointments.php';
                                });
                            </script>";
                            exit; // Stop further execution
                                } else {
                                    respond_error('Failed to update your appointment. Please try again or contact support.', $is_ajax);
                                }
                            }
            } else {
                // Decline the suggestion
                $update_suggestion = "UPDATE reschedule_suggestions SET status = 'declined' WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_suggestion);
                mysqli_stmt_bind_param($stmt, "i", $suggestion_id);
                
                if(mysqli_stmt_execute($stmt)){
                    // Send email notification to doctor if email functions are available
                    if(function_exists('sendAppointmentEmail')){
                        require_once "config/mail.php";
                        
                        // Get doctor's email
                        $doctor_email_sql = "SELECT u.email 
                                            FROM users u 
                                            WHERE u.id = ?";
                        if($stmt = mysqli_prepare($conn, $doctor_email_sql)){
                            mysqli_stmt_bind_param($stmt, "i", $row['doctor_user_id']);
                            mysqli_stmt_execute($stmt);
                            $doctor_email_result = mysqli_stmt_get_result($stmt);
                            $doctor_email_data = mysqli_fetch_assoc($doctor_email_result);
                            
                            if(isset($doctor_email_data['email'])){
                                $subject = 'Reschedule Suggestion Declined - Dental Clinic';
                                $email_body = "The patient has declined your suggested reschedule.<br><br>
                                               The original appointment on " . date('F j, Y', strtotime($row['appointment_date'])) . " at " . 
                                               date('g:i A', strtotime($row['appointment_time'])) . " remains unchanged.<br><br>
                                               Please check your dashboard for more details.";
                                sendAppointmentEmail($doctor_email_data['email'], $subject, $email_body);
                            }
                        }
                    }
                    
                    echo "<script>
                        Swal.fire({
                            title: 'Declined',
                            text: 'You have declined the reschedule suggestion. The original appointment remains unchanged.',
                            icon: 'info',
                            confirmButtonColor: '#4e73df'
                        }).then(() => {
                            window.location.href = 'appointments.php';
                        });
                    </script>";
                    exit; // Stop further execution
                } else {
                    respond_error('Failed to process your response. Please try again or contact support.', $is_ajax);
                }
            }
        } else {
            respond_error('The reschedule suggestion could not be found.', $is_ajax);
        }
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Dental Clinic Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fc;
        }
        .sidebar {
            min-height: 100vh;
            background: #4e73df;
            color: white;
            padding: 1rem;
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
        .canceled-appointment {
            opacity: 0.6;
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
        
        /* Empty state styles */
        .empty-state {
            padding: 30px;
            border-radius: 8px;
            background-color: #f8f9fc;
            border: 1px dashed #d1d3e2;
        }
        
        .empty-state i {
            color: #b7b9cc;
        }
        
        .empty-state h4 {
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .empty-state p {
            font-size: 1rem;
            color: #858796;
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
                    <?php endif; ?>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-2"></i> Dashboard
                        </a>
                    </li>
                    <?php if($role == "patient"): ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="appointments.php">
                            <i class="fas fa-calendar-alt me-2"></i> My Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-2"></i> My Profile
                        </a>
                    </li>
                    <?php elseif($role == "doctor"): ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="appointments.php">
                            <i class="fas fa-calendar-check me-2"></i> Patient Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user-md me-2"></i> Doctor Profile
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="appointments.php">
                            <i class="fas fa-calendar me-2"></i> All Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="doctors.php">
                            <i class="fas fa-user-md me-2"></i> Manage Doctors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="patients.php">
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
                    <h2>Appointments</h2>
                    <?php if($role == "patient"): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAppointmentModal">
                        <i class="fas fa-plus me-2"></i> New Appointment
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Appointments List -->
                <div class="card">
                    <div class="card-body">
                        <div class="row" id="appointmentsList">
                            <?php if(empty($appointments)): ?>
                            <div class="col-12 text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">No Appointments Found</h4>
                                    <?php if($role == "patient"): ?>
                                    <p class="text-muted mb-3">You don't have any appointments scheduled yet.</p>
                                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAppointmentModal">
                                        <i class="fas fa-plus me-2"></i> Schedule Your First Appointment
                                    </a>
                                    <?php else: ?>
                                    <p class="text-muted">There are no appointments available at this time.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php else: ?>
                            <?php foreach($appointments as $appointment): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card appointment-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="card-title mb-0">
                                                <?php echo $role == "patient" ? htmlspecialchars($appointment['doctor_name']) : htmlspecialchars($appointment['patient_name']); ?>
                                            </h6>
                                            <span class="badge bg-<?php 
                                                switch($appointment['status']) {
                                                    case 'approved':
                                                        echo 'success';
                                                        break;
                                                    case 'pending':
                                                        echo 'warning';
                                                        break;
                                                    case 'cancelled':
                                                        echo 'danger';
                                                        break;
                                                    case 'completed':
                                                        echo 'info';
                                                        break;
                                                    default:
                                                        echo 'secondary';
                                                }
                                            ?>" data-status="<?php echo $appointment['status']; ?>">
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
                                        <p class="card-text mb-1">
                                            <i class="fas fa-clock me-2"></i>
                                            <?php echo htmlspecialchars($appointment['duration_format']); ?>
                                        </p>
                                        <p class="card-text mb-2">
                                            <i class="fas fa-money-bill me-2"></i>
                                            ₱<?php echo number_format($appointment['service_price'], 2); ?>
                                        </p>
                                        <?php if(isset($appointment['notes']) && $appointment['notes']): ?>
                                        <p class="card-text mb-2">
                                            <i class="fas fa-sticky-note me-2"></i>
                                            <?php echo htmlspecialchars($appointment['notes']); ?>
                                        </p>
                                        <?php endif; ?>
                                        
                                        <?php if($role == "patient" && $appointment['status'] != 'cancelled' && $appointment['status'] != 'completed'): ?>
                                        <div class="btn-group action-buttons w-100">
                                            <a href="#" class="btn btn-sm btn-primary view-btn" data-id="<?php echo $appointment['id']; ?>" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $appointment['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                                <span>View</span>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#rescheduleModal<?php echo $appointment['id']; ?>">
                                                <i class="fas fa-calendar-alt"></i>
                                                <span>Reschedule</span>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-danger cancel-btn" data-id="<?php echo $appointment['id']; ?>">
                                                <i class="fas fa-times-circle"></i>
                                                <span>Cancel</span>
                                            </a>
                                        </div>
                                        <?php elseif($role == "patient" && $appointment['status'] == 'completed'): ?>
                                        <div class="btn-group action-buttons w-100">
                                            <a href="#" class="btn btn-sm btn-primary view-btn" data-id="<?php echo $appointment['id']; ?>" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $appointment['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                                <span>View</span>
                                            </a>
                                            <a href="patient/invoice_details.php?appointment_id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-file-invoice-dollar"></i>
                                                <span>Invoice</span>
                                            </a>
                                        </div>
                                        <?php elseif($role == "patient" && $appointment['status'] == 'cancelled'): ?>
                                        <div class="btn-group action-buttons w-100">
                                            <a href="#" class="btn btn-sm btn-primary view-btn" data-id="<?php echo $appointment['id']; ?>" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $appointment['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                                <span>View</span>
                                            </a>
                                        </div>
                                        <?php endif; ?>
                    
                                        <?php if($role != "patient" && $appointment['status'] == 'pending'): ?>
                                        <div class="d-flex gap-2 mt-3">
                                            <form method="post" class="d-inline flex-grow-1">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" name="update_status" class="btn btn-outline-success btn-sm w-100 d-flex align-items-center justify-content-center" style="height: 31px;">
                                                    <i class="fas fa-check me-2"></i>
                                                    <span>Approve</span>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-outline-primary btn-sm flex-grow-1 d-flex align-items-center justify-content-center" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#doctorRescheduleModal<?php echo $appointment['id']; ?>"
                                                    style="height: 31px;">
                                                <i class="fas fa-calendar-alt me-2"></i>
                                                <span>Reschedule</span>
                                            </button>
                                            <form method="post" class="d-inline flex-grow-1">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" name="update_status" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center" style="height: 31px;">
                                                    <i class="fas fa-times me-2"></i>
                                                    <span>Cancel</span>
                                                </button>
                                            </form>
                                        </div>
                                        <?php endif; ?>
                    
                                        <?php if($role != "patient" && $appointment['status'] == 'approved'): ?>
                                        <div class="d-flex gap-2 mt-3">
                                            <button type="button" class="btn btn-outline-primary btn-sm flex-grow-1 d-flex align-items-center justify-content-center" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#doctorRescheduleModal<?php echo $appointment['id']; ?>"
                                                    style="height: 31px;">
                                                <i class="fas fa-calendar-alt me-2"></i>
                                                <span>Reschedule</span>
                                            </button>
                                            <form method="post" class="d-inline flex-grow-1">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" name="update_status" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center" style="height: 31px;">
                                                    <i class="fas fa-times me-2"></i>
                                                    <span>Cancel</span>
                                                </button>
                                            </form>
                                            <form method="post" class="d-inline flex-grow-1">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" name="update_status" class="btn btn-outline-info btn-sm w-100 d-flex align-items-center justify-content-center" style="height: 31px;">
                                                    <i class="fas fa-check-double me-2"></i>
                                                    <span>Complete</span>
                                                </button>
                                            </form>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Appointment Modal -->
    <div class="modal fade" id="newAppointmentModal" tabindex="-1" aria-labelledby="newAppointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newAppointmentModalLabel">New Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                    <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle"></i> Booking Limits:
                        <ul class="mb-0">
                            <li>Maximum of 3 appointments per day</li>
                            <li>Appointments are not available on Saturdays</li>
                            <li>You cannot book appointments for past dates or the current day</li>
                            <li>Appointments are only available between 9:00 AM and 5:00 PM</li>
                        </ul>
                    </div>
                    <form id="newAppointmentForm" method="post" action="appointments.php">
                        <input type="hidden" name="new_appointment" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label">Select Service</label>
                            <select class="form-select" name="service_id" required>
                                <option value="">Choose a service...</option>
                                <?php if (!empty($services)): ?>
                                <?php foreach($services as $service): ?>
                                    <option value="<?php echo htmlspecialchars($service['id']); ?>" 
                                            data-duration="<?php echo htmlspecialchars($service['duration']); ?>"
                                            data-price="<?php echo htmlspecialchars($service['price']); ?>">
                                    <?php echo htmlspecialchars($service['name']); ?> 
                                        (<?php echo htmlspecialchars($service['duration_format']); ?> - ₱<?php echo number_format($service['price'], 2); ?>)
                                </option>
                                <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>No services available</option>
                                <?php endif; ?>
                            </select>
                            <?php if (empty($services)): ?>
                                <div class="alert alert-warning mt-2">
                                    <i class="fas fa-exclamation-triangle"></i> No services are currently available. Please try again later.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Select Doctor</label>
                            <select class="form-select" name="doctor_id" required>
                                <option value="">Choose a doctor...</option>
                                <?php if (!empty($doctors)): ?>
                                <?php foreach($doctors as $doctor): ?>
                                    <option value="<?php echo htmlspecialchars($doctor['id']); ?>">
                                    Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                        <?php if(isset($doctor['specialization']) && $doctor['specialization']): ?>
                                            (<?php echo htmlspecialchars($doctor['specialization']); ?>)
                                        <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>No doctors available</option>
                                <?php endif; ?>
                            </select>
                            <?php if (empty($doctors)): ?>
                                <div class="alert alert-warning mt-2">
                                    <i class="fas fa-exclamation-triangle"></i> No doctors are currently available. Please try again later.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Appointment Date</label>
                            <input type="date" class="form-control" name="appointment_date" required 
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
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

                        <button type="submit" class="btn btn-primary" name="submit_appointment">Submit Appointment Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reschedule Modals -->
    <?php foreach($appointments as $appointment): ?>
    <div class="modal fade" id="rescheduleModal<?php echo $appointment['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-calendar-alt me-2"></i>Reschedule Appointment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <?php
                    // Check reschedule count before showing form
                    $check_count_sql = "SELECT reschedule_count FROM appointments WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $check_count_sql);
                    mysqli_stmt_bind_param($stmt, "i", $appointment['id']);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $reschedule_data = mysqli_fetch_assoc($result);
                    
                    if($reschedule_data['reschedule_count'] >= 3):
                    ?>
                        <div class="alert alert-warning border-0 shadow-sm">
                            <div class="d-flex align-items-center">
                                <div class="p-2 bg-warning bg-opacity-25 rounded-circle me-3">
                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Maximum Reschedules Reached</h6>
                                    <p class="mb-0">You have reached the maximum number of reschedules (3) for this appointment. 
                                    Please contact the clinic for assistance.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="p-2 rounded-circle bg-primary bg-opacity-10 me-3">
                                    <i class="fas fa-info-circle text-primary"></i>
                        </div>
                                <h6 class="mb-0">Current Appointment Details</h6>
                            </div>
                            <div class="row g-0 border rounded-3 p-3 mb-3 bg-light">
                                <div class="col-md-4 border-end border-2 pe-3">
                                    <small class="text-muted d-block">Date</small>
                                    <strong><?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></strong>
                                </div>
                                <div class="col-md-4 border-end border-2 px-3">
                                    <small class="text-muted d-block">Time</small>
                                    <strong><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></strong>
                                </div>
                                <div class="col-md-4 ps-3">
                                    <small class="text-muted d-block">Service</small>
                                    <strong><?php echo htmlspecialchars($appointment['service_name']); ?></strong>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-success rounded-pill me-2">
                                    <i class="fas fa-sync-alt me-1"></i>
                                    <?php echo (3 - $reschedule_data['reschedule_count']); ?>
                                </span>
                                <span class="text-muted small">Reschedules remaining</span>
                            </div>
                        </div>
                        
                        <form method="post" action="" class="rescheduleForm" data-appointment-id="<?php echo $appointment['id']; ?>">
                            <input type="hidden" name="reschedule_appointment" value="1">
                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                            <input type="hidden" name="doctor_id" value="<?php echo $appointment['doctor_id']; ?>">
                            <input type="hidden" name="service_id" value="<?php echo $appointment['service_id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold"><i class="fas fa-calendar me-2"></i>New Date</label>
                                <input type="date" class="form-control form-control-lg border-0 shadow-sm" name="new_date" required 
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                <div class="form-text">Appointments are not available on Saturdays.</div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="fas fa-clock me-2"></i>New Time</label>
                                <select class="form-select form-select-lg border-0 shadow-sm" name="new_time" required>
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
                            
                            <div class="alert alert-info border-0 shadow-sm mb-4">
                                <div class="d-flex">
                                    <div class="p-2 bg-info bg-opacity-25 rounded-circle me-3">
                                        <i class="fas fa-info-circle text-info"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-2">Appointment Policy</h6>
                                        <ul class="mb-0 ps-3 small">
                                    <li>Appointments are not available on Saturdays</li>
                                    <li>You cannot reschedule to past dates or the current day</li>
                                    <li>Appointments are only available between 9:00 AM and 5:00 PM</li>
                                </ul>
                                    </div>
                                </div>
                    </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" style="background: linear-gradient(to right, #4e73df, #2e59d9); border: none; box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);">
                                    <i class="fas fa-calendar-check me-2"></i>Reschedule Appointment
                                </button>
                            </div>
                </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Doctor Reschedule Modals -->
    <?php foreach($appointments as $appointment): ?>
    <?php if($role == "doctor" && ($appointment['status'] == 'pending' || $appointment['status'] == 'approved')): ?>
    <div class="modal fade" id="doctorRescheduleModal<?php echo $appointment['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-calendar-alt me-2"></i>Suggest Reschedule to Patient</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="post" action="" class="doctorRescheduleForm">
                        <input type="hidden" name="doctor_reschedule" value="1">
                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="p-2 rounded-circle bg-primary bg-opacity-10 me-3">
                                    <i class="fas fa-user-clock text-primary"></i>
                                </div>
                                <h6 class="mb-0">Appointment with <strong><?php echo htmlspecialchars($appointment['patient_name']); ?></strong></h6>
                            </div>
                        
                            <div class="row g-0 border rounded-3 p-3 mb-3 bg-light">
                                <div class="col-md-4 border-end border-2 pe-3">
                                    <small class="text-muted d-block">Current Date</small>
                                    <strong><?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></strong>
                                </div>
                                <div class="col-md-4 border-end border-2 px-3">
                                    <small class="text-muted d-block">Current Time</small>
                                    <strong><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></strong>
                                </div>
                                <div class="col-md-4 ps-3">
                                    <small class="text-muted d-block">Service</small>
                                    <strong><?php echo htmlspecialchars($appointment['service_name']); ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-calendar me-2"></i>Suggested Date</label>
                            <input type="date" class="form-control form-control-lg border-0 shadow-sm" name="suggested_date" required 
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            <div class="form-text">Appointments are not available on Saturdays.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-clock me-2"></i>Suggested Time</label>
                            <select class="form-select form-select-lg border-0 shadow-sm" name="suggested_time" required>
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
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="fas fa-comment me-2"></i>Reason for Rescheduling</label>
                            <textarea class="form-control border-0 shadow-sm" name="notes" rows="3" placeholder="Explain why you're suggesting a reschedule" required></textarea>
                            <div class="form-text">Please provide a clear explanation to help the patient understand the need for rescheduling.</div>
                        </div>
                        
                        <div class="alert alert-warning border-0 shadow-sm mb-4">
                            <div class="d-flex">
                                <div class="p-2 bg-warning bg-opacity-25 rounded-circle me-3">
                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="mb-2">Important Information</h6>
                                    <p class="mb-0">The patient will receive a notification and must accept this reschedule suggestion for it to take effect. The original appointment will remain active until then.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" style="background: linear-gradient(to right, #4e73df, #2e59d9); border: none; box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);">
                                <i class="fas fa-paper-plane me-2"></i>Send Reschedule Suggestion
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>

    <!-- Patient Reschedule Suggestion Notifications -->
    <?php if($role == "patient"): ?>
    <?php
        // Get pending reschedule suggestions for this patient
        $suggestion_sql = "SELECT rs.*, a.appointment_date, a.appointment_time, a.service_id,
                            CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                            s.name as service_name
                          FROM reschedule_suggestions rs
                          JOIN appointments a ON rs.appointment_id = a.id
                          JOIN doctors d ON rs.doctor_id = d.id
                          JOIN users u ON d.user_id = u.id
                          JOIN services s ON a.service_id = s.id
                          WHERE rs.patient_id = ? AND rs.status = 'pending'";
        
        if($stmt = mysqli_prepare($conn, $suggestion_sql)){
            mysqli_stmt_bind_param($stmt, "i", $patient['id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $reschedule_suggestions = mysqli_fetch_all($result, MYSQLI_ASSOC);
            
            if(!empty($reschedule_suggestions)):
                foreach($reschedule_suggestions as $suggestion):
    ?>
    <div class="modal fade" id="suggestionModal<?php echo $suggestion['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-calendar-alt me-2"></i>Reschedule Suggestion from Doctor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex align-items-center mb-4">
                        <div class="p-3 rounded-circle bg-primary bg-opacity-10 me-3">
                            <i class="fas fa-user-md text-primary"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Request from your doctor</h6>
                            <p class="mb-0 text-primary"><strong>Dr. <?php echo htmlspecialchars($suggestion['doctor_name']); ?></strong> has suggested to reschedule your appointment</p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="d-flex align-items-center border-bottom pb-2 mb-3">
                            <span class="badge bg-secondary rounded-pill me-2"><i class="fas fa-calendar-day"></i></span>
                            Current Appointment
                        </h6>
                        <div class="row g-0 border rounded-3 p-3 bg-light">
                            <div class="col-md-4 border-end border-2 pe-3">
                                <small class="text-muted d-block">Date</small>
                                <strong><?php echo date('F j, Y', strtotime($suggestion['appointment_date'])); ?></strong>
                            </div>
                            <div class="col-md-4 border-end border-2 px-3">
                                <small class="text-muted d-block">Time</small>
                                <strong><?php echo date('g:i A', strtotime($suggestion['appointment_time'])); ?></strong>
                            </div>
                            <div class="col-md-4 ps-3">
                                <small class="text-muted d-block">Service</small>
                                <strong><?php echo htmlspecialchars($suggestion['service_name']); ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="d-flex align-items-center border-bottom pb-2 mb-3">
                            <span class="badge bg-success rounded-pill me-2"><i class="fas fa-calendar-check"></i></span>
                            Suggested New Schedule
                        </h6>
                        <div class="row g-0 border border-success rounded-3 p-3 bg-success bg-opacity-10">
                            <div class="col-md-6 border-end border-2 pe-3">
                                <small class="text-muted d-block">New Date</small>
                                <strong class="text-success"><?php echo date('F j, Y', strtotime($suggestion['suggested_date'])); ?></strong>
                            </div>
                            <div class="col-md-6 ps-3">
                                <small class="text-muted d-block">New Time</small>
                                <strong class="text-success"><?php echo date('g:i A', strtotime($suggestion['suggested_time'])); ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <?php if(!empty($suggestion['notes'])): ?>
                    <div class="mb-4">
                        <h6 class="d-flex align-items-center border-bottom pb-2 mb-3">
                            <span class="badge bg-info rounded-pill me-2"><i class="fas fa-comment-medical"></i></span>
                            Reason for Rescheduling
                        </h6>
                        <div class="border-start border-info ps-3 py-2">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($suggestion['notes'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form method="post" action="" id="respondRescheduleForm<?php echo $suggestion['id']; ?>" class="respondRescheduleForm">
                        <input type="hidden" name="respond_reschedule" value="1">
                        <input type="hidden" name="suggestion_id" value="<?php echo $suggestion['id']; ?>">
                        <div class="d-flex gap-2">
                            <button type="button" onclick="respondToReschedule('<?php echo $suggestion['id']; ?>', 'accept')" class="btn btn-success flex-grow-1" style="box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);">
                                <i class="fas fa-check me-2"></i>
                                Accept Reschedule
                            </button>
                            <button type="button" onclick="respondToReschedule('<?php echo $suggestion['id']; ?>', 'decline')" class="btn btn-outline-danger flex-grow-1" style="box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);">
                                <i class="fas fa-times me-2"></i>
                                Decline Reschedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php 
                endforeach; 
            endif;
        }
    ?>

    <!-- Show notification for patients with pending reschedule suggestions -->
    <script>
        $(document).ready(function() {
            <?php if($role == "patient" && !empty($reschedule_suggestions)): ?>
            Swal.fire({
                title: '<i class="fas fa-calendar-alt text-primary me-2"></i>Reschedule Requests',
                html: `
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-2 bg-primary bg-opacity-10 rounded-circle me-3">
                            <i class="fas fa-bell text-primary"></i>
                        </div>
                        <div class="text-start">
                            <h6 class="mb-1">You have ${<?php echo count($reschedule_suggestions); ?>} pending reschedule request(s)</h6>
                            <p class="small text-muted mb-0">Your doctor has suggested new appointment times</p>
                        </div>
                    </div>
                `,
                iconHtml: '<i class="fas fa-calendar-alt" style="color:#4e73df"></i>',
                customClass: {
                    icon: 'no-border',
                    confirmButton: 'btn btn-primary px-4'
                },
                buttonsStyling: false,
                confirmButtonText: '<i class="fas fa-eye me-2"></i>View Requests',
                showCloseButton: true,
                background: '#fff',
                backdrop: `rgba(38, 46, 64, 0.4)`,
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#suggestionModal<?php echo $reschedule_suggestions[0]['id']; ?>').modal('show');
                }
            });
            <?php endif; ?>
        });
    </script>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    
    <script>
        // Function to handle reschedule suggestion responses
        function respondToReschedule(suggestionId, response) {
            // Confirm before submitting
            let confirmTitle = response === 'accept' ? 'Accept Reschedule?' : 'Decline Reschedule?';
            let confirmText = response === 'accept' ? 
                'Are you sure you want to accept this reschedule suggestion? Your appointment will be updated with the new date and time.' : 
                'Are you sure you want to decline this reschedule suggestion? The original appointment will remain unchanged.';
            
            Swal.fire({
                title: confirmTitle,
                text: confirmText,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: response === 'accept' ? '#28a745' : '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: response === 'accept' ? 'Yes, Accept' : 'Yes, Decline',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading indicator
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we process your response.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Prepare data for AJAX request
                    let formData = new FormData();
                    formData.append('respond_reschedule', '1');
                    formData.append('suggestion_id', suggestionId);
                    formData.append('response', response);
                    
                    // Send AJAX request
                    fetch('appointments.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(data => {
                        try {
                            // Try to parse as JSON
                            const jsonData = JSON.parse(data);
                            if (jsonData.success) {
                                Swal.fire({
                                    title: 'Success',
                                    text: jsonData.message,
                                    icon: 'success',
                                    confirmButtonColor: '#4e73df'
                                }).then(() => {
                                    window.location.href = 'appointments.php';
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: jsonData.error || 'An error occurred',
                                    icon: 'error',
                                    confirmButtonColor: '#4e73df'
                                });
                            }
                        } catch (e) {
                            // If it's not valid JSON, just redirect to refresh the page
                            console.log("Response is not JSON, refreshing page:", data);
                            window.location.href = 'appointments.php';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'There was a problem processing your request. Please try again.',
                            icon: 'error',
                            confirmButtonColor: '#4e73df'
                        });
                    });
                }
            });
        }
        
        $(document).ready(function() {
            // Auto refresh appointments list every 30 seconds
            function loadAppointments() {
                $.ajax({
                    url: 'appointments.php?fetch_appointments=true',
                    type: 'GET',
                    success: function(response) {
                        $('#appointmentsList').html(response);
                    }
                });
            }

            // Initial load and set interval
            loadAppointments();
            setInterval(loadAppointments, 30000);
            
            // Doctor reschedule form submission
            $('.doctorRescheduleForm').on('submit', function(e) {
                e.preventDefault();
                
                var form = $(this);
                var formData = form.serialize();
                
                // Validate the form
                var suggestedDate = form.find('input[name="suggested_date"]').val();
                var suggestedTime = form.find('select[name="suggested_time"]').val();
                var notes = form.find('textarea[name="notes"]').val();
                
                if (!suggestedDate || !suggestedTime || !notes) {
                    Swal.fire({
                        title: 'Missing Information',
                        text: 'Please fill in all required fields',
                        icon: 'warning',
                        confirmButtonColor: '#4e73df'
                    });
                    return;
                }
                
                // Check date is not a Saturday (day 6)
                var selectedDate = new Date(suggestedDate);
                if (selectedDate.getDay() === 6) {
                        Swal.fire({
                        title: 'Invalid Day',
                        text: 'Appointments are not available on Saturdays.',
                        icon: 'error',
                            confirmButtonColor: '#4e73df'
                        });
                    return;
                }
                
                // Confirm before submitting
                Swal.fire({
                    title: 'Confirm Reschedule Suggestion',
                    text: 'Are you sure you want to suggest this new schedule to the patient?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#4e73df',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, suggest it'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'appointments.php',
                            type: 'POST',
                            data: formData,
                            success: function(response) {
                                // Check if response contains success/error message
                                if (response.includes('Success')) {
                                    Swal.fire({
                                        title: 'Reschedule Suggestion Sent',
                                        text: 'The patient will be notified of your suggested reschedule.',
                                        icon: 'success',
                                        confirmButtonColor: '#4e73df'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    // Try to extract error message
                                    Swal.fire({
                                        title: 'Error',
                                        text: 'Failed to send reschedule suggestion. Please try again.',
                                        icon: 'error',
                                        confirmButtonColor: '#4e73df'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Error',
                                    text: 'There was a problem with the request. Please try again.',
                                    icon: 'error',
                                    confirmButtonColor: '#4e73df'
                                });
                            }
                        });
                    }
                });
            });

            // Patient reschedule form submission
            $('.rescheduleForm').on('submit', function(e) {
                e.preventDefault();
                
                var form = $(this);
                var appointmentId = form.data('appointment-id');
                var newDate = form.find('input[name="new_date"]').val();
                var newTime = form.find('select[name="new_time"]').val();
                
                if (!newDate || !newTime) {
                    Swal.fire({
                        title: 'Missing Information',
                        text: 'Please select both a new date and time.',
                        icon: 'warning',
                        confirmButtonColor: '#4e73df'
                    });
                    return;
                }
                
                // Check date is not a Saturday (day 6)
                var selectedDate = new Date(newDate);
                if (selectedDate.getDay() === 6) {
                    Swal.fire({
                        title: 'Invalid Day',
                        text: 'Appointments are not available on Saturdays.',
                        icon: 'error',
                        confirmButtonColor: '#4e73df'
                    });
                    return;
                }
                
                // Confirm before submitting
                Swal.fire({
                    title: 'Confirm Reschedule',
                    text: 'Are you sure you want to reschedule this appointment?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#4e73df',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, reschedule it'
                }).then((result) => {
                    if (result.isConfirmed) {
                    $.ajax({
                            url: 'appointments.php',
                            type: 'POST',
                            data: form.serialize(),
                            dataType: 'json',
                        success: function(response) {
                                if (response.success) {
                                Swal.fire({
                                        title: 'Appointment Rescheduled',
                                        text: 'Your appointment has been successfully rescheduled.',
                                        icon: 'success',
                                    confirmButtonColor: '#4e73df'
                                    }).then(() => {
                                        window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                        title: 'Error',
                                        text: response.error || 'Failed to reschedule appointment.',
                                        icon: 'error',
                                    confirmButtonColor: '#4e73df'
                                });
                            }
                        },
                            error: function(xhr, status, error) {
                                console.error("XHR Response:", xhr.responseText);
                            Swal.fire({
                                title: 'Error',
                                    text: 'There was a problem with the request. Please try again.',
                                icon: 'error',
                                confirmButtonColor: '#4e73df'
                            });
                        }
                    });
                }
            });
            });

            // Show suggestions notification for patients on page load
            <?php if($role == "patient" && !empty($reschedule_suggestions)): ?>
            Swal.fire({
                title: 'Reschedule Suggestions',
                html: `You have ${<?php echo count($reschedule_suggestions); ?>} pending reschedule suggestion(s) from your doctor.`,
                icon: 'info',
                confirmButtonText: 'View Suggestions',
                confirmButtonColor: '#4e73df'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#suggestionModal<?php echo $reschedule_suggestions[0]['id']; ?>').modal('show');
                }
            });
            <?php endif; ?>
            
            // Add special styling for cancelled appointments
            $('[data-status="cancelled"]').closest('.appointment-card').addClass('canceled-appointment');
        });
    </script>
</body>
</html> 