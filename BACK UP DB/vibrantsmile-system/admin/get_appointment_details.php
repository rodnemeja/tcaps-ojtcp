<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if ID parameter is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    exit;
}

$id = intval($_GET['id']);

// Get appointment details with patient, doctor and service information
$sql = "SELECT 
    a.*, 
    p.id as patient_id,
    p.family_code,
    (SELECT name FROM family_codes WHERE code = p.family_code) as family_name,
    (SELECT COUNT(*) FROM patients WHERE family_code = p.family_code) as family_members_count,
    u.first_name,
    u.middle_name,
    u.last_name,
    u.email as patient_email,
    u.phone as patient_phone,
    CONCAT(du.first_name, ' ', du.last_name) as doctor_name,
    d.specialization as doctor_specialization,
    s.name as service_name,
    s.duration as service_duration,
    s.price as service_price,
    a.status as appointment_status,
    a.notes
FROM appointments a
LEFT JOIN patients p ON a.patient_id = p.id
LEFT JOIN users u ON p.user_id = u.id
LEFT JOIN doctors d ON a.doctor_id = d.id
LEFT JOIN users du ON d.user_id = du.id
LEFT JOIN services s ON a.service_id = s.id
WHERE a.id = ?";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        if($appointment = mysqli_fetch_assoc($result)) {
            // Return appointment details as JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'appointment' => $appointment
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Appointment not found'
            ]);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . mysqli_error($conn)
        ]);
    }
    
    mysqli_stmt_close($stmt);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare statement: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?> 