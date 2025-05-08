<div class="notification-panel">
    <?php



    $student_id = $_SESSION['student_id']; // User's ID from the session

    $update_notification_query = "UPDATE notifications SET is_read = 1 WHERE user_id = '$student_id' AND is_read = 0";
mysqli_query($db, $update_notification_query);
    // Fetch all notifications for the logged-in user
    $notification_query = "SELECT * FROM notifications WHERE user_id = '$student_id' ORDER BY created_at DESC";
    $notifications_result = mysqli_query($db, $notification_query);
    
    // Check if there are any notifications
    if (mysqli_num_rows($notifications_result) > 0) {
        // Loop through the notifications and display them
        while ($notification = mysqli_fetch_assoc($notifications_result)) {
            $message = $notification['message'];
            $created_at = $notification['created_at'];
            echo '<div class="notification-item">';
            echo '<p>' . $message . '</p>';
            echo '<span class="timestamp">' . $created_at . '</span>';
            echo '</div>';
        }
    
        // Mark all unread notifications as read (optional)
        $update_notification_query = "UPDATE notifications SET is_read = 1 WHERE user_id = '$student_id' AND is_read = 0";
        mysqli_query($db, $update_notification_query);
    } else {
        echo '<p>No new notifications.</p>';
    }

    if (isset($_POST['approve_user']) || isset($_POST['disapprove_user'])) {
        // Get the user ID and action (approve or disapprove)
        $student_id = $_POST['student_id'];
        
        if (isset($_POST['approve_user'])) {
            $status = 'Approved';
            $notification_message = "Your account has been approved by the admin.";
        } elseif (isset($_POST['disapprove_user'])) {
            $status = 'Disapproved';
            $notification_message = "Your account has been disapproved by the admin.";
        }
    
        // Update the user's status
        $update_status_query = "UPDATE student SET student_status = '$status' WHERE student_id = '$student_id'";
        
        if (mysqli_query($db, $update_status_query)) {
            // Insert a notification for the user about their approval/disapproval
            $insert_notification = "INSERT INTO notifications (user_id, message) VALUES ('$student_id', '$notification_message')";
            if (mysqli_query($db, $insert_notification)) {
                echo "User status updated and notification sent.";
            } else {
                echo "Error sending notification.";
            }
        } else {
            echo "Error updating user status.";
        }
    }
    ?>
</div>

<style>
    .notification-panel {
        width: 300px;
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #ccc;
        padding: 10px;
        background-color: #fff;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    }

    .notification-item {
        padding: 10px;
        border-bottom: 1px solid #eee;
    }

    .notification-item:last-child {
        border-bottom: none;
    }

    .timestamp {
        font-size: 0.8em;
        color: #888;
    }
</style>
