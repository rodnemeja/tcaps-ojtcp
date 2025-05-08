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

// Updated to handle the walk-in mode and family information:
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if walk-in mode is enabled
    $walk_in_mode = isset($_POST["walk_in_mode"]) ? true : false;
    
    // Basic patient information
    $email = trim($_POST["email"]);
    $first_name = trim($_POST["first_name"]);
    $middle_name = trim($_POST["middle_name"]);
    $last_name = trim($_POST["last_name"]);
    $phone = trim($_POST["phone"]);
    $date_of_birth = $_POST["date_of_birth"];
    $gender = $_POST["gender"];
    $address = trim($_POST["address"]);
    $region = $_POST["region"];
    $city = $_POST["city"];
    $barangay = $_POST["barangay"];
    $zipcode = $_POST["zipcode"];
    
    // Generate a temporary username for walk-in patients
    $username = "walkin_" . strtolower(substr($first_name, 0, 3) . substr($last_name, 0, 3) . substr(uniqid(), -4));
    
    // Calculate age from date of birth to determine if patient is a minor
    $age = null;
    $is_minor = false;
    if (!empty($date_of_birth)) {
        $birth_date = new DateTime($date_of_birth);
        $today = new DateTime();
        $age = $birth_date->diff($today)->y;
        $is_minor = ($age < 18);
    } else if (!empty($_POST["age"])) {
        $age = intval($_POST["age"]);
        $is_minor = ($age < 18);
    }
    
    // Guardian information (required for minors)
    $guardian_name = isset($_POST["guardian_name"]) ? trim($_POST["guardian_name"]) : "";
    $guardian_relationship = isset($_POST["guardian_relationship"]) ? trim($_POST["guardian_relationship"]) : "";
    $guardian_phone = isset($_POST["guardian_phone"]) ? trim($_POST["guardian_phone"]) : "";
    $guardian_email = isset($_POST["guardian_email"]) ? trim($_POST["guardian_email"]) : "";
    $guardian_consent = isset($_POST["guardian_consent"]) ? 1 : 0;
    
    // Validate guardian information for minors
    if ($is_minor) {
        if (empty($guardian_name) || empty($guardian_relationship) || empty($guardian_phone) || !$guardian_consent) {
            $error = "Guardian information and consent are required for patients under 18 years of age.";
        }
    }
    
    // Family information
    $family_option = isset($_POST["family_option"]) ? $_POST["family_option"] : "none";
    $family_name = isset($_POST["family_name"]) ? trim($_POST["family_name"]) : "";
    $family_role = isset($_POST["family_role"]) ? trim($_POST["family_role"]) : "";
    $existing_family_code = isset($_POST["existing_family_code"]) ? trim($_POST["existing_family_code"]) : "";
    $join_family_role = isset($_POST["join_family_role"]) ? trim($_POST["join_family_role"]) : "";
    
    // Auto-generate username if walk-in mode is enabled and username is empty
    if($walk_in_mode && empty($username)) {
        // Generate username based on first and last name
        $base_username = strtolower(substr($first_name, 0, 1) . $last_name);
        $base_username = preg_replace('/[^a-z0-9]/', '', $base_username); // Remove special chars
        
        // Check if username exists, if so, add a random number
        $username = $base_username;
        $counter = 1;
        $original_username = $username;
        
        do {
            $sql = "SELECT id FROM users WHERE username = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) > 0) {
                $username = $original_username . $counter;
                $counter++;
            } else {
                break;
            }
        } while(true);
    }
    
    // Generate random password for walk-in patients
    $password = bin2hex(random_bytes(4)); // 8 character random password

    // Validate input with regex
    if(empty($email) || empty($first_name) || empty($last_name) || empty($phone)) {
        $error = "Please fill in all required fields.";
    } elseif(!preg_match("/^[a-zA-Z\s]{2,50}$/", $first_name)) {
        $error = "First name should only contain letters and spaces (2-50 characters).";
    } elseif(!empty($middle_name) && !preg_match("/^[a-zA-Z\s]{2,50}$/", $middle_name)) {
        $error = "Middle name should only contain letters and spaces (2-50 characters).";
    } elseif(!preg_match("/^[a-zA-Z\s]{2,50}$/", $last_name)) {
        $error = "Last name should only contain letters and spaces (2-50 characters).";
    } elseif(!preg_match("/^09\d{9}$/", $phone)) {
        $error = "Phone number should be in Philippine format (09XXXXXXXXX).";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif(!empty($date_of_birth) && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_of_birth)) {
        $error = "Invalid date format.";
    } else {
        // Check if email exists
        $sql = "SELECT id FROM users WHERE email = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) > 0) {
                $error = "This email is already registered.";
            }
        }

        if(empty($error)) {
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Insert new user
                $sql = "INSERT INTO users (username, password, email, first_name, middle_name, last_name, phone, role) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                if($stmt = mysqli_prepare($conn, $sql)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $role = "patient";
                    mysqli_stmt_bind_param($stmt, "ssssssss", 
                        $username, 
                        $hashed_password, 
                        $email, 
                        $first_name,
                        $middle_name,
                        $last_name,
                        $phone, 
                        $role
                    );
                    
                    if(mysqli_stmt_execute($stmt)) {
                        $user_id = mysqli_insert_id($conn);

                        // Insert patient information
                        $sql = "INSERT INTO patients (
                            user_id, date_of_birth, age, gender, address, region, city, barangay, zipcode,
                            is_minor, guardian_name, guardian_relationship, guardian_phone, guardian_email, guardian_consent
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?,
                            ?, ?, ?, ?, ?, ?
                        )";
                        if($stmt = mysqli_prepare($conn, $sql)) {
                            mysqli_stmt_bind_param($stmt, "isissssssissssi", 
                                $user_id, 
                                $date_of_birth,
                                $age,
                                $gender, 
                                $address,
                                $region,
                                $city,
                                $barangay,
                                $zipcode,
                                $is_minor,
                                $guardian_name,
                                $guardian_relationship,
                                $guardian_phone,
                                $guardian_email,
                                $guardian_consent
                            );
                            
                            if(mysqli_stmt_execute($stmt)) {
                                $patient_id = mysqli_insert_id($conn);

                                // Insert medical history if provided
                                if(isset($_POST['medical_history_json'])) {
                                    $medical_history_data = json_decode($_POST['medical_history_json'], true);
                                    
                                    // Encrypt the medical history data
                                    $encrypted_medical_history = encryptMedicalData($_POST['medical_history_json']);
                                    
                                    // Insert into medical_history table
                                    $sql = "INSERT INTO medical_history (
                                        patient_id, has_allergies, allergies_details, has_medications, 
                                        medications_details, medical_conditions, other_conditions_details, 
                                        additional_notes, encrypted_data
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                    
                                    if($stmt = mysqli_prepare($conn, $sql)) {
                                        // Check for allergies in med_conditions
                                        $has_allergies = in_array('allergy', $medical_history_data['med_conditions']) ? 1 : 0;
                                        $allergies_details = $has_allergies ? 'Allergy reported' : null;
                                        
                                        // Check for medications
                                        $has_medications = !empty($medical_history_data['current_medication']) ? 1 : 0;
                                        $medications_details = $medical_history_data['current_medication'] ?? null;
                                        
                                        // Get all medical conditions
                                        $medical_conditions = implode(', ', $medical_history_data['med_conditions']);
                                        
                                        // Other conditions and notes
                                        $other_conditions = $medical_history_data['other_illness'] ?? null;
                                        $additional_notes = json_encode([
                                            'dental_health_status' => $medical_history_data['dental_health_status'] ?? null,
                                            'oral_prophylaxis' => $medical_history_data['oral_prophylaxis'] ?? null,
                                            'pregnancy_status' => $medical_history_data['pregnant'] ?? 'no',
                                            'hospitalization' => $medical_history_data['hospitalization'] ?? 'no',
                                            'hospitalization_cause' => $medical_history_data['hospitalization_cause'] ?? null
                                        ]);

                                        mysqli_stmt_bind_param($stmt, "iisssssss", 
                                            $patient_id,
                                            $has_allergies,
                                            $allergies_details,
                                            $has_medications,
                                            $medications_details,
                                            $medical_conditions,
                                            $other_conditions,
                                            $additional_notes,
                                            $encrypted_medical_history
                                        );

                                        if(!mysqli_stmt_execute($stmt)) {
                                            throw new Exception("Error inserting medical history: " . mysqli_error($conn));
                                        }
                                    }
                                }

                                // Process family information if selected
                                if($family_option == "create" && !empty($family_name) && !empty($family_role)) {
                                    // Generate a unique family code
                                    $unique_code = false;
                                    $family_code = "";
                                    
                                    while (!$unique_code) {
                                        // Generate a 6-character alphanumeric code
                                        $family_code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
                                        
                                        // Check if code already exists
                                        $sql = "SELECT * FROM family_codes WHERE code = ?";
                                        if($stmt = mysqli_prepare($conn, $sql)){
                                            mysqli_stmt_bind_param($stmt, "s", $family_code);
                                            mysqli_stmt_execute($stmt);
                                            $result = mysqli_stmt_get_result($stmt);
                                            if(mysqli_num_rows($result) == 0) {
                                                $unique_code = true;
                                            }
                                        }
                                    }
                                    
                                    // Create family code record
                                    $sql = "INSERT INTO family_codes (code, name, created_by) VALUES (?, ?, ?)";
                                    if($stmt = mysqli_prepare($conn, $sql)){
                                        mysqli_stmt_bind_param($stmt, "ssi", $family_code, $family_name, $patient_id);
                                        mysqli_stmt_execute($stmt);
                                    }
                                    
                                    // Update patient's family code
                                    $sql = "UPDATE patients SET family_code = ?, family_role = ? WHERE id = ?";
                                    if($stmt = mysqli_prepare($conn, $sql)){
                                        mysqli_stmt_bind_param($stmt, "ssi", $family_code, $family_role, $patient_id);
                                        mysqli_stmt_execute($stmt);
                                    }
                                    
                                    $success = "Patient added successfully! New family created with code: " . $family_code;
                                } 
                                else if($family_option == "join" && !empty($existing_family_code) && !empty($join_family_role)) {
                                    // Check if family code exists
                                    $sql = "SELECT * FROM family_codes WHERE code = ?";
                                    if($stmt = mysqli_prepare($conn, $sql)){
                                        mysqli_stmt_bind_param($stmt, "s", $existing_family_code);
                                        mysqli_stmt_execute($stmt);
                                        $result = mysqli_stmt_get_result($stmt);
                                        
                                        if(mysqli_num_rows($result) > 0) {
                                            // Update patient's family code
                                            $sql = "UPDATE patients SET family_code = ?, family_role = ? WHERE id = ?";
                                            if($stmt = mysqli_prepare($conn, $sql)){
                                                mysqli_stmt_bind_param($stmt, "ssi", $existing_family_code, $join_family_role, $patient_id);
                                                mysqli_stmt_execute($stmt);
                                                
                                                $success = "Patient added successfully and joined existing family!";
                                            }
                                        } else {
                                            // Family code doesn't exist, but continue with patient creation
                                            $success = "Patient added successfully, but family code was not found!";
                                        }
                                    }
                                } else {
                                    // No family association
                                    $success = "Patient added successfully!";
                                }
                                
                                // If walk-in mode is enabled, setup auto-redirect to appointment creation
                                if($walk_in_mode) {
                                    $_SESSION['walk_in_patient_id'] = $patient_id;
                                    $_SESSION['walk_in_patient_name'] = $first_name . " " . $last_name;
                                    
                                    // Store patient age for procedure validation
                                    if (!empty($date_of_birth)) {
                                        $birth_date = new DateTime($date_of_birth);
                                        $today = new DateTime();
                                        $_SESSION['walk_in_patient_age'] = $birth_date->diff($today)->y;
                                    } else if (!empty($_POST['age'])) {
                                        $_SESSION['walk_in_patient_age'] = intval($_POST['age']);
                                    }
                                    
                                    // Store selected procedure if any
                                    if (!empty($_POST['walk_in_service'])) {
                                        $_SESSION['walk_in_service'] = $_POST['walk_in_service'];
                                        
                                        // Add warning messages for age-restricted procedures
                                        $patient_age = $_SESSION['walk_in_patient_age'] ?? null;
                                        $service = $_POST['walk_in_service'];
                                        
                                        if ($patient_age !== null) {
                                            $age_warning = '';
                                            
                                            if ($service === 'extraction') {
                                                if ($patient_age < 3) {
                                                    $age_warning = " Note: Pediatric specialist consultation recommended for extraction under age 3.";
                                                } else if ($patient_age < 7) {
                                                    $age_warning = " Note: Special consideration needed for extraction under age 7.";
                                                } else if ($patient_age < 18) {
                                                    $age_warning = " Note: Parental consent required for extraction under age 18.";
                                                }
                                            } else if ($service === 'root_canal' && $patient_age < 7) {
                                                $age_warning = " Note: Specialist consultation recommended for root canal under age 7.";
                                            }
                                            
                                            if (!empty($age_warning)) {
                                                $success .= $age_warning;
                                            }
                                        }
                                    }
                                    
                                    $success .= " Redirecting to appointment creation...";
                                }
                                
                                // Commit transaction
                                mysqli_commit($conn);
                            } else {
                                throw new Exception("Error adding patient information: " . mysqli_error($conn));
                            }
                        }
                    } else {
                        throw new Exception("Error creating user: " . mysqli_error($conn));
                    }
                }
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($conn);
                $error = $e->getMessage();
            }
        }
    }
}

