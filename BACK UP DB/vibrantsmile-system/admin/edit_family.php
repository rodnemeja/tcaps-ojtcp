<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Check if a family ID was provided
if(!isset($_GET['id']) || empty($_GET['id'])){
    $_SESSION['error_message'] = "No family specified";
    header("location: family_profiles.php");
    exit;
}

$family_id = $_GET['id'];
$family_code = "";
$family_name = "";
$family_name_err = "";

// Get family information
$sql = "SELECT * FROM family_codes WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $family_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($row = mysqli_fetch_assoc($result)){
        $family = $row;
        $family_code = $family['code'];
        $family_name = $family['name'];
    } else {
        $_SESSION['error_message'] = "Family not found";
        header("location: family_profiles.php");
        exit;
    }
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate family name
    if(empty(trim($_POST["family_name"]))){
        $family_name_err = "Please enter a family name";
    } else {
        $family_name = trim($_POST["family_name"]);
    }
    
    // If no errors, update the family
    if(empty($family_name_err)){
        $sql = "UPDATE family_codes SET name = ? WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $family_name, $family_id);
            
            if(mysqli_stmt_execute($stmt)){
                $_SESSION['success_message'] = "Family information updated successfully";
                header("location: view_family.php?code=" . $family_code);
                exit;
            } else {
                $_SESSION['error_message'] = "Error updating family information: " . mysqli_error($conn);
            }
        }
    }
}

// Get family members
$sql = "SELECT p.id as patient_id, p.family_role, 
        u.first_name, u.middle_name, u.last_name
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

$page_title = "Edit Family";
$current_page = "family_profiles";
require_once "includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Family</h2>
    <div>
        <a href="view_family.php?code=<?php echo $family_code; ?>" class="btn btn-secondary me-2">
            <i class="fas fa-eye me-2"></i>View Family
        </a>
        <a href="family_profiles.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Families
        </a>
    </div>
</div>

<?php if(isset($_SESSION['error_message'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['error_message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php unset($_SESSION['error_message']); endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Family Information</h6>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $family_id); ?>">
                    <div class="mb-3">
                        <label for="family_code" class="form-label">Family Code</label>
                        <input type="text" class="form-control" id="family_code" value="<?php echo htmlspecialchars($family_code); ?>" readonly>
                        <small class="text-muted">Family code cannot be changed.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="family_name" class="form-label">Family Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php echo !empty($family_name_err) ? 'is-invalid' : ''; ?>" 
                               id="family_name" name="family_name" value="<?php echo htmlspecialchars($family_name); ?>" required>
                        <div class="invalid-feedback"><?php echo $family_name_err; ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Created</label>
                        <input type="text" class="form-control" value="<?php echo date('M d, Y', strtotime($family['created_at'])); ?>" readonly>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="view_family.php?code=<?php echo $family_code; ?>" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Family</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Family Members</h6>
                <a href="add_to_family.php?code=<?php echo $family_code; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-user-plus me-2"></i>Add Member
                </a>
            </div>
            <div class="card-body">
                <?php if(empty($family_members)): ?>
                <div class="text-center text-muted my-4">
                    <p>No family members found</p>
                    <a href="add_to_family.php?code=<?php echo $family_code; ?>" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Add Member
                    </a>
                </div>
                <?php else: ?>
                <div class="list-group">
                    <?php foreach($family_members as $member): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($member['first_name'] . ' ' . 
                                    ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . 
                                    $member['last_name']); ?>
                                </h6>
                                <small class="text-muted">
                                    <?php echo !empty($member['family_role']) 
                                        ? htmlspecialchars($member['family_role']) 
                                        : '<span class="text-danger">No role assigned</span>'; ?>
                                </small>
                            </div>
                            <div class="btn-group" role="group">
                                <a href="edit_patient_role.php?id=<?php echo $member['patient_id']; ?>&code=<?php echo $family_code; ?>" 
                                   class="btn btn-sm btn-outline-primary" title="Edit Role">
                                    <i class="fas fa-user-edit"></i>
                                </a>
                                <a href="javascript:void(0);" 
                                   onclick="confirmRemove(<?php echo $member['patient_id']; ?>, '<?php echo addslashes(htmlspecialchars($member['first_name'] . ' ' . $member['last_name'])); ?>')" 
                                   class="btn btn-sm btn-outline-danger" title="Remove from Family">
                                    <i class="fas fa-user-minus"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Danger Zone</h6>
            </div>
            <div class="card-body">
                <p class="text-muted">Deleting a family will remove all family connections for its members but will not delete patient records.</p>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                    <i class="fas fa-trash me-2"></i>Delete Family
                </button>
            </div>
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
                Are you sure you want to remove <span id="memberName"></span> from this family? This will not delete the patient record.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmRemoveBtn" class="btn btn-warning">Remove</a>
            </div>
        </div>
    </div>
</div>

<!-- Delete Family Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this family?</p>
                <p class="text-danger">This will remove all family connections for all members.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="family_profiles.php?action=delete&id=<?php echo $family_id; ?>" class="btn btn-danger">Delete Family</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmRemove(id, name) {
    document.getElementById('memberName').textContent = name;
    document.getElementById('confirmRemoveBtn').href = 'remove_from_family.php?id=' + id + '&code=<?php echo $family_code; ?>';
    var removeModal = new bootstrap.Modal(document.getElementById('removeModal'));
    removeModal.show();
}
</script>

<?php require_once "includes/footer.php"; ?> 