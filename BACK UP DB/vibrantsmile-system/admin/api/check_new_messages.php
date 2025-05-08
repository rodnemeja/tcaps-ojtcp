<?php
session_start();
header('Content-Type: application/json');
require_once "../../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Handle GET request for checking new messages
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if messages table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'messages'");
    if (mysqli_num_rows($table_check) == 0) {
        echo json_encode([
            'success' => true,
            'data' => []
        ]);
        exit;
    }
    
    // Get all unread messages for admin
    $admin_id = $_SESSION['id'];
    $unread_users = [];
    
    // First, check messages from doctors
    $sql = "SELECT m.from_user_id, COUNT(*) as count, 'doctor' as role
            FROM messages m
            JOIN doctors d ON m.from_user_id = d.user_id
            WHERE m.to_user_id = ? AND m.to_user_role = 'admin' AND m.is_read = 0 AND m.from_user_role = 'doctor'
            GROUP BY m.from_user_id";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $admin_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $unread_users[$row['from_user_id']] = [
                'count' => intval($row['count']),
                'role' => $row['role']
            ];
        }
    }
    
    // Then, check messages from patients
    $sql = "SELECT m.from_user_id, COUNT(*) as count, 'patient' as role
            FROM messages m
            JOIN patients p ON m.from_user_id = p.user_id
            WHERE m.to_user_id = ? AND m.to_user_role = 'admin' AND m.is_read = 0 AND m.from_user_role = 'patient'
            GROUP BY m.from_user_id";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $admin_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $unread_users[$row['from_user_id']] = [
                'count' => intval($row['count']),
                'role' => $row['role']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $unread_users
    ]);
} else {
    // Method not allowed
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
} 