<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

$patient = array();
$error = "";
$success = "";

// Get patient data if ID is provided
if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT p.*, u.username, u.email, u.phone, u.first_name, u.middle_name, u.last_name, mh.additional_notes as medical_history 
            FROM patients p 
            JOIN users u ON p.user_id = u.id 
            LEFT JOIN medical_history mh ON p.id = mh.patient_id 
            WHERE p.id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)) {
            $patient = $row;
        } else {
            $_SESSION['error_message'] = "Patient not found.";
            header("location: patients.php");
            exit;
        }
    }
} else {
    header("location: patients.php");
    exit;
}

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $first_name = trim($_POST["first_name"]);
    $middle_name = trim($_POST["middle_name"]);
    $last_name = trim($_POST["last_name"]);
    $phone = trim($_POST["phone"]);
    $date_of_birth = $_POST["date_of_birth"];
    $gender = $_POST["gender"];
    $address = trim($_POST["address"]);
    $medical_history = trim($_POST["medical_history"]);
    $password = trim($_POST["password"]);
    
    // Calculate age to determine if patient is a minor
    $is_minor = 0;
    if (!empty($date_of_birth)) {
        $birth_date = new DateTime($date_of_birth);
        $today = new DateTime();
        $age = $birth_date->diff($today)->y;
        $is_minor = ($age < 18) ? 1 : 0;
    }
    
    // Guardian information
    $guardian_name = isset($_POST["guardian_name"]) ? trim($_POST["guardian_name"]) : "";
    $guardian_relationship = isset($_POST["guardian_relationship"]) ? trim($_POST["guardian_relationship"]) : "";
    $guardian_phone = isset($_POST["guardian_phone"]) ? trim($_POST["guardian_phone"]) : "";
    $guardian_email = isset($_POST["guardian_email"]) ? trim($_POST["guardian_email"]) : "";
    $guardian_consent = isset($_POST["guardian_consent"]) ? 1 : 0;

    // Validate input
    if(empty($username) || empty($email) || empty($first_name) || empty($last_name) || empty($phone)) {
        $error = "Please fill in all required fields.";
    } elseif(!preg_match("/^[a-zA-Z0-9_]{3,20}$/", $username)) {
        $error = "Username should be 3-20 characters long and can only contain letters, numbers, and underscores.";
    } elseif(!preg_match("/^[a-zA-Z\s]{2,50}$/", $first_name) || !preg_match("/^[a-zA-Z\s]{2,50}$/", $last_name)) {
        $error = "First and last name should only contain letters and spaces (2-50 characters).";
    } elseif(!preg_match("/^09[0-9]{9}$/", $phone)) {
        $error = "Phone number should be in Philippine format (09XXXXXXXXX).";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif(!empty($date_of_birth) && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_of_birth)) {
        $error = "Invalid date format.";
    } else {
        // Check if username exists (excluding current user)
        $sql = "SELECT id FROM users WHERE username = ? AND id != (SELECT user_id FROM patients WHERE id = ?)";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $username, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) > 0) {
                $error = "This username is already taken.";
            }
        }

        if(empty($error)) {
            // Update user information
            $sql = "UPDATE users SET username = ?, email = ?, first_name = ?, middle_name = ?, last_name = ?, phone = ? WHERE id = (SELECT user_id FROM patients WHERE id = ?)";
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssssssi", $username, $email, $first_name, $middle_name, $last_name, $phone, $id);
                if(!mysqli_stmt_execute($stmt)) {
                    $error = "Error updating user information: " . mysqli_error($conn);
                }
            }

            if(empty($error)) {
                // Update patient information
                $sql = "UPDATE patients SET date_of_birth = ?, gender = ?, address = ?, medical_history = ?, 
                       is_minor = ?, guardian_name = ?, guardian_relationship = ?, 
                       guardian_phone = ?, guardian_email = ?, guardian_consent = ? 
                       WHERE id = ?";
                if($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "ssssissssii", $date_of_birth, $gender, $address, $medical_history, 
                                          $is_minor, $guardian_name, $guardian_relationship, 
                                          $guardian_phone, $guardian_email, $guardian_consent, $id);
                    if(!mysqli_stmt_execute($stmt)) {
                        $error = "Error updating patient information: " . mysqli_error($conn);
                    }
                }

                // Update password if provided
                if(!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET password = ? WHERE id = (SELECT user_id FROM patients WHERE id = ?)";
                    if($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $id);
                        if(!mysqli_stmt_execute($stmt)) {
                            $error = "Error updating password: " . mysqli_error($conn);
                        }
                    }
                }

                if(empty($error)) {
                    $_SESSION['success_message'] = "Patient information updated successfully.";
                    header("location: patients.php");
                    exit;
                }
            }
        }
    }
}

