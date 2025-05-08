<?php
session_start();
require_once "config/database.php";
require_once "config/mail.php";

// Check if user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: dashboard.php");
    exit;
}

$username = $password = $confirm_password = $email = $first_name = $middle_name = $last_name = $phone = $date_of_birth = $address = $age = $region = $city = $barangay = $zipcode = "";
$username_err = $password_err = $confirm_password_err = $email_err = $first_name_err = $middle_name_err = $last_name_err = $phone_err = $date_of_birth_err = $address_err = $age_err = $region_err = $city_err = $barangay_err = "";

// Function to generate verification code
function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // If this is a verification code submission
    if(isset($_POST["verification_code"]) && isset($_SESSION["temp_user_id"])) {
        $verification_code = trim($_POST["verification_code"]);
        $user_id = $_SESSION["temp_user_id"];

        // Check if the verification code matches
        $sql = "SELECT * FROM email_verifications WHERE user_id = ? AND token = ? AND verified = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "is", $user_id, $verification_code);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if(mysqli_num_rows($result) > 0) {
                // Update user as verified
                $update_sql = "UPDATE users SET email_verified = 1 WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "i", $user_id);
                
                if(mysqli_stmt_execute($update_stmt)) {
                    // Mark verification code as used
                    $mark_used_sql = "UPDATE email_verifications SET verified = 1 WHERE user_id = ? AND token = ?";
                    $mark_used_stmt = mysqli_prepare($conn, $mark_used_sql);
                    mysqli_stmt_bind_param($mark_used_stmt, "is", $user_id, $verification_code);
                    mysqli_stmt_execute($mark_used_stmt);

                    // Start session
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $user_id;
                    
                    // Get user information
                    $user_sql = "SELECT username, role FROM users WHERE id = ?";
                    $user_stmt = mysqli_prepare($conn, $user_sql);
                    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
                    mysqli_stmt_execute($user_stmt);
                    $user_result = mysqli_stmt_get_result($user_stmt);
                    $user = mysqli_fetch_assoc($user_result);
                    
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["role"] = $user["role"];
                    
                    // Clear temporary session data
                    unset($_SESSION["temp_user_id"]);
                    
                    // Show success message and redirect
                    header("Location: verify_email.php");
                    exit;
                }
            } else {
                $verification_err = "Invalid or expired verification code.";
            }
        }
    } else {
        // Regular registration form processing
        // Validate username
        if(empty(trim($_POST["username"]))){
            $username_err = "Please enter a username.";
        } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))){
            $username_err = "Username can only contain letters, numbers, and underscores.";
        } else{
            $sql = "SELECT id FROM users WHERE username = ?";
            
            $stmt = mysqli_prepare($conn, $sql);
            if($stmt === false) {
                die("Error preparing statement: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim($_POST["username"]);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                $username_err = "Oops! Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
        
        // Validate email
        if(empty(trim($_POST["email"]))){
            $email_err = "Please enter an email.";
        } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
            $email_err = "Please enter a valid email address.";
        } else{
            $sql = "SELECT id FROM users WHERE email = ?";
            
            $stmt = mysqli_prepare($conn, $sql);
            if($stmt === false) {
                die("Error preparing statement: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = trim($_POST["email"]);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "This email is already registered.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                $email_err = "Oops! Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
        
        // Validate first name
        if(empty(trim($_POST["first_name"]))){
            $first_name_err = "Please enter your first name.";
        } else{
            $first_name = trim($_POST["first_name"]);
        }
        
        // Validate middle name
        if(!empty(trim($_POST["middle_name"]))){
            $middle_name = trim($_POST["middle_name"]);
        }
        
        // Validate last name
        if(empty(trim($_POST["last_name"]))){
            $last_name_err = "Please enter your last name.";
        } else{
            $last_name = trim($_POST["last_name"]);
        }
        
        // Validate date of birth
        if(empty(trim($_POST["date_of_birth"]))){
            $date_of_birth_err = "Please enter your date of birth.";
        } else {
            $date_of_birth = trim($_POST["date_of_birth"]);
            // Check if date is valid and not in the future
            $dob_timestamp = strtotime($date_of_birth);
            if($dob_timestamp === false || $dob_timestamp > time()) {
                $date_of_birth_err = "Please enter a valid date of birth.";
            }
        }
        
        // Validate phone
        if(empty(trim($_POST["phone"]))){
            $phone_err = "Please enter your phone number.";
        } else{
            $phone = trim($_POST["phone"]);
        }
        
        // Validate region
        if(empty(trim($_POST["region"]))){
            $region_err = "Please select your region.";
        } else {
            $region = trim($_POST["region"]);
        }
        
        // Validate city
        if(empty(trim($_POST["city"]))){
            $city_err = "Please select your city.";
        } else {
            $city = trim($_POST["city"]);
        }

        // Validate age
        if(empty(trim($_POST["age"]))){
            $age_err = "Age is required.";
        } else {
            $age = trim($_POST["age"]);
            if(!is_numeric($age) || $age < 0 || $age > 120) {
                $age_err = "Please enter a valid age between 0 and 120.";
            }
        }
        
        // Validate address
        if(!empty(trim($_POST["address"]))){
            $address = trim($_POST["address"]);
        }
        
        // Validate barangay
        if(empty(trim($_POST["barangay"]))){
            $barangay_err = "Please select your barangay.";
        } else {
            $barangay = trim($_POST["barangay"]);
        }

        // Get zipcode
        if(!empty(trim($_POST["zipcode"]))){
            $zipcode = trim($_POST["zipcode"]);
        }

        // Validate password
        if(empty(trim($_POST["password"]))){
            $password_err = "Please enter a password.";     
        } elseif(strlen(trim($_POST["password"])) < 6){
            $password_err = "Password must have at least 6 characters.";
        } else{
            $password = trim($_POST["password"]);
        }
        
        // Validate confirm password
        if(empty(trim($_POST["confirm_password"]))){
            $confirm_password_err = "Please confirm password.";     
        } else{
            $confirm_password = trim($_POST["confirm_password"]);
            if(empty($password_err) && ($password != $confirm_password)){
                $confirm_password_err = "Password did not match.";
            }
        }
        
        // Check input errors before inserting in database
        if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($email_err) && empty($first_name_err) && empty($middle_name_err) && empty($last_name_err) && empty($phone_err) && empty($date_of_birth_err) && empty($address_err) && empty($age_err) && empty($region_err) && empty($city_err) && empty($barangay_err)){
            
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Insert into users table with email_verified set to 0
                $sql = "INSERT INTO users (username, password, email, first_name, middle_name, last_name, phone, role, email_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())";
                
                $stmt = mysqli_prepare($conn, $sql);
                if($stmt === false) {
                    throw new Exception("Error preparing statement: " . mysqli_error($conn));
                }
                
                mysqli_stmt_bind_param($stmt, "ssssssss", $param_username, $param_password, $param_email, $param_first_name, $param_middle_name, $param_last_name, $param_phone, $param_role);
                
                $param_username = $username;
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                $param_email = $email;
                $param_first_name = $first_name;
                $param_middle_name = $middle_name;
                $param_last_name = $last_name;
                $param_phone = $phone;
                $param_role = "patient";
                
                if(!mysqli_stmt_execute($stmt)){
                    throw new Exception("Error executing statement: " . mysqli_stmt_error($stmt));
                }
                
                // Get the user ID
                $user_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
                
                // Insert into patients table
                $sql = "INSERT INTO patients (user_id, date_of_birth, address, age, region, city, barangay, zipcode, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = mysqli_prepare($conn, $sql);
                if($stmt === false) {
                    throw new Exception("Error preparing statement: " . mysqli_error($conn));
                }
                
                mysqli_stmt_bind_param($stmt, "isssssss", $user_id, $date_of_birth, $address, $age, $region, $city, $barangay, $zipcode);
                
                if(!mysqli_stmt_execute($stmt)){
                    throw new Exception("Error executing statement: " . mysqli_stmt_error($stmt));
                }
                
                mysqli_stmt_close($stmt);

                // Generate verification code
                $verificationCode = generateVerificationCode();
                
                // Store verification code
                $token_sql = "INSERT INTO email_verifications (user_id, token) VALUES (?, ?)";
                $token_stmt = mysqli_prepare($conn, $token_sql);
                
                if($token_stmt === false) {
                    throw new Exception("Error preparing token statement: " . mysqli_error($conn));
                }
                
                mysqli_stmt_bind_param($token_stmt, "is", $user_id, $verificationCode);
                
                if(!mysqli_stmt_execute($token_stmt)){
                    throw new Exception("Error storing verification token: " . mysqli_stmt_error($token_stmt));
                }
                
                mysqli_stmt_close($token_stmt);
                
                // Send verification email with code
                $fullName = $first_name . ' ' . $last_name;
                if(!sendVerificationEmail($email, $fullName, $verificationCode)) {
                    throw new Exception("Error sending verification email");
                }
                
                // Commit transaction
                mysqli_commit($conn);
                
                // Store user_id temporarily for verification
                $_SESSION["temp_user_id"] = $user_id;
                
                // Show verification code input form
                ?>
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Email Verification</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                </head>
                <body>
                    <script>
                        Swal.fire({
                            title: 'Enter Verification Code',
                            html: `
                                <p>We've sent a verification code to your email: <strong><?php echo $email; ?></strong></p>
                                <p>Please check your email and enter the 6-digit code below:</p>
                                <form id="verificationForm" method="post">
                                    <input type="text" name="verification_code" class="swal2-input" placeholder="Enter 6-digit code" 
                                           pattern="[0-9]{6}" maxlength="6" required style="width: 200px; text-align: center; letter-spacing: 5px; font-size: 20px;">
                                </form>
                            `,
                            showCancelButton: true,
                            confirmButtonText: 'Verify',
                            cancelButtonText: 'Resend Code',
                            allowOutsideClick: false,
                            didOpen: () => {
                                const input = Swal.getPopup().querySelector('input');
                                input.focus();
                                input.addEventListener('input', (e) => {
                                    e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 6);
                                });
                            },
                            preConfirm: () => {
                                const code = Swal.getPopup().querySelector('input[name="verification_code"]').value;
                                if (!code || code.length !== 6 || !/^\d+$/.test(code)) {
                                    Swal.showValidationMessage('Please enter a valid 6-digit code');
                                    return false;
                                }
                                return code;
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                document.getElementById('verificationForm').submit();
                            } else if (result.dismiss === Swal.DismissReason.cancel) {
                                window.location.reload();
                            }
                        });
                    </script>
                </body>
                </html>
                <?php
                exit;
                
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($conn);
                
                // Delete user if exists
                if(isset($user_id)) {
                    $delete_sql = "DELETE FROM users WHERE id = ?";
                    $delete_stmt = mysqli_prepare($conn, $delete_sql);
                    if($delete_stmt) {
                        mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
                        mysqli_stmt_execute($delete_stmt);
                        mysqli_stmt_close($delete_stmt);
                    }
                }
                
                echo "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Dental Clinic Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Add SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --background-color: #f8f9fc;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            background: linear-gradient(135deg, #f8f9fc 0%, #e8eaf6 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .register-container {
            width: 100%;
            max-width: 900px;
            margin: 1rem auto;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            border: none;
            overflow: hidden;
            margin: 0 auto;
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            text-align: center;
            padding: 2rem 1rem;
            border-bottom: none;
        }

        .card-header i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: white;
        }

        .card-header h2 {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: calc(1.25rem + 0.5vw);
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-section {
            background: #fff;
            padding: 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .form-section:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1.25rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
            font-size: calc(1rem + 0.25vw);
        }

        .form-label {
            font-weight: 500;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control {
            border-radius: 8px;
            padding: 0.625rem 0.875rem;
            border: 1px solid #e3e6f0;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .input-group-text {
            border-radius: 8px 0 0 8px;
            border: 1px solid #e3e6f0;
            background-color: #f8f9fc;
        }

        .input-group-text i {
            width: 1rem;
            text-align: center;
            color: var(--secondary-color);
        }

        .input-group > .form-control {
            border-radius: 0 8px 8px 0;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .btn-register {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .btn-register:hover {
            background: #2e59d9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .age-display {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: var(--success-color);
            font-weight: 500;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .login-link a:hover {
            color: #2e59d9;
            text-decoration: underline;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1200px) {
            .register-container {
                max-width: 800px;
            }
        }

        @media (max-width: 992px) {
            .register-container {
                max-width: 700px;
            }
            
            .card-body {
                padding: 1.25rem;
            }
            
            .form-section {
                padding: 1rem;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 0.5rem;
            }

            .register-container {
                margin: 0.5rem auto;
            }

            .card-header {
                padding: 1.5rem 1rem;
            }

            .card-header i {
                font-size: 2rem;
            }

            .form-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .row {
                margin-right: -0.5rem;
                margin-left: -0.5rem;
            }

            .col-md-4, .col-md-6, .col-12 {
                padding-right: 0.5rem;
                padding-left: 0.5rem;
            }
        }

        @media (max-width: 576px) {
            .card-header h2 {
                font-size: 1.5rem;
            }

            .section-title {
                font-size: 1.1rem;
            }

            .form-label {
                font-size: 0.85rem;
            }

            .form-control {
                font-size: 0.85rem;
                padding: 0.5rem 0.75rem;
            }

            .btn-register {
                padding: 0.625rem;
                font-size: 0.9rem;
            }
        }

        /* Smooth scrolling for the entire page */
        html {
            scroll-behavior: smooth;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #2e59d9;
        }
    </style>
</head>
<body>
    <div class="container register-container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-plus"></i>
                <h2>Create Account</h2>
                <p class="text-light mb-0">Join our dental clinic system</p>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <!-- Account Information Section -->
                    <div class="form-section">
                        <h4 class="section-title">Account Information</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                                </div>
                                <span class="invalid-feedback"><?php echo $username_err; ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                                </div>
                                <span class="invalid-feedback"><?php echo $email_err; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <h4 class="section-title">Personal Information</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control <?php echo (!empty($first_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $first_name; ?>" placeholder="Enter First Name">
                                <span class="invalid-feedback"><?php echo $first_name_err; ?></span>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Middle Name (Optional)</label>
                                <input type="text" name="middle_name" class="form-control <?php echo (!empty($middle_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $middle_name; ?>" placeholder="Enter Middle Name">
                                <span class="invalid-feedback"><?php echo $middle_name_err; ?></span>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control <?php echo (!empty($last_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $last_name; ?>" placeholder="Enter Last Name">
                                <span class="invalid-feedback"><?php echo $last_name_err; ?></span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                    <input type="date" name="date_of_birth" class="form-control <?php echo (!empty($date_of_birth_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $date_of_birth; ?>" max="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <span class="invalid-feedback"><?php echo $date_of_birth_err; ?></span>
                                <div class="age-display" id="age-display"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Age</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user-clock"></i></span>
                                    <input type="number" name="age" class="form-control <?php echo (!empty($age_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $age; ?>" min="0" max="120" readonly>
                                </div>
                                <span class="invalid-feedback"><?php echo $age_err; ?></span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" name="phone" class="form-control <?php echo (!empty($phone_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $phone; ?>" pattern="[0-9]{11}" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);" placeholder="Enter 11-digit number">
                                </div>
                                <span class="invalid-feedback"><?php echo $phone_err; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Address Section -->
                    <div class="form-section">
                        <h4 class="section-title">Address Information</h4>
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Region</label>
                                <select name="region" id="region" class="form-control <?php echo (!empty($region_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $region; ?>">
                                    <option value="">Select Region</option>
                                </select>
                                <span class="invalid-feedback"><?php echo $region_err; ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City/Municipality</label>
                                <select name="city" id="city" class="form-control <?php echo (!empty($city_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $city; ?>">
                                    <option value="">Select City/Municipality</option>
                                </select>
                                <span class="invalid-feedback"><?php echo $city_err; ?></span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Barangay</label>
                                <select name="barangay" id="barangay" class="form-control <?php echo (!empty($barangay_err)) ? 'is-invalid' : ''; ?>">
                                    <option value="">Select Barangay</option>
                                </select>
                                <span class="invalid-feedback"><?php echo $barangay_err; ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Zipcode</label>
                                <input type="text" id="zipcode" name="zipcode" class="form-control" readonly>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label class="form-label">Street Address (Optional)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <input type="text" id="address-input" class="form-control" placeholder="House/Building No., Street Name">
                                </div>
                            </div>
                        </div>
                        <!-- Hidden input for full address -->
                        <input type="hidden" name="address" class="form-control">
                    </div>

                    <!-- Password Section -->
                    <div class="form-section">
                        <h4 class="section-title">Security</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
                                </div>
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
                                </div>
                                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-register">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </div>
                </form>
                
                <div class="login-link">
                    Already have an account? <a href="index.php">Login here</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Add SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Age calculation
        document.querySelector('input[name="date_of_birth"]').addEventListener('change', function() {
            const dob = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            
            document.getElementById('age-display').textContent = age > 0 ? `Age: ${age} years` : '';
            document.querySelector('input[name="age"]').value = age > 0 ? age : '';
        });

        // Philippine Regions and Cities with Barangays and Zipcodes
        const regions = {
            'NCR': 'National Capital Region',
            'CAR': 'Cordillera Administrative Region',
            'REGION-I': 'Ilocos Region',
            'REGION-II': 'Cagayan Valley',
            'REGION-III': 'Central Luzon',
            'REGION-IV-A': 'CALABARZON',
            'REGION-IV-B': 'MIMAROPA',
            'REGION-V': 'Bicol Region',
            'REGION-VI': 'Western Visayas',
            'REGION-VII': 'Central Visayas',
            'REGION-VIII': 'Eastern Visayas',
            'REGION-IX': 'Zamboanga Peninsula',
            'REGION-X': 'Northern Mindanao',
            'REGION-XI': 'Davao Region',
            'REGION-XII': 'SOCCSKSARGEN',
            'REGION-XIII': 'Caraga',
            'BARMM': 'Bangsamoro'
        };

        const cities = {
            'REGION-IX': {
                'Zamboanga City': {
                    barangays: ['Ayala', 'Baliwasan', 'Boalan', 'Calarian', 'Campo Islam', 'Canelar', 'Divisoria', 'Guiwan', 'La Paz', 'Lunzuran', 'Mampang', 'Pasonanca', 'Putik', 'Recodo', 'San Jose Gusu', 'San Roque', 'Santa Barbara', 'Santa Catalina', 'Santa Maria', 'Talon-Talon', 'Tetuan', 'Tumaga', 'Zone I', 'Zone II', 'Zone III', 'Zone IV'],
                    zipcode: '7000'
                },
                'Ipil (Zamboanga Sibugay)': {
                    barangays: ['Ipil Heights', 'Ipil Proper', 'Makilas', 'Sanito', 'Taway', 'Tenan', 'Tirso Babiera', 'Upper Ipil Heights', 'Bangkerohan', 'Buluan', 'Caparan', 'Don Andres', 'Domandan', 'Ipil Extension', 'Logan', 'Lumbayao', 'Maasin', 'Magdaup', 'Makilas', 'Poblacion', 'Sangi', 'Suclema', 'Taway', 'Timalang', 'Tomitom'],
                    zipcode: '7001'
                },
                'Kabasalan (Zamboanga Sibugay)': {
                    barangays: ['Banker', 'Cainglet', 'Concepcion', 'Goodyear', 'Little Baguio', 'Nazareth', 'Poblacion', 'Sanghanan', 'Balatacan', 'Batu-batu', 'Buayan', 'Catipan', 'Conception', 'Dipala', 'Gomotoc', 'Kapayawan', 'Lacnapan', 'Palomoc', 'Peñaranda', 'Pontodon', 'Riverside', 'Salipyasin', 'Sayao', 'Shiolan', 'Simbol', 'Tamin', 'Tigbangagan'],
                    zipcode: '7005'
                },
                'Titay (Zamboanga Sibugay)': {
                    barangays: ['Poblacion', 'Tugop', 'Kulasian', 'Malagandis', 'Mercedes', 'Moalboal', 'Tag-amatayan', 'Baduan', 'Bagong Silang', 'Batu', 'Buengbata', 'Bulilit', 'Caliran', 'Camanga', 'Dalangin', 'Dalisay', 'Guinoman', 'Jose P. Brillantes', 'Kitabog', 'La Victoria', 'Lonogon', 'Mabini', 'Magsaysay', 'Mate', 'New Canaan', 'Nuevo', 'San Antonio', 'San Isidro', 'San Jose', 'Santa Fe'],
                    zipcode: '7006'
                },
                'Diplahan (Zamboanga Sibugay)': {
                    barangays: ['Poblacion', 'Guinoman', 'Ditay', 'Goling', 'Kauswagan', 'Mejo', 'Paradise', 'Balangao', 'Butong', 'Diampak', 'Diclotan', 'Dilut', 'Gaulan', 'Guituan', 'Kaliantana', 'Kulasian', 'Lindang', 'Lobing', 'Manangon', 'Mabuhay', 'Natan', 'Piña', 'Sampoli', 'Songcuya', 'Tinongtongan'],
                    zipcode: '7039'
                },
                'Buug (Zamboanga Sibugay)': {
                    barangays: ['Poblacion', 'Anuling', 'Bacalan', 'Bagong Borbon', 'Basalem', 'Bawang', 'Blancia', 'Bulaan', 'Datu Panas', 'Del Monte', 'Guintuloan', 'Hinles', 'Labrador', 'Lantawan', 'Maganay', 'Manlin', 'Muyo', 'Pamintayan', 'San Jose', 'Talamimi', 'Tigbangagan', 'Villacastor'],
                    zipcode: '7009'
                },
                'Mabuhay (Zamboanga Sibugay)': {
                    barangays: ['Poblacion', 'Abunda', 'Bagong Silang', 'Bangkaw-bangkaw', 'Caliran', 'Catipan', 'Kawayan', 'Looc-labuan', 'Malinao', 'Masao', 'Pinalim', 'Sawa', 'Taguisian', 'Tandu-tandu'],
                    zipcode: '7010'
                },
                'Alicia (Zamboanga Sibugay)': {
                    barangays: ['Poblacion', 'Bagong Buhay', 'Calula', 'Concepcion', 'Dawa', 'Gulayon', 'Kapatagan', 'La Paz', 'Lambuyogan', 'Lapirawan', 'Litayon', 'Lunday', 'Mabini', 'Mahayahay', 'Malague', 'Naga-naga', 'Payao', 'Pina', 'San Roque', 'Santa Maria', 'Santo Rosario', 'Talaptap', 'Tampilisan', 'Tandu-gabang'],
                    zipcode: '7040'
                },
                'Malangas (Zamboanga Sibugay)': {
                    barangays: ['Poblacion', 'Bagong Buhay', 'Bacao', 'Bangkaw-bangkaw', 'Candiis', 'Culasian', 'Dansulao', 'Diplo', 'Kigay', 'Kipit', 'La Paz', 'Logpond', 'Malaking Patag', 'Mulom', 'Namilas', 'Payag', 'Sinusayan', 'Tackling'],
                    zipcode: '7031'
                },
                'Naga (Zamboanga Sibugay)': {
                    barangays: ['Poblacion', 'Bangkerohan', 'Cabong', 'Crossing Sta. Clara', 'Gubawan', 'Kaliantana', 'La Paz', 'Langon', 'Mamagon', 'San Isidro', 'Sandayong', 'Santa Clara', 'Taglibas', 'Tigbanuang', 'Tilubog'],
                    zipcode: '7004'
                },
                'Olutanga (Zamboanga Sibugay)': {
                    barangays: ['Poblacion', 'Bateria', 'Calais', 'Calumat', 'Esperanza', 'Fabian', 'Galo', 'Kahayagan', 'La Victoria', 'Lawigan', 'Looc Sapi', 'Pulo Mabao', 'San Jose', 'Santo Niño', 'Tambanan', 'Villacorte', 'Villa Consuelo'],
                    zipcode: '7041'
                },
                'Payao (Zamboanga Sibugay)': {
                    barangays: ['Poblacion', 'Balian', 'Balugo', 'Binangonan', 'Bualan', 'Bulawan', 'Dalama', 'Guintolan', 'Katipunan', 'Kima', 'Kulasian', 'Kulisap', 'Lumapanac', 'Minundas', 'Naboccot', 'Sumilong', 'Talayap'],
                    zipcode: '7008'
                },
                'Roseller Lim (Zamboanga Sibugay)': {
                    barangays: ['Poblacion', 'Balanding', 'Calula', 'Casacon', 'Gango', 'Katipunan', 'Kulambugan', 'Magsaysay', 'Mabini', 'Marapong', 'New Antique', 'Pres. Roxas', 'San Fernandino', 'Siawang', 'Silingan', 'Taruc', 'Tilasan'],
                    zipcode: '7002'
                },
                'Siay (Zamboanga Sibugay)': {
                    barangays: ['Poblacion', 'Bagong Sikaton', 'Balagon', 'Balingasan', 'Balucanan', 'Batu', 'Camanga', 'Daduan', 'Guilawa', 'Kamansi', 'Kima', 'Kumalarang', 'Labasan', 'Lagting', 'Lambuyong', 'Logpond', 'Lucia', 'Magsaysay', 'Mahayahay', 'Malongon', 'Salinding', 'San Isidro', 'Sibuguey', 'Sisay', 'Villagracia'],
                    zipcode: '7007'
                },
                'Talusan (Zamboanga Sibugay)': {
                    barangays: ['Poblacion', 'Bagong Silang', 'Bualan', 'Cawilan', 'Dalangin', 'Kasigpitan', 'Kauswagan', 'Laparay', 'Mahayahay', 'Moalboal', 'Sagay', 'Samonte', 'San Antonio', 'San Jose', 'Tuburan'],
                    zipcode: '7012'
                },
                'Tungawan (Zamboanga Sibugay)': {
                    barangays: ['Poblacion', 'Baluran', 'Batungan', 'Cayamcam', 'Danlugan', 'Langon', 'Libertad', 'Linguisan', 'Loboc', 'Looc-labuan', 'Lower Tungawan', 'Malungon', 'San Isidro', 'San Pedro', 'San Vicente', 'Santo Niño', 'Sisay', 'Tagun', 'Tigbanuang', 'Tigbucay', 'Tigpalay', 'Timbabauan', 'Upper Tungawan'],
                    zipcode: '7018'
                }
            },
            'NCR': {
                'Manila': {
                    barangays: ['Binondo', 'Ermita', 'Intramuros', 'Malate', 'Paco', 'Pandacan', 'Port Area', 'Quiapo', 'Sampaloc', 'San Andres', 'San Miguel', 'San Nicolas', 'Santa Ana', 'Santa Cruz', 'Santa Mesa', 'Tondo'],
                    zipcode: '1000'
                },
                'Quezon City': {
                    barangays: ['Alicia', 'Bagong Lipunan', 'Commonwealth', 'Batasan Hills', 'Fairview', 'Novaliches', 'Project 6', 'Project 7', 'Project 8'],
                    zipcode: '1100'
                },
                'Makati': {
                    barangays: ['Bangkal', 'Bel-Air', 'Forbes Park', 'Guadalupe Nuevo', 'Magallanes', 'Poblacion', 'San Lorenzo', 'Urdaneta'],
                    zipcode: '1200'
                },
                'Taguig': {
                    barangays: ['Bagumbayan', 'Bambang', 'Calzada', 'Central Bicutan', 'Central Signal Village', 'Fort Bonifacio', 'Katuparan', 'Ligid-Tipas'],
                    zipcode: '1630'
                },
                'Pasig': {
                    barangays: ['Bagong Ilog', 'Kapitolyo', 'Manggahan', 'Oranbo', 'Pinagbuhatan', 'San Antonio', 'Santa Lucia', 'Ugong'],
                    zipcode: '1600'
                }
            },
            'CAR': {
                'Baguio': {
                    barangays: ['Asin Road', 'Aurora Hill', 'Camp 7', 'City Camp', 'Engineers Hill', 'Fairview', 'Greenwater', 'Happy Hollow'],
                    zipcode: '2600'
                },
                'La Trinidad': {
                    barangays: ['Alapang', 'Alno', 'Ambiong', 'Bahong', 'Balili', 'Beckel', 'Bineng', 'Betag'],
                    zipcode: '2601'
                }
            },
            'REGION-III': {
                'Angeles': {
                    barangays: ['Agapito del Rosario', 'Balibago', 'Claro M. Recto', 'Lourdes North West', 'Malabanias', 'Mining', 'Pampang', 'Pulungbulu'],
                    zipcode: '2009'
                },
                'San Fernando': {
                    barangays: ['Alasas', 'Baliti', 'Calulut', 'Del Carmen', 'Dolores', 'Juliana', 'Maimpis', 'Malino'],
                    zipcode: '2000'
                }
            }
        };

        // Initialize dropdowns when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeAddressDropdowns();
        });

        function initializeAddressDropdowns() {
            const regionSelect = document.getElementById('region');
            const citySelect = document.getElementById('city');
            const barangaySelect = document.getElementById('barangay');

            // Clear all dropdowns
            regionSelect.innerHTML = '<option value="">Select Region</option>';
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

            // Populate regions
            Object.entries(regions).forEach(([code, name]) => {
                const option = document.createElement('option');
                option.value = code;
                option.textContent = name;
                regionSelect.appendChild(option);
            });

            // Add event listeners
            regionSelect.addEventListener('change', function() {
                const selectedRegion = this.value;
                populateCities(selectedRegion);
                updateAddress();
            });

            citySelect.addEventListener('change', function() {
                const selectedRegion = regionSelect.value;
                const selectedCity = this.value;
                populateBarangays(selectedRegion, selectedCity);
                updateAddress();
            });

            barangaySelect.addEventListener('change', function() {
                updateAddress();
            });

            document.getElementById('address-input').addEventListener('input', updateAddress);
        }

        function populateCities(regionCode) {
            const citySelect = document.getElementById('city');
            const barangaySelect = document.getElementById('barangay');
            const zipcodeInput = document.getElementById('zipcode');

            // Reset city and barangay dropdowns
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            zipcodeInput.value = '';

            if (regionCode && cities[regionCode]) {
                Object.keys(cities[regionCode]).forEach(cityName => {
                    const option = document.createElement('option');
                    option.value = cityName;
                    option.textContent = cityName;
                    citySelect.appendChild(option);
                });
            }
        }

        function populateBarangays(regionCode, cityName) {
            const barangaySelect = document.getElementById('barangay');
            const zipcodeInput = document.getElementById('zipcode');

            // Reset barangay dropdown and zipcode
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            zipcodeInput.value = '';

            if (regionCode && cityName && cities[regionCode] && cities[regionCode][cityName]) {
                const cityData = cities[regionCode][cityName];
                
                // Populate barangays
                cityData.barangays.forEach(barangayName => {
                    const option = document.createElement('option');
                    option.value = barangayName;
                    option.textContent = barangayName;
                    barangaySelect.appendChild(option);
                });

                // Set zipcode
                zipcodeInput.value = cityData.zipcode;
            }
        }

        function updateAddress() {
            const regionSelect = document.getElementById('region');
            const citySelect = document.getElementById('city');
            const barangaySelect = document.getElementById('barangay');
            const addressInput = document.getElementById('address-input');
            const zipcodeInput = document.getElementById('zipcode');

            // Only update if required fields are filled
            if (regionSelect.value && 
                citySelect.value && 
                barangaySelect.value) {
                
                const selectedRegion = regionSelect.options[regionSelect.selectedIndex].text;
                const selectedCity = citySelect.value;
                const selectedBarangay = barangaySelect.value;
                const streetAddress = addressInput.value.trim();
                const zipcode = zipcodeInput.value;

                // Update the hidden full address field
                const fullAddress = streetAddress ? 
                    `${streetAddress}, Brgy. ${selectedBarangay}, ${selectedCity}, ${selectedRegion} ${zipcode}` :
                    `Brgy. ${selectedBarangay}, ${selectedCity}, ${selectedRegion} ${zipcode}`;
                document.querySelector('input[name="address"]').value = fullAddress;

                // Ensure zipcode is set in the form
                zipcodeInput.value = zipcode;
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            let hasError = false;
            
            // Username validation
            const username = document.querySelector('input[name="username"]');
            const usernameRegex = /^[a-zA-Z0-9_]+$/;
            if (!username.value) {
                setInvalid(username, 'Username is required');
                hasError = true;
            } else if (!usernameRegex.test(username.value)) {
                setInvalid(username, 'Username can only contain letters, numbers, and underscores');
                hasError = true;
            }

            // Email validation
            const email = document.querySelector('input[name="email"]');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email.value) {
                setInvalid(email, 'Email is required');
                hasError = true;
            } else if (!emailRegex.test(email.value)) {
                setInvalid(email, 'Please enter a valid email address');
                hasError = true;
            }

            // Name validation
            const firstName = document.querySelector('input[name="first_name"]');
            const middleName = document.querySelector('input[name="middle_name"]');
            const lastName = document.querySelector('input[name="last_name"]');
            
            if (!firstName.value.trim()) {
                setInvalid(firstName, 'First name is required');
                hasError = true;
            }
            if (!middleName.value.trim()) {
                // Middle name is optional, no validation needed
                middleName.classList.remove('is-invalid');
            }
            if (!lastName.value.trim()) {
                setInvalid(lastName, 'Last name is required');
                hasError = true;
            }

            // Date of birth validation
            const dob = document.querySelector('input[name="date_of_birth"]');
            if (!dob.value) {
                setInvalid(dob, 'Date of birth is required');
                hasError = true;
            } else {
                const dobDate = new Date(dob.value);
                const today = new Date();
                if (dobDate > today) {
                    setInvalid(dob, 'Date of birth cannot be in the future');
                    hasError = true;
                }
            }

            // Phone validation
            const phone = document.querySelector('input[name="phone"]');
            const phoneRegex = /^[0-9]{11}$/;
            if (!phone.value) {
                setInvalid(phone, 'Phone number is required');
                hasError = true;
            } else if (!phoneRegex.test(phone.value)) {
                setInvalid(phone, 'Please enter a valid 11-digit phone number');
                hasError = true;
            }

            // Address validation
            const region = document.getElementById('region');
            const city = document.getElementById('city');
            const barangay = document.getElementById('barangay');
            const address = document.getElementById('address-input');
            
            if (!region.value) {
                setInvalid(region, 'Please select a region');
                hasError = true;
            }
            if (!city.value) {
                setInvalid(city, 'Please select a city/municipality');
                hasError = true;
            }
            if (!barangay.value) {
                setInvalid(barangay, 'Please select a barangay');
                hasError = true;
            }
            // Street address is optional, no validation needed

            // Password validation
            const password = document.querySelector('input[name="password"]');
            const confirmPassword = document.querySelector('input[name="confirm_password"]');
            
            if (!password.value) {
                setInvalid(password, 'Password is required');
                hasError = true;
            } else if (password.value.length < 6) {
                setInvalid(password, 'Password must be at least 6 characters');
                hasError = true;
            }
            
            if (!confirmPassword.value) {
                setInvalid(confirmPassword, 'Please confirm your password');
                hasError = true;
            } else if (password.value !== confirmPassword.value) {
                setInvalid(confirmPassword, 'Passwords do not match');
                hasError = true;
            }

            if (hasError) {
                e.preventDefault();
            }
        });

        // Helper function to set invalid state and error message
        function setInvalid(element, message) {
            element.classList.add('is-invalid');
            const feedback = element.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.textContent = message;
            }
        }

        // Add input event listeners to remove invalid state when user starts typing
        document.querySelectorAll('input, select').forEach(element => {
            element.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = '';
                }
            });
        });

        // Add change event listener for date of birth to validate age
        document.querySelector('input[name="date_of_birth"]').addEventListener('change', function() {
            const dob = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            
            const ageInput = document.querySelector('input[name="age"]');
            const ageDisplay = document.getElementById('age-display');
            
            if (age < 0 || age > 120) {
                setInvalid(this, 'Please enter a valid date of birth');
                ageInput.value = '';
                ageDisplay.textContent = '';
            } else {
                this.classList.remove('is-invalid');
                ageInput.value = age;
                ageDisplay.textContent = age > 0 ? `Age: ${age} years` : '';
            }
        });

        // Add phone number input validation
        document.querySelector('input[name="phone"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);
            if (this.value.length === 11) {
                this.classList.remove('is-invalid');
            }
        });

        // Update phone validation in form submit
        const phone = document.querySelector('input[name="phone"]');
        if (!phone.value) {
            setInvalid(phone, 'Phone number is required');
            hasError = true;
        } else if (phone.value.length !== 11) {
            setInvalid(phone, 'Phone number must be exactly 11 digits');
            hasError = true;
        }
    </script>
</body>
</html> 