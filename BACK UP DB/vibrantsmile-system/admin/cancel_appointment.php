<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if ID was provided
if(!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    exit;
}

$id = intval($_POST['id']);

// Redirect to update_appointment_status.php for consistency and to utilize email notifications
// This file is kept for backward compatibility
$_POST['status'] = 'cancelled';

// Include the update_appointment_status.php file
require_once "update_appointment_status.php";

// The script will exit in the included file
?> 