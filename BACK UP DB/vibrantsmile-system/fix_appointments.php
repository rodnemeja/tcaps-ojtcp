<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fixing Appointment System</h1>";

// Step 1: Check if vendor directory exists and is properly set up
echo "<h2>Step 1: Checking Composer Installation</h2>";
if (!file_exists('vendor/autoload.php')) {
    echo "<p>Vendor directory is missing or incomplete. Attempting to install PHPMailer via Composer...</p>";
    
    // Try to install PHPMailer via Composer
    $output = [];
    $return_var = 0;
    exec('composer install', $output, $return_var);
    
    if ($return_var !== 0) {
        echo "<p style='color:red'>Failed to run Composer. Please run 'composer install' manually.</p>";
        echo "<p>Output: " . implode("<br>", $output) . "</p>";
    } else {
        echo "<p style='color:green'>Composer installation successful!</p>";
    }
} else {
    echo "<p style='color:green'>Vendor directory exists and autoload.php is present.</p>";
}

// Step 2: Verify database connection and check appointments table
echo "<h2>Step 2: Verifying Database Connection</h2>";
require_once "config/database.php";

if (!$conn) {
    echo "<p style='color:red'>Database connection failed: " . mysqli_connect_error() . "</p>";
    exit;
}

echo "<p style='color:green'>Database connection successful!</p>";

// Step 3: Check the appointments table structure
echo "<h2>Step 3: Checking Appointments Table Structure</h2>";
$table_result = mysqli_query($conn, "SHOW TABLES LIKE 'appointments'");

if (mysqli_num_rows($table_result) === 0) {
    echo "<p style='color:red'>Appointments table does not exist!</p>";
    exit;
}

echo "<p style='color:green'>Appointments table exists!</p>";

// Check if the 'status' field has all required enum values
$field_result = mysqli_query($conn, "SHOW COLUMNS FROM appointments LIKE 'status'");
$field_info = mysqli_fetch_assoc($field_result);

if (!$field_info) {
    echo "<p style='color:red'>Status field does not exist in appointments table!</p>";
    exit;
}

$type = $field_info['Type'];
echo "<p>Current status field type: $type</p>";

// Check if 'completed' and 'cancelled' statuses are in the enum
if (strpos($type, "'completed'") === false || strpos($type, "'cancelled'") === false) {
    echo "<p style='color:orange'>Status field is missing some required values. Attempting to fix...</p>";
    
    $alter_query = "ALTER TABLE appointments MODIFY COLUMN status ENUM('pending', 'scheduled', 'approved', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'";
    if (mysqli_query($conn, $alter_query)) {
        echo "<p style='color:green'>Successfully updated status field in appointments table!</p>";
    } else {
        echo "<p style='color:red'>Failed to update status field: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color:green'>Status field has all required values.</p>";
}

// Step 4: Update mail.php file if it hasn't been fixed already
echo "<h2>Step 4: Checking mail.php File</h2>";
$mail_file = "config/mail.php";
$mail_content = file_get_contents($mail_file);

if (strpos($mail_content, 'require \'vendor/autoload.php\';') !== false && 
    strpos($mail_content, '$phpMailerAvailable') === false) {
    echo "<p style='color:orange'>mail.php file needs to be updated to handle missing dependencies gracefully.</p>";
    echo "<p>Please update the file manually using the fix provided by the assistant.</p>";
} else {
    echo "<p style='color:green'>mail.php file has already been updated to handle missing dependencies.</p>";
}

// Step 5: Check the update_appointment_status.php file
echo "<h2>Step 5: Checking update_appointment_status.php File</h2>";
$status_file = "admin/update_appointment_status.php";

if (file_exists($status_file)) {
    echo "<p style='color:green'>update_appointment_status.php file exists.</p>";
    
    $status_content = file_get_contents($status_file);
    if (strpos($status_content, '$mailAvailable') === false) {
        echo "<p style='color:orange'>update_appointment_status.php file may need to be updated to handle missing mail dependencies.</p>";
        echo "<p>Please update the file manually using the fix provided by the assistant.</p>";
    } else {
        echo "<p style='color:green'>update_appointment_status.php file has already been updated to handle missing mail dependencies.</p>";
    }
} else {
    echo "<p style='color:red'>update_appointment_status.php file does not exist!</p>";
}

// Step 6: Test appointment status update
echo "<h2>Step 6: Testing Appointment Status Update</h2>";

// Find a valid appointment ID for testing
$appointment_query = "SELECT id FROM appointments LIMIT 1";
$appointment_result = mysqli_query($conn, $appointment_query);

if (mysqli_num_rows($appointment_result) > 0) {
    $appointment = mysqli_fetch_assoc($appointment_result);
    $test_id = $appointment['id'];
    
    echo "<p>Found appointment ID $test_id for testing.</p>";
    
    // Test update in the database directly
    $test_update = "UPDATE appointments SET status = 'completed' WHERE id = $test_id";
    if (mysqli_query($conn, $test_update)) {
        echo "<p style='color:green'>Successfully updated appointment status in database!</p>";
        
        // Reset it back to pending for future tests
        mysqli_query($conn, "UPDATE appointments SET status = 'pending' WHERE id = $test_id");
        echo "<p>Reset appointment back to 'pending' status for future tests.</p>";
    } else {
        echo "<p style='color:red'>Failed to update appointment status: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color:orange'>No appointments found for testing.</p>";
}

// Step 7: Provide verification steps for the user
echo "<h2>Next Steps</h2>";
echo "<p>Please follow these steps to verify the fix:</p>";
echo "<ol>";
echo "<li>Run <code>composer install</code> if the automatic installation failed.</li>";
echo "<li>Navigate to the Admin dashboard and try updating an appointment status.</li>";
echo "<li>Check if the appointment status updates successfully, even if email sending fails.</li>";
echo "<li>If you need email functionality, ensure your PHPMailer is properly installed and configured.</li>";
echo "</ol>";

echo "<p><strong>Fix Summary:</strong> This script has verified your database structure, appointment statuses, and provided guidance for fixing email-related issues. The system should now be able to update appointment statuses even if email sending fails.</p>";

mysqli_close($conn);
?> 