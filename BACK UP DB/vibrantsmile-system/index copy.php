<?php
session_start();
require_once "config/database.php";

// Check if user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    // Redirect based on role
    if($_SESSION["role"] === "admin"){
        header("location: admin/dashboard.php");
    } else {
        header("location: dashboard.php");
    }
    exit;
}

// Add password validation function
function validatePassword($password) {
    // Password must be at least 8 characters long
    // Must contain at least one uppercase letter, one lowercase letter, one number, and one special character
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $special   = preg_match('@[^\w]@', $password);

    return strlen($password) >= 8 && $uppercase && $lowercase && $number && $special;
}

// Process login
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $login_err = "";
    
    if(!empty($username) && !empty($password)){
        $sql = "SELECT id, username, password, role, email_verified, first_name, last_name FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $username);
            
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                
                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_assoc($result);
                    
                    // Debug information
                    error_log("Login attempt - Username: " . $username . ", Role: " . $row["role"] . ", Email verified: " . $row["email_verified"]);
                    
                    if(password_verify($password, $row["password"])){
                        error_log("Password verified successfully");
                        
                        // Check if email is verified (only for patients)
                        if($row["email_verified"] == 0 && $row["role"] == "patient"){
                            error_log("Email verification required for patient");
                            $response = array(
                                "status" => "error",
                                "message" => "Please verify your email address before logging in. Check your email for the verification code."
                            );
                            echo json_encode($response);
        exit;
    }
    
                        // Store session data
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $row["id"];
                        $_SESSION["username"] = $row["username"];
                        $_SESSION["role"] = $row["role"];
                        $_SESSION["name"] = $row["first_name"] . " " . $row["last_name"];
                        
                        error_log("Session data stored. Role: " . $_SESSION["role"]);
                        
                        // Determine redirect URL based on role
                        $redirect_url = "dashboard.php";
                        if($row["role"] === "admin" || $row["role"] === "staff") {
                            $redirect_url = "admin/dashboard.php";
                            error_log("Admin/Staff redirect to: " . $redirect_url);
                        }
                        
                        $response = array(
                            "status" => "success",
                            "redirect" => $redirect_url
                        );
                        echo json_encode($response);
                        exit;
                    } else {
                        error_log("Password verification failed for user: " . $username);
                        $response = array(
                            "status" => "error",
                            "message" => "Invalid username or password."
                        );
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response = array(
                        "status" => "error",
                        "message" => "Invalid username or password."
                    );
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = array(
                    "status" => "error",
                    "message" => "Oops! Something went wrong. Please try again later."
                );
                echo json_encode($response);
                exit;
            }
            
            mysqli_stmt_close($stmt);
        }
    } else {
        $response = array(
            "status" => "error",
            "message" => "Please enter username and password."
        );
        echo json_encode($response);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dental Clinic Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(to right, #4e73df, #36b9cc);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .login-header img {
            max-width: 200px;
            height: auto;
            margin-bottom: 1.5rem;
        }
        .login-header h2 {
            color: #2e384d;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.75rem;
        }
        .login-header p {
            color: #6c757d;
            font-size: 1rem;
        }
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        .input-group-text {
            border-radius: 10px 0 0 10px;
            border: 2px solid #e9ecef;
            border-right: none;
            background-color: #f8f9fa;
        }
        .input-group .form-control {
            border-radius: 0 10px 10px 0;
        }
        .btn-login {
            background: linear-gradient(to right, #4e73df, #36b9cc);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            width: 100%;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        .btn-login:hover {
            background: linear-gradient(to right, #36b9cc, #4e73df);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
            padding: 5px;
        }
        .password-container {
            position: relative;
        }
        .form-label {
            color: #2e384d;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .forgot-password {
            text-align: right;
            margin-top: 0.5rem;
        }
        .forgot-password a {
            color: #4e73df;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .forgot-password a:hover {
            color: #2e59d9;
            text-decoration: underline;
        }
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
        }
        .register-link a {
            color: #4e73df;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .register-link a:hover {
            color: #2e59d9;
            text-decoration: underline;
        }
        .animated {
            animation-duration: 0.5s;
            animation-fill-mode: both;
        }
        .fadeInDown {
            animation-name: fadeInDown;
        }
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translate3d(0, -20px, 0);
            }
            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container animated fadeInDown">
            <div class="login-header">
                <img src="assets/images/logo_vibrant.png" alt="Vibrant Smile Dental Clinic" class="img-fluid">
                <h2>Welcome Back!</h2>
                <p class="text-muted">Sign in to continue to your account</p>
            </div>

            <form id="loginForm" method="post">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" class="form-control" required autocomplete="username" placeholder="Enter your username">
                    </div>
                </div>    
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group password-container">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" required autocomplete="current-password" placeholder="Enter your password">
                        <span class="password-toggle">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <div class="forgot-password">
                        <a href="#" id="forgotPasswordLink">Forgot Password?</a>
                    </div>
                </div>
                <div class="mb-3">
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                    </button>
                </div>
                <div class="register-link">
                    Don't have an account? <a href="register.php">Create Account</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function(){
            $("#loginForm").on("submit", function(e){
        e.preventDefault();
        
                const submitBtn = $(this).find('button[type="submit"]');
                const originalBtnText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Signing in...').prop('disabled', true);
                
                $.ajax({
                    url: "index.php",
                    type: "POST",
                    data: $(this).serialize(),
                    dataType: "json",
                    success: function(response){
                        if(response.status === "success"){
                Swal.fire({
                    icon: 'success',
                                title: 'Welcome Back!',
                                text: 'Login successful',
                    showConfirmButton: false,
                                timer: 1500
                            }).then(function(){
                                window.location.href = response.redirect;
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Login Failed',
                                text: response.message
                            });
                            submitBtn.html(originalBtnText).prop('disabled', false);
                        }
                    },
                    error: function(){
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred. Please try again.'
                        });
                        submitBtn.html(originalBtnText).prop('disabled', false);
                    }
                });
            });
            
            // Toggle password visibility
            $(".password-toggle").click(function(){
                const passwordInput = $(this).siblings('input[type="password"], input[type="text"]');
                const icon = $(this).find('i');
                
                if(passwordInput.attr('type') === 'password'){
                    passwordInput.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordInput.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });

            // Add this to ensure password is hidden when page loads or form resets
            $("#loginForm").on("reset", function() {
                const passwordInput = $('input[name="password"]');
                const icon = $('.password-toggle i');
                passwordInput.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            });

            // Ensure password field is reset to password type when clicking away
            $(document).click(function(event) {
                if (!$(event.target).closest('.password-container').length) {
                    const passwordInput = $('input[name="password"]');
                    const icon = $('.password-toggle i');
                    if (passwordInput.attr('type') === 'text') {
                        passwordInput.attr('type', 'password');
                        icon.removeClass('fa-eye-slash').addClass('fa-eye');
                    }
                }
            });

            // Forgot Password Handler
            $("#forgotPasswordLink").click(function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Reset Password',
                    html: `
                        <div class="mb-3">
                            <input type="email" 
                                id="resetEmail" 
                                class="swal2-input" 
                                placeholder="Enter your email address"
                                pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                                required
                                oninput="validateResetEmail(this)"
                                onkeypress="return blockSpecialChars(event)">
                            <div id="emailError" class="text-danger" style="font-size: 0.875rem; margin-top: 0.25rem;"></div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Send Reset Link',
                    showLoaderOnConfirm: true,
                    didOpen: () => {
                        // Add input event listener
                        const emailInput = document.getElementById('resetEmail');
                        emailInput.addEventListener('input', function() {
                            validateResetEmail(this);
                        });
                    },
                    preConfirm: () => {
                        const email = document.getElementById('resetEmail').value;
                        const errorDiv = document.getElementById('emailError');
                        
                        // Clear previous error
                        errorDiv.textContent = '';
                        
                        // Comprehensive email validation
                        if (!email) {
                            errorDiv.textContent = 'Please enter your email address.';
                            return false;
                        }

                        // Basic format validation
                        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                        if (!emailRegex.test(email)) {
                            errorDiv.textContent = 'Please enter a valid email address.';
                            return false;
                        }

                        // Domain validation
                        const domain = email.split('@')[1];
                        if (!domain || !domain.includes('.')) {
                            errorDiv.textContent = 'Invalid email domain format.';
                            return false;
                        }

                        // TLD validation
                        const tld = domain.split('.').pop();
                        if (!tld || tld.length < 2) {
                            errorDiv.textContent = 'Invalid email: missing or invalid TLD (e.g., .com, .org, .net)';
                            return false;
                        }

                        // Make the AJAX call
                        return $.ajax({
                            url: 'reset_password.php',
                            type: 'POST',
                            data: { email: email },
                            dataType: 'json'
                        }).catch(error => {
                            Swal.showValidationMessage('Request failed: ' + error.message);
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed && result.value.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Email Sent',
                            text: result.value.message
                        });
                    } else if (result.isConfirmed && result.value.status === 'error') {
                Swal.fire({
                    icon: 'error',
                            title: 'Error',
                            text: result.value.message
                        });
                    }
                });
            });

            // Email validation function
            function validateResetEmail(input) {
                const errorDiv = document.getElementById('emailError');
                const email = input.value.trim();
                
                // Clear previous error
                errorDiv.textContent = '';
                
                // Basic validation
                if (!email) {
                    errorDiv.textContent = 'Please enter your email address.';
                    return false;
                }

                // Format validation
                const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                if (!emailRegex.test(email)) {
                    errorDiv.textContent = 'Please enter a valid email address.';
                    return false;
                }

                // Length validation
                if (email.length > 254) {
                    errorDiv.textContent = 'Email address is too long.';
                    return false;
                }

                // Local part validation
                const localPart = email.split('@')[0];
                if (localPart.length > 64) {
                    errorDiv.textContent = 'Local part of email address is too long.';
                    return false;
                }

                // Check for consecutive special characters
                if (/[.]{2,}/.test(localPart) || /[_]{2,}/.test(localPart)) {
                    errorDiv.textContent = 'Invalid format: consecutive special characters not allowed.';
                    return false;
                }

                // Domain validation
                const domain = email.split('@')[1];
                if (!domain || !domain.includes('.')) {
                    errorDiv.textContent = 'Invalid email domain format.';
                    return false;
                }

                // TLD validation
                const tld = domain.split('.').pop().toLowerCase();
                const invalidTlds = ['local', 'localhost', 'test', 'invalid', 'example'];
                if (!tld || tld.length < 2 || invalidTlds.includes(tld)) {
                    errorDiv.textContent = 'Invalid email domain.';
                    return false;
                }

                return true;
            }

            // Block special characters on keypress
            function blockSpecialChars(event) {
                const key = event.key;
                const regex = /^[a-zA-Z0-9@._-]$/;
                
                if (!regex.test(key) && key !== 'Backspace' && key !== 'Delete' && key !== 'ArrowLeft' && key !== 'ArrowRight') {
                    event.preventDefault();
                    return false;
                }
                return true;
            }
    });
    </script>
</body>
</html> 