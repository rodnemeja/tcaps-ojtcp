<?php

include_once("Includes/conn.php");


if (isset($_SESSION['dean_id'])) {

  echo "<script>window.location.href='Dean/index';</script>";
}

if (isset($_SESSION['id'])) {

  echo "<script>window.location.href='Admin/index';</script>";
}


   
$username = $password = "";
$usernameErr = $passwordErr = "";

if (isset($_POST['btnlogin'])) {

    $file_name = $_POST['fname'];

    if (empty($_POST['username'])) {
        $usernameErr = "Email is Required!";
    } else {
        $username = $_POST['username'];
    }

    if (empty($_POST['password'])) {
        $passwordErr = "Password is Required!";
    } else {
        $password = $_POST['password'];
    }

    if ($username && $password) {

        $check_username = mysqli_query($db, "Select * from student where student_username = '$username'");
        $check_username_row = mysqli_num_rows($check_username);

        if ($check_username_row > 0) {
            $row = mysqli_fetch_assoc($check_username);
            $db_password = $row["student_password"];
            $user_id = $row["student_id"];
            $name = $row['student_name'];
            $status = $row['student_status'];

            if($status == "Approved"){

           

            if ($password == $db_password) {

              
 
                    //echo "<script>window.location.href='viewPDF.php?file='.$file_name.'';</script>";
                    $filename = basename($file_name);
                    $path = "Upload/".$filename;

                    if(!empty($filename) && file_exists($path)){
                        header('Cache-control: public');
                        header('Content-description: File Transfer');
                        header('Content-disposition: attachement; filename="'.$filename.'"');
                        header('Content-type: application/pdf');
                        header('Content-transfer-encoding: binary');

                        readfile($path);
                        exit;
                    }else{
                        echo 'File not EXISTS';
                    }
                    
                
            } else {

                $passwordErr = "Wrong Password!";

                echo "<script type'text/javascript'>
                $(document).ready(function(){
                  $('#download_signin_modal').fadeIn('show');
                });
                
                ";
                 
                
              
            }
        }else{
            $usernameErr = "Access Denied";
        }
        } else {

            $usernameErr = "Email Not Registered";
        }
    }
}



?>
<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">

  <title>THESIS AND CAPSTONE ARCHIVING SYSTEM</title>


    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://kit.fontawesome.com/067d14b27b.js" crossorigin="anonymous"></script>

    <link rel="shortcut icon" href="img/stiilogo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/index.css">

    <link rel="shortcut icon" href="images/stii-shorcut.jpg">

  <!-- Custom fonts for this template-->
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

  <!-- bootsrap-->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
  <link rel="stylesheet" href="https://unpkg.com/placeholder-loading/dist/css/placeholder-loading.min.css">

  <!-- Custom styles for this template-->
  <link href="Css/sb-admin-2.min.css" rel="stylesheet">
  <link href="Css/w3.css" rel="stylesheet">
  <link href="Css/custom.css" rel="stylesheet">

  <style>
    /* service Box 3====================================*/
    .serviceBox_3 {
      padding: 15px 15px 25px;
      margin: 60px auto 0;
      text-align: center;
      cursor: pointer;
      border-radius: 4px;
      background: #fff;
      border: 1px solid #4e73df;
      border-bottom-width: 3px;
      position: relative;
      height: 250px;
    }

    .serviceBox_3 .service-icon {
      width: 70px;
      height: 70px;
      line-height: 70px;
      border-radius: 4px;
      border: 1px solid #4e73df;
      background: #fff;
      color: #4e73df;
      margin: -48px auto 20px;
    }

    .serviceBox_3,
    .serviceBox_3 i,
    .serviceBox_3 p,
    .service-icon,
    .serviceBox_3 :after,
    .serviceBox_3 h3:before,
    .service-icon:after,
    .service-icon,
    .service-content {
      transition: all 0.5s ease-in-out;
    }

    .serviceBox_3 p {
      margin: 0 0 15px;
    }

    .serviceBox_3 h3 {
      font-size: 20px;
      font-weight: normal;
      letter-spacing: 0.7px;
      position: relative;
      margin: 20px 0;
      padding: 10px 0;
      background: none;
      overflow: hidden;
    }

    .serviceBox_3 h3:before {
      content: "";
      background: #4e73df;
      width: 0;
      height: 2px;
      position: absolute;
      bottom: 0;
      left: 50%;
    }

    .serviceBox_3 h3:after {
      content: "";
      background: #4e73df;
      width: 0;
      height: 2px;
      position: absolute;
      bottom: 0;
      right: 50%;
    }

    .serviceBox_3:hover h3:after,
    .serviceBox_3:hover h3:before {
      width: 100%;
    }

    .serviceBox_3 i {
      display: inline-block;
      font-size: 3em;
      line-height: 70px;
    }

    .serviceBox_3:hover .service-icon {
      background: #4e73df;
      color: #fff;
    }

    .serviceBox_3 .read {
      color: #727CB6;
    }

    .serviceBox_3 .read:hover {
      color: #4e73df;
    }
  </style>

