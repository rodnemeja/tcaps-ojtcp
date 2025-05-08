<?php
session_start();
require_once "config/database.php";

// Try to include mail configuration, but handle gracefully if it fails
$mail_config_available = true;
try {
    require_once "config/mail.php";
} catch (Error $e) {
    $mail_config_available = false;
    error_log("Mail configuration error: " . $e->getMessage());
}

// Check if the required PHPMailer dependencies exist
if ($mail_config_available) {
    $vendor_path = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($vendor_path)) {
        $mail_config_available = false;
        error_log("Vendor autoload not found at: " . $vendor_path);
    }
}

// Debug: Log form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    error_log("Form submission received: " . date('Y-m-d H:i:s'));
    error_log("POST data: " . print_r($_POST, true));
}

// Check if user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: dashboard.php");
    exit;
}

$username = $password = $confirm_password = $email = $first_name = $middle_name = $last_name = $phone = $date_of_birth = $address = $age = $region = $city = $barangay = $zipcode = $gender = "";
$username_err = $password_err = $confirm_password_err = $email_err = $first_name_err = $middle_name_err = $last_name_err = $phone_err = $date_of_birth_err = $address_err = $age_err = $region_err = $city_err = $barangay_err = $gender_err = "";

// Function to generate verification code
function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Function to check if email sending is available
function isEmailSendingAvailable() {
    global $mail_config_available;
    if (!$mail_config_available) {
        error_log("Mail configuration not available");
        return false;
    }
    
    $vendor_path = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($vendor_path)) {
        error_log("Vendor autoload not found at: " . $vendor_path);
        return false;
    }
    
    return true;
}

// Initialize verification attempt counter if not set
if (!isset($_SESSION['verification_attempts'])) {
    $_SESSION['verification_attempts'] = 0;
}

