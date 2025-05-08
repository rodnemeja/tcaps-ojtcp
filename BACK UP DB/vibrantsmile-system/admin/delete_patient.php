<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

$success = false;
$error = '';

// Check if ID is provided
if(isset($_GET["id"]) && is_numeric($_GET["id"])) {
    $id = $_GET["id"];
    
    // Get user_id for this patient
    $sql = "SELECT user_id FROM patients WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)) {
            $user_id = $row['user_id'];
            
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Soft delete patient
                $sql = "UPDATE patients SET status = 'inactive' WHERE id = ?";
                if($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "i", $id);
                    mysqli_stmt_execute($stmt);
                }
                
                // Soft delete user
                $sql = "UPDATE users SET status = 'inactive' WHERE id = ?";
                if($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);
                }
                
                // Commit transaction
                mysqli_commit($conn);
                $success = true;
                
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($conn);
                $error = "Error deleting patient: " . $e->getMessage();
                error_log($error);
            }
        } else {
            $error = "Patient not found.";
        }
    } else {
        $error = "Database error: " . mysqli_error($conn);
    }
} else {
    $error = "Invalid patient ID.";
}

// Set session message
if($success) {
    $_SESSION['success_message'] = "Patient deleted successfully.";
} else {
    $_SESSION['error_message'] = $error;
}

// Redirect back to patients page
header("location: patients.php");
exit; 