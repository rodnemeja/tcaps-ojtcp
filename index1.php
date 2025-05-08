<?php

include_once("Includes/conn.php");


if (isset($_SESSION['dean_id'])) {

  echo "<script>window.location.href='Research_Coordinator/index';</script>";
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

            $usernameErr = "Email not registered";
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
  <link rel="shortcut icon" href="images/stii-shorcut.jpg">

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
  include_once("Includes/navbar1.php");

  ?>

  <!-- View upload details Modal -->
  <div id="view_upload_details_modal" class="modal fade" tabindex="-1" aria-labelledby="view_details_modal_reservation" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title text-gray-700" id="exampleModalLabel">THESIS AND CAPSTONE ARCHIVING SYSTEM</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body" id="upload_details">

        </div>
      </div>
    </div>
  </div>
  <!-- End of View student Details Modal -->

    <!-- signin Modal -->
    <div id="download_signin_modal" class="modal fade" tabindex="-1" aria-labelledby="view_details_modal_reservation" aria-hidden="true">
    <div class="modal-dialog  modal-dialog-centered">
      <div class="modal-content shadow-lg   border-info">
        <div class="modal-header">
          <h5 class="modal-title text-gray-700" id="exampleModalLabel">You must login to continue</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        
        <div>                         

        <div class="card shadow text-gray-700">
                    <div class="card-body">
                         <form action="" method="POST">
                          <input type="hidden" id="file_name" name="fname" class="text-gray-700 form-control" />
                                <div class="mb-3">
                                    <span class="text-danger"><?php echo $usernameErr; ?></span>
                                    <input type="text" name="username" value="<?php echo $username; ?>" class="text-gray-700 form-control" placeholder="Email" required>
                                </div>
                                

                                <div class="mb-3">
                                <span class="text-danger"><?php echo $passwordErr; ?></span>
                                    <input type="password" name="password" value="" class="text-gray-700 form-control" placeholder="Password" required>
                                </div>
                                

                            
                            <input type="submit" onclick="$('#download_signin_modal').modal({'backdrop': 'static'});" name="btnlogin" value="Login" class="pass_data btn btn-primary col" id="<?php $row["student_username"]; ?>">

                        </form>
                    </div>
                </div>
        </div>
      </div>
    </div>
  </div>
  <!-- signin Modal -->


  <!--start info service-->
  <section class="pb-5 pt-3">
    <div class="container pt-5 pb-5">
      <div class="row pt-1 pb-1">
      <div class="row main-content-grid">
            <div class="ursbanner-container col s12 l9 hide-on-down">
                <img src="images/stiibanner.jpg" alt="stii-banner" class="stiibanner">
            </div>
            <div class="row ursvmco">
            <div class="col s12 m10 l6 offset-m1 left brand-text center text-dark">
                <h3 class="brand-title">BRAND</h2>
                    <p class="brand-content">"The School That Cares For Your Future."</p>
            </div>
            <div class="col s12 m10 l6 offset-m1 left vision-text center text-dark">
                <h3 class="vision-title">VISION</h2>
                    <p class="vision-content">A leading educational Institution that advocate holistic transformational development  for global competitiveness</p>
            </div>
            <div class="col s12 m10 l6 offset-m1 left mission-text center text-dark">
                <h3 class="mission-title">MISSION</h2>
                    <p class="mission-content">To provide responsive, relevant and innovative education and training that equip students with the knowledge, attributes, values and skills to become successful in their chosen career and meet the demands of the national and global industry.
                    </p>
            </div>
            <div class="col s12 m10 l6 offset-m1 left core-values-text center text-dark">
                <h3 class="core-values-title">CORE VALUES</h2>
                    <p><span class="core-values-acro">S</span> <span class="core-values-content">- Servant Leadership </span></p>
                    <p><span class="core-values-acro">I</span> <span class="core-values-content">- Innovativeness</span></p>
                    <p><span class="core-values-acro">C</span> <span class="core-values-content">- Collaboration</span></p>
                    <p><span class="core-values-acro">A</span> <span class="core-values-content">- Adaptability </span></p>
                    <p><span class="core-values-acro">T</span> <span class="core-values-content">- Trustworthiness </span></p>
                    <p><span class="core-values-acro">C</span> <span class="core-values-content">- Compassion </span></p>
                    <p><span class="core-values-acro">A</span> <span class="core-values-content">- Academic Excellence</span></p>
            </div>
        </div>
    </div>

    </div>


      </div>

    </div>
  </section>

<section id="Abouts" >
  <div class="content ">
<div class="container mt-4">
<div class="card shadow p-3 mb-5 bg-white rounded" style="z-index:1;border-top:2px solid blue">
  <div class="card-header">
    <h3>ABOUT</h3>
  </div>
  <div class="card-body ">
    <div class="container">

        <div class="row gx-1">
            <div class="col-md-4">
             <div class="card" style="border-top:2px solid blue">
             <div class="card-body">

                <h2 class="h5 mb-4 mt-3"> Contact</h2>
                <p class="mb-0 w-100 mt-4"><i class="fa fa-envelope"></i> Email Address: sibugaytech@gmail.com<br><br>
                <i class="fa fa-phone"></i> Contact #: (062) 333-2469 | 09184873846 / 0917707304<br><br>
                <i class="fa fa-map-marker"></i> Address #: Lower Taway, Ipil, Zamboanga Sibugay.
            </p>
            </div>

            </div>
            </div>
            <div class="col-md-8">

            <div class="card" style="border-top:2px solid blue">
             <div class="card-body">

                <div class="feature bg-primary bg-gradient text-white rounded-3 mb-3"><i class="bi bi-building"></i></div>
                <h2 class="h5 mb-4">✨About system ?</h2>
               <p class="mb-0 w-100" style="text-indent: 50px;">
                Archiving platform  for research ( Faculty Research’, Capstone, Thesis, ) of faculty and students at Sibugay Technical Institute Incorporated is a digital repository for easy access by the researchers. This study aims to have the school safer and more accurate digital storage for the completed thesis and capstone projects  because such projects are always vital and should be kept for future references. This project ensures a smooth, easy, quick, and effortlessly searching and viewing of information students need. This platform guarantees that all documents, data, and information stored for long-term retention are protected, easy to track,preserve and always available when needed.
               </p>
            </div>
            </div>



        </div>


    </div>


  </div>
</div>
</section>

<script>
window.onscroll = function() {myFunction()};

var navbar = document.getElementById("navbar");
var sticky = navbar.offsetTop;

function myFunction() {
  if (window.pageYOffset >= sticky) {
    navbar.classList.add("sticky")
  } else {
    navbar.classList.remove("sticky");
  }
}
</script>




<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js" integrity="sha384-QJHtvGhmr9XOIpI6YVutG+2QOK9T+ZnN4kzFN1RtK3zEFEIsxhlmWl5/YESvpZ13" crossorigin="anonymous"></script>
<script src="https://archiving-system-web.mywebapp.online/assets/js/jquery.min.js"></script>
 <script type="text/javascript">
    $(window).on('load', function(){
        //you remove this timeout
        setTimeout(function(){
            $('#loader').fadeOut('slow');
        });

    });
  </script>

<script>
  ClassicEditor
        .create( document.querySelector( '#editor'), {
        } )
        .then( editor => {
            const toolbarElement = editor.ui.view.toolbar.element;
            toolbarElement.style.display = 'none';
            editor.enableReadOnlyMode( 'my-feature-id' );
        } )
        .catch( err => {
            console.error( err.stack );
        } );
</script>
<script>
  ClassicEditor
        .create( document.querySelector( '#editor2'), {
        } )
        .then( editor => {
            const toolbarElement = editor.ui.view.toolbar.element;
            toolbarElement.style.display = 'none';
            editor.enableReadOnlyMode( 'my-feature-id' );
        } )
        .catch( err => {
            console.error( err.stack );
        } );
</script>
<script>
  ClassicEditor
        .create( document.querySelector( '#editor3'), {
        } )
        .then( editor => {
            const toolbarElement = editor.ui.view.toolbar.element;
            toolbarElement.style.display = 'none';
            editor.enableReadOnlyMode( 'my-feature-id' );
        } )
        .catch( err => {
            console.error( err.stack );
        } );
</script>
<script>
  ClassicEditor
        .create( document.querySelector( '#editor4'), {
        } )
        .then( editor => {
            const toolbarElement = editor.ui.view.toolbar.element;
            toolbarElement.style.display = 'none';
            editor.enableReadOnlyMode( 'my-feature-id' );
        } )
        .catch( err => {
            console.error( err.stack );
        } );
</script>




</div></div></div>



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

  