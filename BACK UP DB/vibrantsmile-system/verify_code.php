<?php
session_start();
require_once "config/database.php";

// Check if user has a temporary ID (means they just registered)
if (!isset($_SESSION["temp_user_id"])) {
    header("location: register.php");
    exit;
}

// Initialize verification attempts if not set
if (!isset($_SESSION['verification_attempts'])) {
    $_SESSION['verification_attempts'] = 0;
}

$verification_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["verification_code"])) {
        $verification_code = trim($_POST["verification_code"]);
        $user_id = $_SESSION["temp_user_id"];

        // Check if user has exceeded maximum attempts
        if ($_SESSION['verification_attempts'] >= 3) {
            // Set session flag for max attempts
            $_SESSION['max_attempts_reached'] = true;
            $verification_err = "Maximum attempts exceeded!";
            
            // Clear all session data
            session_unset();
            session_destroy();
            
            // Start a new session just for the error message
            session_start();
            $_SESSION['verification_error'] = true;
            
            // Return JSON response for AJAX
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Maximum attempts exceeded']);
                exit;
            }
            
            // If not AJAX, redirect with JavaScript
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            </head>
            <body>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Maximum Attempts Exceeded',
                        html: `
                            <div class="text-center">
                                <div class="mb-3">
                                    <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                                    <h5>You have exceeded the maximum number of verification attempts.</h5>
                                </div>
                                <p class="mb-3">For security reasons, you will need to register again.</p>
                                <div class="countdown-timer mb-3">
                                    <span id="countdown">5</span> seconds remaining
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>
                        `,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        timer: 5000,
                        timerProgressBar: true,
                        didOpen: () => {
                            const timer = setInterval(() => {
                                const countdownElement = document.getElementById('countdown');
                                let timeLeft = parseInt(countdownElement.textContent);
                                if (timeLeft > 0) {
                                    countdownElement.textContent = timeLeft - 1;
                                } else {
                                    clearInterval(timer);
                                }
                            }, 1000);
                        }
                    }).then(() => {
                        window.location.href = 'register.php';
                    });

                    // Force redirect after 6 seconds (backup)
                    setTimeout(() => {
                        window.location.href = 'register.php';
                    }, 6000);
                </script>
            </body>
            </html>
            <?php
            exit;
        }

        try {
            // Start transaction
            mysqli_begin_transaction($conn);

            // Check if the verification code matches
            $sql = "SELECT * FROM email_verifications WHERE user_id = ? AND token = ? AND verified = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "is", $user_id, $verification_code);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if (mysqli_num_rows($result) > 0) {
                    // Update user as verified
                    $update_sql = "UPDATE users SET email_verified = 1 WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_sql);
                    mysqli_stmt_bind_param($update_stmt, "i", $user_id);
                    
                    if (mysqli_stmt_execute($update_stmt)) {
                        // Mark verification code as used
                        $mark_used_sql = "UPDATE email_verifications SET verified = 1 WHERE user_id = ? AND token = ?";
                        $mark_used_stmt = mysqli_prepare($conn, $mark_used_sql);
                        mysqli_stmt_bind_param($mark_used_stmt, "is", $user_id, $verification_code);
                        mysqli_stmt_execute($mark_used_stmt);

                        // Get user information
                        $user_sql = "SELECT username, role FROM users WHERE id = ?";
                        $user_stmt = mysqli_prepare($conn, $user_sql);
                        mysqli_stmt_bind_param($user_stmt, "i", $user_id);
                        mysqli_stmt_execute($user_stmt);
                        $user_result = mysqli_stmt_get_result($user_stmt);
                        $user = mysqli_fetch_assoc($user_result);

                        // Commit transaction
                        mysqli_commit($conn);

                        // Start user session
                        session_regenerate_id(true); // Prevent session fixation
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $user_id;
                        $_SESSION["username"] = $user["username"];
                        $_SESSION["role"] = $user["role"];
                        
                        // Clear verification data
                        unset($_SESSION["temp_user_id"]);
                        unset($_SESSION["temp_email"]);
                        unset($_SESSION["verification_attempts"]);
                        
                        // Show success message and redirect
                        ?>
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                        </head>
                        <body>
                            <script>
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Email Verified Successfully!',
                                    text: 'Redirecting to dashboard...',
                                    timer: 2000,
                                    timerProgressBar: true,
                                    showConfirmButton: false,
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        const timer = setInterval(() => {
                                            const b = Swal.getHtmlContainer().querySelector('b');
                                            if (b) {
                                                b.textContent = (Swal.getTimerLeft() / 1000).toFixed(0);
                                            }
                                        }, 100);
                                    }
                                }).then(() => {
                                    <?php if(isset($_SESSION['family_success'])): ?>
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Family Information',
                                        html: '<?php echo $_SESSION['family_success']; ?>',
                                        timer: 3000,
                                        timerProgressBar: true,
                                        showConfirmButton: true,
                                        confirmButtonText: 'Go to Dashboard'
                                    }).then(() => {
                                        window.location.href = 'dashboard.php';
                                    });
                                    <?php elseif(isset($_SESSION['family_error'])): ?>
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Family Information',
                                        html: '<?php echo $_SESSION['family_error']; ?>',
                                        timer: 3000,
                                        timerProgressBar: true,
                                        showConfirmButton: true,
                                        confirmButtonText: 'Go to Dashboard'
                                    }).then(() => {
                                        window.location.href = 'dashboard.php';
                                    });
                                    <?php else: ?>
                                    window.location.href = 'dashboard.php';
                                    <?php endif; ?>
                                });

                                // Force redirect after 3 seconds (backup)
                                setTimeout(() => {
                                    window.location.href = 'dashboard.php';
                                }, 3000);
                            </script>
                        </body>
                        </html>
                        <?php
                        exit;
                    }
                } else {
                    // Increment verification attempts
                    $_SESSION['verification_attempts']++;
                    $remaining_attempts = 3 - $_SESSION['verification_attempts'];
                    
                    if ($remaining_attempts > 0) {
                        $verification_err = "Invalid verification code. You have {$remaining_attempts} attempts remaining.";
                        ?>
                        <script>
                            Swal.fire({
                                icon: 'error',
                                title: 'Invalid Code',
                                html: `
                                    <div class="text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                                            <h5>The verification code you entered is incorrect.</h5>
                                        </div>
                                        <p class="text-danger fw-bold">
                                            You have ${remaining_attempts} attempt${remaining_attempts === 1 ? '' : 's'} remaining
                                        </p>
                                        <div class="progress mt-3" style="height: 5px;">
                                            <div class="progress-bar bg-danger" role="progressbar" 
                                                 style="width: ${(($_SESSION['verification_attempts'] / 3) * 100)}%">
                                            </div>
                                        </div>
                                    </div>
                                `,
                                showConfirmButton: true,
                                confirmButtonText: 'Try Again',
                                allowOutsideClick: false
                            });
                        </script>
                        <?php
                    } else {
                        $_SESSION['max_attempts_reached'] = true;
                        $verification_err = "Maximum attempts exceeded!";
                        
                        // Clear all session data
                        session_unset();
                        session_destroy();
                        
                        // Start a new session just for the error message
                        session_start();
                        $_SESSION['verification_error'] = true;
                        
                        ?>
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                        </head>
                        <body>
                            <script>
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Maximum Attempts Exceeded',
                                    html: `
                                        <div class="text-center">
                                            <div class="mb-3">
                                                <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                                                <h5>You have exceeded the maximum number of verification attempts.</h5>
                                            </div>
                                            <p class="mb-3">For security reasons, you will need to register again.</p>
                                            <div class="countdown-timer mb-3">
                                                <span id="countdown">5</span> seconds remaining
                                            </div>
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar bg-danger" role="progressbar" style="width: 100%"></div>
                                            </div>
                                        </div>
                                    `,
                                    showConfirmButton: false,
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    timer: 5000,
                                    timerProgressBar: true,
                                    didOpen: () => {
                                        const timer = setInterval(() => {
                                            const countdownElement = document.getElementById('countdown');
                                            let timeLeft = parseInt(countdownElement.textContent);
                                            if (timeLeft > 0) {
                                                countdownElement.textContent = timeLeft - 1;
                                            } else {
                                                clearInterval(timer);
                                            }
                                        }, 1000);
                                    }
                                }).then(() => {
                                    window.location.href = 'register.php';
                                });

                                // Force redirect after 6 seconds (backup)
                                setTimeout(() => {
                                    window.location.href = 'register.php';
                                }, 6000);
                            </script>
                        </body>
                        </html>
                        <?php
                        exit;
                    }
                }
                mysqli_stmt_close($stmt);
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $verification_err = "An error occurred. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Dental Clinic Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #13C5DD;
            --secondary: #354F8E;
        }

        body {
            background: linear-gradient(135deg, #F8F9FA 0%, #E9ECEF 100%);
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
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, #0D9EB5 100%);
            color: white;
            text-align: center;
            padding: 2rem;
            border: none;
        }

        .card-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .card-body {
            padding: 2rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1.5px solid #E1E5EA;
            font-size: 1.25rem;
            letter-spacing: 3px;
            text-align: center;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(19, 197, 221, 0.15);
        }

        .btn-verify {
            background: linear-gradient(135deg, var(--primary) 0%, #0D9EB5 100%);
            border: none;
            border-radius: 50px;
            padding: 0.875rem;
            font-weight: 600;
            color: white;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(19, 197, 221, 0.3);
        }

        .btn-resend {
            background: none;
            border: 2px solid #E1E5EA;
            border-radius: 50px;
            padding: 0.875rem;
            font-weight: 600;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .btn-resend:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }

        #remainingAttempts {
            font-weight: 500;
            margin-top: 0.5rem;
            text-align: center;
        }

        #remainingAttempts.text-danger {
            color: #dc3545 !important;
        }

        .alert {
            border-radius: 10px;
            margin-top: 1rem;
        }

        .verification-email {
            text-align: center;
            margin-bottom: 1rem;
            color: #666;
        }

        .verification-email strong {
            color: var(--primary);
        }

        /* Add custom styles for SweetAlert */
        .swal2-popup {
            border-radius: 15px;
            padding: 2rem;
        }

        .swal2-title {
            color: #dc3545;
            font-size: 1.5rem;
        }

        .swal2-html-container {
            margin: 1rem 0;
        }

        .swal2-timer-progress-bar {
            background: #dc3545;
        }

        .text-muted {
            color: #6c757d !important;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-envelope-open-text"></i>
                <h3 class="mb-0">Email Verification</h3>
            </div>
            <div class="card-body">
                <div class="verification-email">
                    We've sent a verification code to:<br>
                    <strong><?php echo isset($_SESSION['temp_email']) ? $_SESSION['temp_email'] : ''; ?></strong>
                </div>

                <form id="verificationForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <input type="text" name="verification_code" class="form-control" maxlength="6" required 
                               pattern="[0-9]{6}" inputmode="numeric" autocomplete="off" placeholder="Enter 6-digit code">
                        <div class="form-text text-center">Please check your email for the verification code</div>
                        <div id="remainingAttempts" class="<?php echo ($_SESSION['verification_attempts'] >= 2) ? 'text-danger' : ''; ?>">
                            Remaining attempts: <?php echo (3 - $_SESSION['verification_attempts']); ?>
                        </div>
                    </div>

                    <?php if (!empty($verification_err)): ?>
                        <div class="alert alert-danger text-center">
                            <?php echo $verification_err; ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-verify">
                        <i class="fas fa-check-circle me-2"></i>Verify Email
                    </button>
                    <button type="button" class="btn btn-resend" id="resendCode">
                        <i class="fas fa-redo me-2"></i>Resend Code
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check for verification error on page load
            <?php if (isset($_SESSION['verification_error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Session Expired',
                text: 'Your verification session has expired. Please register again.',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                willClose: () => {
                    window.location.href = 'register.php';
                }
            });
            <?php 
            // Clear the error flag
            unset($_SESSION['verification_error']);
            endif; ?>
            
            // Format verification code input
            const codeInput = document.querySelector('input[name="verification_code"]');
            if (codeInput) {
                codeInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
                });
            }

            // Handle resend code button
            document.getElementById('resendCode').addEventListener('click', function() {
                Swal.fire({
                    icon: 'info',
                    title: 'Verification Code Resent',
                    text: 'A new verification code has been sent to your email.',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            });
        });
    </script>
</body>
</html> 