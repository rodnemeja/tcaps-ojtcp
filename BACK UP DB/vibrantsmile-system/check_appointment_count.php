<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    exit(json_encode(['error' => 'Not logged in']));
}

// Check if date is provided
if(!isset($_POST['date'])) {
    exit(json_encode(['error' => 'Date not provided']));
}

$date = $_POST['date'];
$user_id = $_SESSION['id'];

// Get patient ID
$patient_sql = "SELECT id FROM patients WHERE user_id = ?";
if($stmt = mysqli_prepare($conn, $patient_sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $patient = mysqli_fetch_assoc($result);
}

if(!$patient) {
    exit(json_encode(['error' => 'Patient not found']));
}

// Count appointments for the patient on the given date
$count_sql = "SELECT COUNT(*) as count FROM appointments 
              WHERE patient_id = ? 
              AND appointment_date = ? 
              AND status != 'cancelled'";

if($stmt = mysqli_prepare($conn, $count_sql)){
    mysqli_stmt_bind_param($stmt, "is", $patient['id'], $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $count_data = mysqli_fetch_assoc($result);
    
    echo json_encode(['count' => $count_data['count']]);
} else {
    echo json_encode(['error' => 'Database error']);
}
?> 