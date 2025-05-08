<?php
// Check session and admin login
session_start();
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Check role
if ((!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') && 
    (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Include database connection
// Try both types of database connection
if (file_exists('../includes/db_connect.php')) {
    include '../includes/db_connect.php';
} elseif (file_exists('../config/database.php')) {
    include '../config/database.php';
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection file not found']);
    exit;
}

// Validate appointment_id
if (!isset($_GET['appointment_id']) || empty($_GET['appointment_id'])) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$appointment_id = intval($_GET['appointment_id']);

// Debug log
error_log("Fetching services for appointment ID: " . $appointment_id);

// Get services for this appointment
$services = [];

// First check if the appointment exists and is completed
$appointment_query = "SELECT * FROM appointments WHERE id = ? AND status = 'completed'";
$stmt = mysqli_prepare($conn, $appointment_query);
mysqli_stmt_bind_param($stmt, "i", $appointment_id);
mysqli_stmt_execute($stmt);
$appointment_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($appointment_result) === 0) {
    // Appointment not found or not completed
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Get the appointment data
$appointment = mysqli_fetch_assoc($appointment_result);

// First try to get services from appointment_services junction table
$query = "SELECT a.service_id, COALESCE(1, 1) as quantity, s.name, s.price 
          FROM appointment_services a
          JOIN services s ON a.service_id = s.id
          WHERE a.appointment_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $appointment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    // Services found in appointment_services table
    while ($row = mysqli_fetch_assoc($result)) {
        $services[] = [
            'service_id' => $row['service_id'],
            'name' => $row['name'],
            'price' => $row['price'],
            'quantity' => $row['quantity']
        ];
    }
    
    // Debug log
    error_log("Found " . count($services) . " services from appointment_services table");
} else {
    // Fallback: Check if there's a service directly in the appointments table
    // This is for backward compatibility if your system previously stored services differently
    if (!empty($appointment['service_id'])) {
        $service_query = "SELECT id as service_id, name, price FROM services WHERE id = ?";
        $stmt = mysqli_prepare($conn, $service_query);
        mysqli_stmt_bind_param($stmt, "i", $appointment['service_id']);
        mysqli_stmt_execute($stmt);
        $service_result = mysqli_stmt_get_result($stmt);
        
        if ($service = mysqli_fetch_assoc($service_result)) {
            $services[] = [
                'service_id' => $service['service_id'],
                'name' => $service['name'],
                'price' => $service['price'],
                'quantity' => 1 // Default quantity
            ];
        }
        
        // Debug log
        error_log("Found " . count($services) . " services from appointments table");
    }
    
    // If still no services found, try looking for services in invoice_items
    if (count($services) == 0) {
        $invoice_query = "SELECT ii.service_id, s.name, s.price, ii.quantity 
                FROM invoice_items ii
                JOIN invoices i ON ii.invoice_id = i.id
                JOIN services s ON ii.service_id = s.id 
                WHERE i.appointment_id = ?";
        
        $stmt = mysqli_prepare($conn, $invoice_query);
        mysqli_stmt_bind_param($stmt, "i", $appointment_id);
        mysqli_stmt_execute($stmt);
        $invoice_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($invoice_result) > 0) {
            while ($row = mysqli_fetch_assoc($invoice_result)) {
                $services[] = [
                    'service_id' => $row['service_id'],
                    'name' => $row['name'],
                    'price' => $row['price'],
                    'quantity' => $row['quantity']
                ];
            }
            
            // Debug log
            error_log("Found " . count($services) . " services from invoice_items table");
        }
    }
}

// Ensure we always return an array even if empty
if (!is_array($services)) {
    $services = [];
}

// Turn off any PHP errors/warnings output
error_reporting(0);
ini_set('display_errors', 0);

// Return the services as JSON
header('Content-Type: application/json');
echo json_encode($services);

// Close database connection
mysqli_close($conn);
?> 