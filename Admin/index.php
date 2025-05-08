<?php

include('../Includes/header.php');
include('../Includes/admin_navbar.php');

include('../Includes/conn_pdo.php');

$today = date("Y/m/d");
$time = date("h:i a");

$queryRoom = "SELECT * FROM department";
$statement = $connect->prepare($queryRoom);
$statement->execute();
$total_data = $statement->rowCount();

$queryRoom = "SELECT * FROM courses";
$statement = $connect->prepare($queryRoom);
$statement->execute();
$total_dataR = $statement->rowCount();
 


$queryDean = "SELECT * FROM dean";
$statementqueryDean = $connect->prepare($queryDean);
$statementqueryDean->execute();
$total_dataD = $statementqueryDean->rowCount();


 
$queryStudent = "SELECT * FROM student";
$statementqueryStudent = $connect->prepare($queryStudent);
$statementqueryStudent->execute();
$total_dataC = $statementqueryStudent->rowCount();

$queryStudent = "SELECT * FROM student WHERE student_status = 'Confirmed'";
$statementqueryStudent = $connect->prepare($queryStudent);
$statementqueryStudent->execute();
$total_dataS = $statementqueryStudent->rowCount();

$queryStudentP = "SELECT * FROM student WHERE student_status = 'Pending'";
$statementqueryStudentP = $connect->prepare($queryStudentP);
$statementqueryStudentP->execute();
$total_dataP = $statementqueryStudentP->rowCount();
 


$queryUpload = "SELECT * FROM upload";
$statementqueryUpload = $connect->prepare($queryUpload);
$statementqueryUpload->execute();
$total_dataU = $statementqueryUpload->rowCount();
 

 



?>

<!-- Begin Page Content -->
<div class="container-fluid">

  <!-- Page Heading -->
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h2 class="h3 mb-0 text-gray-800">Admin Dashboard</h2>
    <div class="mr-3">
      <h5>

        <?php

        echo date('l,  F d, Y');
        // $today = strtotime('2024-06-11');
        // echo date('l,  F d, Y', $today);
        ?>
      </h5>
    </div>


  </div>

  <div class="row">






    <!-- Department -->
    <div class="col-xl-3 col-md-6 mb-4">
      <a href="department" role="button" class=" " style="text-decoration: none;">

        <div class="card border-left-primary shadow h-100 py-3 ">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="  font-weight-bold text-primary text-uppercase mb-1">Number of Department</div>
                <div class="row no-gutters align-items-center">
                  <div class="col-auto">
                    <div class="h2 mb-0 mr-3 font-weight-bold text-gray-800"><?php 
                                                                              echo $total_data; ?></div>
                  </div>
                  <div class="pt-4">
                    <a href="department">View Details</a>
                  </div>
                </div>
              </div>
              <div class="col-auto mr-3">
                <i class="fas fa-building fa-3x text-gray-300"></i>
                 
              </div>
            </div>

          </div>
        </div>
      </a>
    </div>

        <!-- Curriculum -->
        <div class="col-xl-3 col-md-6 mb-4">
      <a href="dep_courses" role="button" class=" " style="text-decoration: none;">

        <div class="card border-left-primary shadow h-100 py-3 ">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="  font-weight-bold text-primary text-uppercase mb-1">Number of Curriculum</div>
                <div class="row no-gutters align-items-center">
                  <div class="col-auto">
                    <div class="h2 mb-0 mr-3 font-weight-bold text-gray-800"><?php 
                                                                              echo $total_dataR; ?></div>
                  </div>
                  <div class="pt-4">
                    <a href="dep_courses">View Details</a>
                  </div>
                </div>
              </div>
              <div class="col-auto mr-3">
                <i class="fas fa-building fa-3x text-gray-300"></i>
                 
              </div>
            </div>

          </div>
        </div>
      </a>
    </div>

 
    <!-- Department-->
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
                    <a href="dean">View Details</a>
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

    

        <!-- Department -->
        <div class="col-xl-3 col-md-6 mb-4">
      <a href="student" role="button" class=" " style="text-decoration: none;">

        <div class="card border-left-primary shadow h-100 py-3">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="  font-weight-bold text-primary text-uppercase mb-1">Number of Students</div><br>
                <div class="row no-gutters align-items-center">
                  <div class="col-auto">
                    <div class="h2 mb-0 mr-3 font-weight-bold text-gray-800"><?php  echo $total_dataC; ?></div>
                  </div>
                  <div class="pt-4">
                    <a href="student">View Details</a>
                  </div>
                </div>
              </div>
              <div class="col-auto mr-3">
                <i class="fas fa-users fa-3x text-gray-300"></i>
              </div>
            </div>

          </div>
        </div>
      </a>
    </div>


        <!-- Department -->
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