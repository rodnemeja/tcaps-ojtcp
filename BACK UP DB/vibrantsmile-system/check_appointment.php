<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Not logged in']));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doctor_id = $_POST['doctor_id'];
    $service_id = $_POST['service_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $exclude_appointment_id = isset($_POST['exclude_appointment_id']) ? $_POST['exclude_appointment_id'] : null;

    // Get service duration
    $service_sql = "SELECT duration FROM services WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $service_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $service_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $service = mysqli_fetch_assoc($result);
        
        // Calculate end time
        $end_time = date('H:i:s', strtotime($appointment_time . ' +' . $service['duration'] . ' minutes'));

        // Check for overlapping appointments
        $sql = "SELECT COUNT(*) as count FROM appointments 
                WHERE doctor_id = ? 
                AND appointment_date = ? 
                AND status != 'cancelled'";
        
        // Add condition to exclude current appointment if rescheduling
        if ($exclude_appointment_id) {
            $sql .= " AND id != ?";
        }
        
        $sql .= " AND (
                    appointment_time = ? OR
                    (appointment_time <= ? AND end_time > ?) OR
                    (appointment_time >= ? AND appointment_time < ?)
                )";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            if ($exclude_appointment_id) {
                mysqli_stmt_bind_param($stmt, "issssss", 
                    $doctor_id, 
                    $appointment_date,
                    $exclude_appointment_id,
                    $appointment_time,
                    $appointment_time,
                    $appointment_time,
                    $appointment_time,
                    $end_time
                );
            } else {
                mysqli_stmt_bind_param($stmt, "isssss", 
                    $doctor_id, 
                    $appointment_date, 
                    $appointment_time,
                    $appointment_time,
                    $appointment_time,
                    $appointment_time,
                    $end_time
                );
            }
            
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                
                header('Content-Type: application/json');
                echo json_encode(['exists' => $row['count'] > 0]);
                exit;
            } else {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
                exit;
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Query preparation failed: ' . mysqli_error($conn)]);
            exit;
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Service not found']);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}
?> 