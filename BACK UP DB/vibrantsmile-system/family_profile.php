<?php
session_start();
require_once "config/database.php";
require_once "includes/functions.php";

// Check if user is logged in and is a patient
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "patient"){
    header("location: index.php");
    exit;
}

$user_id = $_SESSION["id"];
$error_message = "";
$success_message = "";

// Get patient information
$sql = "SELECT p.id as patient_id, p.family_code, u.first_name, u.middle_name, u.last_name, p.gender, p.date_of_birth, p.family_role 
        FROM users u 
        JOIN patients p ON u.id = p.user_id 
        WHERE u.id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $patient = mysqli_fetch_assoc($result);
    $patient_id = $patient['patient_id'];
    $current_family_code = $patient['family_code'];
    $current_family_role = $patient['family_role'];
}

// Handle creating a new family
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create_family"])){
    $family_role = trim($_POST["family_role"]);
    $family_name = trim($_POST["family_name"]);
    
    // Validate role and name
    if(empty($family_role)) {
        $role_err = "Please select your role in the family.";
    } elseif(empty($family_name)) {
        $name_err = "Please enter a family name.";
    } else {
        // Generate a unique family code
        $family_code = strtoupper(substr(md5(uniqid()), 0, 6));
        
        // Insert into family_codes table with the provided family name
        $sql = "INSERT INTO family_codes (code, name, created_by) VALUES (?, ?, ?)";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $family_code, $family_name, $patient_id);
            
            if(mysqli_stmt_execute($stmt)) {
                // Update patient's family code and role
                $sql = "UPDATE patients SET family_code = ?, family_role = ? WHERE id = ?";
                if($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "ssi", $family_code, $family_role, $patient_id);
                    mysqli_stmt_execute($stmt);
                    
                    // Redirect to refresh the page
                    header("location: family_profile.php");
                    exit;
                }
            }
        }
    }
}

// Handle joining a family
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["join_family"])){
    $join_code = trim($_POST["join_code"]);
    
    // Check if code exists
    $sql = "SELECT * FROM family_codes WHERE code = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $join_code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if(mysqli_num_rows($result) > 0) {
            // Update patient's family code
            $sql = "UPDATE patients SET family_code = ? WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "si", $join_code, $patient_id);
                if(mysqli_stmt_execute($stmt)){
                    $current_family_code = $join_code;
                    $success_message = "Successfully joined family!";
                } else {
                    $error_message = "Error joining family.";
                }
            }
        } else {
            $error_message = "Invalid family code. Please try again.";
        }
    }
}

// Handle updating family role
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_family_role"])){
    $family_role = trim($_POST["family_role"]);
    
    // Update patient's family role
    $sql = "UPDATE patients SET family_role = ? WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "si", $family_role, $patient_id);
        if(mysqli_stmt_execute($stmt)){
            $success_message = "Family role updated successfully!";
        } else {
            $error_message = "Error updating family role.";
        }
    }
}

// Handle leaving a family
if(isset($_GET['leave_family']) && $_GET['leave_family'] == 1 && $current_family_code) {
    // Update patient's family code to NULL
    $sql = "UPDATE patients SET family_code = NULL WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $patient_id);
        if(mysqli_stmt_execute($stmt)){
            // Check if this was the last member
            $sql = "SELECT COUNT(*) as count FROM patients WHERE family_code = ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "s", $current_family_code);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                
                // If no more members, delete the family code
                if($row['count'] == 0) {
                    $sql = "DELETE FROM family_codes WHERE code = ?";
                    if($stmt = mysqli_prepare($conn, $sql)){
                        mysqli_stmt_bind_param($stmt, "s", $current_family_code);
                        mysqli_stmt_execute($stmt);
                    }
                }
            }
            
            $success_message = "Successfully left family.";
            $current_family_code = null;
        } else {
            $error_message = "Error leaving family.";
        }
    }
    header("location: family_profile.php");
    exit;
}

// Get family information if in a family
$family_info = null;
$family_members = [];

if($current_family_code) {
    // Get family code info with creator's name
    $sql = "SELECT fc.*, CONCAT(u.first_name, ' ', u.last_name) as creator_name 
            FROM family_codes fc
            JOIN patients p ON fc.created_by = p.id
            JOIN users u ON p.user_id = u.id
            WHERE fc.code = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $current_family_code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $family_info = mysqli_fetch_assoc($result);
    }
    
    // Get family members
    $sql = "SELECT p.id, u.first_name, u.last_name, u.email, u.phone, p.gender, p.date_of_birth, p.family_role 
            FROM patients p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.family_code = ? AND p.id != ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "si", $current_family_code, $patient_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $family_members[] = $row;
        }
    }
}

