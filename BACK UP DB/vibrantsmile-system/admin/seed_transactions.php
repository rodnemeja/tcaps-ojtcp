<?php
// Start session and check permission
session_start();

// Only admin can access this script
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    echo "Unauthorized access. Only administrators can run this script.";
    exit;
}

// This script creates sample transactions for testing
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/activity_logger.php";

// Create header
echo "<!DOCTYPE html>
<html>
<head>
    <title>Seed Transactions</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='container mt-5'>
    <h2 class='mb-4'>Creating sample transactions...</h2>
    <div class='card'>
        <div class='card-body' style='font-family: monospace;'>";

// Create sample transactions for each available doctor
$sql = "SELECT d.id, d.user_id, CONCAT(u.first_name, ' ', u.last_name) AS doctor_name 
        FROM doctors d 
        JOIN users u ON d.user_id = u.id";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("<div class='alert alert-danger'>Error executing query: " . mysqli_error($conn) . "\nSQL: $sql</div>");
}

// Count how many transactions we'll be creating
$doctor_count = mysqli_num_rows($result);
if ($doctor_count == 0) {
    die("<div class='alert alert-warning'>No doctors found in the system. Cannot create sample transactions.</div>");
}

$transaction_types = [
    'appointment' => 'Appointment scheduling and management',
    'payment' => 'Payment processing', 
    'prescription' => 'Prescription management',
    'medical_record' => 'Medical record updates',
    'patient' => 'Patient registration and updates',
    'invoice' => 'Invoice management'
];

$patient_query = "SELECT p.id, CONCAT(u.first_name, ' ', u.last_name) AS patient_name 
                FROM patients p 
                JOIN users u ON p.user_id = u.id 
                ORDER BY RAND() 
                LIMIT 1";

echo "<div style='white-space: pre-wrap;'>";

$created_count = 0;
$error_count = 0;

while ($doctor = mysqli_fetch_assoc($result)) {
    echo "<strong>Creating transactions for doctor: {$doctor['doctor_name']}</strong>\n";
    
    // Create 5 sample transactions for each doctor
    for ($i = 0; $i < 5; $i++) {
        // Get a random transaction type
        $type = array_rand($transaction_types);
        $description = $transaction_types[$type];
        
        // Get a random patient
        $patient_result = mysqli_query($conn, $patient_query);
        if (!$patient_result) {
            echo "<span style='color: red;'>Error getting random patient: " . mysqli_error($conn) . "</span>\n";
            $error_count++;
            continue;
        }
        
        $patient = mysqli_fetch_assoc($patient_result);
        if (!$patient) {
            echo "<span style='color: orange;'>No patients found in database. Skipping.</span>\n";
            $error_count++;
            continue;
        }
        
        // Generate random amount for payment transactions
        $amount = null;
        if ($type === 'payment' || $type === 'invoice') {
            $amount = rand(500, 5000);
        }
        
        // Create a meaningful transaction description
        $details = '';
        switch($type) {
            case 'appointment':
                $details = "Scheduled an appointment for {$patient['patient_name']} on " . 
                          date('M d, Y', strtotime('+' . rand(1, 30) . ' days')) . " at " . 
                          date('h:i A', strtotime('+' . rand(8, 17) . ' hours'));
                break;
            case 'payment':
                $details = "Processed payment of ₱" . number_format($amount, 2) . 
                          " from {$patient['patient_name']} for dental services";
                break;
            case 'prescription':
                $details = "Created prescription for {$patient['patient_name']} - " . 
                          "Medication: " . ['Amoxicillin', 'Ibuprofen', 'Tylenol', 'Peridex'][rand(0, 3)];
                break;
            case 'medical_record':
                $details = "Updated medical records for {$patient['patient_name']} - " . 
                          "Added notes about " . ['allergy', 'dental history', 'previous treatments', 'medical conditions'][rand(0, 3)];
                break;
            case 'patient':
                $details = "Updated profile information for {$patient['patient_name']} - " . 
                          "Changed " . ['contact details', 'address', 'insurance information', 'emergency contact'][rand(0, 3)];
                break;
            case 'invoice':
                $details = "Created invoice #INV-" . date('Ymd') . "-" . strtoupper(substr(md5(rand()), 0, 4)) . 
                          " for {$patient['patient_name']} - Total: ₱" . number_format($amount, 2);
                break;
        }
        
        // Random date within the last 30 days
        $random_days = rand(0, 30);
        $transaction_date = date('Y-m-d H:i:s', strtotime("-$random_days days"));
        
        // Insert directly into the database
        $sql = "INSERT INTO staff_transactions (staff_id, transaction_type, details, patient_id, amount, transaction_date) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "issids", $doctor['user_id'], $type, $details, $patient['id'], $amount, $transaction_date);
            
            if (mysqli_stmt_execute($stmt)) {
                echo "<span style='color: green;'>✓ Created $type transaction: " . htmlspecialchars($details) . "</span>\n";
                $created_count++;
            } else {
                echo "<span style='color: red;'>✗ Error creating transaction: " . mysqli_error($conn) . "</span>\n";
                $error_count++;
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    echo "\n";
}

echo "</div>";

// Show summary
echo "<div class='alert alert-" . ($error_count > 0 ? "warning" : "success") . " mt-3'>
    <strong>Summary:</strong> $created_count transactions created successfully. $error_count errors encountered.
</div>";

echo "<div class='mt-4 mb-5'>
    <a href='transactions.php' class='btn btn-primary'>View Transactions</a>
    <a href='dashboard.php' class='btn btn-secondary ms-2'>Return to Dashboard</a>
</div>";

echo "</div>
    </div>
</body>
</html>";

mysqli_close($conn);
?> 