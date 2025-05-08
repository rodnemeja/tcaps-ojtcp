<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_picture"])) {
    $user_id = $_POST['user_id'];
    $file = $_FILES["profile_picture"];
    
    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['error_message'] = "Invalid file type. Only JPG, PNG & GIF files are allowed.";
        header("location: staff.php");
        exit;
    }
    
    if ($file['size'] > $max_size) {
        $_SESSION['error_message'] = "File is too large. Maximum size is 5MB.";
        header("location: staff.php");
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = "../uploads/profile_pictures/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $file_extension;
    $target_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Update database
        $sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            $profile_path = "uploads/profile_pictures/" . $filename;
            mysqli_stmt_bind_param($stmt, "si", $profile_path, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "Profile picture updated successfully.";
            } else {
                $_SESSION['error_message'] = "Error updating profile picture in database.";
            }
        }
    } else {
        $_SESSION['error_message'] = "Error uploading file.";
    }
    
    header("location: staff.php");
    exit;
} 