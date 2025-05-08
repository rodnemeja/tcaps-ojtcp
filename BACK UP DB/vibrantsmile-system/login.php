<?php
require_once "config/init.php";
require_once "config/database.php";

// Initialize variables
$username = $password = "";
$login_err = "";

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get username and password
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    
    // Validate credentials
    if(!empty($username) && !empty($password)) {
        // Prepare a select statement
        $sql = "SELECT id, username, password, role, full_name FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the statement
            mysqli_stmt_bind_param($stmt, "s", $username);
            
            // Execute the statement
            if(mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if username exists
                if(mysqli_stmt_num_rows($stmt) == 1) {
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role, $full_name);
                    if(mysqli_stmt_fetch($stmt)) {
                        if(password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $role;
                            $_SESSION["full_name"] = $full_name;
                            
                            // Set success message
                            echo "<script>
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Welcome back!',
                                    text: 'Login successful. Redirecting...',
                                    timer: 1500,
                                    showConfirmButton: false,
                                    customClass: {
                                        popup: 'animated fadeInDown faster'
                                    }
                                }).then(function() {
                                    window.location.href = '" . ($role === "admin" ? "admin/dashboard.php" : "dashboard.php") . "';
                                });
                            </script>";
                            exit;
                        } else {
                            // Set password error message
                            echo "<script>
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Invalid Password',
                                    text: 'The password you entered is incorrect.',
                                    confirmButtonColor: '#d33',
                                    customClass: {
                                        popup: 'animated shake faster'
                                    }
                                });
                            </script>";
                        }
                    }
                } else {
                    // Set username error message
                    echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'User Not Found',
                            text: 'No account found with that username.',
                            confirmButtonColor: '#d33',
                            customClass: {
                                popup: 'animated shake faster'
                            }
                        });
                    </script>";
                }
            } else {
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong. Please try again later.',
                        confirmButtonColor: '#d33'
                    });
                </script>";
            }
            
            // Close statement
            mysqli_stmt_close($stmt);
        }
    } else {
        echo "<script>
            Swal.fire({
                icon: 'warning',
                title: 'Empty Fields',
                text: 'Please enter both username and password.',
                confirmButtonColor: '#3085d6'
            });
        </script>";
    }
    
    // Close connection
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dental Clinic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <style>
        body {
            background: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header img {
            width: 120px;
            margin-bottom: 1rem;
        }
        .form-floating {
            margin-bottom: 1rem;
        }
        .btn-login {
            width: 100%;
            padding: 0.8rem;
            font-size: 1.1rem;
        }
        .animated {
            animation-duration: 0.5s;
            animation-fill-mode: both;
        }
        .fadeInDown {
            animation-name: fadeInDown;
        }
        .shake {
            animation-name: shake;
        }
        .faster {
            animation-duration: 0.3s;
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
        @keyframes shake {
            from, to {
                transform: translate3d(0, 0, 0);
            }
            10%, 30%, 50%, 70%, 90% {
                transform: translate3d(-5px, 0, 0);
            }
            20%, 40%, 60%, 80% {
                transform: translate3d(5px, 0, 0);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="assets/images/logo.png" alt="Dental Clinic Logo">
            <h2>Welcome Back!</h2>
            <p class="text-muted">Please login to your account</p>
        </div>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-floating">
                <input type="text" name="username" class="form-control" id="username" placeholder="Username" required>
                <label for="username">Username</label>
            </div>
            
            <div class="form-floating">
                <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-login">
                Login
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 