<?php
if (session_status() === PHP_SESSION_NONE) {
session_start();
}
require_once "config/database.php";

// Debug logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    // Debug logging
    error_log("User already logged in. Role: " . $_SESSION["role"]);
    
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

// For AJAX requests, we only want to process the login logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    
    if(empty($username) || empty($password)){
        echo json_encode([
            "status" => "error",
            "message" => "Please enter both username and password."
        ]);
        exit;
    }

        $sql = "SELECT id, username, password, role, email_verified, first_name, last_name FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $username);
            
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                
                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_assoc($result);
                    
                    if(password_verify($password, $row["password"])){
                    // Check if email verification is required (only for patients)
                        if($row["email_verified"] == 0 && $row["role"] == "patient"){
                        echo json_encode([
                                "status" => "error",
                            "message" => "Please verify your email address before logging in."
                        ]);
        exit;
    }
    
                    // Set session variables
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $row["id"];
                        $_SESSION["username"] = $row["username"];
                        $_SESSION["role"] = $row["role"];
                        $_SESSION["name"] = $row["first_name"] . " " . $row["last_name"];
                        
                    // Determine redirect URL
                    $redirect_url = ($row["role"] === "admin" || $row["role"] === "staff") ? "admin/dashboard.php" : "dashboard.php";
                    
                    echo json_encode([
                            "status" => "success",
                            "redirect" => $redirect_url
                    ]);
                } else {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Invalid username or password."
                    ]);
                }
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid username or password."
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Something went wrong. Please try again later."
            ]);
        }
        
        mysqli_stmt_close($stmt);
        exit;
    }
}

// Regular page load - check if already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: " . ($_SESSION["role"] === "admin" ? "admin/dashboard.php" : "dashboard.php"));
    exit;
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
            background: none;
            min-height: auto;
            display: block;
            align-items: normal;
            justify-content: normal;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: none;
            padding: 1.5rem;
            width: 100%;
            max-width: none;
            margin: 0;
        }
        .login-container::before {
            display: none;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header img {
            max-width: 180px;
            height: auto;
            margin: 0 auto 1.5rem;
            display: block;
        }
        .login-header h2 {
            font-size: 1.8rem;
            color: #2B2B2B;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #6c757d;
            font-size: 1rem;
            margin-bottom: 0;
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
        @media (max-width: 576px) {
            .login-container {
                padding: 1rem;
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
                <p>Sign in to continue to your account</p>
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
                        <a href="forgot_password.php" id="forgotPasswordLink">Forgot Password?</a>
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
        $(document).ready(function() {
            // Handle form submission
            $("#loginForm").on("submit", function(e) {
        e.preventDefault();
        
                const submitBtn = $(this).find('button[type="submit"]');
                const originalBtnText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Signing in...').prop('disabled', true);
                
                $.ajax({
                    type: "POST",
                    url: "index1.php",
                    data: $(this).serialize(),
                    dataType: "json",
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        if(response.status === "success") {
                Swal.fire({
                    icon: 'success',
                                title: 'Welcome Back!',
                                text: 'Login successful',
                    showConfirmButton: false,
                                timer: 1500
                            }).then(function() {
                                window.location.href = response.redirect;
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Login Failed',
                                text: response.message || 'Invalid username or password.',
                                confirmButtonColor: '#4e73df'
                            });
                            submitBtn.html(originalBtnText).prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Login error:", error);
                        console.error("Response:", xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while trying to log in. Please try again.',
                            confirmButtonColor: '#4e73df'
                        });
                        submitBtn.html(originalBtnText).prop('disabled', false);
                    }
                });
            });
            
            // Password visibility toggle
            $(".password-toggle").click(function() {
                var passwordInput = $(this).siblings('input');
                var icon = $(this).find('i');
                
                if (passwordInput.attr('type') === 'password') {
                    passwordInput.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordInput.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });

            // Update Forgot Password link behavior (no modal, just redirect)
            $("#forgotPasswordLink").click(function(e) {
                // Let the default link behavior work (no preventDefault)
            });
    });
    </script>
</body>
</html> 