</head>

<body style="margin: 0">
  <?php
  include_once("Includes/navbar.php");

  ?>

  <section class="mt-5 pt-5 pb-4 " style="background-color: white;" id="#">


  </section>


  <!-- View upload details Modal -->
  <?php


if(isset($_POST['delete_upload_btn'])){
    $studID2 = $_POST['upload_ID'];
    $query2 = "DELETE FROM upload WHERE upload_id = $studID2";
	$query_run2 = mysqli_query($db, $query2);

    mysqli_close($db);

}
?>

<!-- Begin Page Content -->
<div class="container-fluid">



    <!-- Room Tables -->
    <div class="card w3-white" style="margin-top: 10px; box-shadow: 0 1px 3px rgb(0 0 0 / 0.2);">

        <div class="">
            <div>
                <div class="d-flex justify-content-lg-between align-items-lg-baseline border-bottom-primary px-4 pt-3">
                    <p style="font-size: 1.4rem;" class="w3-left text-primary "><b>LIST OF THESIS AND CAPSTONE</b></p>

                    <div class="d-flex">
                    <div class="">
    <label for="department_filter" class="col-form-label m-0">Category:</label>
  </div>
  <div class="col-auto">
    <select id="department_filter" class="form-control form-select  " aria-label="Default select example" >
      <option value="">All Department</option>
      <!-- Populate dynamically from the database -->
      <?php
          $dept_query = "SELECT * FROM department";
          $dept_result = mysqli_query($db, $dept_query);
          while($dept = mysqli_fetch_assoc($dept_result)) {
              echo "<option value='{$dept['department_id']}'>{$dept['department_name']}</option>";
          }
      ?>
    </select>
  </div>

                        <input type="text" name="search_box" id="search_box" class="form-control" placeholder="Search..." />
                        <a href="signin_student_deposit">
    <button style="margin-left: 10px; width: 110px; height: 35px;  " type="button"  class="btn btn-primary fas fa-plus " data-bs-toggle="modal" data-bs-target="#exampleModal">
        NEW
    </button>
