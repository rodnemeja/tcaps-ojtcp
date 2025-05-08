<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'appointment' => null
];

// Debug session data
error_log('SESSION: ' . print_r($_SESSION, true));

// Make sure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $response['message'] = 'You must be logged in to view appointment details.';
    echo json_encode($response);
    exit;
}

// Get appointment ID
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$appointment_id) {
    $response['message'] = 'Invalid appointment ID.';
    echo json_encode($response);
    exit;
}

// Connect to database
require_once 'config/database.php';
$conn = getConnection();

// Get user info
$role = $_SESSION['role'];
$user_id = $_SESSION['id'];

// Debug info
error_log("Role: $role, User ID: $user_id, Appointment ID: $appointment_id");

// Construct query based on role
if ($role === 'patient') {
    // Get the patient ID first
    $patient_query = "SELECT id FROM patients WHERE user_id = ?";
    $patient_stmt = mysqli_prepare($conn, $patient_query);
    mysqli_stmt_bind_param($patient_stmt, "i", $user_id);
    mysqli_stmt_execute($patient_stmt);
    $patient_result = mysqli_stmt_get_result($patient_stmt);
    
    if ($patient_row = mysqli_fetch_assoc($patient_result)) {
        $patient_id = $patient_row['id'];
        
        // Now use that patient ID to query appointments
        $sql = "SELECT a.*, 
                s.name AS service_name, 
                s.duration, 
                s.duration_format,
                CONCAT(du.first_name, ' ', du.last_name) AS doctor_name, 
                CONCAT(pu.first_name, ' ', pu.last_name) AS patient_name,
                pu.email AS patient_email
                FROM appointments a
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN doctors d ON a.doctor_id = d.id
                LEFT JOIN users du ON d.user_id = du.id
                LEFT JOIN patients p ON a.patient_id = p.id
                LEFT JOIN users pu ON p.user_id = pu.id
                WHERE a.id = ? AND a.patient_id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $patient_id);
    } else {
        $response['message'] = 'Could not find patient record.';
        echo json_encode($response);
        exit;
    }
} else if ($role === 'doctor') {
    // Get the doctor ID first
    $doctor_query = "SELECT id FROM doctors WHERE user_id = ?";
    $doctor_stmt = mysqli_prepare($conn, $doctor_query);
    mysqli_stmt_bind_param($doctor_stmt, "i", $user_id);
    mysqli_stmt_execute($doctor_stmt);
    $doctor_result = mysqli_stmt_get_result($doctor_stmt);
    
    if ($doctor_row = mysqli_fetch_assoc($doctor_result)) {
        $doctor_id = $doctor_row['id'];
        
        // Now use that doctor ID to query appointments
        $sql = "SELECT a.*, 
                s.name AS service_name, 
                s.duration, 
                s.duration_format,
                CONCAT(du.first_name, ' ', du.last_name) AS doctor_name, 
                CONCAT(pu.first_name, ' ', pu.last_name) AS patient_name,
                pu.email AS patient_email
                FROM appointments a
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN doctors d ON a.doctor_id = d.id
                LEFT JOIN users du ON d.user_id = du.id
                LEFT JOIN patients p ON a.patient_id = p.id
                LEFT JOIN users pu ON p.user_id = pu.id
                WHERE a.id = ? AND a.doctor_id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $doctor_id);
    } else {
        $response['message'] = 'Could not find doctor record.';
        echo json_encode($response);
        exit;
    }
} else {
    // Admins can view all appointments
    $sql = "SELECT a.*, 
            s.name AS service_name, 
            s.duration, 
            s.duration_format,
            CONCAT(du.first_name, ' ', du.last_name) AS doctor_name, 
            CONCAT(pu.first_name, ' ', pu.last_name) AS patient_name,
            pu.email AS patient_email
            FROM appointments a
            LEFT JOIN services s ON a.service_id = s.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN users du ON d.user_id = du.id
            LEFT JOIN patients p ON a.patient_id = p.id
            LEFT JOIN users pu ON p.user_id = pu.id
            WHERE a.id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
}

// Execute query and get results
if (!mysqli_stmt_execute($stmt)) {
    error_log("SQL Error: " . mysqli_stmt_error($stmt));
    $response['message'] = 'Database query error.';
    echo json_encode($response);
    exit;
}

$result = mysqli_stmt_get_result($stmt);

// Check if we got results
if ($row = mysqli_fetch_assoc($result)) {
    // Format date and time for display
    $row['appointment_date'] = date('F j, Y', strtotime($row['appointment_date']));
    $row['appointment_time'] = date('g:i A', strtotime($row['appointment_time']));
    
    // Format duration if not already formatted
    if (!isset($row['duration_format']) && isset($row['duration'])) {
        $row['duration_format'] = $row['duration'] . ' minutes';
    }
    
    $response['success'] = true;
    $response['appointment'] = $row;
    error_log("Appointment found: " . print_r($row, true));
} else {
    error_log("No appointment found for ID: $appointment_id");
    $response['message'] = 'Appointment not found or you do not have permission to view it.';
}

// Close database connection
mysqli_close($conn);

// Set content type header
header('Content-Type: application/json');

// Return JSON response
echo json_encode($response);
?> 