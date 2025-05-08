<?php
// Set error handling to not display errors directly
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Function to handle errors and return JSON
function handleError($message, $error = null) {
    error_log("Error in update_appointment_status.php: " . $message);
    if ($error !== null) {
        error_log("Error details: " . $error);
    }
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => $message];
    if ($error !== null && !is_null($error)) {
        $response['error_details'] = $error;
    }
    echo json_encode($response);
    exit;
}

// Set error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $error = "Error [$errno]: $errstr in $errfile on line $errline";
    error_log($error);
    handleError("A server error occurred. Please check logs.", $error);
});

try {
    error_log("Starting update_appointment_status.php");
    
    require_once "../config/init.php";
    require_once "../config/database.php";
    
    error_log("Required files loaded");

    // Check if user is logged in and is admin
    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
        error_log("Unauthorized access attempt");
        handleError('Unauthorized access');
    }

    // Check if required parameters are present
    if(!isset($_POST['id']) || !isset($_POST['status'])) {
        error_log("Missing parameters - POST data: " . print_r($_POST, true));
        handleError('Missing required parameters');
    }

    $id = $_POST['id'];
    $status = $_POST['status'];
    
    error_log("Processing update for appointment ID: $id, status: $status");

    // Validate status
    $allowed_statuses = ['pending', 'scheduled', 'approved', 'completed', 'cancelled'];
    if(!in_array($status, $allowed_statuses)) {
        error_log("Invalid status: $status");
        handleError('Invalid status');
    }

    // Update appointment status
    $sql = "UPDATE appointments SET status = ? WHERE id = ?";
    error_log("Preparing SQL: $sql");
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $status, $id);
        
        if(mysqli_stmt_execute($stmt)) {
            error_log("Appointment status updated successfully");
            // Return success response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Appointment status updated successfully.'
            ]);
        } else {
            $error = mysqli_error($conn);
            error_log("Database error: $error");
            handleError('Database error: ' . $error);
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $error = mysqli_error($conn);
        error_log("Failed to prepare statement: $error");
        handleError('Failed to prepare statement: ' . $error);
    }

    mysqli_close($conn);
} catch (Exception $e) {
    error_log("Exception in update_appointment_status.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    handleError('Server error: ' . $e->getMessage());
}
?> 