<?php
 include_once("Includes/conn.php");
include_once("Includes/header.php");

include_once("Includes/coordinator_login_nav.php");

if (isset($_SESSION['dean_id'])) {
     
    echo "<script>window.location.href='Research_Coordinator/index';</script>";
    
}


$username = $password = "";
$usernameErr = $passwordErr = "";

if (isset($_POST['btnlogin'])) {
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

        $check_username = mysqli_query($db, "Select * from dean where dean_username = '$username'");
        $check_username_row = mysqli_num_rows($check_username);

        if ($check_username_row > 0) {
            $row = mysqli_fetch_assoc($check_username);
            $db_password = $row["dean_password"];
             $user_id = $row["dean_id"];
            $name = $row['dean_name'];

            if ($password == $db_password) {
 
                    echo "<script>window.location.href='Research_Coordinator/index';</script>";

                    $_SESSION['dean_id'] = $user_id;
                     $_SESSION['dean_name'] = $name;
                   
                
            } else {

                $passwordErr = "Invalid Username or Password!";
            }
        } else {

            $usernameErr = "Invalid Username or Password!";
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

    <title> STII | THESIS / CAPSTONE ARCHIVING SYSTEM</title>

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
    <link rel="shortcut icon" href="Images/stii-shorcut.jpg">

    <style>
    body {
      margin: 0;
      background-image: url("images/stiibg1.jpg");
      background-size: cover;
      background-repeat: no-repeat;
      background-position: center;
    }
    .card {
  opacity: 0.9; 
}
h3{
    font-family:fanstasy;
}
  </style>

</head>

<body style="margin: 0">
    <div class="container">
        <div class="row">
            <div class="col-lg-4"></div>
            <div class="col-lg-4">
                <div class="card shadow text-gray-700" style="margin-top: 180px;">
                    <div class="card-body">
                        <center> 
                            <img src="images/stiilogo.png" alt="Login Image" style="width: 130px; height: 130px;">
                            <h3 class="text-primary">COORDINATOR</h3>
                        </center>
                        <form action="" method="POST">
                            <div class="mb-3">
                                <span class="text-danger"><?php echo $usernameErr; ?></span>
                                <input type="text" name="username" value="<?php echo $username; ?>" class="text-gray-700 form-control" placeholder="Username" required>
                            </div>

                            <div class="mb-3">
                                <span class="text-danger"><?php echo $passwordErr; ?></span>
                                <input type="password" name="password" value="" class="text-gray-700 form-control" placeholder="Password" required>
                            </div>

                            <input type="submit" name="btnlogin" value="Login" class="pass_data btn btn-primary col" id="<?php echo $row["dean_username"]; ?>">

                            <div class="text-primary">
                                <!-- <h6><a href="forgotpass" >Forgot Password?</a></h6> -->
                                <a href="forgot-password/forgot_password.php" class="forgot-password">Forgot Password?</a>
                            </div>

                            <div class="w3-center">
                                <p><h5>Don't have an account? <a href="signup_coordinator">Signup</a></h5></p>
                            </div>
                            <div class="text-center" style="margin-top: 20px;">
            <a href="index.php" class="btn btn-secondary">Go to Website</a>
        </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-4"></div>
        </div>

    </div>
</body>


</html>


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
