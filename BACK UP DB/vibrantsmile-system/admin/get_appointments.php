<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Check if patient_id is provided
if(!isset($_GET['patient_id']) || !is_numeric($_GET['patient_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid patient ID']);
    exit;
}

$patient_id = $_GET['patient_id'];

// Get all patient's appointments without filtering by status
$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status,
        CASE WHEN i.id IS NOT NULL THEN TRUE ELSE FALSE END as has_invoice
        FROM appointments a
        LEFT JOIN invoices i ON a.id = i.appointment_id
        WHERE a.patient_id = ? AND a.status = 'completed'
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

// Debug
error_log("Fetching completed appointments for patient ID: " . $patient_id);

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $appointments = array();
    
    // Debug
    $count = mysqli_num_rows($result);
    error_log("Found " . $count . " completed appointments for patient ID: " . $patient_id);
    
    while($row = mysqli_fetch_assoc($result)) {
        // Format the date and time for display
        $date = date('M d, Y', strtotime($row['appointment_date']));
        $time = date('h:i A', strtotime($row['appointment_time']));
        
        $status = ucfirst($row['status']);
        
        // Add note for appointments that already have invoices
        if ($row['has_invoice']) {
            $status .= ' (Has Invoice)';
        }
        
        $appointments[] = array(
            'id' => $row['id'],
            'appointment_date' => $date,
            'appointment_time' => $time,
            'status' => $status,
            'has_invoice' => $row['has_invoice']
        );
    }
    
    header('Content-Type: application/json');
    echo json_encode($appointments);
    
    mysqli_stmt_close($stmt);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
}
?> 