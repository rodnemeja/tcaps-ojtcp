<?php
require_once('../Includes/conn_pdo.php');

if(isset($_POST['department_id'])) {
  $department_id = $_POST['department_id'];

  $query = "UPDATE department SET status = 'inactive' WHERE department_id = :department_id";
  $statement = $connect->prepare($query);
  $statement->execute([':department_id' => $department_id]);

  if($statement->rowCount() > 0) {
    echo 'Department set to inactive successfully.';
  } else {
    echo 'Failed to set department to inactive.';
  }
}
?>
