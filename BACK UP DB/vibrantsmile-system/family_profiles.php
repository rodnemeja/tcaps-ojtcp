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

// Get families that the doctor has interacted with
$sql = "SELECT fc.*, 
        COUNT(DISTINCT p.id) as total_members,
        MAX(a.appointment_date) as latest_visit
        FROM family_codes fc
        JOIN patients p ON fc.code = p.family_code
        JOIN appointments a ON p.id = a.patient_id
        WHERE a.doctor_id = ?
        GROUP BY fc.id
        ORDER BY latest_visit DESC";

$families = [];
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
        $families[] = $row;
    }
}

// Page title
$page_title = "Family Profiles";
$current_page = "family_profiles";
include "includes/header.php";
?>

<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Family Profiles</h1>
    </div>

    <?php if(empty($families)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-users fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">No Family Profiles Found</h4>
            <p class="text-muted">You haven't seen any patients with family connections yet.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
        <?php foreach($families as $family): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="m-0 font-weight-bold"><?php echo htmlspecialchars($family['name']); ?></h5>
                    <span class="badge bg-primary"><?php echo $family['total_members']; ?> members</span>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <i class="fas fa-hashtag me-2"></i> 
                        <strong>Family Code:</strong> <?php echo htmlspecialchars($family['code']); ?>
                    </p>
                    <p class="mb-2">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <strong>Created:</strong> <?php echo date('M d, Y', strtotime($family['created_at'])); ?>
                    </p>
                    <p class="mb-2">
                        <i class="fas fa-calendar-check me-2"></i>
                        <strong>Latest Visit:</strong> <?php echo date('M d, Y', strtotime($family['latest_visit'])); ?>
                    </p>
                    
                    <?php
                    // Get family members with appointments with this doctor
                    $members_sql = "SELECT p.id, u.first_name, u.last_name, 
                                   COUNT(a.id) as appointment_count,
                                   MAX(a.appointment_date) as last_visit
                                   FROM patients p
                                   JOIN users u ON p.user_id = u.id
                                   JOIN appointments a ON p.id = a.patient_id
                                   WHERE p.family_code = ? AND a.doctor_id = ?
                                   GROUP BY p.id
                                   ORDER BY last_visit DESC
                                   LIMIT 3";
                    $members = [];
                    if($stmt = mysqli_prepare($conn, $members_sql)){
                        mysqli_stmt_bind_param($stmt, "si", $family['code'], $doctor_id);
                        mysqli_stmt_execute($stmt);
                        $members_result = mysqli_stmt_get_result($stmt);
                        while($member = mysqli_fetch_assoc($members_result)){
                            $members[] = $member;
                        }
                    }
                    ?>
                    
                    <h6 class="mt-4 mb-2">Recent Members:</h6>
                    <ul class="list-group">
                    <?php foreach($members as $member): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-user me-2"></i>
                                <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                            </div>
                            <div class="text-muted small">
                                <?php echo date('M d', strtotime($member['last_visit'])); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card-footer bg-white">
                    <a href="doctor_patient_family.php?patient_id=<?php echo $members[0]['id']; ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-eye me-2"></i> View Family Details
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include "includes/footer.php"; ?> 