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
$error = '';
$success = '';

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

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST['add_members']) && isset($_POST['patient_ids']) && !empty($_POST['patient_ids'])){
        $patient_ids = $_POST['patient_ids'];
        $roles = isset($_POST['roles']) ? $_POST['roles'] : [];
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            $success_count = 0;
            
            foreach($patient_ids as $patient_id){
                $patient_id = intval($patient_id);
                $role = isset($roles[$patient_id]) ? trim($roles[$patient_id]) : null;
                
                // Update patient's family code
                $update_sql = "UPDATE patients SET family_code = ?, family_role = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "ssi", $family_code, $role, $patient_id);
                
                if(mysqli_stmt_execute($update_stmt)){
                    $success_count++;
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            if($success_count > 0){
                $_SESSION['success_message'] = "Successfully added " . $success_count . " patient" . ($success_count > 1 ? "s" : "") . " to the family";
                header("location: view_family.php?code=" . $family_code);
                exit;
            } else {
                $error = "No patients were added to the family";
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    } else {
        $error = "No patients selected";
    }
}

// Get patients not in any family or in a different family
$sql = "SELECT p.id, p.date_of_birth, p.gender, p.family_code,
        u.first_name, u.middle_name, u.last_name, u.email, u.phone
        FROM patients p
        JOIN users u ON p.user_id = u.id
        WHERE (p.family_code IS NULL OR p.family_code = '' OR p.family_code != ?)
        ORDER BY u.last_name, u.first_name";

$available_patients = [];
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "s", $family_code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
        $available_patients[] = $row;
    }
}

$page_title = "Add to Family";
$current_page = "family_profiles";
require_once "includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Add Members to Family: <?php echo htmlspecialchars($family['name']); ?></h2>
    <a href="view_family.php?code=<?php echo $family_code; ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Family
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
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Select Patients to Add</h6>
        <div class="input-group" style="width: 300px;">
            <input type="text" class="form-control" id="searchPatient" placeholder="Search patients...">
            <button class="btn btn-outline-secondary" type="button" id="searchButton">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if(empty($available_patients)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> No available patients found. All patients are already assigned to families.
        </div>
        <?php else: ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?code=" . $family_code); ?>" id="addMembersForm">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="patientsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="50px">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                </div>
                            </th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Gender</th>
                            <th>Date of Birth</th>
                            <th>Current Family</th>
                            <th>Role in New Family</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($available_patients as $patient): ?>
                        <tr>
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input patient-select" type="checkbox" name="patient_ids[]" value="<?php echo $patient['id']; ?>">
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($patient['first_name'] . ' ' . 
                                    ($patient['middle_name'] ? $patient['middle_name'] . ' ' : '') . 
                                    $patient['last_name']); 
                                ?>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($patient['email']); ?></div>
                                <div><?php echo htmlspecialchars($patient['phone']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($patient['date_of_birth'])); ?></td>
                            <td>
                                <?php if(!empty($patient['family_code']) && $patient['family_code'] != $family_code): ?>
                                    <span class="badge bg-warning text-dark">
                                        <?php echo htmlspecialchars($patient['family_code']); ?>
                                    </span>
                                    <div class="small text-muted mt-1">Will be moved to this family</div>
                                <?php else: ?>
                                    <span class="text-muted">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select class="form-select form-select-sm" name="roles[<?php echo $patient['id']; ?>]">
                                    <option value="">-- Select role --</option>
                                    <option value="Parent">Parent</option>
                                    <option value="Child">Child</option>
                                    <option value="Spouse">Spouse</option>
                                    <option value="Grandparent">Grandparent</option>
                                    <option value="Sibling">Sibling</option>
                                    <option value="Other">Other</option>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <a href="view_family.php?code=<?php echo $family_code; ?>" class="btn btn-secondary me-md-2">Cancel</a>
                <button type="submit" name="add_members" class="btn btn-primary" id="addButton" disabled>
                    <i class="fas fa-user-plus me-2"></i>Add Selected Patients
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const patientCheckboxes = document.querySelectorAll('.patient-select');
    const addButton = document.getElementById('addButton');
    
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        patientCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        updateAddButton();
    });
    
    patientCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateAddButton();
            updateSelectAllCheckbox();
        });
    });
    
    function updateAddButton() {
        const anyChecked = Array.from(patientCheckboxes).some(checkbox => checkbox.checked);
        addButton.disabled = !anyChecked;
    }
    
    function updateSelectAllCheckbox() {
        const allChecked = Array.from(patientCheckboxes).every(checkbox => checkbox.checked);
        const someChecked = Array.from(patientCheckboxes).some(checkbox => checkbox.checked);
        
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = !allChecked && someChecked;
    }
    
    // Search functionality
    document.getElementById('searchButton').addEventListener('click', performSearch);
    document.getElementById('searchPatient').addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            performSearch();
        }
    });
    
    function performSearch() {
        const searchTerm = document.getElementById('searchPatient').value.toLowerCase();
        const tableRows = document.querySelectorAll('#patientsTable tbody tr');
        
        tableRows.forEach(row => {
            const nameCell = row.cells[1].textContent.toLowerCase();
            const emailCell = row.cells[2].textContent.toLowerCase();
            
            if (nameCell.includes(searchTerm) || emailCell.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    // Form validation
    document.getElementById('addMembersForm').addEventListener('submit', function(event) {
        const checkedBoxes = document.querySelectorAll('.patient-select:checked');
        
        if (checkedBoxes.length === 0) {
            event.preventDefault();
            alert('Please select at least one patient to add to the family');
            return;
        }
        
        // Optional: validate that roles are selected for each checked patient
        let allRolesSelected = true;
        checkedBoxes.forEach(checkbox => {
            const patientId = checkbox.value;
            const roleSelect = document.querySelector(`select[name="roles[${patientId}]"]`);
            
            if (roleSelect && roleSelect.value === '') {
                allRolesSelected = false;
            }
        });
        
        if (!allRolesSelected) {
            if (!confirm('Some patients do not have roles assigned. Continue anyway?')) {
                event.preventDefault();
            }
        }
    });
});
</script>

<?php require_once "includes/footer.php"; ?> 