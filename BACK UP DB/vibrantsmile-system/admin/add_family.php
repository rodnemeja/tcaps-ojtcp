<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Define variables
$family_name = "";
$family_name_err = "";
$patient_id = 0;
$patient_role = "";
$success = "";
$error = "";

// Get available patients not currently in a family
$sql = "SELECT p.id, p.date_of_birth, p.gender, 
        u.first_name, u.middle_name, u.last_name, u.email
        FROM patients p
        JOIN users u ON p.user_id = u.id
        WHERE p.family_code IS NULL OR p.family_code = ''
        ORDER BY u.last_name, u.first_name";

$available_patients = [];
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
        $available_patients[] = $row;
    }
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate family name
    if(empty(trim($_POST["family_name"]))){
        $family_name_err = "Please enter a family name";
    } else {
        $family_name = trim($_POST["family_name"]);
        
        // Check if family name already exists
        $check_name_sql = "SELECT id FROM family_codes WHERE name = ?";
        if($check_name_stmt = mysqli_prepare($conn, $check_name_sql)){
            mysqli_stmt_bind_param($check_name_stmt, "s", $family_name);
            mysqli_stmt_execute($check_name_stmt);
            mysqli_stmt_store_result($check_name_stmt);
            
            if(mysqli_stmt_num_rows($check_name_stmt) > 0){
                $family_name_err = "This family name already exists. Please choose a different name.";
            }
            
            mysqli_stmt_close($check_name_stmt);
        }
    }
    
    $patient_id = isset($_POST["patient_id"]) ? intval($_POST["patient_id"]) : 0;
    $patient_role = isset($_POST["patient_role"]) ? trim($_POST["patient_role"]) : "";
    
    // We need a patient to create a family due to foreign key constraint
    if($patient_id <= 0) {
        $error = "You must select a patient to create a family";
    }
    
    // Check if inputs are valid
    if(empty($family_name_err) && empty($error)){
        // Generate a unique family code
        $unique_code = false;
        $family_code = "";
        
        while(!$unique_code) {
            // Generate a random 6-character alphanumeric code
            $family_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
            
            // Check if code already exists
            $check_sql = "SELECT id FROM family_codes WHERE code = ?";
            if($check_stmt = mysqli_prepare($conn, $check_sql)){
                mysqli_stmt_bind_param($check_stmt, "s", $family_code);
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_store_result($check_stmt);
                
                if(mysqli_stmt_num_rows($check_stmt) == 0){
                    $unique_code = true;
                }
                
                mysqli_stmt_close($check_stmt);
            }
        }
        
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // First update the patient's family info
            $update_sql = "UPDATE patients SET family_code = ?, family_role = ? WHERE id = ?";
            if($update_stmt = mysqli_prepare($conn, $update_sql)){
                mysqli_stmt_bind_param($update_stmt, "ssi", $family_code, $patient_role, $patient_id);
                
                if(!mysqli_stmt_execute($update_stmt)){
                    throw new Exception("Error adding member to family: " . mysqli_error($conn));
                }
                
                // Now create the family with the created_by field
                $insert_sql = "INSERT INTO family_codes (name, code, created_by, created_at) VALUES (?, ?, ?, NOW())";
                if($insert_stmt = mysqli_prepare($conn, $insert_sql)){
                    mysqli_stmt_bind_param($insert_stmt, "ssi", $family_name, $family_code, $patient_id);
                    
                    if(!mysqli_stmt_execute($insert_stmt)){
                        throw new Exception("Error creating family: " . mysqli_error($conn));
                    }
                    
                    // Commit transaction
                    mysqli_commit($conn);
                    
                    $_SESSION['success_message'] = "Family successfully created with code: " . $family_code;
                    header("location: view_family.php?code=" . $family_code);
                    exit;
                }
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

$page_title = "Add New Family";
$current_page = "family_profiles";
require_once "includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Create New Family</h2>
    <a href="family_profiles.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Families
    </a>
</div>

<?php if(!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if(!empty($success)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Family Information</h6>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="addFamilyForm">
            <div class="mb-3">
                <label for="family_name" class="form-label">Family Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?php echo !empty($family_name_err) ? 'is-invalid' : ''; ?>" 
                       id="family_name" name="family_name" value="<?php echo htmlspecialchars($family_name); ?>" required>
                <div class="invalid-feedback"><?php echo $family_name_err; ?></div>
                <small class="form-text text-muted">Enter a name for this family group (e.g. "Smith Family")</small>
            </div>
            
            <div class="mb-3">
                <label for="patient_id" class="form-label">Add First Member <span class="text-danger">*</span></label>
                <select class="form-control <?php echo ($patient_id <= 0 && !empty($error)) ? 'is-invalid' : ''; ?>" 
                       id="patient_id" name="patient_id" required>
                    <option value="0">-- Select a patient --</option>
                    <?php foreach($available_patients as $patient): ?>
                    <option value="<?php echo $patient['id']; ?>" <?php echo ($patient_id == $patient['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($patient['first_name'] . ' ' . 
                            ($patient['middle_name'] ? $patient['middle_name'] . ' ' : '') . 
                            $patient['last_name'] . ' (' . $patient['email'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if($patient_id <= 0 && !empty($error)): ?>
                <div class="invalid-feedback"><?php echo $error; ?></div>
                <?php endif; ?>
                <small class="form-text text-muted">A patient must be selected as the family creator</small>
            </div>
            
            <div class="mb-3" id="roleDiv" style="display: <?php echo ($patient_id > 0) ? 'block' : 'none'; ?>;">
                <label for="patient_role" class="form-label">Family Role <span class="text-danger">*</span></label>
                <select class="form-select" id="patient_role" name="patient_role" required>
                    <option value="">-- Select role --</option>
                    <option value="Parent" <?php echo ($patient_role == 'Parent') ? 'selected' : ''; ?>>Parent</option>
                    <option value="Child" <?php echo ($patient_role == 'Child') ? 'selected' : ''; ?>>Child</option>
                    <option value="Spouse" <?php echo ($patient_role == 'Spouse') ? 'selected' : ''; ?>>Spouse</option>
                    <option value="Grandparent" <?php echo ($patient_role == 'Grandparent') ? 'selected' : ''; ?>>Grandparent</option>
                    <option value="Sibling" <?php echo ($patient_role == 'Sibling') ? 'selected' : ''; ?>>Sibling</option>
                    <option value="Other" <?php echo ($patient_role == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle me-2"></i>
                A unique family code will be automatically generated when you create the family.
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="family_profiles.php" class="btn btn-secondary me-md-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Family</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show/hide role field based on patient selection
    const patientSelect = document.getElementById('patient_id');
    const roleDiv = document.getElementById('roleDiv');
    
    patientSelect.addEventListener('change', function() {
        if (this.value != '0') {
            roleDiv.style.display = 'block';
        } else {
            roleDiv.style.display = 'none';
        }
    });
    
    // Form validation
    document.getElementById('addFamilyForm').addEventListener('submit', function(event) {
        const familyName = document.getElementById('family_name').value.trim();
        const patientId = document.getElementById('patient_id').value;
        const patientRole = document.getElementById('patient_role').value;
        
        if (familyName === '') {
            event.preventDefault();
            alert('Please enter a family name');
            return;
        }
        
        if (patientId == '0') {
            event.preventDefault();
            alert('Please select a patient as the family creator');
            return;
        }
        
        if (patientRole === '') {
            event.preventDefault();
            alert('Please select a family role for the patient');
            return;
        }
    });
});
</script>

<?php require_once "includes/footer.php"; ?> 