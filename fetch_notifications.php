<?php
session_start();
include_once("Includes/conn.php");
include('Includes/conn_pdo.php');

$student_id = $_SESSION['student_id']; // Get the logged-in student's ID

// Fetch all notifications for the student
$sql = "SELECT * FROM notifications WHERE student_id = ? ORDER BY created_at DESC";
$stmt = $connect->prepare($sql);
$stmt->execute([$student_id]);
$notifications = $stmt->fetchAll();

// Count unread notifications
$sql_unread = "SELECT COUNT(*) FROM notifications WHERE student_id = ? AND status = 'unread'";
$stmt_unread = $connect->prepare($sql_unread);
$stmt_unread->execute([$student_id]);
$unread_count = $stmt_unread->fetchColumn();

// Build notification output
$output = '';
if (!empty($notifications)) {
    foreach ($notifications as $notification) {
        $status_class = $notification['status'] == 'unread' ? 'font-weight-bold' : '';
        $output .= '
        <a href="#" class="dropdown-item ' . $status_class . '">
            ' . htmlspecialchars($notification['message']) . '
        </a>';
    }
} else {
    $output = '<p class="dropdown-item text-center">No notifications</p>';
}

// Return response as JSON
echo json_encode(['notifications' => $output, 'unread_count' => $unread_count]);


$sql = "SELECT * FROM notifications WHERE student_id = ? ORDER BY created_at DESC";
$stmt = $connect->prepare($sql);
$stmt->execute([$student_id]);
$notifications = $stmt->fetchAll();

$output = '';
if (count($notifications) > 0) {
    foreach ($notifications as $notification) {
        $output .= '
        <div class="notification-item">
            <p>' . htmlspecialchars($notification['message']) . '</p>
            <small>' . htmlspecialchars($notification['created_at']) . '</small>
        </div>';
    }
} else {
    $output = '<p>No notifications found.</p>';
}

echo $output;
?>
