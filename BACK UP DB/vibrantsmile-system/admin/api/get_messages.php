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

// Handle GET request for retrieving messages
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get query parameters
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $user_role = isset($_GET['user_role']) ? $_GET['user_role'] : '';
    
    // Validate data
    if (empty($user_id) || empty($user_role)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters'
        ]);
        exit;
    }
    
    // Check if messages table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'messages'");
    if (mysqli_num_rows($table_check) == 0) {
        echo json_encode([
            'success' => true,
            'data' => []
        ]);
        exit;
    }
    
    // Get messages between admin and the specified user
    $admin_id = $_SESSION['id'];
    
    $sql = "SELECT * FROM messages 
            WHERE ((from_user_id = ? AND to_user_id = ? AND from_user_role = 'admin' AND to_user_role = ?) 
                OR (from_user_id = ? AND to_user_id = ? AND from_user_role = ? AND to_user_role = 'admin')
                OR (from_user_role = 'system' AND to_user_id = ? AND to_user_role = ?))
            ORDER BY timestamp ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iisisiss", $admin_id, $user_id, $user_role, $user_id, $admin_id, $user_role, $user_id, $user_role);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $messages = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $messages[] = [
                'id' => $row['id'],
                'from_user_id' => $row['from_user_id'],
                'to_user_id' => $row['to_user_id'],
                'from_user_role' => $row['from_user_role'],
                'to_user_role' => $row['to_user_role'],
                'message' => $row['message'],
                'is_read' => (bool)$row['is_read'],
                'timestamp' => $row['timestamp']
            ];
        }
        
        // Mark messages as read
        $update_sql = "UPDATE messages 
                       SET is_read = 1 
                       WHERE to_user_id = ? AND to_user_role = 'admin' AND is_read = 0";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $admin_id);
        mysqli_stmt_execute($update_stmt);
        
        echo json_encode([
            'success' => true,
            'data' => $messages
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to retrieve messages: ' . mysqli_error($conn)
        ]);
    }
} else {
    // Method not allowed
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
} 