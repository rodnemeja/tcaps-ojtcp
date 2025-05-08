<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    exit(json_encode(['error' => 'Not logged in']));
}

$role = $_SESSION["role"];
$user_id = $_SESSION["id"];

// Get appointments based on role
if($role == "patient"){
    // First get the patient's ID
    $sql = "SELECT id FROM patients WHERE user_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $patient = mysqli_fetch_assoc($result);
        if(!$patient) {
            exit(json_encode(['error' => 'Patient record not found']));
        }
        $patient_id = $patient['id'];
    }
    
    $sql = "SELECT a.*, d.specialization, CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
            s.name as service_name, s.duration 
            FROM appointments a 
            JOIN doctors d ON a.doctor_id = d.id 
            JOIN users u ON d.user_id = u.id 
            LEFT JOIN services s ON a.service_id = s.id
            WHERE a.patient_id = ? 
            ORDER BY a.appointment_date, a.appointment_time";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $patient_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }
} elseif($role == "doctor"){
    // First get the doctor's ID
    $sql = "SELECT id FROM doctors WHERE user_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $doctor = mysqli_fetch_assoc($result);
        $doctor_id = $doctor['id'];
    }

    // Then get the appointments using the doctor's ID
    $sql = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as patient_name,
            s.name as service_name, s.duration
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.id 
            JOIN users u ON p.user_id = u.id 
            LEFT JOIN services s ON a.service_id = s.id
            WHERE a.doctor_id = ? 
            ORDER BY a.appointment_date, a.appointment_time";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $doctor_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }
}

$events = array();

if(isset($result) && $result){
    while($row = mysqli_fetch_assoc($result)){
        $start = $row['appointment_date'] . 'T' . $row['appointment_time'];
        
        // Use service duration if available, otherwise default to 60 minutes
        $duration = !empty($row['duration']) ? intval($row['duration']) : 60;
        $end = date('Y-m-d H:i:s', strtotime($start . ' +' . $duration . ' minutes'));
        
        // Get service name or default text
        $serviceInfo = !empty($row['service_name']) ? 
            $row['service_name'] : 
            'Appointment';
        
        $event = array(
            'id' => $row['id'],
            'title' => $serviceInfo,
            'start' => $start,
            'end' => $end,
            'status' => $row['status'],
            'doctor' => $role == "patient" ? $row['doctor_name'] : null,
            'patient' => $role == "doctor" ? $row['patient_name'] : null,
            'allDay' => false
        );
        
        // Set color based on status
        switch($row['status']){
            case 'approved':
                $event['color'] = '#28a745'; // green
                break;
            case 'pending':
                $event['color'] = '#ffc107'; // yellow
                break;
            case 'cancelled':
                $event['color'] = '#dc3545'; // red
                break;
            case 'completed':
                $event['color'] = '#17a2b8'; // blue
                break;
            default:
                $event['color'] = '#6c757d'; // gray
        }
        
        $events[] = $event;
    }
}

// Add debug info
$debug = [
    'total_events' => count($events),
    'user_role' => $role,
    'user_id' => $user_id,
    'query_successful' => isset($result) ? true : false,
    'error' => mysqli_error($conn)
];

$response = [
    'events' => $events,
    'debug' => $debug
];

// Log to server
error_log("Calendar Debug: " . json_encode($debug));

// Only return events in production
echo json_encode($events);
?> 