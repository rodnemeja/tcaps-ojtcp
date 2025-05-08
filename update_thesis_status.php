<?php
session_start();
include('Includes/conn_pdo.php');
include('notification_functions.php');

$upload_id = $_POST['upload_id']; // ID of the thesis
$new_status = $_POST['status'];   // 'Approved' or 'Disapproved'

// Get student ID and thesis details from the `upload` table
$sql = "SELECT upload_name, upload_file, upload_student_id FROM upload WHERE upload_id = ?";
$stmt = $connect->prepare($sql);
$stmt->execute([$upload_id]);
$upload = $stmt->fetch();

$upload_name = $upload['upload_name'];
$upload_file = $upload['upload_file'];
$student_id = $upload['upload_student_id'];

// Update the status of the thesis
$sql_update = "UPDATE upload SET status = ? WHERE upload_id = ?";
$stmt_update = $connect->prepare($sql_update);
$stmt_update->execute([$new_status, $upload_id]);

// Send notification to the student
if ($new_status == 'Approved') {
    $message = "Your thesis titled '$upload_name' (file: $upload_file) has been approved.";
    $type = "thesis_approved";
} else {
    $message = "Your thesis titled '$upload_name' (file: $upload_file) has been disapproved.";
    $type = "thesis_disapproved";
}

notifyStudent($student_id, $message, $type, $connect);

echo "Thesis status updated and notification sent!";
?>
