<?php
session_start();
include('Includes/conn_pdo.php');
include('notification_functions.php');

$student_id = $_SESSION['student_id']; // Assume session contains student ID
$upload_name = $_POST['upload_name']; // Name of the thesis
$upload_file = $_FILES['thesis_file']['name']; // File name of the uploaded thesis
$upload_abstract = $_POST['upload_abstract']; // Abstract of the thesis
$department_id = $_SESSION['student_department']; // Student's department ID

// Move uploaded file to a directory (example)
$target_dir = "uploads/";
$target_file = $target_dir . basename($upload_file);
move_uploaded_file($_FILES['thesis_file']['tmp_name'], $target_file);

// Insert the thesis into the upload table
$sql = "INSERT INTO upload (upload_name, upload_abstract, upload_file, upload_student_id, upload_department, status) 
        VALUES (?, ?, ?, ?, ?, 'Pending')";
$stmt = $connect->prepare($sql);
$stmt->execute([$upload_name, $upload_abstract, $upload_file, $student_id, $department_id]);

// Notify the student of the upload
$message = "You uploaded a thesis titled '$upload_name' (file: $upload_file). It is pending approval.";
notifyStudent($student_id, $message, "thesis_upload", $connect);

echo "Thesis uploaded successfully and notification sent!";
?>