$page_title = "Add New Patient";
$current_page = "patients";
require_once "includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Add New Patient</h2>
    <a href="patients.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Patients
    </a>
</div>

<?php if(!empty($error)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?php echo $error; ?>',
                confirmButtonColor: '#3085d6'
            });
        });
    </script>
<?php endif; ?>

<?php if(!empty($success)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo $success; ?>',
                confirmButtonColor: '#3085d6'
            }).then((result) => {
                if (result.isConfirmed) {
                    <?php if(isset($_SESSION['walk_in_patient_id'])): ?>
                    // Redirect to appointment creation for walk-in patients
                    window.location.href = 'appointment_form.php?patient_id=<?php echo $_SESSION['walk_in_patient_id']; ?>';
                    <?php else: ?>
                    window.location.href = 'patients.php';
                    <?php endif; ?>
                }
            });
        });
    </script>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Walk-in Registration Options</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="walkInMode" name="walk_in_mode" checked>
                    <label class="form-check-label fw-bold" for="walkInMode">Walk-in Quick Registration Mode</label>
                    <div class="form-text">Enable for faster registration of walk-in patients. Auto-generates credentials and requires only essential fields.</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating mb-3">
                    <select class="form-select" id="walkInService" name="walk_in_service">
                        <option value="">-- Select Procedure (if known) --</option>
                        <option value="check_up">Regular Check-up</option>
                        <option value="cleaning">Teeth Cleaning</option>
                        <option value="extraction">Tooth Extraction</option>
                        <option value="filling">Dental Filling</option>
                        <option value="root_canal">Root Canal</option>
                        <option value="other">Other Procedure</option>
                    </select>
                    <label for="walkInService">Planned Dental Procedure</label>
                </div>
            </div>
        </div>
        <div id="ageWarningAlert" class="alert alert-warning d-none mt-2">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="ageWarningText"></span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="email" name="email" class="form-control" 
                               pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                               title="Please enter a valid email address"
                               required>
                        <label>Email *</label>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="text" name="first_name" class="form-control" 
                               pattern="[a-zA-Z\s]{2,50}"
                               title="First name should only contain letters and spaces (2-50 characters)"
                               required>
                        <label>First Name *</label>
                    </div>
                    <div class="form-text">Only letters and spaces allowed (2-50 characters)</div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="text" name="middle_name" class="form-control" 
                               pattern="[a-zA-Z\s]{2,50}"
                               title="Middle name should only contain letters and spaces (2-50 characters)">
                        <label>Middle Name</label>
                    </div>
                    <div class="form-text">Optional (2-50 characters)</div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="text" name="last_name" class="form-control" 
                               pattern="[a-zA-Z\s]{2,50}"
                               title="Last name should only contain letters and spaces (2-50 characters)"
                               required>
                        <label>Last Name *</label>
                    </div>
                    <div class="form-text">Only letters and spaces allowed (2-50 characters)</div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="tel" name="phone" id="phone" class="form-control" 
                               pattern="^09\d{9}$"
                               maxlength="11"
                               oninput="validatePhoneNumber(this)"
                               onkeypress="return onlyNumbers(event)"
                               placeholder="09XXXXXXXXX"
                               required>
                        <label>Phone Number *</label>
                        <div class="form-text">Format: 09XXXXXXXXX (e.g., 09123456789)</div>
                        <div id="phone-error" class="invalid-feedback">
                            Please enter a valid Philippine mobile number starting with 09.
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="date" name="date_of_birth" id="date_of_birth" class="form-control" 
                               max="<?php echo date('Y-m-d'); ?>"
                               onchange="calculateAge()">
                        <label>Date of Birth</label>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="number" name="age" id="age" class="form-control" 
                               min="0" max="150"
                               onchange="calculateDateOfBirth()">
                        <label>Age</label>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <select name="gender" class="form-select">
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                        <label>Gender</label>
                    </div>
                </div>

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
                                        <input type="text" class="form-control" id="guardian_name" name="guardian_name">
                                        <label for="guardian_name">Guardian Full Name</label>
                                        <div class="invalid-feedback">Guardian name is required for patients under 18.</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="guardian_relationship" name="guardian_relationship">
                                            <option value="">-- Select Relationship --</option>
                                            <option value="Parent">Parent</option>
                                            <option value="Legal Guardian">Legal Guardian</option>
                                            <option value="Grandparent">Grandparent</option>
                                            <option value="Sibling">Sibling (18+ years old)</option>
                                            <option value="Other">Other</option>
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
                                               placeholder="09XXXXXXXXX">
                                        <label for="guardian_phone">Guardian Phone Number</label>
                                        <div class="form-text">Format: 09XXXXXXXXX (e.g., 09123456789)</div>
                                        <div class="invalid-feedback">Please enter a valid guardian phone number (Format: 09XXXXXXXXX).</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="guardian_email" name="guardian_email">
                                        <label for="guardian_email">Guardian Email (Optional)</label>
                                        <div class="invalid-feedback">Please enter a valid email address.</div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="guardian_consent" name="guardian_consent" value="1">
                                        <label class="form-check-label" for="guardian_consent">
                                            I confirm that I am the parent/legal guardian of this patient or have the authority to provide consent for dental treatment.
                                        </label>
                                        <div class="invalid-feedback" style="display: none;">Guardian consent is required for patients under 18.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Address Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="region" name="region" onchange="loadProvinces()">
                                            <option value="">Select Region</option>
                                        </select>
                                        <label>Region</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="province" name="province" onchange="loadCities()" disabled>
                                            <option value="">Select Province</option>
                                        </select>
                                        <label>Province</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="city" name="city" onchange="loadBarangays()" disabled>
                                            <option value="">Select City/Municipality</option>
                                        </select>
                                        <label>City/Municipality</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="barangay" name="barangay" disabled>
                                            <option value="">Select Barangay</option>
                                        </select>
                                        <label>Barangay</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="street_address" name="street_address" placeholder="House/Building No., Street Name">
                                        <label>Street Address (House/Building No., Street Name)</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="zipcode" name="zipcode" readonly>
                                        <label>Zipcode</label>
                                    </div>
                                </div>

                                <!-- Hidden input to store complete address -->
                                <input type="hidden" name="address" id="complete_address">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Family Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="family_option" id="no_family" value="none" checked>
                                        <label class="form-check-label" for="no_family">No Family Association</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="family_option" id="create_family" value="create">
                                        <label class="form-check-label" for="create_family">Create New Family</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="family_option" id="join_family" value="join">
                                        <label class="form-check-label" for="join_family">Join Existing Family</label>
                                    </div>
                                </div>

                                <!-- Create New Family (hidden by default) -->
                                <div class="col-12" id="createFamilySection" style="display: none;">
                                    <div class="card border border-success">
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="family_name" class="form-label">Family Name</label>
                                                <input type="text" class="form-control" id="family_name" name="family_name" placeholder="e.g., Santos Family">
                                                <div class="form-text">Enter a name for the new family group</div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="family_role" class="form-label">Patient's Role in Family</label>
                                                <select class="form-select" id="family_role" name="family_role">
                                                    <option value="">-- Select role --</option>
                                                    <option value="Parent">Parent</option>
                                                    <option value="Child">Child</option>
                                                    <option value="Grandparent">Grandparent</option>
                                                    <option value="Sibling">Sibling</option>
                                                    <option value="Spouse">Spouse</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                            <div class="alert alert-info mb-0">
                                                <i class="fas fa-info-circle me-2"></i>
                                                A unique family code will be automatically generated when you save.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Join Existing Family (hidden by default) -->
                                <div class="col-12" id="joinFamilySection" style="display: none;">
                                    <div class="card border border-primary">
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="family_search" class="form-label">Search for Family</label>
                                                <div class="input-group mb-3">
                                                    <input type="text" class="form-control" id="family_search" placeholder="Search by patient name or family code">
                                                    <button class="btn btn-outline-primary" type="button" id="searchFamilyBtn">
                                                        <i class="fas fa-search"></i> Search
                                                    </button>
                                                </div>
                                            </div>
                                            <div id="familySearchResults" class="mb-3">
                                                <!-- Search results will be displayed here -->
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    Search for existing families by patient name or family code
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="existing_family_code" class="form-label">Family Code</label>
                                                <input type="text" class="form-control" id="existing_family_code" name="existing_family_code" placeholder="Enter family code">
                                                <div class="form-text">Enter the family code directly if you know it</div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="join_family_role" class="form-label">Patient's Role in Family</label>
                                                <select class="form-select" id="join_family_role" name="join_family_role">
                                                    <option value="">-- Select role --</option>
                                                    <option value="Parent">Parent</option>
                                                    <option value="Child">Child</option>
                                                    <option value="Grandparent">Grandparent</option>
                                                    <option value="Sibling">Sibling</option>
                                                    <option value="Spouse">Spouse</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Medical & Dental History</h5>
                        </div>
                        <div class="card-body">
                            <!-- Medical History Section -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h6 class="text-primary">Medical History</h6>
                                    <hr>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">PREGNANT:</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="pregnant" value="no" id="pregnant_no" checked>
                                        <label class="form-check-label" for="pregnant_no">NO</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="pregnant" value="yes" id="pregnant_yes">
                                        <label class="form-check-label" for="pregnant_yes">YES</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="med_conditions[]" value="allergy" id="allergy">
                                        <label class="form-check-label" for="allergy">ALLERGY</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="med_conditions[]" value="blood_pressure" id="blood_pressure">
                                        <label class="form-check-label" for="blood_pressure">BLOOD PRESSURE</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="med_conditions[]" value="asthma" id="asthma">
                                        <label class="form-check-label" for="asthma">ASTHMA</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="med_conditions[]" value="heart_disease" id="heart_disease">
                                        <label class="form-check-label" for="heart_disease">HEART DISEASE</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">PREVIOUS HOSPITALIZATION FOR THE PAST 5 YEARS UP TO PRESENT:</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="hospitalization" value="no" id="hospitalization_no" checked>
                                        <label class="form-check-label" for="hospitalization_no">NO</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="hospitalization" value="yes" id="hospitalization_yes">
                                        <label class="form-check-label" for="hospitalization_yes">YES</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">TO WHAT CAUSE:</label>
                                    <input type="text" class="form-control" name="hospitalization_cause">
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">ARE YOU IN ANY MEDICATION NOW AT PRESENT:</label>
                                    <input type="text" class="form-control" name="current_medication">
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">ANY OTHER ILLNESS NOT MENTIONED:</label>
                                    <input type="text" class="form-control" name="other_illness">
                                </div>
                            </div>
                            
                            <!-- Dental Chart Section -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h6 class="text-primary">Dental Chart</h6>
                                    <hr>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">DENTAL HEALTH STATUS:</label>
                                    <input type="text" class="form-control" name="dental_health_status">
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">ORAL PROPHYLAXIS:</label>
                                    <input type="text" class="form-control" name="oral_prophylaxis">
                                </div>
                                
                                <!-- Upper Teeth -->
                                <div class="col-md-12 mb-4">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Last Extraction</h6>
                                        </div>
                                        <div class="card-body">
                                            <!-- First Section: 55-65 -->
                                            <div class="row">
                                                <!-- Operation and Condition with Teeth -->
                                                <div class="col-12 mb-4">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="fw-bold fs-5">RIGHT</span>
                                                        <div class="d-flex justify-content-between flex-grow-1 mx-4">
                                                            <?php 
                                                            $upperTeeth = [55, 54, 53, 52, 51, 61, 62, 63, 64, 65];
                                                            foreach($upperTeeth as $i => $tooth): 
                                                            ?>
                                                            <div class="text-center position-relative" style="width: 45px;">
                                                                <!-- Operation Checkbox -->
                                                                <div class="form-check mb-2">
                                                                    <input class="form-check-input" type="checkbox" name="upper_operation_<?php echo $i; ?>" value="1">
                                                                    <label class="form-check-label d-block">
                                                                        <small class="text-primary fw-bold">OPERATION</small>
                                                                    </label>
                                                                </div>
                                                                
                                                                <!-- Tooth Icon and Number -->
                                                                <div class="mb-2">
                                                                    <div class="tooth-icon border border-2 rounded-circle mx-auto mb-1 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                                                        <i class="fas fa-tooth text-primary fs-5"></i>
                                                                    </div>
                                                                    <span class="fw-bold"><?php echo $tooth; ?></span>
                                                                </div>
                                                                
                                                                <!-- Condition Checkbox -->
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="upper_condition_<?php echo $i; ?>" value="1">
                                                                    <label class="form-check-label d-block">
                                                                        <small class="text-danger fw-bold">CONDITION</small>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <span class="fw-bold fs-5">LEFT</span>
                                                    </div>
                                                </div>

                                                <!-- UPPER Label -->
                                                <div class="col-12 text-center mb-4">
                                                    <h5 class="fw-bold text-primary">(UPPER)</h5>
                                                </div>

                                                <!-- Teeth Numbers 18-28 -->
                                                <div class="col-12 mb-4">
                                                    <div class="d-flex justify-content-center gap-3">
                                                        <?php 
                                                        $upperLowerTeeth = [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28];
                                                        foreach($upperLowerTeeth as $i => $tooth): 
                                                        ?>
                                                        <div class="text-center position-relative" style="width: 45px;">
                                                            <!-- Operation Checkbox -->
                                                            <div class="form-check mb-2">
                                                                <input class="form-check-input" type="checkbox" name="upper_operation2_<?php echo $i; ?>" value="1">
                                                                <label class="form-check-label d-block">
                                                                    <small class="text-primary fw-bold">OPERATION</small>
                                                                </label>
                                                            </div>
                                                            
                                                            <!-- Tooth Icon and Number -->
                                                            <div class="mb-2">
                                                                <div class="tooth-icon border border-2 rounded-circle mx-auto mb-1 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                                                    <i class="fas fa-tooth text-primary fs-5"></i>
                                                                </div>
                                                                <span class="fw-bold"><?php echo $tooth; ?></span>
                                                            </div>
                                                            
                                                            <!-- Condition Checkbox -->
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="upper_condition2_<?php echo $i; ?>" value="1">
                                                                <label class="form-check-label d-block">
                                                                    <small class="text-danger fw-bold">CONDITION</small>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- LOWER Label -->
                                                <div class="col-12 text-center mb-4">
                                                    <h5 class="fw-bold text-primary">(LOWER)</h5>
                                                </div>

                                                <!-- Bottom Teeth 18-23 -->
                                                <div class="col-12 mb-3">
                                                    <div class="d-flex justify-content-center gap-3">
                                                        <?php 
                                                        $bottomTeeth = [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23];
                                                        foreach($bottomTeeth as $i => $tooth): 
                                                        ?>
                                                        <div class="text-center position-relative" style="width: 45px;">
                                                            <!-- Operation Checkbox -->
                                                            <div class="form-check mb-2">
                                                                <input class="form-check-input" type="checkbox" name="bottom_operation_<?php echo $i; ?>" value="1">
                                                                <label class="form-check-label d-block">
                                                                    <small class="text-primary fw-bold">OPERATION</small>
                                                                </label>
                                                            </div>
                                                            
                                                            <!-- Tooth Icon and Number -->
                                                            <div class="mb-2">
                                                                <div class="tooth-icon border border-2 rounded-circle mx-auto mb-1 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                                                    <i class="fas fa-tooth text-primary fs-5"></i>
                                                                </div>
                                                                <span class="fw-bold"><?php echo $tooth; ?></span>
                                                            </div>
                                                            
                                                            <!-- Condition Checkbox -->
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="bottom_condition_<?php echo $i; ?>" value="1">
                                                                <label class="form-check-label d-block">
                                                                    <small class="text-danger fw-bold">CONDITION</small>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Legend -->
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Legend</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4 mb-2">
                                                    <span class="fw-bold">C:</span> Caries
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <span class="fw-bold">Rf:</span> Root Fragment
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <span class="fw-bold">X:</span> Indicated for EXO
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <span class="fw-bold">Tf:</span> Temporary Filling
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <span class="fw-bold">M:</span> Missing
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <span class="fw-bold">RCT:</span> Root Canal Therapy
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <span class="fw-bold">Am:</span> Amalgam
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <span class="fw-bold">UN:</span> Unerupted
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <span class="fw-bold">LC:</span> Light Cured
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Save as JSON in the medical_history field -->
                            <input type="hidden" name="medical_history_json" id="medical_history_json">
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        A temporary password will be automatically generated for walk-in patients.
                    </div>
                </div>

                <div class="col-12">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="patients.php" class="btn btn-secondary me-md-2">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Patient
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?> 

