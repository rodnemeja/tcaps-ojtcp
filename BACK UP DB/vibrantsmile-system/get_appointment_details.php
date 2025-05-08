<?php
// Set headers for JSON response
header('Content-Type: application/json');
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'appointment' => null
];

// Debug session information
error_log("Session data: " . print_r($_SESSION, true));

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    $response['message'] = 'You must be logged in to view appointment details.';
    echo json_encode($response);
    exit;
}

// Get appointment ID from request
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$appointment_id) {
    $response['message'] = 'Invalid appointment ID.';
    echo json_encode($response);
    exit;
}

// Connect to database
require_once 'config/database.php';
$conn = getConnection();

// Get user role from session
$role = $_SESSION['role'];
$user_id = $_SESSION['id'];

// Construct SQL query based on user role
if ($role === 'patient') {
    // Patients can only view their own appointments
    $sql = "SELECT a.*, 
            s.name AS service_name, 
            s.duration, 
            CONCAT(d.first_name, ' ', d.last_name) AS doctor_name, 
            CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
            p.email AS patient_email
            FROM appointments a
            LEFT JOIN services s ON a.service_id = s.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN patients p ON a.patient_id = p.id
            WHERE a.id = ? AND a.patient_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $user_id);
} else if ($role === 'doctor') {
    // Doctors can only view appointments assigned to them
    $sql = "SELECT a.*, 
            s.name AS service_name, 
            s.duration, 
            CONCAT(d.first_name, ' ', d.last_name) AS doctor_name, 
            CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
            p.email AS patient_email
            FROM appointments a
            LEFT JOIN services s ON a.service_id = s.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN patients p ON a.patient_id = p.id
            WHERE a.id = ? AND a.doctor_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $user_id);
} else {
    // Admins can view all appointments
    $sql = "SELECT a.*, 
            s.name AS service_name, 
            s.duration, 
            CONCAT(d.first_name, ' ', d.last_name) AS doctor_name, 
            CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
            p.email AS patient_email
            FROM appointments a
            LEFT JOIN services s ON a.service_id = s.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN patients p ON a.patient_id = p.id
            WHERE a.id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
}

// Execute query
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    // Format date and time for display
    $row['appointment_date'] = date('F j, Y', strtotime($row['appointment_date']));
    $row['appointment_time'] = date('g:i A', strtotime($row['appointment_time']));
    
    // Format duration
    if (isset($row['duration']) && $row['duration']) {
        $row['duration_format'] = $row['duration'] . ' minutes';
    }
    
    $response['success'] = true;
    $response['appointment'] = $row;
} else {
    $response['message'] = 'Appointment not found or you do not have permission to view it.';
}

// Close database connection
mysqli_close($conn);

// Return JSON response
echo json_encode($response);
?> 