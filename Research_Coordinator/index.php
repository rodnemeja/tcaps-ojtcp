<?php
include('../Includes/header.php'); 
include('../Includes/dean_navbar.php'); 

include('../Includes/conn_pdo.php');

include('../Includes/conn.php');

$dean_id = $_SESSION['dean_id'];
$sql_dean = "SELECT * FROM dean WHERE dean_id = $dean_id";
$run_sql_dean = mysqli_query($db, $sql_dean);
$res_dean = mysqli_fetch_assoc($run_sql_dean);





$today = date("Y/m/d");
$time = date("h:i a");

 
 
$queryStudent = "SELECT * FROM student WHERE student_status = 'Approved' ";
$statementqueryStudent = $connect->prepare($queryStudent);
$statementqueryStudent->execute();
$total_dataS = $statementqueryStudent->rowCount();

$queryStudentP = "SELECT * FROM student WHERE student_status = 'Pending'";
$statementqueryStudentP = $connect->prepare($queryStudentP);
$statementqueryStudentP->execute();
$total_dataP = $statementqueryStudentP->rowCount();
 
$queryUpload = "SELECT * FROM dean ";
$statementqueryUpload = $connect->prepare($queryUpload);
$statementqueryUpload->execute();
$total_dataD = $statementqueryUpload->rowCount();

$queryUpload = "SELECT * FROM upload ";
$statementqueryUpload = $connect->prepare($queryUpload);
$statementqueryUpload->execute();
$total_dataU = $statementqueryUpload->rowCount();

$queryUpload = "SELECT * FROM upload WHERE status = 'Pending' ";
$statementqueryUpload = $connect->prepare($queryUpload);
$statementqueryUpload->execute();
$total_dataW = $statementqueryUpload->rowCount();
 

 



?>


<!-- Begin Page Content -->
<div class="container-fluid">

  <!-- Page Heading -->
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
     
    <!-- <h2 class="h3 mb-0 text-gray-800"></h2> -->
    <div class="mr-3">
      <h5>

        <?php

        echo date('l,  F d, Y');
        //$today = strtotime('2024-06-01');
        //echo date('l,  F d, Y', $today);
        ?>
      </h5>
    </div>


  </div>

  <div class="row">






    <!-- Department -->
    <div class="col-xl-3 col-md-6 mb-4">
      <a href="reservation" role="button" class=" " style="text-decoration: none;">

        <div class="card border-left-primary shadow h-100 py-3">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="  font-weight-bold text-primary text-uppercase mb-1">Number of Approved Student</div>
                <div class="row no-gutters align-items-center">
                  <div class="col-auto">
                    <div class="h2 mb-0 mr-3 font-weight-bold text-gray-800"><?php 
                                                                              echo $total_dataS; ?></div>
                  </div>
                  <div class="pt-4">
                    <a href="student">View Details</a>
                  </div>
                </div>
              </div>
              <div class="col-auto mr-3">
                <i class="fas fa-user fa-3x text-gray-300"></i>
                 
              </div>
            </div>

          </div>
        </div>
      </a>
    </div>

 
    <!-- Department HEad-->
    <div class="col-xl-3 col-md-6 mb-4">
      <a href="student" role="button" class=" " style="text-decoration: none;">

        <div class="card border-left-primary shadow h-100 py-3">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="  font-weight-bold text-primary text-uppercase mb-1">Number of Pending Student</div>
                <div class="row no-gutters align-items-center">
                  <div class="col-auto">
                    <div class="h2 mb-0 mr-3 font-weight-bold text-gray-800"><?php 
                                                                              echo $total_dataP; ?></div>
                  </div>
                  <div class="pt-4">
                    <a href="student">View Details</a>
                  </div>
                </div>
              </div>
              <div class="col-auto mr-3">
                <i class="fas fa-user fa-3x text-gray-300"></i>
              </div>
            </div>

          </div>
        </div>
      </a>
    </div>

        <!-- Coordinator-->
        <div class="col-xl-3 col-md-6 mb-4">
      <a href="dean" role="button" class=" " style="text-decoration: none;">

        <div class="card border-left-primary shadow h-100 py-3">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="  font-weight-bold text-primary text-uppercase mb-1">Number of Reaserh Coordinator</div>
                <div class="row no-gutters align-items-center">
                  <div class="col-auto">
                    <div class="h2 mb-0 mr-3 font-weight-bold text-gray-800"><?php 
                                                                              echo $total_dataD; ?></div>
                  </div>
                  <div class="pt-4">
                    <a href="dean1">View Details</a>
                  </div>
                </div>
              </div>
              <div class="col-auto mr-3">
                <i class="fas fa-user-tie fa-3x text-gray-300"></i>
              </div>
            </div>

          </div>
        </div>
      </a>
    </div>


        <!-- Uploaded File -->
        <div class="col-xl-3 col-md-6 mb-4">
      <a href="upload" role="button" class=" " style="text-decoration: none;">

        <div class="card border-left-primary shadow h-100 py-3">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="  font-weight-bold text-primary text-uppercase mb-1">Number of Uploaded Thesis/Capstone</div>
                <div class="row no-gutters align-items-center">
                  <div class="col-auto">
                    <div class="h2 mb-0 mr-3 font-weight-bold text-gray-800"><?php 
                                                                              echo $total_dataU; ?></div>
                  </div>
                  <div class="pt-4">
                    <a href="upload1">View Details</a>
                  </div>
                </div>
              </div>
              <div class="col-auto mr-3">
                <i class="fas fa-book fa-3x text-gray-300"></i>
              </div>
            </div>

          </div>
        </div>
      </a>
    </div>

    <!-- Pending File -->
        <div class="col-xl-3 col-md-6 mb-4">
      <a href="upload" role="button" class=" " style="text-decoration: none;">

        <div class="card border-left-primary shadow h-100 py-3">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="  font-weight-bold text-primary text-uppercase mb-1">Number of Uploaded Thesis/Capstone Pending</div>
                <div class="row no-gutters align-items-center">
                  <div class="col-auto">
                    <div class="h2 mb-0 mr-3 font-weight-bold text-gray-800"><?php 
                                                                              echo $total_dataW; ?></div>
                  </div>
                  <div class="pt-4">
                    <a href="upload">View Details</a>
                  </div>
                </div>
              </div>
              <div class="col-auto mr-3">
                <i class="fas fa-book fa-3x text-gray-300"></i>
              </div>
            </div>

          </div>
        </div>
      </a>
    </div>





       








    <?php
    include('../Includes/script.php');

    ?>