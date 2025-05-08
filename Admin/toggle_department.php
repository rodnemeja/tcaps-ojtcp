<?php
require_once('../Includes/conn_pdo.php');

if(isset($_POST['department_id']) && isset($_POST['status'])) {
  $department_id = $_POST['department_id'];
  $status = $_POST['status'];

  $query = "UPDATE department SET status = :status WHERE department_id = :department_id";
  $statement = $connect->prepare($query);
  $statement->execute([':status' => $status, ':department_id' => $department_id]);

  if($statement->rowCount() > 0) {
    echo 'Department status updated successfully.';
  } else {
    echo 'Failed to update department status.';
  }
}
?>
