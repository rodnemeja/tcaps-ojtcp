<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'vibrant_system');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if(!$conn){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Set charset to utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Function to get database connection
function getConnection() {
    global $conn;
    
    // If connection doesn't exist or is closed, create a new one
    if (!$conn || !mysqli_ping($conn)) {
        $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if (!$conn) {
            die("ERROR: Could not connect. " . mysqli_connect_error());
        }
        mysqli_set_charset($conn, "utf8mb4");
    }
    
    return $conn;
}

// Set timezone
date_default_timezone_set('Asia/Manila');

// Function to sanitize input
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Function to format currency
function format_currency($amount) {
    return '₱' . number_format($amount, 2);
}

// Function to format date
function format_date($date) {
    return date('M d, Y', strtotime($date));
}

// Function to format time
function format_time($time) {
    return date('h:i A', strtotime($time));
}

// Function to get status badge class
function get_status_badge_class($status) {
    switch(strtolower($status)) {
        case 'completed':
            return 'success';
        case 'pending':
            return 'warning';
        case 'cancelled':
            return 'danger';
        case 'active':
            return 'success';
        case 'inactive':
            return 'secondary';
        default:
            return 'primary';
    }
}

// Function to get full name from separate fields
function get_full_name($first_name, $last_name, $middle_name = null) {
    $name_parts = array_filter([$first_name, $middle_name, $last_name]);
    return implode(' ', $name_parts);
}

// Function to split full name into parts
function split_full_name($full_name) {
    $parts = explode(' ', trim($full_name));
    $first_name = array_shift($parts);
    $last_name = array_pop($parts);
    $middle_name = !empty($parts) ? implode(' ', $parts) : null;
    
    return [
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'last_name' => $last_name
    ];
}
?> 