<?php
require_once "config/database.php";

// Add sample services if none exist
$sql = "SELECT COUNT(*) as count FROM services";
$result = mysqli_query($conn, $sql);
if (mysqli_fetch_assoc($result)['count'] == 0) {
    $services = [
        ['Dental Cleaning', 800.00],
        ['Tooth Extraction', 1000.00],
        ['Root Canal', 15000.00],
        ['Dental Filling', 1500.00],
        ['Teeth Whitening', 5000.00]
    ];
    
    foreach ($services as $service) {
        $sql = "INSERT INTO services (name, cost) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sd", $service[0], $service[1]);
        mysqli_stmt_execute($stmt);
    }
    echo "Added sample services<br>";
}

// Add sample invoices and invoice items for the last 30 days
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');

// Get service IDs
$sql = "SELECT id, cost FROM services";
$result = mysqli_query($conn, $sql);
$services = [];
while ($row = mysqli_fetch_assoc($result)) {
    $services[] = $row;
}

// Get a sample appointment ID
$sql = "SELECT id FROM appointments LIMIT 1";
$result = mysqli_query($conn, $sql);
$appointment_id = mysqli_fetch_assoc($result)['id'];

if ($appointment_id) {
    // Add sample invoices
    for ($i = 30; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        
        // Create 1-3 invoices per day
        $invoices_per_day = rand(1, 3);
        for ($j = 0; $j < $invoices_per_day; $j++) {
            // Random payment status
            $status = ['pending', 'partial', 'paid'][rand(0, 2)];
            
            // Insert invoice
            $sql = "INSERT INTO invoices (appointment_id, payment_status, created_at) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iss", $appointment_id, $status, $date);
            mysqli_stmt_execute($stmt);
            
            $invoice_id = mysqli_insert_id($conn);
            
            // Add 1-3 services to the invoice
            $total_amount = 0;
            $num_services = rand(1, 3);
            $used_services = array_rand($services, $num_services);
            if (!is_array($used_services)) {
                $used_services = [$used_services];
            }
            
            foreach ($used_services as $service_index) {
                $service = $services[$service_index];
                $quantity = rand(1, 2);
                $unit_price = $service['cost'];
                $total_price = $quantity * $unit_price;
                $total_amount += $total_price;
                
                $sql = "INSERT INTO invoice_items (invoice_id, service_id, quantity, unit_price, total_price) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iidd", $invoice_id, $service['id'], $quantity, $unit_price, $total_price);
                mysqli_stmt_execute($stmt);
            }
            
            // Update invoice total
            $sql = "UPDATE invoices SET total_amount = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "di", $total_amount, $invoice_id);
            mysqli_stmt_execute($stmt);
        }
    }
    echo "Added sample invoices and invoice items for the last 30 days<br>";
} else {
    echo "Error: No appointments found. Please create at least one appointment first.<br>";
}

echo "Done adding sample data. You can now check the reports.";
?> 