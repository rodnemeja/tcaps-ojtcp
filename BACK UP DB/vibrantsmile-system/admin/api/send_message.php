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

// Handle POST request for sending messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data
    $to_user_id = isset($_POST['to_user_id']) ? intval($_POST['to_user_id']) : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $to_user_role = isset($_POST['to_user_role']) ? $_POST['to_user_role'] : '';
    $from_user_role = isset($_POST['from_user_role']) ? $_POST['from_user_role'] : 'admin';
    
    // Validate data
    if (empty($to_user_id) || empty($message) || empty($to_user_role)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    // Check if messages table exists, if not create it
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'messages'");
    if (mysqli_num_rows($table_check) == 0) {
        $create_table_sql = "CREATE TABLE messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            from_user_id INT,
            to_user_id INT,
            from_user_role ENUM('admin', 'doctor', 'patient', 'system') NOT NULL,
            to_user_role ENUM('admin', 'doctor', 'patient') NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (from_user_id),
            INDEX (to_user_id),
            INDEX (from_user_role),
            INDEX (to_user_role),
            INDEX (is_read)
        )";
        
        if (!mysqli_query($conn, $create_table_sql)) {
            echo json_encode([
                'success' => false,
                'message' => 'Could not create messages table: ' . mysqli_error($conn)
            ]);
            exit;
        }
    }
    
    // Insert message into database
    $from_user_id = $_SESSION['id'];
    
    // For system messages, set from_user_id to NULL
    if ($from_user_role === 'system') {
        $from_user_id = NULL;
        $sql = "INSERT INTO messages (from_user_id, to_user_id, from_user_role, to_user_role, message) 
                VALUES (NULL, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isss", $to_user_id, $from_user_role, $to_user_role, $message);
    } else {
        $sql = "INSERT INTO messages (from_user_id, to_user_id, from_user_role, to_user_role, message) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iisss", $from_user_id, $to_user_id, $from_user_role, $to_user_role, $message);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'success' => true,
            'message' => 'Message sent successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send message: ' . mysqli_error($conn)
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