<?php
session_start();
require_once "config/database.php";

// Check if user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: dashboard.php");
    exit;
}

// Check if we have a temporary user ID
if(!isset($_SESSION["temp_user_id"])) {
    header("location: index.php");
    exit;
}

$verification_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST["verification_code"])) {
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

                    // Get user information
                    $user_sql = "SELECT username, email, role FROM users WHERE id = ?";
                    $user_stmt = mysqli_prepare($conn, $user_sql);
                    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
                    mysqli_stmt_execute($user_stmt);
                    $user_result = mysqli_stmt_get_result($user_stmt);
                    $user = mysqli_fetch_assoc($user_result);
                    
                    // Start session
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $user_id;
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["role"] = $user["role"];
                    
                    // Clear temporary session data
                    unset($_SESSION["temp_user_id"]);
                    
                    // Redirect to dashboard
                    header("location: dashboard.php");
                    exit;
                }
            } else {
                $verification_err = "Invalid or expired verification code.";
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
    <title>Email Verification - Dental Clinic System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fc 0%, #e8eaf6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .verification-container {
            max-width: 500px;
            width: 100%;
            padding: 20px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .card-header {
            background: #4e73df;
            color: white;
            text-align: center;
            padding: 1.5rem;
            border-radius: 15px 15px 0 0;
            border-bottom: none;
        }
        .verification-input {
            letter-spacing: 0.5em;
            text-align: center;
            font-size: 1.5em;
        }
        .btn-verify {
            background: #4e73df;
            border: none;
            padding: 10px 30px;
            font-weight: 600;
        }
        .btn-verify:hover {
            background: #2e59d9;
        }
        .btn-resend {
            color: #4e73df;
            text-decoration: none;
        }
        .btn-resend:hover {
            color: #2e59d9;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-envelope fa-2x mb-3"></i>
                <h3>Email Verification</h3>
                <p class="mb-0">Please enter the verification code sent to your email</p>
            </div>
            <div class="card-body p-4">
                <?php if(!empty($verification_err)): ?>
                    <div class="alert alert-danger"><?php echo $verification_err; ?></div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-4">
                        <input type="text" name="verification_code" 
                               class="form-control verification-input" 
                               placeholder="000000"
                               pattern="[0-9]{6}" 
                               maxlength="6" 
                               required 
                               autocomplete="off">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-verify">
                            <i class="fas fa-check-circle me-2"></i>Verify Email
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p class="mb-0">Didn't receive the code?</p>
                    <a href="register.php" class="btn-resend">Resend Code</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-format verification code input
        document.querySelector('input[name="verification_code"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    </script>
</body>
</html> 