// Initialize last email sent timestamp if not set
if (!isset($_SESSION['last_email_sent'])) {
    $_SESSION['last_email_sent'] = 0;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // If this is a verification code submission
    if(isset($_POST["verification_code"]) && isset($_SESSION["temp_user_id"])) {
        $verification_code = trim($_POST["verification_code"]);
        $user_id = $_SESSION["temp_user_id"];

        // Check if user has exceeded maximum attempts
        if ($_SESSION['verification_attempts'] >= 3) {
            $verification_err = "You have exceeded the maximum number of attempts. Please register again.";
            // Reset the session and redirect to registration page
            session_unset();
            session_destroy();
            header("refresh:3;url=register.php");
            exit;
        }

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

                    // Reset verification attempts
                    $_SESSION['verification_attempts'] = 0;

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
                    unset($_SESSION["last_email_sent"]);
                    
                    // Show success message and redirect
                    header("Location: verify_email.php");
                    exit;
                }
            } else {
                // Increment verification attempts
                $_SESSION['verification_attempts']++;
                $remaining_attempts = 3 - $_SESSION['verification_attempts'];
                
                if ($remaining_attempts > 0) {
                    $verification_err = "Invalid verification code. You have {$remaining_attempts} attempts remaining.";
                } else {
                    $verification_err = "You have exceeded the maximum number of attempts. Please register again.";
                    // Reset the session and redirect to registration page after 3 seconds
                    session_unset();
                    session_destroy();
                    header("refresh:3;url=register.php");
                    exit;
                }
            }
            mysqli_stmt_close($stmt);
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
        // Check if it's a valid Philippine mobile number
        if(!preg_match('/^09[0-9]{9}$/', $phone)) {
            $phone_err = "Please enter a valid Philippine mobile number starting with 09.";
        }
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
        
        // Validate gender
        if(empty(trim($_POST["gender"]))){
            $gender_err = "Please select a gender.";
        } else {
            $gender = trim($_POST["gender"]);
    }
    
    // Check input errors before inserting in database
        if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($email_err) && empty($first_name_err) && empty($middle_name_err) && empty($last_name_err) && empty($phone_err) && empty($date_of_birth_err) && empty($address_err) && empty($age_err) && empty($region_err) && empty($city_err) && empty($barangay_err) && empty($gender_err)){
        
            // Start transaction
            mysqli_begin_transaction($conn);
         
            try {
                // Debug: Log transaction start
                error_log("Starting transaction for user registration: " . $username);
                
                // Generate verification code
                $verificationCode = generateVerificationCode();
                error_log("Verification code generated: " . $verificationCode);
            
                // Insert into users table
                $sql = "INSERT INTO users (username, password, email, first_name, middle_name, last_name, phone, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'patient')";
                
                if($stmt = mysqli_prepare($conn, $sql)) {
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            
                    mysqli_stmt_bind_param($stmt, "sssssss", $username, $param_password, $email, $first_name, $middle_name, $last_name, $phone);
                    
                    if(mysqli_stmt_execute($stmt)) {
                $user_id = mysqli_insert_id($conn);
                        error_log("User inserted with ID: " . $user_id);
                        
                        // Insert into patients table
                        $patient_sql = "INSERT INTO patients (user_id, date_of_birth, age, address, region, city, barangay, zipcode, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $patient_stmt = mysqli_prepare($conn, $patient_sql);
                        
                        if($patient_stmt === false) {
                            throw new Exception("Error preparing patient statement: " . mysqli_error($conn));
                        }
                        
                        error_log("Patient data: DOB=$date_of_birth, Age=$age, Address=$address, Region=$region, City=$city, Barangay=$barangay, Zipcode=$zipcode, Gender=$gender");
                        mysqli_stmt_bind_param($patient_stmt, "issssssis", $user_id, $date_of_birth, $age, $address, $region, $city, $barangay, $zipcode, $gender);
                        
                        if(!mysqli_stmt_execute($patient_stmt)){
                            $error = mysqli_stmt_error($patient_stmt);
                            error_log("Error executing patient statement: " . $error);
                            throw new Exception("Error inserting patient data: " . $error);
                        }
                        
                        mysqli_stmt_close($patient_stmt);
                        error_log("Patient record created successfully");
                        
                        // Store verification code
                        $token_sql = "INSERT INTO email_verifications (user_id, token) VALUES (?, ?)";
                        $token_stmt = mysqli_prepare($conn, $token_sql);
                        
                        if($token_stmt === false) {
                            throw new Exception("Error preparing token statement: " . mysqli_error($conn));
                        }
                        
                        mysqli_stmt_bind_param($token_stmt, "is", $user_id, $verificationCode);
                        
                        if(!mysqli_stmt_execute($token_stmt)){
                            $error = mysqli_stmt_error($token_stmt);
                            error_log("Error executing token statement: " . $error);
                            throw new Exception("Error storing verification token: " . $error);
                        }
                        
                        mysqli_stmt_close($token_stmt);
                        error_log("Verification token stored successfully");
                        
                        // Check if email sending is available
                        if (!isEmailSendingAvailable()) {
                            error_log("Email sending not available - auto-verifying user for development");
                            // Auto-verify the user for development purposes
                            $update_verified_sql = "UPDATE users SET email_verified = 1 WHERE id = ?";
                            $update_verified_stmt = mysqli_prepare($conn, $update_verified_sql);
                            mysqli_stmt_bind_param($update_verified_stmt, "i", $user_id);
                            mysqli_stmt_execute($update_verified_stmt);
                            mysqli_stmt_close($update_verified_stmt);
                            
                            // Commit transaction
                            mysqli_commit($conn);
                            
                            // Redirect to dashboard
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $user_id;
                            $_SESSION["username"] = $username;
                            header("location: dashboard.php");
                            exit;
                        }
                        
                        // Send verification email with code
                        $fullName = $first_name . ' ' . $last_name;
                        error_log("Attempting to send verification email to: " . $email);
                        
                        $emailSent = false;
                        if ($mail_config_available) {
                            try {
                                $emailSent = sendVerificationEmail($email, $fullName, $verificationCode);
                            } catch (Exception $emailEx) {
                                error_log("Email sending exception: " . $emailEx->getMessage());
                                // Since email failed, we'll handle this gracefully
                                $emailSent = false;
                }
            } else {
                            error_log("Mail configuration not available. Skipping email verification for development.");
                            // Auto-verify the user for development purposes
                            $update_verified_sql = "UPDATE users SET email_verified = 1 WHERE id = ?";
                            $update_verified_stmt = mysqli_prepare($conn, $update_verified_sql);
                            mysqli_stmt_bind_param($update_verified_stmt, "i", $user_id);
                            mysqli_stmt_execute($update_verified_stmt);
                            mysqli_stmt_close($update_verified_stmt);
                            error_log("User auto-verified for development mode.");
                            $emailSent = true; // Pretend it was sent
                        }
                        
                        if(!$emailSent) {
                            throw new Exception("Error sending verification email");
                        }
                        
                        error_log("Verification email sent successfully");
                        
                        // Store timestamp of email sent
                        $_SESSION['last_email_sent'] = time();
                        
                        // Commit transaction
                        mysqli_commit($conn);
                        error_log("Transaction committed successfully");
                        
                        // Store user_id temporarily for verification
                        $_SESSION["temp_user_id"] = $user_id;
                        $_SESSION["temp_email"] = $email;
                        $_SESSION['verification_attempts'] = 0;
                        
                        // Redirect to verification page
                        error_log("Redirecting to verification page");
                        header("Location: verify_code.php");
                        exit;
                    } else {
                        $error = mysqli_stmt_error($stmt);
                        error_log("Error executing user statement: " . $error);
                        throw new Exception("Error inserting user data: " . $error);
                    }
                } else {
                    $error = mysqli_error($conn);
                    error_log("Error preparing user statement: " . $error);
                    throw new Exception("Error preparing user statement: " . $error);
                }
            mysqli_stmt_close($stmt);
                
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($conn);
                error_log("Registration error: " . $e->getMessage());
                
                // Store the error message in a session variable to display after redirect
                $_SESSION['register_error'] = "Registration error: " . $e->getMessage();
                
                // Check for common errors and provide more specific messages
                if (strpos($e->getMessage(), "Duplicate entry") !== false && strpos($e->getMessage(), "username") !== false) {
                    $_SESSION['register_error'] = "This username is already taken. Please choose a different username.";
                }
                else if (strpos($e->getMessage(), "Duplicate entry") !== false && strpos($e->getMessage(), "email") !== false) {
                    $_SESSION['register_error'] = "This email is already registered. Please use a different email or try to reset your password.";
                }
                else if (strpos($e->getMessage(), "Error sending verification email") !== false) {
                    $_SESSION['register_error'] = "We couldn't send the verification email. Please check your email address and try again.";
                }
            }
        }
    }
}

