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

// Handle POST request for sending broadcast messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data
    $type = isset($_POST['type']) ? $_POST['type'] : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $schedule = isset($_POST['schedule']) ? trim($_POST['schedule']) : '';
    $recipients = isset($_POST['recipients']) ? $_POST['recipients'] : [];
    
    // Validate data
    if (empty($type) || empty($subject) || empty($message)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    // Check if broadcast_messages table exists, if not create it
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'broadcast_messages'");
    if (mysqli_num_rows($table_check) == 0) {
        $create_table_sql = "CREATE TABLE broadcast_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('all', 'doctors', 'patients', 'selected') NOT NULL,
            schedule DATETIME NULL,
            status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            sent_at TIMESTAMP NULL,
            INDEX (admin_id),
            INDEX (type),
            INDEX (status),
            INDEX (schedule)
        )";
        
        if (!mysqli_query($conn, $create_table_sql)) {
            echo json_encode([
                'success' => false,
                'message' => 'Could not create broadcast_messages table: ' . mysqli_error($conn)
            ]);
            exit;
        }
        
        $create_recipients_table_sql = "CREATE TABLE broadcast_recipients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            broadcast_id INT NOT NULL,
            user_id INT NOT NULL,
            user_role ENUM('doctor', 'patient') NOT NULL,
            is_sent TINYINT(1) DEFAULT 0,
            sent_at TIMESTAMP NULL,
            FOREIGN KEY (broadcast_id) REFERENCES broadcast_messages(id) ON DELETE CASCADE,
            INDEX (broadcast_id),
            INDEX (user_id),
            INDEX (user_role),
            INDEX (is_sent)
        )";
        
        if (!mysqli_query($conn, $create_recipients_table_sql)) {
            echo json_encode([
                'success' => false,
                'message' => 'Could not create broadcast_recipients table: ' . mysqli_error($conn)
            ]);
            exit;
        }
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
    
    // Get admin ID
    $admin_id = $_SESSION['id'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Insert broadcast message
        $schedule_value = !empty($schedule) ? $schedule : null;
        $status = !empty($schedule) ? 'pending' : 'sent';
        
        $sql = "INSERT INTO broadcast_messages (admin_id, subject, message, type, schedule, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssss", $admin_id, $subject, $message, $type, $schedule_value, $status);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to insert broadcast message: ' . mysqli_error($conn));
        }
        
        $broadcast_id = mysqli_insert_id($conn);
        
        // Get recipients based on type
        $recipients_to_add = [];
        
        if ($type === 'all' || $type === 'doctors') {
            $sql = "SELECT u.id, 'doctor' as role 
                    FROM users u 
                    JOIN doctors d ON u.id = d.user_id 
                    WHERE u.active = 1";
            
            $result = mysqli_query($conn, $sql);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $recipients_to_add[] = [
                    'id' => $row['id'],
                    'role' => $row['role']
                ];
            }
        }
        
        if ($type === 'all' || $type === 'patients') {
            $sql = "SELECT u.id, 'patient' as role 
                    FROM users u 
                    JOIN patients p ON u.id = p.user_id 
                    WHERE u.active = 1";
            
            $result = mysqli_query($conn, $sql);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $recipients_to_add[] = [
                    'id' => $row['id'],
                    'role' => $row['role']
                ];
            }
        }
        
        if ($type === 'selected' && !empty($recipients)) {
            if (is_string($recipients)) {
                $recipients = json_decode($recipients, true);
            }
            
            if (!is_array($recipients)) {
                throw new Exception('Invalid recipients format');
            }
            
            foreach ($recipients as $recipient) {
                if (is_array($recipient) && isset($recipient['id']) && isset($recipient['role'])) {
                    $recipients_to_add[] = [
                        'id' => intval($recipient['id']),
                        'role' => $recipient['role']
                    ];
                } else if (isset($recipient) && is_string($recipient)) {
                    // Extract id and role from string format
                    $parts = explode(':', $recipient);
                    if (count($parts) === 2) {
                        $recipients_to_add[] = [
                            'id' => intval($parts[0]),
                            'role' => $parts[1]
                        ];
                    }
                }
            }
        }
        
        // Insert recipients
        $insert_recipients_sql = "INSERT INTO broadcast_recipients (broadcast_id, user_id, user_role) 
                                  VALUES (?, ?, ?)";
        $insert_recipients_stmt = mysqli_prepare($conn, $insert_recipients_sql);
        
        foreach ($recipients_to_add as $recipient) {
            mysqli_stmt_bind_param($insert_recipients_stmt, "iis", $broadcast_id, $recipient['id'], $recipient['role']);
            
            if (!mysqli_stmt_execute($insert_recipients_stmt)) {
                throw new Exception('Failed to add recipient: ' . mysqli_error($conn));
            }
        }
        
        // If not scheduled, send messages immediately
        if (empty($schedule)) {
            $full_message = "📢 $subject\n\n$message";
            
            $insert_message_sql = "INSERT INTO messages (from_user_id, to_user_id, from_user_role, to_user_role, message) 
                                  VALUES (?, ?, 'system', ?, ?)";
            $insert_message_stmt = mysqli_prepare($conn, $insert_message_sql);
            
            foreach ($recipients_to_add as $recipient) {
                $user_id = $recipient['id'];
                $user_role = $recipient['role'];
                
                mysqli_stmt_bind_param($insert_message_stmt, "iiss", $admin_id, $user_id, $user_role, $full_message);
                
                if (!mysqli_stmt_execute($insert_message_stmt)) {
                    throw new Exception('Failed to send message to user: ' . mysqli_error($conn));
                }
                
                // Mark recipient as sent
                $update_recipient_sql = "UPDATE broadcast_recipients 
                                        SET is_sent = 1, sent_at = NOW() 
                                        WHERE broadcast_id = ? AND user_id = ? AND user_role = ?";
                $update_recipient_stmt = mysqli_prepare($conn, $update_recipient_sql);
                mysqli_stmt_bind_param($update_recipient_stmt, "iis", $broadcast_id, $user_id, $user_role);
                mysqli_stmt_execute($update_recipient_stmt);
            }
            
            // Update broadcast status
            $update_broadcast_sql = "UPDATE broadcast_messages 
                                    SET status = 'sent', sent_at = NOW() 
                                    WHERE id = ?";
            $update_broadcast_stmt = mysqli_prepare($conn, $update_broadcast_sql);
            mysqli_stmt_bind_param($update_broadcast_stmt, "i", $broadcast_id);
            mysqli_stmt_execute($update_broadcast_stmt);
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true,
            'message' => !empty($schedule) ? 'Broadcast scheduled successfully' : 'Broadcast sent successfully',
            'data' => [
                'broadcast_id' => $broadcast_id,
                'recipients_count' => count($recipients_to_add)
            ]
        ]);
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
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