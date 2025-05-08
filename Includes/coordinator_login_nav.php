<?php
include('Includes/conn.php'); 
session_start();

if(isset($_SESSION['dean_id'])){
    $id = $_SESSION['dean_id'];
  
    $query_student = "SELECT * FROM dean WHERE dean_id = $id";
    $run_query_student = mysqli_query($db, $query_student);
    $result_student = mysqli_fetch_assoc($run_query_student);
  
    $name = $result_student['dean_name'];

    $btn = '
    <li class="nav-item dropdown no-arrow">

    <a class=" nav-link text-dark font-weight-bold dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    '.$name.'
                    <img class="ml-2 rounded-circle" src="Images/head.jpg" width="28rem;" height="28rem;">
                    </a>

                <!-- Dropdown - User Information -->
                <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">

                <a class="dropdown-item" href="logout_student">
                        <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                        Logout
                    </a>
                
                </div>
                </li>
    ';
  
}

?>
 