<script>
// Age calculation functions
function calculateAge() {
    const dateOfBirth = document.getElementById('date_of_birth').value;
    const age = document.getElementById('age');
    
    if (!dateOfBirth) {
        age.value = "";
        return;
    }
    
    const dob = new Date(dateOfBirth);
    const today = new Date();
    const ageDiff = today.getFullYear() - dob.getFullYear();
    
    // Check if birthday has occurred this year
    const monthDiff = today.getMonth() - dob.getMonth();
    const dayDiff = today.getDate() - dob.getDate();
    
    let calculatedAge;
    if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
        calculatedAge = ageDiff - 1;
    } else {
        calculatedAge = ageDiff;
    }
    
    age.value = calculatedAge;
    
    // Check if guardian section should be shown/hidden
    toggleGuardianSection(calculatedAge);
}

// Calculate approximate Date of Birth based on age
function calculateDateOfBirth() {
    const age = document.getElementById('age').value;
    const dateOfBirth = document.getElementById('date_of_birth');
    
    if (!age) {
        dateOfBirth.value = "";
        return;
    }
    
    const today = new Date();
    const approxDob = new Date(today.getFullYear() - age, today.getMonth(), today.getDate());
    dateOfBirth.value = approxDob.toISOString().split('T')[0];
    
    // Check if guardian section should be shown/hidden
    toggleGuardianSection(parseInt(age));
}

