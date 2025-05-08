<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

$error = "";
$success = "";

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $first_name = trim($_POST["first_name"]);
    $middle_name = trim($_POST["middle_name"]);
    $last_name = trim($_POST["last_name"]);
    $phone = trim($_POST["phone"]);
    $password = trim($_POST["password"]);

    // Validate input
    if(empty($username) || empty($email) || empty($first_name) || empty($last_name) || empty($phone)) {
        $error = "Please fill in all required fields.";
    } elseif(!preg_match("/^[a-zA-Z0-9_]{3,20}$/", $username)) {
        $error = "Username should be 3-20 characters long and can only contain letters, numbers, and underscores.";
    } elseif(!preg_match("/^[a-zA-Z\s]{2,50}$/", $first_name) || !preg_match("/^[a-zA-Z\s]{2,50}$/", $last_name)) {
        $error = "First and last name should only contain letters and spaces (2-50 characters).";
    } elseif(!empty($middle_name) && !preg_match("/^[a-zA-Z\s]{2,50}$/", $middle_name)) {
        $error = "Middle name should only contain letters and spaces (2-50 characters).";
    } elseif(!preg_match("/^09[0-9]{9}$/", $phone)) {
        $error = "Phone number should be in Philippine format (09XXXXXXXXX).";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if username exists (excluding current user)
        $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $username, $_SESSION["id"]);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) > 0) {
                $error = "This username is already taken.";
            }
        }

        if(empty($error)) {
            // Update user information
            $sql = "UPDATE users SET username = ?, email = ?, first_name = ?, middle_name = ?, last_name = ?, phone = ? WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssssssi", $username, $email, $first_name, $middle_name, $last_name, $phone, $_SESSION["id"]);
                if(!mysqli_stmt_execute($stmt)) {
                    $error = "Error updating profile: " . mysqli_error($conn);
                }
            }

            // Update password if provided
            if(empty($error) && !empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                if($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "si", $hashed_password, $_SESSION["id"]);
                    if(!mysqli_stmt_execute($stmt)) {
                        $error = "Error updating password: " . mysqli_error($conn);
                    }
                }
            }

            if(empty($error)) {
                $_SESSION['success_message'] = "Profile updated successfully.";
                header("location: profile.php");
                exit;
            }
        }
    }
}

// If there was an error, redirect back to profile page with error message
if(!empty($error)) {
    $_SESSION['error_message'] = $error;
    header("location: profile.php");
    exit;
}
?> 