// Page title
$page_title = "Family Profile Management";
$current_page = "family_profile";
include "includes/header.php";
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3">
            <!-- Sidebar -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h5 class="my-3"><?php echo htmlspecialchars($patient['first_name']) . " " . htmlspecialchars($patient['last_name']); ?></h5>
                    <p class="text-muted mb-1">Patient</p>
                    <div class="d-flex justify-content-center mb-2">
                        <a href="profile.php" class="btn btn-outline-primary ms-1">Profile</a>
                        <a href="appointments.php" class="btn btn-outline-primary ms-1">Appointments</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            <!-- Success/Error Messages -->
            <?php if(!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if(!$current_family_code): ?>
            <!-- Not in a family -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Join a Family</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">Create a New Family</h6>
                                    <p class="card-text">Create a new family group and get a code to share with your family members.</p>
                                    <form method="post" action="family_profile.php">
                                        <div class="mb-3">
                                            <label for="familyName" class="form-label">Family Name</label>
                                            <input type="text" class="form-control" id="familyName" name="family_name" required>
                                            <?php if(isset($name_err)): ?>
                                                <div class="text-danger"><?php echo $name_err; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mb-3">
                                            <label for="familyRole" class="form-label">Family Role</label>
                                            <select class="form-select" id="familyRole" name="family_role" required>
                                                <option value="" disabled selected>Select your role</option>
                                                <option value="Parent">Parent</option>
                                                <option value="Child">Child</option>
                                                <option value="Spouse">Spouse</option>
                                                <option value="Grandparent">Grandparent</option>
                                                <option value="Sibling">Sibling</option>
                                                <option value="Other">Other</option>
                                            </select>
                                            <?php if(isset($role_err)): ?>
                                                <div class="text-danger"><?php echo $role_err; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <button type="submit" class="btn btn-primary" name="create_family">Create Family</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">Join Existing Family</h6>
                                    <p class="card-text">Enter a family code shared with you to join an existing family group.</p>
                                    <form method="post" action="family_profile.php">
                                        <div class="mb-3">
                                            <label for="joinCode" class="form-label">Family Code</label>
                                            <input type="text" class="form-control" id="joinCode" name="join_code" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary" name="join_family">Join Family</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- In a family -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Family: <?php echo htmlspecialchars($family_info['name']); ?></h5>
                    <a href="family_profile.php?leave_family=1" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to leave this family?');">
                        <i class="fas fa-sign-out-alt"></i> Leave Family
                    </a>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Family Code:</strong> <?php echo htmlspecialchars($current_family_code); ?>
                        <p class="mb-0">Share this code with family members to invite them to your family group.</p>
                    </div>
                    
                    <!-- Family Role Display -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="card-title">My Role in Family</h6>
                            <p class="mb-0">
                                <strong>Current Role:</strong> 
                                <?php echo !empty($current_family_role) ? htmlspecialchars($current_family_role) : '<span class="text-muted">Not specified</span>'; ?>
                            </p>
                        </div>
                    </div>
                    
                    <h6>Family Members</h6>
                    <?php if(empty($family_members)): ?>
                        <p class="text-muted">No other family members have joined yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Gender</th>
                                        <th>Age</th>
                                        <th>Role</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($family_members as $member): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                                        <td>
                                            <small class="d-block text-muted"><?php echo htmlspecialchars($member['email']); ?></small>
                                            <small class="d-block text-muted"><?php echo htmlspecialchars($member['phone']); ?></small>
                                        </td>
                                        <td><?php echo ucfirst(htmlspecialchars($member['gender'])); ?></td>
                                        <td><?php echo htmlspecialchars(calculateAge($member['date_of_birth'])); ?></td>
                                        <td>
                                            <?php 
                                            echo !empty($member['family_role']) 
                                                ? htmlspecialchars($member['family_role']) 
                                                : '<span class="text-muted">Not specified</span>'; 
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Family Appointments Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Family Appointments</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get family member IDs
                    $family_ids = [];
                    foreach($family_members as $member) {
                        $family_ids[] = $member['id'];
                    }
                    
                    if(!empty($family_ids)) {
                        $placeholders = str_repeat('?,', count($family_ids) - 1) . '?';
                        $sql = "SELECT a.*, u.first_name, u.last_name, s.name as service_name, s.duration, d.id as doctor_id, 
                                du.first_name as doctor_first_name, du.last_name as doctor_last_name
                                FROM appointments a
                                JOIN patients p ON a.patient_id = p.id
                                JOIN users u ON p.user_id = u.id
                                JOIN services s ON a.service_id = s.id
                                LEFT JOIN doctors d ON a.doctor_id = d.id
                                LEFT JOIN users du ON d.user_id = du.id
                                WHERE a.patient_id IN ($placeholders)
                                AND a.appointment_date >= CURDATE()
                                ORDER BY a.appointment_date ASC, a.appointment_time ASC
                                LIMIT 10";
                        
                        if($stmt = mysqli_prepare($conn, $sql)){
                            mysqli_stmt_bind_param($stmt, str_repeat('i', count($family_ids)), ...$family_ids);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            
                            if(mysqli_num_rows($result) > 0) {
                                echo '<div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Family Member</th>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Service</th>
                                                    <th>Doctor</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>';
                                
                                while($row = mysqli_fetch_assoc($result)) {
                                    echo '<tr>
                                            <td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>
                                            <td>' . date('M d, Y', strtotime($row['appointment_date'])) . '</td>
                                            <td>' . date('h:i A', strtotime($row['appointment_time'])) . '</td>
                                            <td>' . htmlspecialchars($row['service_name']) . '</td>
                                            <td>' . htmlspecialchars($row['doctor_first_name'] . ' ' . $row['doctor_last_name']) . '</td>
                                            <td><span class="badge bg-' . 
                                                ($row['status'] == 'pending' ? 'warning' : 
                                                ($row['status'] == 'scheduled' ? 'info' : 
                                                ($row['status'] == 'approved' ? 'primary' : 
                                                ($row['status'] == 'completed' ? 'success' : 'danger')))) . 
                                                '">' . ucfirst($row['status']) . '</span></td>
                                        </tr>';
                                }
                                
                                echo '</tbody></table></div>';
                            } else {
                                echo '<p class="text-muted">No upcoming appointments for family members.</p>';
                            }
                        }
                    } else {
                        echo '<p class="text-muted">No family members yet. Share your family code to invite others.</p>';
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?> 