// Toggle guardian section based on age
function toggleGuardianSection(age) {
    const guardianInfoCard = document.getElementById('guardianInfoCard');
    const guardianName = document.getElementById('guardian_name');
    const guardianRelationship = document.getElementById('guardian_relationship');
    const guardianPhone = document.getElementById('guardian_phone');
    const guardianConsent = document.getElementById('guardian_consent');
    
    // If age is under 18, show guardian section and make fields required
    if (age < 18) {
        guardianInfoCard.style.display = 'block';
        guardianName.setAttribute('required', 'required');
        guardianRelationship.setAttribute('required', 'required');
        guardianPhone.setAttribute('required', 'required');
        guardianConsent.setAttribute('required', 'required');
        
        // Add validation listeners if not already added
        if (!guardianName.hasAttribute('data-validation-added')) {
            guardianName.setAttribute('data-validation-added', 'true');
            guardianName.addEventListener('input', function() {
                validateGuardianField(this, this.value.trim() === '');
            });
            
            guardianRelationship.addEventListener('change', function() {
                validateGuardianField(this, this.value === '');
            });
            
            guardianPhone.addEventListener('input', function() {
                const isValid = /^09[0-9]{9}$/.test(this.value);
                validateGuardianField(this, !isValid && this.value !== '');
            });
            
            guardianConsent.addEventListener('change', function() {
                const consentFeedback = this.parentElement.querySelector('.invalid-feedback');
                if (!this.checked) {
                    this.classList.add('is-invalid');
                    consentFeedback.style.display = 'block';
                } else {
                    this.classList.remove('is-invalid');
                    consentFeedback.style.display = 'none';
                }
            });
        }
        
        // Call validation on page load for already filled fields
        validateGuardianField(guardianName, guardianName.value.trim() === '');
        validateGuardianField(guardianRelationship, guardianRelationship.value === '');
        validateGuardianField(guardianPhone, guardianPhone.value !== '' && !/^09[0-9]{9}$/.test(guardianPhone.value));
        
        // Check consent
        const consentFeedback = guardianConsent.parentElement.querySelector('.invalid-feedback');
        if (!guardianConsent.checked) {
            guardianConsent.classList.add('is-invalid');
            consentFeedback.style.display = 'block';
        } else {
            guardianConsent.classList.remove('is-invalid');
            consentFeedback.style.display = 'none';
        }
        
    } else {
        guardianInfoCard.style.display = 'none';
        guardianName.removeAttribute('required');
        guardianRelationship.removeAttribute('required');
        guardianPhone.removeAttribute('required');
        guardianConsent.removeAttribute('required');
        
        // Remove validation styles
        guardianName.classList.remove('is-invalid', 'is-valid');
        guardianRelationship.classList.remove('is-invalid', 'is-valid');
        guardianPhone.classList.remove('is-invalid', 'is-valid');
        guardianConsent.classList.remove('is-invalid', 'is-valid');
        guardianConsent.parentElement.querySelector('.invalid-feedback').style.display = 'none';
    }
}

