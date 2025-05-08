<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Verify request method and required parameters
if ($_SERVER["REQUEST_METHOD"] === "POST" && 
    isset($_POST['doctor_id']) && 
    isset($_POST['appointment_date']) && 
    isset($_POST['appointment_time']) &&
    isset($_POST['service_id'])) {
    
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $service_id = $_POST['service_id'];
    $appointment_id = !empty($_POST['appointment_id']) ? $_POST['appointment_id'] : null;
    
    // Get service duration
    $duration_sql = "SELECT duration FROM services WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $duration_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $service_id);
        mysqli_stmt_execute($stmt);
        $duration_result = mysqli_stmt_get_result($stmt);
        if($duration_row = mysqli_fetch_assoc($duration_result)) {
            // Calculate end time
            $duration = intval($duration_row['duration']); // Convert to integer minutes
            $end_time = date('H:i:s', strtotime($appointment_time . ' +' . $duration . ' minutes'));
            
            // Check for appointment conflicts
            $sql = "SELECT COUNT(*) as count FROM appointments 
                    WHERE doctor_id = ? 
                    AND appointment_date = ? 
                    AND status != 'cancelled' 
                    AND (
                        (appointment_time <= ? AND end_time > ?) OR
                        (appointment_time < ? AND end_time >= ?) OR
                        (appointment_time >= ? AND appointment_time < ?)
                    )";
            
            $params = [$doctor_id, $appointment_date, $appointment_time, $appointment_time, $end_time, $end_time, $appointment_time, $end_time];
            $types = "isssssss";
            
            // If editing an existing appointment, exclude the current appointment
            if($appointment_id) {
                $sql .= " AND id != ?";
                $params[] = $appointment_id;
                $types .= "i";
            }
            
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                
                // Return JSON response
                header('Content-Type: application/json');
                echo json_encode(['conflict' => $row['count'] > 0]);
                exit;
            }
        }
    }
    
    // If we get here, something went wrong
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to check appointment availability']);
    exit;
} else {
    // Invalid request
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request']);
    exit;
}
?> 