$page_title = "Edit Patient";
$current_page = "patients";
require_once "includes/header.php";
?>

<!-- Main Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Edit Patient</h5>
                        <a href="patients.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-2"></i>Back to Patients
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id; ?>" method="post">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="username" class="form-control" 
                                           value="<?php echo htmlspecialchars($patient['username']); ?>"
                                           pattern="[a-zA-Z0-9_]{3,20}"
                                           title="Username should be 3-20 characters long and can only contain letters, numbers, and underscores"
                                           required>
                                    <label>Username *</label>
                                </div>
                                <div class="form-text">3-20 characters, letters, numbers, and underscores only</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($patient['email']); ?>"
                                           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                                           title="Please enter a valid email address"
                                           required>
                                    <label>Email *</label>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="first_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($patient['first_name']); ?>"
                                           pattern="[a-zA-Z\s]{2,50}"
                                           title="First name should only contain letters and spaces (2-50 characters)"
                                           required>
                                    <label>First Name *</label>
                                </div>
                                <div class="form-text">Only letters and spaces allowed (2-50 characters)</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="middle_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($patient['middle_name']); ?>"
                                           pattern="[a-zA-Z\s]{2,50}"
                                           title="Middle name should only contain letters and spaces (2-50 characters)">
                                    <label>Middle Name</label>
                                </div>
                                <div class="form-text">Only letters and spaces allowed (2-50 characters)</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($patient['last_name']); ?>"
                                           pattern="[a-zA-Z\s]{2,50}"
                                           title="Last name should only contain letters and spaces (2-50 characters)"
                                           required>
                                    <label>Last Name *</label>
                                </div>
                                <div class="form-text">Only letters and spaces allowed (2-50 characters)</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($patient['phone']); ?>"
                                           pattern="09[0-9]{9}"
                                           title="Phone number should be in Philippine format (09XXXXXXXXX)"
                                           placeholder="09XXXXXXXXX"
                                           required>
                                    <label>Phone *</label>
                                </div>
                                <div class="form-text">Format: 09XXXXXXXXX (11 digits)</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <input type="date" name="date_of_birth" class="form-control" id="date_of_birth"
                                           value="<?php echo $patient['date_of_birth']; ?>"
                                           max="<?php echo date('Y-m-d'); ?>"
                                           onchange="calculateAgeAndToggleGuardian()">
                                    <label>Date of Birth</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <select name="gender" class="form-select">
                                        <option value="male" <?php echo $patient['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo $patient['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo $patient['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                    <label>Gender</label>
                                </div>
                            </div>

                            <!-- Guardian Information Section -->
                            <div class="col-12">
                                <div class="card mb-3" id="guardianInfoCard" style="display: none;">
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Guardian Information (Required for Minors)</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-12">
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    Parent/Guardian information is required for patients under 18 years of age
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="guardian_name" name="guardian_name" 
                                                           value="<?php echo htmlspecialchars($patient['guardian_name'] ?? ''); ?>">
                                                    <label for="guardian_name">Guardian Full Name</label>
                                                    <div class="invalid-feedback">Guardian name is required for patients under 18.</div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select class="form-select" id="guardian_relationship" name="guardian_relationship">
                                                        <option value="">-- Select Relationship --</option>
                                                        <option value="Parent" <?php echo ($patient['guardian_relationship'] ?? '') === 'Parent' ? 'selected' : ''; ?>>Parent</option>
                                                        <option value="Legal Guardian" <?php echo ($patient['guardian_relationship'] ?? '') === 'Legal Guardian' ? 'selected' : ''; ?>>Legal Guardian</option>
                                                        <option value="Grandparent" <?php echo ($patient['guardian_relationship'] ?? '') === 'Grandparent' ? 'selected' : ''; ?>>Grandparent</option>
                                                        <option value="Sibling" <?php echo ($patient['guardian_relationship'] ?? '') === 'Sibling' ? 'selected' : ''; ?>>Sibling (18+ years old)</option>
                                                        <option value="Other" <?php echo ($patient['guardian_relationship'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                                    </select>
                                                    <label for="guardian_relationship">Relationship to Patient</label>
                                                    <div class="invalid-feedback">Please select the guardian's relationship to the patient.</div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="tel" class="form-control" id="guardian_phone" name="guardian_phone" 
                                                           pattern="09[0-9]{9}"
                                                           maxlength="11"
                                                           placeholder="09XXXXXXXXX"
                                                           value="<?php echo htmlspecialchars($patient['guardian_phone'] ?? ''); ?>">
                                                    <label for="guardian_phone">Guardian Phone Number</label>
                                                    <div class="form-text">Format: 09XXXXXXXXX (e.g., 09123456789)</div>
                                                    <div class="invalid-feedback">Please enter a valid guardian phone number (Format: 09XXXXXXXXX).</div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="email" class="form-control" id="guardian_email" name="guardian_email"
                                                           value="<?php echo htmlspecialchars($patient['guardian_email'] ?? ''); ?>">
                                                    <label for="guardian_email">Guardian Email (Optional)</label>
                                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="guardian_consent" name="guardian_consent" value="1"
                                                           <?php echo ($patient['guardian_consent'] ?? 0) == 1 ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="guardian_consent">
                                                        I confirm that I am the parent/legal guardian of this patient or have the authority to provide consent for dental treatment.
                                                    </label>
                                                    <div class="invalid-feedback">Guardian consent is required for patients under 18.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea name="address" class="form-control" style="height: 100px" 
                                              pattern="[a-zA-Z0-9\s,.-]{5,200}"
                                              title="Address should be between 5 and 200 characters"><?php echo htmlspecialchars($patient['address']); ?></textarea>
                                    <label>Address</label>
                                    <div class="form-text">Enter a valid address (5-200 characters)</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea name="medical_history" class="form-control" style="height: 150px"
                                              pattern="[a-zA-Z0-9\s,.-]{0,1000}"
                                              title="Medical history should not exceed 1000 characters"><?php echo htmlspecialchars($patient['medical_history']); ?></textarea>
                                    <label>Medical History</label>
                                    <div class="form-text">Enter medical history (max 1000 characters)</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="password" name="password" class="form-control" 
                                           pattern=".{6,}"
                                           title="Password must be at least 6 characters long">
                                    <label>New Password (leave blank to keep current)</label>
                                    <div class="form-text">Minimum 6 characters</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add this JavaScript at the end of the file, before the closing </body> tag -->
<script>
// Function to calculate age and toggle guardian section
function calculateAgeAndToggleGuardian() {
    const dateOfBirthInput = document.getElementById('date_of_birth');
    const guardianInfoCard = document.getElementById('guardianInfoCard');
    
    if (!dateOfBirthInput.value) {
        guardianInfoCard.style.display = 'none';
        return;
    }
    
    const dob = new Date(dateOfBirthInput.value);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const monthDifference = today.getMonth() - dob.getMonth();
    
    if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < dob.getDate())) {
        age--;
    }
    
    // Toggle guardian info section based on age
    if (age < 18) {
        guardianInfoCard.style.display = 'block';
        
        // Make guardian fields required
        document.getElementById('guardian_name').required = true;
        document.getElementById('guardian_relationship').required = true;
        document.getElementById('guardian_phone').required = true;
        document.getElementById('guardian_consent').required = true;
    } else {
        guardianInfoCard.style.display = 'none';
        
        // Remove required attribute
        document.getElementById('guardian_name').required = false;
        document.getElementById('guardian_relationship').required = false;
        document.getElementById('guardian_phone').required = false;
        document.getElementById('guardian_consent').required = false;
    }
}

// Run on page load to set initial state
document.addEventListener('DOMContentLoaded', function() {
    calculateAgeAndToggleGuardian();
});
</script>

<?php require_once "includes/footer.php"; ?> 