// Helper function to validate guardian fields
function validateGuardianField(field, isInvalid) {
    if (isInvalid) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
    } else if (field.value.trim() !== '') {
        field.classList.add('is-valid');
        field.classList.remove('is-invalid');
    } else {
        field.classList.remove('is-valid', 'is-invalid');
    }
}

// Add form submit validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const originalSubmit = form.onsubmit;
    
    // Add direct age input event handler
    const ageInput = document.getElementById('age');
    ageInput.addEventListener('input', function() {
        if (this.value !== '') {
            toggleGuardianSection(parseInt(this.value));
        }
    });
    
    // Check initial values on page load
    const initialAge = document.getElementById('age').value;
    if (initialAge !== '') {
        toggleGuardianSection(parseInt(initialAge));
    }
    
    form.onsubmit = function(e) {
        const age = parseInt(document.getElementById('age').value) || 0;
        const dateOfBirth = document.getElementById('date_of_birth').value;
        
        // Calculate age if date of birth is provided but age isn't
        let calculatedAge = age;
        if (!age && dateOfBirth) {
            const dob = new Date(dateOfBirth);
            const today = new Date();
            calculatedAge = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();
            const dayDiff = today.getDate() - dob.getDate();
            if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
                calculatedAge--;
            }
        }
        
        // Validate guardian information for minors
        if (calculatedAge < 18) {
            const guardianName = document.getElementById('guardian_name');
            const guardianRelationship = document.getElementById('guardian_relationship');
            const guardianPhone = document.getElementById('guardian_phone');
            const guardianConsent = document.getElementById('guardian_consent');
            
            let isValid = true;
            
            // Check each required field
            if (guardianName.value.trim() === '') {
                guardianName.classList.add('is-invalid');
                isValid = false;
            }
            
            if (guardianRelationship.value === '') {
                guardianRelationship.classList.add('is-invalid');
                isValid = false;
            }
            
            if (guardianPhone.value.trim() === '' || !/^09[0-9]{9}$/.test(guardianPhone.value)) {
                guardianPhone.classList.add('is-invalid');
                isValid = false;
            }
            
            if (!guardianConsent.checked) {
                guardianConsent.classList.add('is-invalid');
                guardianConsent.parentElement.querySelector('.invalid-feedback').style.display = 'block';
                isValid = false;
            }
            
            if (!isValid) {
                // Scroll to the guardian section
                document.getElementById('guardianInfoCard').scrollIntoView({ behavior: 'smooth' });
                
                // Show error alert
                Swal.fire({
                    icon: 'error',
                    title: 'Missing Guardian Information',
                    text: 'Please complete all required guardian information for minor patients.',
                    confirmButtonColor: '#3085d6'
                });
                
                e.preventDefault();
                return false;
            }
        }
        
        // Call the original submit handler if it exists
        if (typeof originalSubmit === 'function') {
            return originalSubmit.call(this, e);
        }
    };
});

