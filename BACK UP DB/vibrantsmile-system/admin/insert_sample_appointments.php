<?php
require_once "../config/init.php";
require_once "../config/database.php";

// First, let's get the patient ID
$sql = "SELECT p.id as patient_id FROM patients p 
        JOIN users u ON p.user_id = u.id 
        WHERE u.full_name = 'John Do'";
$result = mysqli_query($conn, $sql);
$patient = mysqli_fetch_assoc($result);
$patient_id = $patient['patient_id'];

// Sample appointments data
$appointments = [
    [
        'date' => date('Y-m-d', strtotime('+1 day')),
        'time' => '09:00:00',
        'service_id' => 1, // Dental Check-up
        'status' => 'scheduled',
        'notes' => 'Regular check-up appointment'
    ],
    [
        'date' => date('Y-m-d', strtotime('+2 days')),
        'time' => '14:30:00',
        'service_id' => 2, // Teeth Whitening
        'status' => 'scheduled',
        'notes' => 'Teeth whitening treatment'
    ],
    [
        'date' => date('Y-m-d', strtotime('+5 days')),
        'time' => '11:00:00',
        'service_id' => 6, // Tooth Extraction
        'status' => 'scheduled',
        'notes' => 'Tooth extraction procedure'
    ],
    [
        'date' => date('Y-m-d', strtotime('+1 week')),
        'time' => '10:00:00',
        'service_id' => 8, // Orthodontic Consultation
        'status' => 'scheduled',
        'notes' => 'Initial orthodontic consultation'
    ],
    [
        'date' => date('Y-m-d', strtotime('+2 weeks')),
        'time' => '15:00:00',
        'service_id' => 5, // Dental Crown
        'status' => 'scheduled',
        'notes' => 'Crown placement procedure'
    ]
];

// Insert appointments
foreach ($appointments as $appointment) {
    // Insert into appointments table
    $sql = "INSERT INTO appointments (patient_id, appointment_date, appointment_time, status, notes) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "issss", 
        $patient_id,
        $appointment['date'],
        $appointment['time'],
        $appointment['status'],
        $appointment['notes']
    );
    mysqli_stmt_execute($stmt);
    
    // Get the appointment ID
    $appointment_id = mysqli_insert_id($conn);
    
    // Insert into appointment_services table
    $sql = "INSERT INTO appointment_services (appointment_id, service_id) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $appointment['service_id']);
    mysqli_stmt_execute($stmt);
}

echo "Sample appointments have been inserted successfully!";
?> 