<?php
/**
 * Activity Logger
 * 
 * Functions for logging user activities and transactions in the system
 */

/**
 * Log an activity performed by a user
 * 
 * @param int $user_id The ID of the user performing the action
 * @param string $activity_type Type of activity (login, logout, create, update, delete, etc.)
 * @param string $description Description of the activity
 * @return bool True if logged successfully, false otherwise
 */
function log_activity($user_id, $activity_type, $description) {
    global $conn;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $sql = "INSERT INTO user_activity_logs (user_id, activity_type, description, ip_address) 
            VALUES (?, ?, ?, ?)";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "isss", $user_id, $activity_type, $description, $ip);
        
        if(mysqli_stmt_execute($stmt)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Log a transaction performed by a staff member or doctor
 * 
 * @param int $staff_id The ID of the staff or doctor performing the transaction
 * @param string $transaction_type Type of transaction (appointment, payment, prescription, etc.)
 * @param string $details Details of the transaction
 * @param int|null $patient_id ID of the patient involved (if applicable)
 * @param int|null $appointment_id ID of the appointment (if applicable)
 * @param float|null $amount Amount involved in the transaction (if applicable)
 * @return bool True if logged successfully, false otherwise
 */
function log_transaction($staff_id, $transaction_type, $details, $patient_id = null, $appointment_id = null, $amount = null) {
    global $conn;
    
    $sql = "INSERT INTO staff_transactions (staff_id, transaction_type, details, patient_id, appointment_id, amount) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "issiid", $staff_id, $transaction_type, $details, $patient_id, $appointment_id, $amount);
        
        if(mysqli_stmt_execute($stmt)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get activities for a specific user
 * 
 * @param int $user_id User ID
 * @param int $limit Number of records to return
 * @return array Array of activity records
 */
function get_user_activities($user_id, $limit = 50) {
    global $conn;
    
    $sql = "SELECT * FROM user_activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
    
    $activities = [];
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)) {
            $activities[] = $row;
        }
    }
    
    return $activities;
}

/**
 * Get transactions for a specific staff/doctor
 * 
 * @param int $staff_id Staff/Doctor ID
 * @param int $limit Number of records to return
 * @return array Array of transaction records
 */
function get_staff_transactions($staff_id, $limit = 50) {
    global $conn;
    
    $sql = "SELECT st.*, u.first_name, u.last_name, u.role
            FROM staff_transactions st
            JOIN users u ON st.staff_id = u.id
            WHERE st.staff_id = ? 
            ORDER BY st.transaction_date DESC 
            LIMIT ?";
    
    $transactions = [];
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $staff_id, $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)) {
            $transactions[] = $row;
        }
    }
    
    return $transactions;
}

/**
 * Get all staff transactions for admin view
 * 
 * @param string|null $filter_type Filter by transaction type
 * @param string|null $date_from Starting date for filter (Y-m-d)
 * @param string|null $date_to Ending date for filter (Y-m-d)
 * @param int $limit Number of records to return
 * @return array Array of transaction records
 */
function get_all_staff_transactions($filter_type = null, $date_from = null, $date_to = null, $limit = 100) {
    global $conn;
    
    $conditions = [];
    $params = [];
    $types = "";
    
    if($filter_type) {
        $conditions[] = "st.transaction_type = ?";
        $params[] = $filter_type;
        $types .= "s";
    }
    
    if($date_from) {
        $conditions[] = "DATE(st.transaction_date) >= ?";
        $params[] = $date_from;
        $types .= "s";
    }
    
    if($date_to) {
        $conditions[] = "DATE(st.transaction_date) <= ?";
        $params[] = $date_to;
        $types .= "s";
    }
    
    $where_clause = "";
    if(count($conditions) > 0) {
        $where_clause = "WHERE " . implode(" AND ", $conditions);
    }
    
    $sql = "SELECT st.*, 
            u.first_name as staff_first_name, u.last_name as staff_last_name, u.role,
            p.first_name as patient_first_name, p.last_name as patient_last_name
            FROM staff_transactions st
            JOIN users u ON st.staff_id = u.id
            LEFT JOIN patients pt ON st.patient_id = pt.id
            LEFT JOIN users p ON pt.user_id = p.id
            $where_clause
            ORDER BY st.transaction_date DESC 
            LIMIT ?";
    
    $params[] = $limit;
    $types .= "i";
    
    $transactions = [];
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        if(!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)) {
            $transactions[] = $row;
        }
    }
    
    return $transactions;
}
?> 