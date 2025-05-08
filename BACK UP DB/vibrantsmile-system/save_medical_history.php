<?php
session_start();
require_once "config/database.php";

// Set header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode([
        "status" => "error",
        "message" => "Please log in to continue."
    ]);
    exit;
}

// Get user ID
$user_id = $_SESSION["id"];

// Sanitize and validate input data
$has_allergies = isset($_POST['has_allergies']) ? (int)$_POST['has_allergies'] : 0;
$allergies_details = $has_allergies ? mysqli_real_escape_string($conn, $_POST['allergies_details']) : '';

$has_medications = isset($_POST['has_medications']) ? (int)$_POST['has_medications'] : 0;
$medications_details = $has_medications ? mysqli_real_escape_string($conn, $_POST['medications_details']) : '';

$medical_conditions = isset($_POST['medical_conditions']) ? implode(',', $_POST['medical_conditions']) : '';
$other_conditions_details = isset($_POST['other_conditions_details']) ? mysqli_real_escape_string($conn, $_POST['other_conditions_details']) : '';

$additional_notes = isset($_POST['additional_notes']) ? mysqli_real_escape_string($conn, $_POST['additional_notes']) : '';

// Check if medical history already exists
$check_sql = "SELECT id FROM medical_history WHERE patient_id = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "i", $user_id);
mysqli_stmt_execute($check_stmt);
$result = mysqli_stmt_get_result($check_stmt);

if(mysqli_num_rows($result) > 0) {
    // Update existing record
    $sql = "UPDATE medical_history SET 
            has_allergies = ?,
            allergies_details = ?,
            has_medications = ?,
            medications_details = ?,
            medical_conditions = ?,
            other_conditions_details = ?,
            additional_notes = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE patient_id = ?";
} else {
    // Insert new record
    $sql = "INSERT INTO medical_history 
            (patient_id, has_allergies, allergies_details, has_medications, medications_details, 
             medical_conditions, other_conditions_details, additional_notes, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
}

$stmt = mysqli_prepare($conn, $sql);

if(mysqli_num_rows($result) > 0) {
    mysqli_stmt_bind_param($stmt, "isisssss", 
        $has_allergies,
        $allergies_details,
        $has_medications,
        $medications_details,
        $medical_conditions,
        $other_conditions_details,
        $additional_notes,
        $user_id
    );
} else {
    mysqli_stmt_bind_param($stmt, "isissssss", 
        $user_id,
        $has_allergies,
        $allergies_details,
        $has_medications,
        $medications_details,
        $medical_conditions,
        $other_conditions_details,
        $additional_notes
    );
}

if(mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "status" => "success",
        "message" => "Medical history saved successfully."
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Error saving medical history: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?> 