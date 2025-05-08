<?php
require_once "config/database.php";

echo "<h2>Fixing Reports Data</h2>";

// 1. Add sample invoices for the last 30 days
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');

// Get a valid appointment ID
$sql = "SELECT id FROM appointments LIMIT 1";
$result = mysqli_query($conn, $sql);
$appointment = mysqli_fetch_assoc($result);

if (!$appointment) {
    // Create a test appointment if none exists
    $sql = "SELECT id FROM patients LIMIT 1";
    $result = mysqli_query($conn, $sql);
    $patient = mysqli_fetch_assoc($result);
    
    if ($patient) {
        $sql = "INSERT INTO appointments (patient_id, appointment_date, appointment_time, status) 
                VALUES (?, CURDATE(), '10:00:00', 'completed')";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $patient['id']);
        mysqli_stmt_execute($stmt);
        $appointment_id = mysqli_insert_id($conn);
    } else {
        die("Error: No patients found in the database.");
    }
} else {
    $appointment_id = $appointment['id'];
}

// Get services
$sql = "SELECT id, cost FROM services WHERE status = 'active' OR status IS NULL";
$result = mysqli_query($conn, $sql);
$services = [];
while ($row = mysqli_fetch_assoc($result)) {
    $services[] = $row;
}

if (count($services) == 0) {
    die("Error: No active services found in the database.");
}

// Generate invoices for the last 30 days
for ($i = 30; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    
    // Create 1-3 invoices per day
    $invoices_per_day = rand(1, 3);
    for ($j = 0; $j < $invoices_per_day; $j++) {
        // Create invoice
        $payment_status = ['pending', 'partial', 'paid'][rand(0, 2)];
        $payment_method = ['cash', 'card', 'insurance'][rand(0, 2)];
        $invoice_number = 'INV-' . date('Ymd', strtotime($date)) . '-' . sprintf('%03d', rand(1, 999));
        
        $sql = "INSERT INTO invoices (appointment_id, invoice_number, payment_status, payment_method, created_at) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issss", $appointment_id, $invoice_number, $payment_status, $payment_method, $date);
        mysqli_stmt_execute($stmt);
        $invoice_id = mysqli_insert_id($conn);
        
        // Add 1-3 random services to the invoice
        $total_amount = 0;
        $num_services = rand(1, 3);
        $used_services = array_rand($services, $num_services);
        if (!is_array($used_services)) {
            $used_services = [$used_services];
        }
        
        foreach ($used_services as $index) {
            $service = $services[$index];
            $quantity = rand(1, 2);
            $unit_price = $service['cost'];
            $total_price = $quantity * $unit_price;
            $total_amount += $total_price;
            
            $sql = "INSERT INTO invoice_items (invoice_id, service_id, quantity, unit_price, total_price) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iiidd", $invoice_id, $service['id'], $quantity, $unit_price, $total_price);
            mysqli_stmt_execute($stmt);
        }
        
        // Update invoice total
        $sql = "UPDATE invoices SET total_amount = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "di", $total_amount, $invoice_id);
        mysqli_stmt_execute($stmt);
    }
}

echo "Added sample invoices for the last 30 days<br>";

// Verify the data
$sql = "SELECT COUNT(*) as count, payment_status FROM invoices GROUP BY payment_status";
$result = mysqli_query($conn, $sql);
echo "<br>Invoice Status Distribution:<br>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "- " . $row['payment_status'] . ": " . $row['count'] . "<br>";
}

$sql = "SELECT COUNT(*) as count FROM invoice_items";
$result = mysqli_query($conn, $sql);
echo "<br>Total invoice items: " . mysqli_fetch_assoc($result)['count'] . "<br>";

$sql = "SELECT DATE(created_at) as date, SUM(total_amount) as total 
        FROM invoices 
        WHERE payment_status = 'paid' 
        GROUP BY DATE(created_at) 
        ORDER BY date DESC 
        LIMIT 5";
$result = mysqli_query($conn, $sql);
echo "<br>Recent Daily Revenue:<br>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "- " . $row['date'] . ": ₱" . number_format($row['total'], 2) . "<br>";
}

echo "<br>Done! Please check the reports page now.";
?> 