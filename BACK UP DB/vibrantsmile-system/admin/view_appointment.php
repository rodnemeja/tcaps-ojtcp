<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Check if appointment ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: appointments.php");
    exit;
}

$appointment_id = $_GET['id'];

// Get appointment details
$sql = "SELECT 
    a.*,
    CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) as patient_name,
    u.email as patient_email,
    u.phone as patient_phone,
    CONCAT(du.first_name, ' ', du.last_name) as doctor_name,
    (
        SELECT GROUP_CONCAT(DISTINCT s.name SEPARATOR ', ')
        FROM (
            SELECT service_id 
            FROM appointment_services 
            WHERE appointment_id = ?
            UNION
            SELECT service_id 
            FROM appointments 
            WHERE id = ? AND service_id IS NOT NULL
        ) as all_services
        JOIN services s ON all_services.service_id = s.id
    ) as services
FROM appointments a
LEFT JOIN patients p ON a.patient_id = p.id
LEFT JOIN users u ON p.user_id = u.id
LEFT JOIN doctors d ON a.doctor_id = d.id
LEFT JOIN users du ON d.user_id = du.id
WHERE a.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iii", $appointment_id, $appointment_id, $appointment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(!$result || mysqli_num_rows($result) === 0) {
    header("location: appointments.php");
    exit;
}

$appointment = mysqli_fetch_assoc($result);

// If services is null, set it to empty string
if ($appointment['services'] === null) {
    $appointment['services'] = '';
}

$page_title = "View Appointment";
$current_page = "appointments";
require_once "includes/header.php";
?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Appointment Details</h1>
        <a href="appointments.php" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Appointments
        </a>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Appointment Information</h6>
                    <div>
                        <span class="badge bg-<?php 
                            echo $appointment['status'] === 'completed' ? 'success' : 
                                ($appointment['status'] === 'cancelled' ? 'danger' : 
                                ($appointment['status'] === 'pending' ? 'warning' : 'info')); 
                        ?>">
                            <?php echo ucfirst($appointment['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Patient Information</h6>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($appointment['patient_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($appointment['patient_email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($appointment['patient_phone']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Appointment Details</h6>
                            <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($appointment['appointment_date'])); ?></p>
                            <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></p>
                            <p><strong>Doctor:</strong> <?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Not assigned'); ?></p>
                            <p><strong>Services:</strong> <?php echo htmlspecialchars($appointment['services'] ?? 'No services selected'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Created: <?php echo date('F d, Y h:i A', strtotime($appointment['created_at'])); ?></small>
                        </div>
                        <div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle approve button click
    document.querySelector('.approve-btn')?.addEventListener('click', function() {
        const id = this.dataset.id;
        handleStatusUpdate(id, 'approved');
    });

    // Handle complete button click
    document.querySelector('.complete-btn')?.addEventListener('click', function() {
        const id = this.dataset.id;
        handleStatusUpdate(id, 'completed');
    });

    // Handle cancel button click
    document.querySelector('.cancel-btn')?.addEventListener('click', function() {
        const id = this.dataset.id;
        handleStatusUpdate(id, 'cancelled');
    });

    function handleStatusUpdate(id, status) {
        const action = status === 'approved' ? 'approve' : 
                      status === 'completed' ? 'complete' : 'cancel';
        
        Swal.fire({
            title: `Are you sure you want to ${action} this appointment?`,
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: `Yes, ${action} it!`,
            cancelButtonText: 'No, cancel!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Processing...',
                    text: `Please wait while we ${action} the appointment.`,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Send update request
                fetch('update_appointment_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: `Appointment has been ${action}d successfully.`,
                            icon: 'success',
                            confirmButtonColor: '#3085d6'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        throw new Error(data.message || 'Failed to update appointment status');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error!',
                        text: error.message || 'A server error occurred. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#3085d6'
                    });
                });
            }
        });
    }
});
</script>

<?php require_once "includes/footer.php"; ?> 