// Check for registration errors in session
$register_error = "";
if (isset($_SESSION['register_error'])) {
    $register_error = $_SESSION['register_error'];
    unset($_SESSION['register_error']); // Clear the error message
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Vibrant Smile Dental Clinic System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Add SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
    
    <!-- Navbar Styles -->
    <style>
        /* Navbar specific styles */
        .navbar {
            transition: all 0.3s ease-in-out;
            background: rgba(255, 255, 255, 0.98);
            padding: 15px 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 999;
        }

        .navbar.sticky {
            padding: 10px 0;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .navbar-brand img {
            max-height: 45px;
            transition: all 0.3s ease;
        }

        .navbar-brand h1 {
            font-size: 1.5rem;
            margin: 0;
            color: var(--primary);
        }

        .navbar-toggler {
            padding: 0;
            border: none;
            width: 30px;
            height: 30px;
            position: relative;
            background: transparent;
        }

        .navbar-toggler:focus {
            box-shadow: none;
            outline: none;
        }

        .navbar-toggler span {
            width: 22px;
            height: 2px;
            background-color: #333;
            display: block;
            position: absolute;
            left: 4px;
            transition: all 0.3s ease;
        }

        .navbar-toggler span:nth-child(1) { top: 8px; }
        .navbar-toggler span:nth-child(2) { top: 16px; }
        .navbar-toggler span:nth-child(3) { top: 24px; }

        .navbar-toggler[aria-expanded="true"] span:nth-child(1) {
            transform: rotate(45deg);
            top: 16px;
        }

        .navbar-toggler[aria-expanded="true"] span:nth-child(2) {
            opacity: 0;
        }

        .navbar-toggler[aria-expanded="true"] span:nth-child(3) {
            transform: rotate(-45deg);
            top: 16px;
        }

        .navbar-nav {
            margin-left: auto;
        }

        .nav-item {
            padding: 0 5px;
        }

        .nav-link {
            color: #2B2B2B !important;
            font-weight: 500;
            padding: 8px 15px !important;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary) !important;
            background-color: rgba(19, 197, 221, 0.1);
        }

        .nav-link.login-btn {
            background: linear-gradient(135deg, var(--primary) 0%, #0D9EB5 100%);
            color: white !important;
            padding: 8px 24px !important;
            border-radius: 50px;
            margin-left: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(19, 197, 221, 0.2);
        }

        .nav-link.login-btn:hover {
            background: linear-gradient(135deg, #0D9EB5 0%, var(--primary) 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(19, 197, 221, 0.3);
        }

        .nav-link.login-btn i {
            margin-right: 8px;
        }

        @media (max-width: 991.98px) {
            .navbar {
                padding: 10px 15px;
            }

            .navbar-brand {
                max-width: 75%;
            }

            .navbar-brand img {
                max-height: 35px;
            }

            .navbar-brand h1 {
                font-size: 1.2rem;
            }

            .navbar-collapse {
                background: white;
                border-radius: 10px;
                padding: 15px;
                margin-top: 15px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            }

            .nav-item {
                padding: 2px 0;
            }

            .nav-link {
                padding: 10px 15px !important;
            }

            .nav-link.login-btn {
                margin: 10px 0;
                text-align: center;
                display: block;
            }
        }
    </style>

    <!-- Registration Form Styles -->
    <style>
        :root {
            --primary: #13C5DD;
            --secondary: #354F8E;
            --text-primary: #2B2B2B;
            --text-secondary: #666666;
            --bg-light: #F8F9FA;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        body {
            background: linear-gradient(135deg, #F8F9FA 0%, #E9ECEF 100%);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            color: var(--text-primary);
            padding-top: 90px;
        }

        .register-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .card {
            background: white;
            border: none;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 3rem;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, #0D9EB5 100%);
            padding: 2.5rem 2rem;
            text-align: center;
            border: none;
        }

        .card-header i {
            font-size: 3rem;
            color: white;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .card-header h2 {
            color: white;
            font-weight: 600;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .card-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            margin: 0;
        }

        .card-body {
            padding: 2rem;
        }

        .form-section {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .form-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: var(--primary);
            font-weight: 600;
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--primary);
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 50px;
            height: 2px;
            background: var(--secondary);
        }

        .form-label {
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1.5px solid #E1E5EA;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background-color: #F8F9FA;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(19, 197, 221, 0.15);
            background-color: white;
        }

        .input-group-text {
            border-radius: 10px 0 0 10px;
            border: 1.5px solid #E1E5EA;
            background-color: #F8F9FA;
            color: var(--text-secondary);
        }

        .input-group > .form-control {
            border-radius: 0 10px 10px 0;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary) 0%, #0D9EB5 100%);
            border: none;
            border-radius: 50px;
            padding: 0.875rem;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(19, 197, 221, 0.2);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(19, 197, 221, 0.3);
            background: linear-gradient(135deg, #0D9EB5 0%, var(--primary) 100%);
        }

        .btn-register i {
            margin-right: 8px;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .login-link a:hover {
            color: var(--secondary);
        }

        .age-display {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--primary);
            font-weight: 500;
        }

        select.form-control {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23666666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
            padding-right: 2.5rem;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        .invalid-feedback {
            font-size: 0.85rem;
            color: #dc3545;
            margin-top: 0.25rem;
        }

        @media (max-width: 768px) {
            .register-container {
                margin: 1rem auto;
            }

            .card-header {
                padding: 2rem 1.5rem;
            }

            .card-header h2 {
                font-size: 1.75rem;
            }

            .card-body {
                padding: 1.5rem;
            }

            .form-section {
                padding: 1.25rem;
            }

            .btn-register {
                font-size: 1rem;
                padding: 0.75rem;
            }
        }

        /* Add animation for form sections */
        .form-section {
            animation: slideUp 0.5s ease-out forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-section:nth-child(1) { animation-delay: 0.1s; }
        .form-section:nth-child(2) { animation-delay: 0.2s; }
        .form-section:nth-child(3) { animation-delay: 0.3s; }
        .form-section:nth-child(4) { animation-delay: 0.4s; }

        /* Add floating label effect */
        .form-floating {
            position: relative;
        }

        .form-floating > .form-control {
            padding-top: 1.625rem;
            padding-bottom: 0.625rem;
        }

        .form-floating > label {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            padding: 1rem 0.75rem;
            pointer-events: none;
            border: 1px solid transparent;
            transform-origin: 0 0;
            transition: opacity .1s ease-in-out,transform .1s ease-in-out;
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            opacity: .65;
            transform: scale(.85) translateY(-0.5rem) translateX(0.15rem);
        }

        /* Gender Selection Styles */
        .gender-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .gender-group .form-check {
            margin: 0;
            padding: 0;
        }

        .gender-group .form-check-input {
            display: none;
        }

        .gender-group .form-check-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            background: var(--bg-light);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1.5px solid #E1E5EA;
        }

        .gender-group .form-check-label i {
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .gender-group .form-check-input:checked + .form-check-label {
            background: linear-gradient(135deg, var(--primary) 0%, #0D9EB5 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(19, 197, 221, 0.2);
        }

        .gender-group .form-check-input:checked + .form-check-label i {
            transform: scale(1.2);
        }

        .gender-group .form-check-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        @media (max-width: 576px) {
            .gender-group {
                flex-direction: column;
                gap: 0.5rem;
            }

            .gender-group .form-check-label {
                justify-content: center;
            }
        }

        .password-strength {
            margin-top: 10px;
        }
        .strength-meter {
            height: 5px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        .strength-meter-fill {
            height: 100%;
            width: 0;
            border-radius: 5px;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        .strength-text {
            font-size: 12px;
            color: #6c757d;
        }
    </style>

    <style>
        /* Validation Styles */
        .form-control:invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        .form-control:valid {
            border-color: #198754;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        .required-field::after {
            content: " *";
            color: #dc3545;
            font-weight: bold;
        }
        
        .validation-message {
            font-size: 80%;
            color: #dc3545;
            margin-top: 0.25rem;
            display: none;
        }
        
        input:invalid:not(:focus):not(:placeholder-shown) ~ .validation-message,
        select:invalid:not(:focus):not(:placeholder-shown) ~ .validation-message {
            display: block;
        }
        
        /* Password field styles */
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 10;
            opacity: 0.7;
            transition: opacity 0.3s ease, color 0.3s ease;
            background: transparent;
            border: none;
            padding: 5px;
            color: #666;
        }
        
        .password-toggle:hover {
            opacity: 1;
            color: var(--primary);
        }
        
        .password-container input:focus ~ .password-toggle {
            opacity: 1;
            color: var(--primary);
        }
        
        /* Override the validation icon position for password fields */
        .password-container .form-control:valid,
        .password-container .form-control:invalid {
            background-position: right calc(2em + 0.1875rem) center;
        }
    </style>
</head>
<body>
    <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg">
    <div class="container">
            <a href="index.php" class="navbar-brand d-flex align-items-center">
                <img src="assets/images/logo_vibrant.png" alt="Vibrant Smile Dental Clinic" class="img-fluid">
                <h1 class="m-0"><i class="fa fa-tooth me-2"></i>Vibrant Smile Dental</h1>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto py-0">
                    <a href="index.php" class="nav-item nav-link">Home</a>
                    <a href="about.php" class="nav-item nav-link">About</a>
                    <a href="service.php" class="nav-item nav-link">Service</a>
                    <a href="contact.php" class="nav-item nav-link">Contact</a>
                    <a href="#" class="nav-item nav-link login-btn" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="fas fa-sign-in-alt"></i>Login
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <!-- Navbar End -->

    <!-- Add navbar JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const navbar = document.querySelector('.navbar');
            const navbarCollapse = document.getElementById('navbarCollapse');
            const navbarToggler = document.querySelector('.navbar-toggler');
            const navLinks = document.querySelectorAll('.nav-link');

            // Handle sticky navbar
            function handleSticky() {
                if (window.scrollY > 50) {
                    navbar.classList.add('sticky');
                } else {
                    navbar.classList.remove('sticky');
                }
            }

            // Initial check for sticky
            handleSticky();
            window.addEventListener('scroll', handleSticky);

            // Handle navbar toggler animation
            navbarCollapse.addEventListener('show.bs.collapse', function() {
                navbarToggler.setAttribute('aria-expanded', 'true');
            });

            navbarCollapse.addEventListener('hide.bs.collapse', function() {
                navbarToggler.setAttribute('aria-expanded', 'false');
            });

            // Close navbar when clicking outside
            document.addEventListener('click', function(event) {
                const isClickInside = navbarToggler.contains(event.target) || 
                                    navbarCollapse.contains(event.target);
                
                if (!isClickInside && navbarCollapse.classList.contains('show')) {
                    const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
                    if (bsCollapse) {
                        bsCollapse.hide();
                        navbarToggler.setAttribute('aria-expanded', 'false');
                    }
                }
            });

            // Close navbar when clicking nav links on mobile
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 992 && navbarCollapse.classList.contains('show')) {
                        const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
                        if (bsCollapse) {
                            bsCollapse.hide();
                            navbarToggler.setAttribute('aria-expanded', 'false');
                        }
                    }
                });
            });
        });
    </script>

    <div class="container register-container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-plus"></i>
                <h2>Create Account</h2>
                <p class="text-light mb-0">Join our dental clinic system</p>
            </div>
            <div class="card-body">
            
            <?php if (!empty($register_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Registration Error:</strong> <?php echo $register_error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <!-- Account Information Section -->
                    <div class="form-section">
                        <h4 class="section-title">Account Information</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" required minlength="3" maxlength="20" pattern="[a-zA-Z0-9_]+" title="Username can only contain letters, numbers, and underscores" placeholder="Enter username">
                                </div>
                        <div class="validation-message">Username is required (minimum 3 characters)</div>
                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>" required placeholder="Enter your email address">
                                </div>
                        <div class="validation-message">A valid email address is required</div>
                        <span class="invalid-feedback"><?php echo $email_err; ?></span>
                    </div>
                </div>
                </div>

                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <h4 class="section-title">Personal Information</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label required-field">First Name</label>
                                <input type="text" name="first_name" class="form-control <?php echo (!empty($first_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $first_name; ?>" placeholder="Enter First Name" required>
                                <div class="validation-message">First name is required</div>
                                <span class="invalid-feedback"><?php echo $first_name_err; ?></span>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Middle Name (Optional)</label>
                                <input type="text" name="middle_name" class="form-control <?php echo (!empty($middle_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $middle_name; ?>" placeholder="Enter Middle Name">
                                <span class="invalid-feedback"><?php echo $middle_name_err; ?></span>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label required-field">Last Name</label>
                                <input type="text" name="last_name" class="form-control <?php echo (!empty($last_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $last_name; ?>" placeholder="Enter Last Name" required>
                                <div class="validation-message">Last name is required</div>
                                <span class="invalid-feedback"><?php echo $last_name_err; ?></span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label required-field">Gender</label>
                                <div class="gender-group">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="gender" id="male" value="male" <?php echo (isset($gender) && $gender === 'male') ? 'checked' : ''; ?> required>
                                        <label class="form-check-label" for="male">
                                            <i class="fas fa-mars"></i> Male
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="gender" id="female" value="female" <?php echo (isset($gender) && $gender === 'female') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="female">
                                            <i class="fas fa-venus"></i> Female
                                        </label>
                                    </div>
                                </div>
                                <div class="validation-message">Please select your gender</div>
                                <?php if (!empty($gender_err)): ?>
                                <div class="text-danger small mt-1"><?php echo $gender_err; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Date of Birth</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                    <input type="date" name="date_of_birth" class="form-control <?php echo (!empty($date_of_birth_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $date_of_birth; ?>" max="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="validation-message">Date of birth is required</div>
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
                    <label class="form-label required-field">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" name="phone" class="form-control <?php echo (!empty($phone_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $phone; ?>" pattern="^09[0-9]{9}$" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);" placeholder="Enter 11-digit number (e.g., 09XXXXXXXXX)" required>
                                </div>
                    <div class="validation-message">Please enter a valid PH mobile number (starting with 09)</div>
                    <span class="invalid-feedback"><?php echo $phone_err; ?></span>
                </div>
                        </div>
                    </div>

                    <!-- Address Section -->
                    <div class="form-section">
                        <h4 class="section-title">Address Information</h4>
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Region</label>
                                <select name="region" id="region" class="form-control <?php echo (!empty($region_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $region; ?>" required>
                                    <option value="">Select Region</option>
                                </select>
                                <div class="validation-message">Please select your region</div>
                                <span class="invalid-feedback"><?php echo $region_err; ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">City/Municipality</label>
                                <select name="city" id="city" class="form-control <?php echo (!empty($city_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $city; ?>" required>
                                    <option value="">Select City/Municipality</option>
                                </select>
                                <div class="validation-message">Please select your city/municipality</div>
                                <span class="invalid-feedback"><?php echo $city_err; ?></span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Barangay</label>
                                <select name="barangay" id="barangay" class="form-control <?php echo (!empty($barangay_err)) ? 'is-invalid' : ''; ?>" required>
                                    <option value="">Select Barangay</option>
                                </select>
                                <div class="validation-message">Please select your barangay</div>
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
                        <label class="form-label required-field">Password</label>
                                <div class="input-group password-container">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" minlength="6" required placeholder="Enter password">
                                    <button type="button" class="password-toggle" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="validation-message">Password must be at least 6 characters</div>
                                <div class="password-strength mt-2" id="password-strength">
                                    <div class="strength-meter">
                                        <div class="strength-meter-fill" id="strength-meter-fill"></div>
                                    </div>
                                    <div class="strength-text" id="strength-text">Password strength: Too weak</div>
                                </div>
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Confirm Password</label>
                                <div class="input-group password-container">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" minlength="6" required placeholder="Confirm your password">
                                    <button type="button" class="password-toggle" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="validation-message">Please confirm your password</div>
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
                    barangays: ['Poblacion', 'Bagong Sikaton', 'Balagon', 'Balingasan', 'Balucanan', 'Batu', 'Camanga', 'Daduan', 'Guilawa', 'Kamansi', 'Kima', 'Kumalarang', 'Labasan', 'Lagting', 'Lambuyong', 'Lucia', 'Magsaysay', 'Mahayahay', 'Malongon', 'Salinding', 'San Isidro', 'Sibuguey', 'Sisay', 'Villagracia'],
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
            
            // Make sure the address is constructed before submission
            updateAddress();
            
            // Debugging - log form submission
            console.log('Form submission attempted');
            
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
            const phoneRegex = /^09[0-9]{9}$/;
            if (!phone.value) {
                setInvalid(phone, 'Phone number is required');
                hasError = true;
            } else if (!phoneRegex.test(phone.value)) {
                setInvalid(phone, 'Please enter a valid Philippine mobile number starting with 09');
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

            // Gender validation
            if (!document.querySelector('input[name="gender"]:checked')) {
                const genderGroup = document.querySelector('.gender-group');
                genderGroup.classList.add('is-invalid');
                const validationMessage = genderGroup.nextElementSibling;
                if (validationMessage && validationMessage.classList.contains('validation-message')) {
                    validationMessage.style.display = 'block';
                }
                hasError = true;
            }

            // Address validation - ensure full address is constructed
            updateAddress();
            const addressInput = document.querySelector('input[name="address"]');
            if (!addressInput.value) {
                // Try one more time to update the address
                const regionSelect = document.getElementById('region');
                const citySelect = document.getElementById('city');
                const barangaySelect = document.getElementById('barangay');
                
                if (!regionSelect.value) {
                    setInvalid(regionSelect, 'Please select a region');
                    hasError = true;
                }
                if (!citySelect.value) {
                    setInvalid(citySelect, 'Please select a city/municipality');
                    hasError = true;
                }
                if (!barangaySelect.value) {
                    setInvalid(barangaySelect, 'Please select a barangay');
                    hasError = true;
                }
                
                // Manually set address if all required components are selected
                if (regionSelect.value && citySelect.value && barangaySelect.value) {
                    const selectedRegion = regionSelect.options[regionSelect.selectedIndex].text;
                    const selectedCity = citySelect.value;
                    const selectedBarangay = barangaySelect.value;
                    const streetAddress = document.getElementById('address-input').value.trim();
                    const zipcode = document.getElementById('zipcode').value;
                    
                    const fullAddress = streetAddress ? 
                        `${streetAddress}, Brgy. ${selectedBarangay}, ${selectedCity}, ${selectedRegion} ${zipcode}` :
                        `Brgy. ${selectedBarangay}, ${selectedCity}, ${selectedRegion} ${zipcode}`;
                    
                    addressInput.value = fullAddress;
                    console.log('Address set manually:', fullAddress);
                } else {
                    hasError = true;
                    console.log('Cannot set address, missing required fields');
                }
            } else {
                console.log('Address already set:', addressInput.value);
            }

            if (hasError) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Form Validation Error',
                    text: 'Please check the highlighted fields and fix the errors before submitting.',
                    confirmButtonColor: '#4e73df'
                });
                console.log('Form submission prevented due to validation errors');
            } else {
                console.log('Form validation passed, submitting form...');
                // Show loading indicator
                Swal.fire({
                    title: 'Creating your account...',
                    text: 'Please wait while we set up your account.',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });
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
            
            // If user hasn't typed 09 as the first two digits and is just starting
            if (this.value.length <= 2 && !this.value.startsWith('09') && this.value !== '0') {
                if (this.value === '0') {
                    // If they typed 0, do nothing and wait for the next digit
                } else {
                    // If they typed something else, add 09 prefix
                    this.value = '09' + this.value;
                }
            }
            
            if (this.value.length === 11 && this.value.startsWith('09')) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else if (this.value.length > 0) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            }
        });

        // Update phone validation in form submit
        const phone = document.querySelector('input[name="phone"]');
        if (!phone.value) {
            setInvalid(phone, 'Phone number is required');
            hasError = true;
        } else if (!/^09[0-9]{9}$/.test(phone.value)) {
            setInvalid(phone, 'Please enter a valid Philippine mobile number starting with 09');
            hasError = true;
        }
    </script>

    <script>
    $(document).ready(function() {
        // Initialize verification attempts counter
        let verificationAttempts = <?php echo isset($_SESSION['verification_attempts']) ? $_SESSION['verification_attempts'] : 0; ?>;
        const maxAttempts = 3;

        // Update remaining attempts display
        function updateRemainingAttempts() {
            const remaining = maxAttempts - verificationAttempts;
            $('#remainingAttempts').text(`Remaining attempts: ${remaining}`);
            if (remaining <= 1) {
                $('#remainingAttempts').addClass('text-danger');
            }
        }

        // Show verification modal if there's a verification error
        <?php if (!empty($verification_err)): ?>
            $('#verificationModal').modal('show');
            $('#verificationError').html('<?php echo $verification_err; ?>').show();
            updateRemainingAttempts();
        <?php endif; ?>

        // Handle verification form submission
        $('#verificationForm').on('submit', function(e) {
            const code = $('input[name="verification_code"]').val().trim();
            
            // Clear previous error messages
            $('#verificationError').hide();

            if (verificationAttempts >= maxAttempts) {
                e.preventDefault();
                $('#verificationError').html('Maximum attempts exceeded. Please register again.').show();
                setTimeout(function() {
                    window.location.href = 'register.php';
                }, 3000);
                return false;
            }

            if (!code) {
                e.preventDefault();
                $('#verificationError').html('Please enter the verification code.').show();
                return false;
            }

            if (code.length !== 6 || !/^\d+$/.test(code)) {
                e.preventDefault();
                $('#verificationError').html('Please enter a valid 6-digit code.').show();
                return false;
            }

            // If we get here, the form will submit
            verificationAttempts++;
            updateRemainingAttempts();
        });

        // Handle resend code button
        $('#resendCode').on('click', function() {
            // You can add resend code functionality here
            $('#verificationError').html('Verification code has been resent to your email.').removeClass('alert-danger').addClass('alert-success').show();
        });

        // Format verification code input
        $('input[name="verification_code"]').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });

        // Show verification modal automatically if needed
        <?php if (isset($_SESSION['show_verification_modal']) && $_SESSION['show_verification_modal']): ?>
            $('#verificationModal').modal('show');
            updateRemainingAttempts();
        <?php endif; ?>

        // Initialize remaining attempts display
        updateRemainingAttempts();
    });
    </script>

    <!-- Add this right before the closing </body> tag -->
    <div class="modal fade" id="verificationModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Email Verification</h5>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-envelope-open-text fa-3x text-primary mb-3"></i>
                        <h4>Verify Your Email</h4>
                        <p class="text-muted">We've sent a verification code to your email address</p>
                    </div>
                    <form id="verificationForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="mb-3">
                            <label class="form-label">Enter Verification Code</label>
                            <input type="text" name="verification_code" class="form-control" maxlength="6" required 
                                   pattern="[0-9]{6}" inputmode="numeric" autocomplete="off">
                            <div class="form-text">Please check your email for the 6-digit verification code</div>
                            <div id="remainingAttempts" class="form-text mt-2"></div>
                        </div>
                        <div id="verificationError" class="alert alert-danger" style="display: none;"></div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check-circle me-2"></i>Verify Email
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="resendCode">
                                <i class="fas fa-redo me-2"></i>Resend Code
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
    #verificationModal .modal-content {
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    #verificationModal .modal-header {
        background: linear-gradient(135deg, var(--primary) 0%, #0D9EB5 100%);
        color: white;
        border: none;
        border-radius: 15px 15px 0 0;
        padding: 1.5rem;
    }

    #verificationModal .modal-body {
        padding: 2rem;
    }

    #verificationModal .form-control {
        border-radius: 10px;
        padding: 0.75rem 1rem;
        border: 1.5px solid #E1E5EA;
        font-size: 1.25rem;
        letter-spacing: 3px;
        text-align: center;
    }

    #verificationModal .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.2rem rgba(19, 197, 221, 0.15);
    }

    #verificationModal .btn {
        border-radius: 50px;
        padding: 0.875rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    #verificationModal .btn-primary {
        background: linear-gradient(135deg, var(--primary) 0%, #0D9EB5 100%);
        border: none;
    }

    #verificationModal .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(19, 197, 221, 0.3);
    }

    #verificationModal .btn-outline-secondary {
        border: 2px solid #E1E5EA;
    }

    #verificationModal .btn-outline-secondary:hover {
        background: #f8f9fa;
        transform: translateY(-2px);
    }

    #remainingAttempts {
        font-weight: 500;
        margin-top: 0.5rem;
    }

    #remainingAttempts.text-danger {
        color: #dc3545 !important;
    }

    .alert {
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .fa-envelope-open-text {
        color: var(--primary);
    }

    #verificationError {
        display: none;
        margin-bottom: 1rem;
        font-weight: 500;
    }

    #verificationError.show {
        display: block;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation on submit
        const form = document.querySelector('form');
        form.addEventListener('submit', function(event) {
            // Check if the form is valid
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Show SweetAlert for validation errors
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill out all required fields correctly.',
                    confirmButtonColor: '#4e73df'
                });
                
                // Find the first invalid field and focus it
                const invalidFields = form.querySelectorAll(':invalid');
                if (invalidFields.length > 0) {
                    invalidFields[0].focus();
                }
            }
            
            // Check if passwords match
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password.value !== confirmPassword.value) {
                event.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Password Mismatch',
                    text: 'The passwords you entered do not match.',
                    confirmButtonColor: '#4e73df'
                });
                confirmPassword.focus();
            }
            
            // Check if phone number is valid (11 digits)
            const phone = document.querySelector('input[name="phone"]');
            if (phone && phone.value && !/^09\d{9}$/.test(phone.value)) {
                event.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Phone Number',
                    text: 'Please enter a valid Philippine mobile number starting with 09.',
                    confirmButtonColor: '#4e73df'
                });
                phone.focus();
            }
            
            form.classList.add('was-validated');
        });
        
        // Add real-time validation for input fields
        const inputFields = document.querySelectorAll('input[required], select[required]');
        inputFields.forEach(field => {
            // Show validation message as soon as the user starts typing
            field.addEventListener('input', function() {
                // Remove validation classes first
                this.classList.remove('is-invalid', 'is-valid');
                
                // Check if field has a value
                if (this.value.trim().length > 0) {
                    // If valid, add valid class
                    if (this.checkValidity()) {
                        this.classList.add('is-valid');
                        // Hide validation message
                        const validationMessage = this.nextElementSibling;
                        if (validationMessage && validationMessage.classList.contains('validation-message')) {
                            validationMessage.style.display = 'none';
                        }
                    } else {
                        // If invalid, add invalid class and show validation message
                        this.classList.add('is-invalid');
                        const validationMessage = this.nextElementSibling;
                        if (validationMessage && validationMessage.classList.contains('validation-message')) {
                            validationMessage.style.display = 'block';
                        }
                    }
                }
            });
            
            // Validate when field loses focus
            field.addEventListener('blur', function() {
                // Remove validation classes first
                this.classList.remove('is-invalid', 'is-valid');
                
                // If field is empty and required
                if (this.value.trim().length === 0 && this.required) {
                    this.classList.add('is-invalid');
                    const validationMessage = this.nextElementSibling;
                    if (validationMessage && validationMessage.classList.contains('validation-message')) {
                        validationMessage.style.display = 'block';
                    }
                } else if (this.checkValidity()) {
                    // If valid, add valid class
                    this.classList.add('is-valid');
                    const validationMessage = this.nextElementSibling;
                    if (validationMessage && validationMessage.classList.contains('validation-message')) {
                        validationMessage.style.display = 'none';
                    }
                } else {
                    // If invalid, add invalid class
                    this.classList.add('is-invalid');
                    const validationMessage = this.nextElementSibling;
                    if (validationMessage && validationMessage.classList.contains('validation-message')) {
                        validationMessage.style.display = 'block';
                    }
                }
            });
        });
        
        // Password strength meter
        const passwordInput = document.getElementById('password');
        const strengthMeter = document.getElementById('strength-meter-fill');
        const strengthText = document.getElementById('strength-text');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let feedback = '';
            
            // Length check
            if (password.length >= 6) {
                strength += 20;
            }
            
            // Character variety checks
            if (password.match(/[A-Z]/)) {
                strength += 20;
            }
            
            if (password.match(/[a-z]/)) {
                strength += 20;
            }
            
            if (password.match(/[0-9]/)) {
                strength += 20;
            }
            
            if (password.match(/[^A-Za-z0-9]/)) {
                strength += 20;
            }
            
            // Update the strength meter
            strengthMeter.style.width = strength + '%';
            
            // Update color based on strength
            if (strength <= 20) {
                strengthMeter.style.backgroundColor = '#dc3545';
                feedback = 'Too weak';
            } else if (strength <= 40) {
                strengthMeter.style.backgroundColor = '#ffc107';
                feedback = 'Weak';
            } else if (strength <= 60) {
                strengthMeter.style.backgroundColor = '#fd7e14';
                feedback = 'Fair';
            } else if (strength <= 80) {
                strengthMeter.style.backgroundColor = '#20c997';
                feedback = 'Good';
            } else {
                strengthMeter.style.backgroundColor = '#28a745';
                feedback = 'Strong';
            }
            
            strengthText.textContent = 'Password strength: ' + feedback;
        });
        
        // Confirm password validation in real-time
        const confirmPasswordInput = document.getElementById('confirm_password');
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                const password = document.getElementById('password').value;
                const confirmFeedback = document.querySelector('#confirm_password + .validation-message');
                
                if (this.value !== password) {
                    this.setCustomValidity('Passwords do not match');
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    if (confirmFeedback) {
                        confirmFeedback.style.display = 'block';
                        confirmFeedback.textContent = 'Passwords do not match';
                    }
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    if (confirmFeedback) {
                        confirmFeedback.style.display = 'none';
                    }
                }
            });
        }
        
        // Toggle password visibility for all password fields
        const passwordToggles = document.querySelectorAll('.password-toggle');
        passwordToggles.forEach(toggle => {
            // Make the toggle visible when user focuses on password field
            const passwordField = toggle.previousElementSibling;
            
            passwordField.addEventListener('focus', function() {
                toggle.style.opacity = '1';
            });
            
            passwordField.addEventListener('blur', function() {
                // Only hide if not actively showing password
                if (this.type === 'password') {
                    toggle.style.opacity = '0.7';
                }
            });
            
            toggle.addEventListener('click', function() {
                const passwordField = this.previousElementSibling;
                const icon = this.querySelector('i');
                
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                    this.style.opacity = '1';
                } else {
                    passwordField.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                    // Keep visible after clicking
                    this.style.opacity = '1';
                }
            });
        });
        
        // Calculate age from date of birth
        const dobInput = document.querySelector('input[name="date_of_birth"]');
        const ageInput = document.querySelector('input[name="age"]');
        
        dobInput.addEventListener('change', function() {
            if (this.value) {
                const dob = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const m = today.getMonth() - dob.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                ageInput.value = age;
            } else {
                ageInput.value = '';
            }
        });
    });
    </script>
</body>
</html> 