// Philippine Address API Integration
const API_ENDPOINT = 'https://psgc.gitlab.io/api';
let addressComponents = {
    region: '',
    province: '',
    city: '',
    barangay: '',
    street: ''
};

// Load Regions on page load
document.addEventListener('DOMContentLoaded', async function() {
    try {
        const response = await fetch(`${API_ENDPOINT}/regions`);
        const regions = await response.json();
        const regionSelect = document.getElementById('region');
        
        regions.sort((a, b) => a.name.localeCompare(b.name)).forEach(region => {
            const option = new Option(region.name, region.code);
            regionSelect.add(option);
        });
    } catch (error) {
        console.error('Error loading regions:', error);
    }
});

// Load Provinces based on selected Region
async function loadProvinces() {
    const regionCode = document.getElementById('region').value;
    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');
    const barangaySelect = document.getElementById('barangay');
    
    // Reset dependent dropdowns
    provinceSelect.innerHTML = '<option value="">Select Province</option>';
    citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    
    provinceSelect.disabled = !regionCode;
    citySelect.disabled = true;
    barangaySelect.disabled = true;
    
    if (!regionCode) return;
    
    try {
        const response = await fetch(`${API_ENDPOINT}/regions/${regionCode}/provinces`);
        const provinces = await response.json();
        
        provinces.sort((a, b) => a.name.localeCompare(b.name)).forEach(province => {
            const option = new Option(province.name, province.code);
            provinceSelect.add(option);
        });
        
        updateCompleteAddress();
    } catch (error) {
        console.error('Error loading provinces:', error);
    }
}

// Load Cities based on selected Province
async function loadCities() {
    const provinceCode = document.getElementById('province').value;
    const citySelect = document.getElementById('city');
    const barangaySelect = document.getElementById('barangay');
    
    // Reset dependent dropdowns
    citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    
    citySelect.disabled = !provinceCode;
    barangaySelect.disabled = true;
    
    if (!provinceCode) return;
    
    try {
        // Load both cities and municipalities
        const [citiesResponse, municipalitiesResponse] = await Promise.all([
            fetch(`${API_ENDPOINT}/provinces/${provinceCode}/cities`),
            fetch(`${API_ENDPOINT}/provinces/${provinceCode}/municipalities`)
        ]);

        const cities = await citiesResponse.json();
        const municipalities = await municipalitiesResponse.json();

        // Combine and sort all locations
        const allLocations = [...(Array.isArray(cities) ? cities : []), ...(Array.isArray(municipalities) ? municipalities : [])];
        
        allLocations.sort((a, b) => a.name.localeCompare(b.name)).forEach(location => {
            const option = new Option(location.name, location.code);
            citySelect.add(option);
        });
        
        updateCompleteAddress();
    } catch (error) {
        console.error('Error loading cities/municipalities:', error);
    }
}

// Load Barangays based on selected City/Municipality
async function loadBarangays() {
    const cityCode = document.getElementById('city').value;
    const barangaySelect = document.getElementById('barangay');
    
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    barangaySelect.disabled = !cityCode;
    
    if (!cityCode) return;
    
    try {
        // First try to load barangays using the city endpoint
        const cityResponse = await fetch(`${API_ENDPOINT}/cities/${cityCode}/barangays`);
        let barangays = [];
        
        if (cityResponse.ok) {
            barangays = await cityResponse.json();
        } else {
            // If city endpoint fails, try municipality endpoint
            const municipalityResponse = await fetch(`${API_ENDPOINT}/municipalities/${cityCode}/barangays`);
            if (municipalityResponse.ok) {
                barangays = await municipalityResponse.json();
            }
        }

        // Check if we got valid barangays data
        if (Array.isArray(barangays) && barangays.length > 0) {
            barangays
                .sort((a, b) => a.name.localeCompare(b.name))
                .forEach(barangay => {
                    const option = new Option(barangay.name, barangay.code);
                    barangaySelect.add(option);
                });
        } else {
            // If no barangays found, add a message
            const option = new Option("No barangays found", "");
            barangaySelect.add(option);
        }
        
        updateCompleteAddress();
    } catch (error) {
        console.error('Error loading barangays:', error);
        // Add error message to dropdown
        const option = new Option("Error loading barangays", "");
        barangaySelect.add(option);
    }
}

