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
$sql = "SELECT p.id as patient_id, p.date_of_birth, p.gender, p.family_role, p.family_code, 
        u.id as user_id, u.first_name, u.middle_name, u.last_name, u.email, u.phone, u.active
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

// Get all appointments for the family
$appointments_sql = "SELECT 
    a.id as appointment_id,
    a.appointment_date,
    a.appointment_time,
    a.status,
    a.notes,
    a.created_at,
    CONCAT(u.first_name, ' ', u.last_name) as patient_name,
    CONCAT(du.first_name, ' ', du.last_name) as doctor_name,
    s.name as service_name,
    s.duration as service_duration,
    s.price as service_price,
    d.specialization as doctor_specialization,
    p.family_role
FROM appointments a
LEFT JOIN patients p ON a.patient_id = p.id
LEFT JOIN users u ON p.user_id = u.id
LEFT JOIN doctors d ON a.doctor_id = d.id
LEFT JOIN users du ON d.user_id = du.id
LEFT JOIN services s ON a.service_id = s.id
WHERE p.family_code = ?
ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = mysqli_prepare($conn, $appointments_sql);
mysqli_stmt_bind_param($stmt, "s", $family_code);
mysqli_stmt_execute($stmt);
$appointments_result = mysqli_stmt_get_result($stmt);

// Get statistics
$stats = [
    'total_appointments' => 0,
    'completed_appointments' => 0,
    'cancelled_appointments' => 0,
    'total_spent' => 0,
    'most_common_service' => 'None',
    'most_visited_doctor' => 'None',
    'most_active_member' => 'None'
];

// Total and status counts
$sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE p.family_code = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "s", $family_code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($row = mysqli_fetch_assoc($result)){
        $stats['total_appointments'] = $row['total'];
        $stats['completed_appointments'] = $row['completed'];
        $stats['cancelled_appointments'] = $row['cancelled'];
    }
}

// Total spent
$sql = "SELECT COALESCE(SUM(i.total_amount), 0) as total_spent
        FROM invoices i
        JOIN appointments a ON i.appointment_id = a.id
        JOIN patients p ON a.patient_id = p.id
        WHERE p.family_code = ? AND i.payment_status = 'paid'";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "s", $family_code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($row = mysqli_fetch_assoc($result)){
        $stats['total_spent'] = $row['total_spent'];
    }
}

// Most common service
$sql = "SELECT s.name, COUNT(*) as count
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        JOIN patients p ON a.patient_id = p.id
        WHERE p.family_code = ?
        GROUP BY s.id, s.name
        ORDER BY count DESC
        LIMIT 1";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "s", $family_code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($row = mysqli_fetch_assoc($result)){
        $stats['most_common_service'] = $row['name'];
    }
}

// Most visited doctor
$sql = "SELECT CONCAT(u.first_name, ' ', u.last_name) as doctor_name, COUNT(*) as count
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        JOIN users u ON d.user_id = u.id
        JOIN patients p ON a.patient_id = p.id
        WHERE p.family_code = ?
        GROUP BY d.id
        ORDER BY count DESC
        LIMIT 1";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "s", $family_code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($row = mysqli_fetch_assoc($result)){
        $stats['most_visited_doctor'] = $row['doctor_name'];
    }
}

// Most active member
$sql = "SELECT CONCAT(u.first_name, ' ', u.last_name) as member_name, COUNT(*) as count
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN users u ON p.user_id = u.id
        WHERE p.family_code = ?
        GROUP BY p.id
        ORDER BY count DESC
        LIMIT 1";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "s", $family_code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($row = mysqli_fetch_assoc($result)){
        $stats['most_active_member'] = $row['member_name'];
    }
}

$page_title = "View Family";
$current_page = "family_profiles";
require_once "includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Family Profile: <?php echo htmlspecialchars($family['name']); ?></h2>
    <div>
        <a href="add_to_family.php?code=<?php echo $family_code; ?>" class="btn btn-primary me-2">
            <i class="fas fa-user-plus me-1"></i>Add Members
        </a>
        <a href="schedule_family.php?code=<?php echo $family_code; ?>" class="btn btn-success me-2">
            <i class="fas fa-calendar-plus me-1"></i>Schedule Appointments
        </a>
        <a href="family_profiles.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Families
        </a>
    </div>
</div>

