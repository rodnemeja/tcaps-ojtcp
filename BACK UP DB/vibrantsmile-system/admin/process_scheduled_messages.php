<?php
// This script is meant to be run via cron job to process scheduled broadcast messages
// Example cron job: * * * * * php /path/to/process_scheduled_messages.php

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load database configuration
require_once "../config/database.php";

// Log function
function log_message($message) {
    $log_file = __DIR__ . '/../logs/broadcast_messages.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message" . PHP_EOL;
    
    // Create logs directory if it doesn't exist
    if (!is_dir(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    
    // Append to log file
    file_put_contents($log_file, $log_message, FILE_APPEND);
    
    // Also output to stdout for cron emails
    echo $log_message;
}

// Check if broadcast_messages and messages tables exist
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'broadcast_messages'");
if (mysqli_num_rows($table_check) == 0) {
    log_message("Broadcast messages table does not exist. No processing needed.");
    exit(0);
}

$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'messages'");
if (mysqli_num_rows($table_check) == 0) {
    log_message("Messages table does not exist. No processing needed.");
    exit(0);
}

// Get pending broadcasts that are scheduled to be sent
$now = date('Y-m-d H:i:s');
$sql = "SELECT * FROM broadcast_messages 
        WHERE status = 'pending' 
        AND schedule IS NOT NULL 
        AND schedule <= '$now'";

$result = mysqli_query($conn, $sql);

if (!$result) {
    log_message("Error querying scheduled broadcasts: " . mysqli_error($conn));
    exit(1);
}

$broadcasts_count = mysqli_num_rows($result);
log_message("Found $broadcasts_count scheduled broadcasts to process.");

if ($broadcasts_count == 0) {
    exit(0);
}

// Process each broadcast
while ($broadcast = mysqli_fetch_assoc($result)) {
    $broadcast_id = $broadcast['id'];
    $admin_id = $broadcast['admin_id'];
    $subject = $broadcast['subject'];
    $message = $broadcast['message'];
    
    log_message("Processing broadcast #$broadcast_id: $subject");
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update broadcast status to 'processing'
        $update_sql = "UPDATE broadcast_messages 
                      SET status = 'sent', sent_at = NOW() 
                      WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $broadcast_id);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Failed to update broadcast status: " . mysqli_error($conn));
        }
        
        // Get recipients
        $recipients_sql = "SELECT * FROM broadcast_recipients 
                          WHERE broadcast_id = ? AND is_sent = 0";
        $recipients_stmt = mysqli_prepare($conn, $recipients_sql);
        mysqli_stmt_bind_param($recipients_stmt, "i", $broadcast_id);
        
        if (!mysqli_stmt_execute($recipients_stmt)) {
            throw new Exception("Failed to get recipients: " . mysqli_error($conn));
        }
        
        $recipients_result = mysqli_stmt_get_result($recipients_stmt);
        $recipients_count = mysqli_num_rows($recipients_result);
        
        log_message("Found $recipients_count recipients to send to.");
        
        // If no recipients, mark as sent and continue
        if ($recipients_count == 0) {
            mysqli_commit($conn);
            continue;
        }
        
        // Prepare full message
        $full_message = "📢 $subject\n\n$message";
        
        // Prepare message insert
        $insert_message_sql = "INSERT INTO messages (from_user_id, to_user_id, from_user_role, to_user_role, message) 
                              VALUES (?, ?, 'system', ?, ?)";
        $insert_message_stmt = mysqli_prepare($conn, $insert_message_sql);
        
        // Prepare recipient update
        $update_recipient_sql = "UPDATE broadcast_recipients 
                               SET is_sent = 1, sent_at = NOW() 
                               WHERE id = ?";
        $update_recipient_stmt = mysqli_prepare($conn, $update_recipient_sql);
        
        $sent_count = 0;
        $failed_count = 0;
        
        // Send message to each recipient
        while ($recipient = mysqli_fetch_assoc($recipients_result)) {
            $recipient_id = $recipient['id'];
            $user_id = $recipient['user_id'];
            $user_role = $recipient['user_role'];
            
            // Send message
            mysqli_stmt_bind_param($insert_message_stmt, "iiss", $admin_id, $user_id, $user_role, $full_message);
            
            if (mysqli_stmt_execute($insert_message_stmt)) {
                // Mark recipient as sent
                mysqli_stmt_bind_param($update_recipient_stmt, "i", $recipient_id);
                mysqli_stmt_execute($update_recipient_stmt);
                $sent_count++;
            } else {
                $failed_count++;
                log_message("Failed to send message to user $user_id: " . mysqli_error($conn));
            }
        }
        
        log_message("Sent $sent_count messages, $failed_count failed.");
        
        // Commit transaction
        mysqli_commit($conn);
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        
        log_message("Error processing broadcast #$broadcast_id: " . $e->getMessage());
        
        // Update broadcast status to 'failed'
        $fail_sql = "UPDATE broadcast_messages SET status = 'failed' WHERE id = ?";
        $fail_stmt = mysqli_prepare($conn, $fail_sql);
        mysqli_stmt_bind_param($fail_stmt, "i", $broadcast_id);
        mysqli_stmt_execute($fail_stmt);
    }
}

log_message("Processing completed successfully.");
exit(0); 