// Add zipcode mapping
const zipcodeMap = {
    // Metro Manila
    'National Capital Region': {
        'Manila': '1000',
        'Quezon City': '1100',
        'Caloocan': '1400',
        'Las Piñas': '1740',
        'Makati': '1200',
        'Malabon': '1470',
        'Mandaluyong': '1550',
        'Marikina': '1800',
        'Muntinlupa': '1770',
        'Navotas': '1485',
        'Parañaque': '1700',
        'Pasay': '1300',
        'Pasig': '1600',
        'San Juan': '1500',
        'Taguig': '1630',
        'Valenzuela': '1440'
    },
    // Zamboanga Peninsula (Region IX)
    'Zamboanga Peninsula': {
        'Zamboanga City': '7000',
        'Ipil': '7001',
        'Kabasalan': '7005',
        'Buug': '7009',
        'Diplahan': '7039',
        'Imelda': '7007',
        'Mabuhay': '7010',
        'Malangas': '7031',
        'Naga': '7004',
        'Olutanga': '7041',
        'Payao': '7008',
        'Roseller Lim': '7002',
        'Siay': '7006',
        'Talusan': '7012',
        'Titay': '7003',
        'Tungawan': '7018',
        'default': '7000'
    },
    'Cordillera Administrative Region': {'default': '2600'},
    'Ilocos Region': {'default': '2900'},
    'Cagayan Valley': {'default': '3500'},
    'Central Luzon': {'default': '2000'},
    'CALABARZON': {'default': '4100'},
    'MIMAROPA Region': {'default': '5300'},
    'Bicol Region': {'default': '4500'},
    'Western Visayas': {'default': '6100'},
    'Central Visayas': {'default': '6000'},
    'Eastern Visayas': {'default': '6500'},
    'Northern Mindanao': {'default': '9000'},
    'Davao Region': {'default': '8000'},
    'SOCCSKSARGEN': {'default': '9500'},
    'Caraga': {'default': '8400'},
    'Bangsamoro Autonomous Region in Muslim Mindanao': {'default': '9700'}
};

// Update the updateCompleteAddress function to include zipcode
function updateCompleteAddress() {
    const region = document.getElementById('region');
    const province = document.getElementById('province');
    const city = document.getElementById('city');
    const barangay = document.getElementById('barangay');
    const street = document.getElementById('street_address');
    const zipcodeInput = document.getElementById('zipcode');
    
    addressComponents = {
        region: region.options[region.selectedIndex]?.text || '',
        province: province.options[province.selectedIndex]?.text || '',
        city: city.options[city.selectedIndex]?.text || '',
        barangay: barangay.options[barangay.selectedIndex]?.text || '',
        street: street.value || ''
    };

    // Set zipcode based on region and city/municipality
    let zipcode = '';
    if (addressComponents.region && addressComponents.city) {
        const regionZipcodes = zipcodeMap[addressComponents.region];
        if (regionZipcodes) {
            // Try to get specific city/municipality zipcode, fallback to default
            zipcode = regionZipcodes[addressComponents.city] || regionZipcodes['default'] || '';
            
            // If still no zipcode, try to match partial city/municipality name
            if (!zipcode) {
                const cityName = addressComponents.city.toLowerCase();
                for (const [key, value] of Object.entries(regionZipcodes)) {
                    if (key !== 'default' && cityName.includes(key.toLowerCase())) {
                        zipcode = value;
                        break;
                    }
                }
                // If still no match, use default
                if (!zipcode) {
                    zipcode = regionZipcodes['default'] || '';
                }
            }
        }
    }
    zipcodeInput.value = zipcode;
    
    // Only include components that are not empty and not error messages
    const completeAddress = [
        addressComponents.street,
        (addressComponents.barangay && addressComponents.barangay !== "No barangays found" && addressComponents.barangay !== "Error loading barangays") ? addressComponents.barangay : '',
        addressComponents.city,
        addressComponents.province,
        addressComponents.region,
        zipcode ? `${zipcode}` : ''
    ].filter(Boolean).join(', ');
    
    document.getElementById('complete_address').value = completeAddress;
}

// Add event listeners for address changes
document.addEventListener('DOMContentLoaded', function() {
    // Existing event listeners...
    
    // Add change event listeners for all address components
    document.getElementById('region').addEventListener('change', updateCompleteAddress);
    document.getElementById('province').addEventListener('change', updateCompleteAddress);
    document.getElementById('city').addEventListener('change', updateCompleteAddress);
    document.getElementById('barangay').addEventListener('change', updateCompleteAddress);
    document.getElementById('street_address').addEventListener('input', updateCompleteAddress);
    
    // Initial load of regions
    loadRegions();
});

// Load Regions function
async function loadRegions() {
    try {
        const response = await fetch(`${API_ENDPOINT}/regions`);
        if (!response.ok) throw new Error('Failed to load regions');
        
        const regions = await response.json();
        const regionSelect = document.getElementById('region');
        
        regions
            .sort((a, b) => a.name.localeCompare(b.name))
            .forEach(region => {
                const option = new Option(region.name, region.code);
                regionSelect.add(option);
            });
    } catch (error) {
        console.error('Error loading regions:', error);
        const regionSelect = document.getElementById('region');
        regionSelect.innerHTML = '<option value="">Error loading regions</option>';
    }
}

// Existing medical history JSON data collection
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    // Calculate initial age if date of birth is pre-filled
    calculateAge();
    
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        
        // Show loading state
        Swal.fire({
            title: 'Adding Patient',
            text: 'Please wait...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Collect medical history data
        const medicalHistoryData = {
            pregnant: document.querySelector('input[name="pregnant"]:checked').value,
            med_conditions: Array.from(document.querySelectorAll('input[name="med_conditions[]"]:checked')).map(el => el.value),
            hospitalization: document.querySelector('input[name="hospitalization"]:checked').value,
            hospitalization_cause: document.querySelector('input[name="hospitalization_cause"]').value,
            current_medication: document.querySelector('input[name="current_medication"]').value,
            other_illness: document.querySelector('input[name="other_illness"]').value,
            dental_health_status: document.querySelector('input[name="dental_health_status"]').value,
            oral_prophylaxis: document.querySelector('input[name="oral_prophylaxis"]').value,
            
            // Upper teeth section
            upper_operations: [...Array(10)].map((_, i) => document.querySelector(`input[name="upper_operation_${i}"]`).checked ? 1 : 0),
            upper_conditions: [...Array(10)].map((_, i) => document.querySelector(`input[name="upper_condition_${i}"]`).checked ? 1 : 0),
            upper_operations2: [...Array(10)].map((_, i) => document.querySelector(`input[name="upper_operation2_${i}"]`).checked ? 1 : 0),
            upper_conditions2: [...Array(10)].map((_, i) => document.querySelector(`input[name="upper_condition2_${i}"]`).checked ? 1 : 0),
            
            // Lower teeth section
            lower_operations: [...Array(10)].map((_, i) => document.querySelector(`input[name="bottom_operation_${i}"]`).checked ? 1 : 0),
            lower_conditions: [...Array(10)].map((_, i) => document.querySelector(`input[name="bottom_condition_${i}"]`).checked ? 1 : 0)
        };
        
        document.getElementById('medical_history_json').value = JSON.stringify(medicalHistoryData);

        // Submit the form
        form.submit();
    });
});