<?php if(isset($_SESSION['success_message'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['success_message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php unset($_SESSION['success_message']); endif; ?>

<?php if(isset($_SESSION['error_message'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['error_message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php unset($_SESSION['error_message']); endif; ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Family Information</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Family Name:</strong> <?php echo htmlspecialchars($family['name']); ?>
                </div>
                <div class="mb-3">
                    <strong>Family Code:</strong> <?php echo htmlspecialchars($family['code']); ?>
                </div>
                <div class="mb-3">
                    <strong>Created:</strong> <?php echo date('M d, Y', strtotime($family['created_at'])); ?>
                </div>
                <div class="mb-3">
                    <strong>Member Count:</strong> <?php echo count($family_members); ?>
                </div>
                <div class="d-grid gap-2 mt-4">
                    <a href="edit_family.php?id=<?php echo $family['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Edit Family
                    </a>
                    <a href="schedule_family.php?code=<?php echo $family_code; ?>" class="btn btn-success">
                        <i class="fas fa-calendar-plus me-2"></i>Schedule Group Appointment
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Family Statistics</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card bg-primary text-white shadow">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Total Appointments</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $stats['total_appointments']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card bg-success text-white shadow">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Completed Appointments</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $stats['completed_appointments']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card bg-warning text-white shadow">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Total Spent</div>
                                <div class="h5 mb-0 font-weight-bold">₱<?php echo number_format($stats['total_spent'], 2); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card bg-danger text-white shadow">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Cancelled Appointments</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $stats['cancelled_appointments']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light shadow">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Most Common Service</div>
                                <div class="h6 mb-0 font-weight-bold"><?php echo htmlspecialchars($stats['most_common_service']); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light shadow">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Most Visited Doctor</div>
                                <div class="h6 mb-0 font-weight-bold"><?php echo htmlspecialchars($stats['most_visited_doctor']); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light shadow">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Most Active Member</div>
                                <div class="h6 mb-0 font-weight-bold"><?php echo htmlspecialchars($stats['most_active_member']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Family Members</h6>
        <a href="add_to_family.php?code=<?php echo $family_code; ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-user-plus me-2"></i>Add Member
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="membersTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Contact</th>
                        <th>Gender</th>
                        <th>Date of Birth</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($family_members)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No family members found</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($family_members as $member): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($member['first_name'] . ' ' . 
                                    ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . 
                                    $member['last_name']); 
                                ?>
                            </td>
                            <td>
                                <?php echo !empty($member['family_role']) 
                                    ? htmlspecialchars($member['family_role']) 
                                    : '<span class="text-muted">Not specified</span>'; 
                                ?>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($member['email']); ?></div>
                                <div><?php echo htmlspecialchars($member['phone']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($member['gender']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($member['date_of_birth'])); ?></td>
                            <td>
                                <?php if($member['active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="view_patient.php?id=<?php echo $member['patient_id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_patient.php?id=<?php echo $member['patient_id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0);" onclick="confirmRemove(<?php echo $member['patient_id']; ?>)" class="btn btn-sm btn-warning">
                                        <i class="fas fa-user-minus"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Recent Appointments</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="appointmentsTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Patient</th>
                        <th>Role</th>
                        <th>Service</th>
                        <th>Doctor</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($appointments_result)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No appointments found</td>
                    </tr>
                    <?php else: ?>
                        <?php while($appointment = mysqli_fetch_assoc($appointments_result)): ?>
                        <tr>
                            <td>
                                <div><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></div>
                                <div class="small text-muted"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                            <td>
                                <?php echo !empty($appointment['family_role']) 
                                    ? htmlspecialchars($appointment['family_role']) 
                                    : '<span class="text-muted">Not specified</span>'; 
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($appointment['service_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($appointment['doctor_name'] ?? 'N/A'); ?></td>
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
                                <a href="view_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Remove from Family Confirmation Modal -->
<div class="modal fade" id="removeModal" tabindex="-1" aria-labelledby="removeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="removeModalLabel">Confirm Remove</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to remove this member from the family? This will not delete the patient record.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmRemoveBtn" class="btn btn-warning">Remove</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmRemove(id) {
    document.getElementById('confirmRemoveBtn').href = 'remove_from_family.php?id=' + id + '&code=<?php echo $family_code; ?>';
    var removeModal = new bootstrap.Modal(document.getElementById('removeModal'));
    removeModal.show();
}

// Initialize DataTables
$(document).ready(function() {
    $('#membersTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 10,
        "language": {
            "lengthMenu": "Show _MENU_ members per page",
            "zeroRecords": "No family members found",
            "info": "Showing page _PAGE_ of _PAGES_",
            "infoEmpty": "No family members available",
            "search": "Search members:"
        }
    });
    
    $('#appointmentsTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 10,
        "language": {
            "lengthMenu": "Show _MENU_ appointments per page",
            "zeroRecords": "No appointments found",
            "info": "Showing page _PAGE_ of _PAGES_",
            "infoEmpty": "No appointments available",
            "search": "Search appointments:"
        }
    });
});
</script>

<?php require_once "includes/footer.php"; ?> 