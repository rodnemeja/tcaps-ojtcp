<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    exit(json_encode(['error' => 'Not logged in']));
}

// Check if required parameters are provided
if(!isset($_POST['date']) || !isset($_POST['time']) || !isset($_POST['doctor_id'])) {
    exit(json_encode(['error' => 'Missing required parameters']));
}

$date = $_POST['date'];
$time = $_POST['time'];
$doctor_id = $_POST['doctor_id'];

// Get service duration for the selected doctor's appointments
$duration_sql = "SELECT s.duration 
                 FROM appointments a 
                 JOIN services s ON a.service_id = s.id 
                 WHERE a.doctor_id = ? 
                 AND a.appointment_date = ? 
                 AND a.appointment_time = ? 
                 AND a.status != 'cancelled'";

if($stmt = mysqli_prepare($conn, $duration_sql)){
    mysqli_stmt_bind_param($stmt, "iss", $doctor_id, $date, $time);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) > 0) {
        // Doctor is not available at this time
        echo json_encode(['available' => false]);
    } else {
        // Check for overlapping appointments
        $overlap_sql = "SELECT COUNT(*) as count 
                       FROM appointments a 
                       WHERE a.doctor_id = ? 
                       AND a.appointment_date = ? 
                       AND a.status != 'cancelled'
                       AND (
                           (a.appointment_time <= ? AND a.end_time > ?) OR
                           (a.appointment_time < DATE_ADD(?, INTERVAL 30 MINUTE) AND a.end_time >= ?)
                       )";
        
        if($stmt = mysqli_prepare($conn, $overlap_sql)){
            mysqli_stmt_bind_param($stmt, "isssss", $doctor_id, $date, $time, $time, $time, $time);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $overlap_data = mysqli_fetch_assoc($result);
            
            echo json_encode(['available' => $overlap_data['count'] == 0]);
        } else {
            echo json_encode(['error' => 'Database error']);
        }
    }
} else {
    echo json_encode(['error' => 'Database error']);
}
?> 