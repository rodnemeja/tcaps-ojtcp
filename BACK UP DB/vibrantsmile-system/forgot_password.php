<?php
session_start();
require_once "config/database.php";
require_once "config/mail.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$email = $message = "";
$message_type = "";

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate email
    if(empty(trim($_POST["email"]))){
        $message = "Please enter your email address.";
        $message_type = "danger";
    } else {
        $email = trim($_POST["email"]);
        
        // Check if email exists in the database
        $sql = "SELECT id, username FROM users WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    mysqli_stmt_bind_result($stmt, $user["id"], $user["username"]);
                    mysqli_stmt_fetch($stmt);
                    
                    // Generate token
                    $token = bin2hex(random_bytes(32));
                    $token_hash = password_hash($token, PASSWORD_DEFAULT);
                    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Store token in the database
                    $sql = "INSERT INTO password_resets (user_id, token, expiry, ip_address) VALUES (?, ?, ?, ?)";
                    
                    if($update_stmt = mysqli_prepare($conn, $sql)){
                        $ip_address = $_SERVER['REMOTE_ADDR'];
                        mysqli_stmt_bind_param($update_stmt, "isss", $user["id"], $token, $expiry, $ip_address);
                        
                        if(mysqli_stmt_execute($update_stmt)){
                            // Send email with reset link
                            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/vibrantsmile-system/new_password.php?token=" . $token . "&email=" . urlencode($email);
                            
                            // Get user's name for the email
                            $name = $user["username"];
                            
                            // Use our mail function to send the reset email
                            if(sendPasswordResetEmail($email, $name, $reset_link)) {
                                $message = "Password reset link has been sent to your email address.";
                                $message_type = "success";
                            } else {
                                $message = "Failed to send reset email. Please try again later.";
                                $message_type = "danger";
                            }
                        } else {
                            $message = "Something went wrong. Please try again later.";
                            $message_type = "danger";
                        }
                    }
                } else {
                    $message = "No account found with that email address.";
                    $message_type = "danger";
                }
            } else {
                $message = "Something went wrong. Please try again later.";
                $message_type = "danger";
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
    <title>Forgot Password - Dental Clinic Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reset-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 2rem;
            width: 100%;
            max-width: 500px;
            margin: 2rem auto;
        }
        .reset-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .reset-header img {
            max-width: 150px;
            height: auto;
            margin: 0 auto 1.5rem;
            display: block;
        }
        .reset-header h2 {
            font-size: 1.8rem;
            color: #2B2B2B;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .reset-header p {
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
        .btn-primary {
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
        .btn-primary:hover {
            background: linear-gradient(to right, #36b9cc, #4e73df);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
        }
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        .back-link a {
            color: #4e73df;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .back-link a:hover {
            color: #2e59d9;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <div class="reset-header">
                <img src="assets/images/logo_vibrant.png" alt="Vibrant Smile Dental Clinic" class="img-fluid">
                <h2>Forgot Password</h2>
                <p>Enter your email address to receive password reset instructions</p>
            </div>

            <?php if(!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="Enter your email address" value="<?php echo $email; ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                    </button>
                </div>
                <div class="back-link">
                    <a href="index.php"><i class="fas fa-arrow-left me-1"></i>Back to Homepage</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 