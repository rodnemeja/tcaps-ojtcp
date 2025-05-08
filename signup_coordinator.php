<?php
 include_once("Includes/conn.php");
include_once("Includes/header.php");
include_once("Includes/coordinator_login_nav.php");

if (isset($_SESSION['dean_id'])) {
    echo "<script>window.location.href='Research_Coordinator/index';</script>";
}

$username = $password = $name = $email = "";
$usernameErr = $passwordErr = $nameErr = $emailErr = "";

if (isset($_POST['deanSave'])) {
    if (empty($_POST['deanUsername'])) {
        $usernameErr = "Username is Required!";
    } else {
        $username = $_POST['deanUsername'];
    }

    if (empty($_POST['deanPassword'])) {
        $passwordErr = "Password is Required!";
    } else {
        $password = $_POST['deanPassword'];
    }

    if (empty($_POST['deanName'])) {
        $nameErr = "Name is Required!";
    } else {
        $name = $_POST['deanName'];
    }

    if (empty($_POST['deanEmail'])) {
        $emailErr = "Email is Required!";
    } else {
        $email = $_POST['deanEmail'];
    }

    

    // if ($username && $password && $name && $email) {
    //     // Check if username already exists
    //     $check_username = mysqli_query($db, "SELECT * FROM dean WHERE dean_username = '$username'");
    //     $check_username_row = mysqli_num_rows($check_username);

    //     if ($check_username_row == 0) {
    //         $insert_user = mysqli_query($db, "INSERT INTO dean (dean_username, dean_password, dean_name, dean_email) VALUES ('$username', '$password', '$name', '$email')");
    //         if ($insert_user) {
    //             echo "<script>alert('Signup successful! Please login.');</script>";
    //             echo "<script>window.location.href='signin_dean.php';</script>";
    //         } else {
    //             echo "<script>alert('Error during signup. Please try again.');</script>";
    //         }
    //     } else {
    //         $usernameErr = "Username already exists!";
    //     }
    // }
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
    <title>STII | THESIS / CAPSTONE ARCHIVING SYSTEM</title>
    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <!-- Bootstrap-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/placeholder-loading/dist/css/placeholder-loading.min.css">
    <!-- Custom styles for this template-->
    <link href="Css/sb-admin-2.min.css" rel="stylesheet">
    <link href="Css/w3.css" rel="stylesheet">
    <link href="Css/custom.css" rel="stylesheet">
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
        h3 {
            font-family: fantasy;
        }
    </style>
</head>

<body style="margin: 0">
    <div class="container">
        <div class="row">
            <div class="col-lg-4"></div>
            <div class="col-lg-4 ">
                <div class="card shadow text-gray-700" style="margin-top: 180px;">
                    <div class="card-body">
                        <center>
                            <img src="images/stiilogo.png" alt="Signup Image" style="width: 130px; height: 130px;">
                            <h3 class="text-primary">COORDINATOR SIGNUP</h3>
                        </center>
                        <?php
                    if (isset($_SESSION['deanMessage'])) {
                        echo $_SESSION['deanMessage'];
                    }

                    ?>
                        <form action="Includes/server.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <span class="text-danger"><?php echo $usernameErr; ?></span>
                                <input type="text" name="deanUsername" value="<?php echo $username; ?>" class="text-gray-700 form-control" placeholder="Username" required>
                            </div>
                            <div class="mb-3">
                                <span class="text-danger"><?php echo $passwordErr; ?></span>
                                <input type="password" name="deanPassword" value="" class="text-gray-700 form-control" placeholder="Password" required>
                            </div>
                            <div class="mb-3">
                                <span class="text-danger"><?php echo $nameErr; ?></span>
                                <input type="text" name="deanName" value="<?php echo $name; ?>" class="text-gray-700 form-control" placeholder="Name" required>
                            </div>
                            <div class="mb-3">
                                <span class="text-danger"><?php echo $emailErr; ?></span>
                                <input type="email" name="deanEmail" value="<?php echo $email; ?>" class="text-gray-700 form-control" placeholder="Email" required>
                            </div>
<div class="modal-body px-4">
    <input type="hidden" name="departmentID">

    <div class="mb-3">
        <label>Department</label>
        <select name="deanDepartment"  class="form-select">
            <option disabled selected hidden>Select Department</option>
            <?php 
             include('./Admin/select_option_dean.php')
            ?>
        </select>
     </div>
                            <input type="submit" name="deanSave" value="Signup" class="pass_data btn btn-primary col">
                            <div class="text-primary">
                                <a href="signin_dean" class="forgot-password">Already have an account? Login</a>
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
