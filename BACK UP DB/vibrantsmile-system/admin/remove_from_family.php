<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Check if patient ID and family code were provided
if(!isset($_GET['id']) || !isset($_GET['code'])) {
    $_SESSION['error_message'] = "Missing information to remove patient from family";
    header("location: family_profiles.php");
    exit;
}

$patient_id = $_GET['id'];
$family_code = $_GET['code'];

// Verify the patient exists and belongs to the family
$sql = "SELECT * FROM patients WHERE id = ? AND family_code = ?";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "is", $patient_id, $family_code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) === 0) {
        $_SESSION['error_message'] = "Patient does not exist or does not belong to this family";
        header("location: view_family.php?code=" . $family_code);
        exit;
    }
    
    $patient = mysqli_fetch_assoc($result);
}

// Update the patient to remove family code and role
$sql = "UPDATE patients SET family_code = NULL, family_role = NULL WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Patient successfully removed from family";
    } else {
        $_SESSION['error_message'] = "Error removing patient from family: " . mysqli_error($conn);
    }
}

// Redirect back to the family view
header("location: view_family.php?code=" . $family_code);
exit;
?> 