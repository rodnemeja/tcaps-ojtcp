<?php
session_start();
require_once "config/database.php";

$token = $_GET['token'] ?? '';
$verification_status = '';

if (empty($token)) {
    $verification_status = 'error';
    $message = 'Invalid verification token.';
} else {
    // Check if token exists and is not expired
    $sql = "SELECT user_id, created_at FROM email_verifications WHERE token = ? AND verified = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                // Update user as verified
                $update_sql = "UPDATE users SET email_verified = 1 WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "i", $row['user_id']);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    // Mark verification token as used
                    $mark_used_sql = "UPDATE email_verifications SET verified = 1 WHERE token = ?";
                    $mark_used_stmt = mysqli_prepare($conn, $mark_used_sql);
                    mysqli_stmt_bind_param($mark_used_stmt, "s", $token);
                    mysqli_stmt_execute($mark_used_stmt);
                    
                    $verification_status = 'success';
                    $message = 'Your email has been successfully verified! You can now log in to your account.';
                } else {
                    $verification_status = 'error';
                    $message = 'Error updating verification status.';
                }
            } else {
                $verification_status = 'error';
                $message = 'Invalid or expired verification token.';
            }
        } else {
            $verification_status = 'error';
            $message = 'Error processing verification.';
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
            background: #f8f9fc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verification-container {
            max-width: 500px;
            width: 90%;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            text-align: center;
        }
        .icon-container {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .success-icon {
            color: #1cc88a;
        }
        .error-icon {
            color: #e74a3b;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="icon-container">
            <?php if ($verification_status === 'success'): ?>
                <i class="fas fa-check-circle success-icon"></i>
            <?php else: ?>
                <i class="fas fa-times-circle error-icon"></i>
            <?php endif; ?>
        </div>
        
        <h3><?php echo $verification_status === 'success' ? 'Verification Successful!' : 'Verification Failed'; ?></h3>
        <p class="mb-4"><?php echo htmlspecialchars($message); ?></p>
        
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-home me-2"></i>Go to Login
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 