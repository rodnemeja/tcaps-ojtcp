<?php
/**
 * Common functions for the Dental Clinic Management System
 */

/**
 * Format a date to a readable format
 * 
 * @param string $date Date in Y-m-d format
 * @return string Formatted date
 */
function formatDate($date) {
    if (!$date) return '';
    return date('F j, Y', strtotime($date));
}

/**
 * Format a time to a readable format
 * 
 * @param string $time Time in H:i:s format
 * @return string Formatted time
 */
function formatTime($time) {
    if (!$time) return '';
    return date('g:i A', strtotime($time));
}

/**
 * Calculate age from date of birth
 * 
 * @param string $dob Date of birth in Y-m-d format
 * @return int Age in years
 */
function calculateAge($dob) {
    if (!$dob) return 0;
    $today = new DateTime();
    $birthdate = new DateTime($dob);
    $interval = $today->diff($birthdate);
    return $interval->y;
}

/**
 * Format a phone number
 * 
 * @param string $phone Phone number
 * @return string Formatted phone number
 */
function formatPhone($phone) {
    if (!$phone) return '';
    
    // Remove any non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Format based on length
    if (strlen($phone) == 10) {
        return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $phone);
    } elseif (strlen($phone) == 11 && substr($phone, 0, 1) == '1') {
        return preg_replace('/1(\d{3})(\d{3})(\d{4})/', '+1 ($1) $2-$3', $phone);
    }
    
    return $phone;
}

/**
 * Get a list of family relationships
 * 
 * @param int $patient_id Patient ID
 * @param object $conn Database connection
 * @return array Array of family relationships
 */
function getFamilyRelationships($patient_id, $conn) {
    $family_members = [];
    $sql = "SELECT fr.*, u.first_name, u.middle_name, u.last_name, u.email, u.phone, p.gender, p.date_of_birth 
            FROM family_relationships fr
            JOIN patients p ON fr.related_patient_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE fr.patient_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $patient_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $family_members[] = $row;
        }
    }
    return $family_members;
}

/**
 * Generate a random family code
 * 
 * @param int $length Length of the code
 * @return string Random family code
 */
function generateFamilyCode($length = 6) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Removed similar-looking characters
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

/**
 * Get family members by family code
 * 
 * @param string $family_code Family code
 * @param object $conn Database connection
 * @param int $exclude_patient_id Optional patient ID to exclude from results
 * @return array Array of family members
 */
function getFamilyMembersByCode($family_code, $conn, $exclude_patient_id = null) {
    $family_members = [];
    $sql = "SELECT p.id, p.date_of_birth, p.gender, p.address, p.family_role,
                  u.id as user_id, u.first_name, u.middle_name, u.last_name, u.email, u.phone
            FROM patients p
            JOIN users u ON p.user_id = u.id
            WHERE p.family_code = ?";
    
    if ($exclude_patient_id) {
        $sql .= " AND p.id != ?";
    }
    
    if($stmt = mysqli_prepare($conn, $sql)){
        if ($exclude_patient_id) {
            mysqli_stmt_bind_param($stmt, "si", $family_code, $exclude_patient_id);
        } else {
            mysqli_stmt_bind_param($stmt, "s", $family_code);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $family_members[] = $row;
        }
    }
    return $family_members;
}

/**
 * Get a family code's information
 * 
 * @param string $family_code Family code
 * @param object $conn Database connection
 * @return array|null Family code information or null if not found
 */
function getFamilyCodeInfo($family_code, $conn) {
    $sql = "SELECT fc.*, CONCAT(u.first_name, ' ', u.last_name) as creator_name
            FROM family_codes fc
            JOIN patients p ON fc.created_by = p.id
            JOIN users u ON p.user_id = u.id
            WHERE fc.code = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $family_code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            return $row;
        }
    }
    return null;
} 