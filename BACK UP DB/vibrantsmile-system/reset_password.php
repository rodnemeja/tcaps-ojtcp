<?php
session_start();
require_once "config/database.php";
require_once "config/mail.php";

header('Content-Type: application/json');

function validateEmail($email) {
    // Basic email format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Please enter a valid email address.";
    }

    // Extract domain part
    $domain = substr(strrchr($email, "@"), 1);
    
    // Check if domain is empty
    if (empty($domain)) {
        return "Invalid email format: missing domain.";
    }

    // Check domain format
    if (!preg_match("/^[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]\.([a-zA-Z]{2,})+$/", $domain)) {
        return "Invalid email domain format.";
    }

    // Validate TLD (Top Level Domain)
    $tld = substr(strrchr($domain, '.'), 1);
    if (empty($tld) || strlen($tld) < 2) {
        return "Invalid email: missing or invalid TLD (e.g., .com, .org, .net)";
    }

    // Common invalid TLDs
    $invalid_tlds = array('local', 'localhost', 'test', 'invalid', 'example');
    if (in_array(strtolower($tld), $invalid_tlds)) {
        return "Invalid email domain TLD.";
    }

    // Check for valid domain
    if (!checkdnsrr($domain, "MX")) {
        return "Invalid email domain: no valid mail server found.";
    }

    // Check email length
    if (strlen($email) > 254) {
        return "Email address is too long.";
    }

    // Check local part length (before @)
    $local = strstr($email, '@', true);
    if (strlen($local) > 64) {
        return "Local part of email address is too long.";
    }

    // Check for common disposable email domains
    $disposable_domains = array(
        'tempmail.com', 'throwawaymail.com', 'mailinator.com',
        'temp-mail.org', 'guerrillamail.com', 'yopmail.com',
        'sharklasers.com', '10minutemail.com', 'trashmail.com',
        'fakeinbox.com', 'tempinbox.com', 'dumpmail.de',
        'mailnull.com', 'emltmp.com', 'emailondeck.com'
    );
    
    if (in_array(strtolower($domain), $disposable_domains)) {
        return "Disposable email addresses are not allowed.";
    }

    // Check for common email patterns that might indicate temporary emails
    $suspicious_patterns = array(
        '/^temp[_.-]/', '/^tmp[_.-]/', '/^fake[_.-]/',
        '/^test[_.-]/', '/^anonymous[_.-]/', '/^disposable[_.-]/'
    );
    
    foreach ($suspicious_patterns as $pattern) {
        if (preg_match($pattern, strtolower($local))) {
            return "This type of email address is not allowed.";
        }
    }

    // Check for repeated special characters
    if (preg_match('/[.]{2,}/', $local) || preg_match('/[_]{2,}/', $local)) {
        return "Invalid email format: consecutive special characters not allowed.";
    }

    // Check for valid characters in local part
    if (!preg_match('/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+$/', $local)) {
        return "Invalid characters in email address.";
    }

    return "";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $response = array();

    if (empty($email)) {
        $response = array(
            "status" => "error",
            "message" => "Please enter your email address."
        );
    } else {
        // Validate email
        $validation_error = validateEmail($email);
        if (!empty($validation_error)) {
            $response = array(
                "status" => "error",
                "message" => $validation_error
            );
        } else {
            // Check for rate limiting (3 attempts per hour)
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $current_time = date('Y-m-d H:i:s');
            $one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));

            $rate_limit_sql = "SELECT COUNT(*) as attempt_count FROM password_resets 
                             WHERE created_at > ? AND ip_address = ?";
            
            if ($rate_stmt = mysqli_prepare($conn, $rate_limit_sql)) {
                mysqli_stmt_bind_param($rate_stmt, "ss", $one_hour_ago, $ip_address);
                mysqli_stmt_execute($rate_stmt);
                $rate_result = mysqli_stmt_get_result($rate_stmt);
                $attempt_count = mysqli_fetch_assoc($rate_result)['attempt_count'];

                if ($attempt_count >= 3) {
                    $response = array(
                        "status" => "error",
                        "message" => "Too many reset attempts. Please try again later."
                    );
                    echo json_encode($response);
                    exit;
                }
            }

            // Check if email exists in database
            $sql = "SELECT id, username, first_name, last_name, email_verified FROM users WHERE email = ?";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $email);
                
                if (mysqli_stmt_execute($stmt)) {
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if (mysqli_num_rows($result) == 1) {
                        $user = mysqli_fetch_assoc($result);
                        
                        // Check if email is verified
                        if ($user['email_verified'] != 1) {
                            $response = array(
                                "status" => "error",
                                "message" => "Please verify your email address first."
                            );
                        } else {
                            // Generate reset token
                            $token = bin2hex(random_bytes(32));
                            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                            
                            // Store reset token in database
                            $update_sql = "INSERT INTO password_resets (user_id, token, expiry, ip_address) VALUES (?, ?, ?, ?)";
                            
                            if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                                mysqli_stmt_bind_param($update_stmt, "isss", $user["id"], $token, $expiry, $ip_address);
                                
                                if (mysqli_stmt_execute($update_stmt)) {
                                    // Send reset email
                                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/new_password.php?token=" . $token;
                                    $name = $user["first_name"] . " " . $user["last_name"];
                                    
                                    if (sendPasswordResetEmail($email, $name, $reset_link)) {
                                        $response = array(
                                            "status" => "success",
                                            "message" => "Password reset instructions have been sent to your email."
                                        );
                                    } else {
                                        $response = array(
                                            "status" => "error",
                                            "message" => "Error sending reset email. Please try again later."
                                        );
                                    }
                                } else {
                                    $response = array(
                                        "status" => "error",
                                        "message" => "Error processing request. Please try again later."
                                    );
                                }
                                mysqli_stmt_close($update_stmt);
                            }
                        }
                    } else {
                        // For security, don't reveal if email exists or not
                        $response = array(
                            "status" => "success",
                            "message" => "If an account exists with this email, you will receive reset instructions."
                        );
                    }
                } else {
                    $response = array(
                        "status" => "error",
                        "message" => "Error processing request. Please try again later."
                    );
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    echo json_encode($response);
    exit;
} 