</a>
                         
                    </div>
                </div>


                <div class="px-3 py-3">
                    <?php
                    if (isset($_SESSION['studentMessage'])) {
                        echo $_SESSION['studentMessage'];
                        unset($_SESSION['studentMessage']);
                    }

                    ?>


                    <div class="table" id="dynamic_content">
                    </div>
                </div>

            </div>
            <!-- End room tables -->
      

            <!-- View upload details Modal -->
        <div id="view_upload_details_modal" class="modal fade" tabindex="-1" aria-labelledby="view_details_modal_reservation" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-dark" id="exampleModalLabel">INFO</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body" id="upload_details">

                    </div>
                </div>
            </div>
        </div>
        <!-- End of View student Details Modal -->

        


        <!-- Delete  Modal -->
        <div class="modal fade" id="delete_modal" tabindex="-1" aria-labelledby="deletemodal" aria-hidden="true">
            <div class="modal-dialog ">
                <div class="modal-content">

                    <form action="#" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="upload_ID" id="delete_stud_id">
                        <div class="modal-body px-4 w3-center">
                            <i class="fa fa-check text-gray-400 fa-3x py-3"></i>
                            <h4> Are you sure to delete the Thesis/Capstone?</h4>
                            <h4 class="text-warning">This action cannot be undone!</h4>
                        </div>
                        <div class="pb-4 w3-center">
                            <button type="button" class="btn btn-danger w3-text-white px-5" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_upload_btn" class="btn btn-primary px-5">Confirm</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>



            <?php
            include('./Includes/script.php');

            ?>




            <script>
                $(document).ready(function() {

                    load_data(1);

                    function load_data(page, query = '') {
                        $.ajax({
                            url: "fetch_upload.php",
                            method: "POST",
                            data: {
                                page: page,
                                query: query
                            },
                            success: function(data) {
                                $('#dynamic_content').html(data);
                            }
                        });
                    }


                    $(document).on('click', '.page-link', function() {
                        var page = $(this).data('page_number');
                        var query = $('#search_box').val();
                        load_data(page, query);
                    });



                    $('#search_box').keyup(function() {
                        var query = $('#search_box').val();
                        load_data(1, query);
                    });


                    $(document).on('click', '.editbtn', function() {
                        $('#editmodal').modal('show');

                        $tr = $(this).closest('tr');

                        var data = $tr.children("td").map(function() {
                            return $(this).text();
                        }).get();


                        console.log(data);
                        $('#deanID').val(data[0]);
                        $('#deanName').val(data[1]);
                        $('#deanUsername').val(data[2]);
                        $('#deanPassword').val(data[3]);

                    });

 


                    $(document).on('click', '.view_upload', function() {
                    var upload_id = $(this).attr("id");
                    if (upload_id != '') {
                        $.ajax({
                            url: "upload_details.php",
                            method: "POST",
                            data: {
                                upload_id: upload_id
                            },
                            success: function(data) {
                                $('#upload_details').html(data);
                                $('#view_upload_details_modal').modal('show');
                            }
                        });
                    }
                    });


                    $(document).on('click', '.confirm_btn', function(e) {
                    e.preventDefault();

                    var uploadID = $(this).closest('tr').find('.upload_id').text();
                    //console.log(staffid);
                    $('#confirm_stud_id').val(uploadID);
                    $('#confirmmodal').modal('show');
                    });


                    $(document).on('click', '.delete_btn', function(e) {
                    e.preventDefault();

                    var upload_ID = $(this).closest('tr').find('.upload_id').text();
                    //console.log(staffid);
                    $('#delete_stud_id').val(upload_ID);
                    $('#delete_modal').modal('show');
                    });




                });

                $(document).ready(function() {
                    $("#flash-msg").delay(2000).fadeOut("slow");
                });
            </script>

  <!-- Bootstrap core JavaScript-->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- Core plugin JavaScript-->
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

  <!-- Custom scripts for all pages-->
  <script src="js/sb-admin-2.min.js"></script>

  <!-- Bootstrap core JavaScript-->
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>




  <script>
    $(document).ready(function() {

      load_data(1);

      function load_data(page, query = '') {
        $.ajax({
          url: "fetch_Submission.php",
          method: "POST",
          data: {
            page: page,
            query: query
          },
          success: function(data) {
            $('#dynamic_content').html(data);
          }
        });
      }

      $(document).on('click', '.page-link', function() {
        var page = $(this).data('page_number');
        var query = $('#search_box').val();
        load_data(page, query);
      });


      $('#search_box').keyup(function() {
        var query = $('#search_box').val();
        load_data(1, query);
      });




      $(document).on('click', '.view_upload', function() {
        var upload_id = $(this).attr("id");
        if (upload_id != '') {
          $.ajax({
            url: "upload_details.php",
            method: "POST",
            data: {
              upload_id: upload_id
            },
            success: function(data) {
              $('#upload_details').html(data);
              $('#view_upload_details_modal').modal('show');
            }
          });
        }
      });

    });
  </script>
  <script>
    $(document).ready(function() {
        function load_data(page, query = '', department = '') {
            $.ajax({
                url: "fetch_upload.php",
                method: "POST",
                data: { page: page, query: query, department: department },
                success: function(data) {
                    $('#dynamic_content').html(data);
                }
            });
        }

        load_data(1);

        $(document).on('click', '.page-link', function() {
            var page = $(this).data('page_number');
            var query = $('#search_box').val();
            var department = $('#department_filter').val();
            load_data(page, query, department);
        });

        $('#search_box, #department_filter').on('keyup change', function() {
            var query = $('#search_box').val();
            var department = $('#department_filter').val();
            load_data(1, query, department);
        });

    });
</script>

  <script>
    $(document).ready(function() {

      



      function load_data(page, query = '') {
        $.ajax({
          url: "fetch_search_result.php",
          method: "POST",
          data: {
            page: page,
            query: query
          },
          success: function(data) {
            $('#search_result').html(data);
          }
        });
      }


      $('#search_box2').keyup(function() {
        var query = $('#search_box2').val();
        load_data(1, query);
      });


      $(document).on('click', '.view_upload', function() {
        var upload_id = $(this).attr("id");
        if (upload_id != '') {
          $.ajax({
            url: "upload_details.php",
            method: "POST",
            data: {
              upload_id: upload_id
            },
            success: function(data) {
              $('#upload_details').html(data);
              $('#view_upload_details_modal').modal('show');
            }
          });
        }
      });

       

      //prevents downloads
      $(document).on('click', '.download_btn', function(e) {
                e.preventDefault();

                var file = $(this).closest('tr').find('.pdf_file').text();
                 $('#file_name').val(file);
                $('#download_signin_modal').modal('show');
            });


            //
            $('#download_signin_modal').modal({
              backdrop: 'static',
              keyboard: false

            });

 

    });
  </script>

  