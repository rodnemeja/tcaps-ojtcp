<?php
session_start();
require_once "config/database.php";

// Check if token is provided
if (!isset($_GET["token"])) {
    header("location: index.php");
    exit;
}

$token = $_GET["token"];
$token_valid = false;
$token_expired = false;
$user_id = null;

// Validate token
$sql = "SELECT user_id, expiry FROM password_resets WHERE token = ? AND used = 0 ORDER BY created_at DESC LIMIT 1";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $token);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
            
            if (strtotime($row["expiry"]) >= time()) {
                $token_valid = true;
                $user_id = $row["user_id"];
            } else {
                $token_expired = true;
            }
        }
    }
    mysqli_stmt_close($stmt);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valid) {
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $error = "";
    
    // Validate password
    if (empty($password)) {
        $error = "Please enter a password.";
    } elseif (strlen($password) < 8) {
        $error = "Password must have at least 8 characters.";
    } elseif ($password != $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Update password
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            mysqli_stmt_bind_param($stmt, "si", $param_password, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                // Mark token as used
                $update_sql = "UPDATE password_resets SET used = 1 WHERE token = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "s", $token);
                mysqli_stmt_execute($update_stmt);
                
                // Redirect to login page
                header("location: index.php?password_reset=success");
                exit;
            } else {
                $error = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Dental Clinic Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .reset-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }
        .reset-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(to right, #4e73df, #36b9cc);
        }
        .reset-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .reset-header img {
            max-width: 150px;
            height: auto;
            margin-bottom: 1.5rem;
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
        .btn-reset {
            background: linear-gradient(to right, #4e73df, #36b9cc);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            width: 100%;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-reset:hover {
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
        .alert {
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <div class="reset-header">
                <img src="assets/images/logo_vibrant.png" alt="Vibrant Smile Dental Clinic" class="img-fluid">
                <h2>Reset Password</h2>
                <?php if (!$token_valid): ?>
                    <div class="alert alert-danger">
                        <?php 
                        if ($token_expired) {
                            echo "This password reset link has expired. Please request a new one.";
                        } else {
                            echo "Invalid password reset link.";
                        }
                        ?>
                    </div>
                    <a href="index.php" class="btn btn-primary">Back to Login</a>
                <?php else: ?>
                    <p class="text-muted">Please enter your new password</p>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?token=' . $token; ?>" method="post">
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <div class="password-container">
                                <input type="password" name="password" class="form-control" required minlength="8">
                                <span class="password-toggle">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <div class="password-container">
                                <input type="password" name="confirm_password" class="form-control" required minlength="8">
                                <span class="password-toggle">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <button type="submit" class="btn btn-reset">
                                <i class="fas fa-key me-2"></i>Reset Password
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Toggle password visibility
        $(".password-toggle").click(function(){
            const passwordInput = $(this).siblings('input[type="password"]');
            const icon = $(this).find('i');
            
            if(passwordInput.attr('type') === 'password'){
                passwordInput.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordInput.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    </script>
</body>
</html> 