// Add these new functions for phone number validation
function onlyNumbers(event) {
    const charCode = (event.which) ? event.which : event.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;
    }
    return true;
}

function validatePhoneNumber(input) {
    // Remove any non-numeric characters
    input.value = input.value.replace(/\D/g, '');
    
    // Ensure it starts with 09
    if (input.value.length > 0 && !input.value.startsWith('09')) {
        input.value = '09' + input.value.substring(2);
    }
    
    // Limit to 11 digits
    if (input.value.length > 11) {
        input.value = input.value.slice(0, 11);
    }
    
    // Check if the number is valid
    const isValid = /^09\d{9}$/.test(input.value);
    input.classList.toggle('is-invalid', !isValid && input.value.length > 0);
    input.classList.toggle('is-valid', isValid);
}

// Handle family option selection
document.addEventListener('DOMContentLoaded', function() {
    // Age validation for dental procedures
    const ageInput = document.getElementById('age');
    const dateOfBirthInput = document.getElementById('date_of_birth');
    const walkInServiceSelect = document.getElementById('walkInService');
    const ageWarningAlert = document.getElementById('ageWarningAlert');
    const ageWarningText = document.getElementById('ageWarningText');
    
    // Function to validate age for procedures
    function validateAgeForProcedure() {
        const age = parseInt(ageInput.value);
        const selectedProcedure = walkInServiceSelect.value;
        
        // Reset warning
        ageWarningAlert.classList.add('d-none');
        
        // If age is not provided or not a number, return
        if (isNaN(age)) return;
        
        let warningMessage = '';
        
        switch(selectedProcedure) {
            case 'extraction':
                if (age < 3) {
                    warningMessage = '<strong>Warning:</strong> Tooth extraction for patients under 3 years requires pediatric specialist consultation.';
                } else if (age < 7) {
                    warningMessage = '<strong>Note:</strong> Tooth extraction for patients under 7 years may require special consideration. Consider pediatric referral.';
                } else if (age < 18) {
                    warningMessage = '<strong>Reminder:</strong> Parental consent required for extraction procedures for patients under 18.';
                }
                break;
                
            case 'root_canal':
                if (age < 7) {
                    warningMessage = '<strong>Warning:</strong> Root canal on primary teeth (patients under 7) is uncommon and may require specialist consultation.';
                } else if (age < 18) {
                    warningMessage = '<strong>Reminder:</strong> Parental/guardian consent required for this procedure for patients under 18.';
                }
                break;
                
            case 'check_up':
                if (age < 1) {
                    warningMessage = '<strong>Note:</strong> First dental visit recommended between 6 months to 1 year, after first tooth eruption.';
                }
                break;
        }
        
        if (warningMessage) {
            ageWarningText.innerHTML = warningMessage;
            ageWarningAlert.classList.remove('d-none');
        }
    }
    
    // Add change event listeners
    ageInput.addEventListener('change', validateAgeForProcedure);
    walkInServiceSelect.addEventListener('change', validateAgeForProcedure);
    
    // Calculate age from date of birth and validate procedure
    dateOfBirthInput.addEventListener('change', function() {
        calculateAge();
        validateAgeForProcedure();
    });
    
    // Family section visibility toggles
    const createFamilySection = document.getElementById('createFamilySection');
    const joinFamilySection = document.getElementById('joinFamilySection');
    const familyOptions = document.querySelectorAll('input[name="family_option"]');
    
    familyOptions.forEach(option => {
        option.addEventListener('change', function() {
            // Hide all sections first
            createFamilySection.style.display = 'none';
            joinFamilySection.style.display = 'none';
            
            // Show the selected section
            if (this.value === 'create') {
                createFamilySection.style.display = 'block';
            } else if (this.value === 'join') {
                joinFamilySection.style.display = 'block';
            }
        });
    });
    
    // Walk-in mode toggle functionality
    const walkInModeToggle = document.getElementById('walkInMode');
    const usernameField = document.querySelector('input[name="username"]');
    const usernameContainer = usernameField.closest('.col-12');
    
    function updateWalkInMode() {
        if (walkInModeToggle.checked) {
            // In walk-in mode, make username optional by removing required attribute
            usernameField.removeAttribute('required');
            
            // Add a note that username will be auto-generated
            let noteElem = document.createElement('div');
            noteElem.className = 'form-text text-success walk-in-note';
            noteElem.innerHTML = '<i class="fas fa-info-circle"></i> Username will be auto-generated for walk-in patients';
            
            // Remove any existing notes
            document.querySelectorAll('.walk-in-note').forEach(el => el.remove());
            
            // Add the note
            usernameContainer.appendChild(noteElem);
        } else {
            // Not in walk-in mode, username is required
            usernameField.setAttribute('required', 'required');
            
            // Remove any auto-generate notes
            document.querySelectorAll('.walk-in-note').forEach(el => el.remove());
        }
    }
    
    // Initial update
    updateWalkInMode();
    
    // Update when toggle changes
    walkInModeToggle.addEventListener('change', updateWalkInMode);
    
    // Family search functionality
    const searchFamilyBtn = document.getElementById('searchFamilyBtn');
    const familySearchInput = document.getElementById('family_search');
    const familySearchResults = document.getElementById('familySearchResults');
    const existingFamilyCodeInput = document.getElementById('existing_family_code');
    
    searchFamilyBtn.addEventListener('click', function() {
        const searchTerm = familySearchInput.value.trim();
        
        if (searchTerm.length < 2) {
            familySearchResults.innerHTML = '<div class="alert alert-warning">Please enter at least 2 characters to search</div>';
            return;
        }
        
        familySearchResults.innerHTML = '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        
        // Fetch results via AJAX
        fetch(`search_families.php?term=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    familySearchResults.innerHTML = '<div class="alert alert-info">No families found matching your search</div>';
                    return;
                }
                
                let resultsHtml = '<div class="list-group">';
                
                data.forEach(family => {
                    resultsHtml += `
                        <button type="button" class="list-group-item list-group-item-action family-result" 
                                data-code="${family.code}" data-name="${family.name}">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${family.name}</h6>
                                <small class="text-primary">${family.code}</small>
                            </div>
                            <p class="mb-1">Members: ${family.member_count}</p>
                            <small>Created: ${family.created_at}</small>
                        </button>
                    `;
                });
                
                resultsHtml += '</div>';
                familySearchResults.innerHTML = resultsHtml;
                
                // Add click handlers to results
                document.querySelectorAll('.family-result').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const code = this.getAttribute('data-code');
                        existingFamilyCodeInput.value = code;
                        
                        // Highlight all results as inactive, this one as active
                        document.querySelectorAll('.family-result').forEach(el => {
                            el.classList.remove('active');
                        });
                        this.classList.add('active');
                    });
                });
            })
            .catch(error => {
                familySearchResults.innerHTML = `<div class="alert alert-danger">Error searching for families: ${error.message}</div>`;
            });
    });
    
    // Allow pressing Enter in search field
    familySearchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchFamilyBtn.click();
        }
    });
});
</script> 