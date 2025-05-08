<?php
include_once("../Includes/conn.php");
include_once("../Includes/navbar1.php");

if (!isset($_SESSION['student_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}

$student_id = $_SESSION['student_id'];
$get_record = mysqli_query($db, "SELECT * FROM student WHERE student_id = '$student_id'");
$row = mysqli_fetch_assoc($get_record);

if (!$row) {
    echo "Student not found!";
    exit();
}

if (isset($_POST['student_update'])) {
    $stud_name = $_POST['student_name'];
    $stud_middlename = $_POST['student_middlename'];
    $stud_lastname = $_POST['student_lastname'];
    $stud_suffix = $_POST['student_suffix'];
    $stud_section = $_POST['student_section'];
    $stud_department = $_POST['student_department'];
    $stud_username = $_POST['student_username'];
    $stud_password = $_POST['student_password'];
    $confirm_password = $_POST['confirm_password'];
    $stud_status = $row['student_status'];

    if ($stud_password !== $confirm_password) {
        $_SESSION['UpdateMessage'] = '
            <div class="alert alert-danger alert-dismissable" id="flash-msg">
                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                <h5>Passwords do not match!</h5>
            </div>
        ';
        echo "<script>window.location.href='edit_student.php';</script>";
        exit();
    }

    $profile_image = $row['profile_image'];
    $id_image = $row['id_image'];

    if ($_FILES["profile_image"]["name"]) {
        $target_dir = "Upload/";
        $profile_image = $target_dir . basename($_FILES["profile_image"]["name"]);
        move_uploaded_file($_FILES["profile_image"]["tmp_name"], $profile_image);
    }

    if ($_FILES["id_image"]["name"]) {
        $target_dir = "Upload/";
        $id_image = $target_dir . basename($_FILES["id_image"]["name"]);
        move_uploaded_file($_FILES["id_image"]["tmp_name"], $id_image);
    }

    $stud_update = "UPDATE student SET 
        student_name='$stud_name', 
        student_middlename='$stud_middlename', 
        student_lastname='$stud_lastname', 
        student_suffix='$stud_suffix', 
        student_section='$stud_section', 
        student_department='$stud_department', 
        student_username='$stud_username', 
        student_password='$stud_password', 
        profile_image='$profile_image', 
        id_image='$id_image' 
        WHERE student_id='$student_id'";

    $run_stud_query = mysqli_query($db, $stud_update);

    if ($run_stud_query) {
        $_SESSION['UpdateMessage'] = '
            <div class="alert alert-success alert-dismissable" id="flash-msg">
                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                <h5>Update Successfully!</h5>
            </div>
        ';
        echo "<script>window.location.href='./index1';</script>";
    } else {
        $_SESSION['UpdateMessage'] = '
            <div class="alert alert-danger alert-dismissable" id="flash-msg">
                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                <h5>Some error occurred, please try again!</h5>
            </div>
        ';
        echo "<script>window.location.href='edit_student.php';</script>";
    }
    mysqli_close($db);
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
    <title>STII | TCAPS</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link rel="shortcut icon" href="images/stii-shorcut.jpg">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/placeholder-loading/dist/css/placeholder-loading.min.css">
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
        .container {
            padding-top: 100px; /* Adjust as needed for spacing */
        }
        .card {
            margin: 0 auto; /* Center align the card */
            max-width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-sm-12">
                <div class="card shadow text-dark-700">
                    <div class="card-body">
                        <center>
                            <img src="images/stiilogo.png" alt="Login Image" style="width: 150px; height: 150px;">
                            <h3 class="text-primary">EDIT STUDENT</h3>
                        </center>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <input type="text" name="student_name" value="<?php echo $row['student_name']; ?>" class="form-control" placeholder="First Name" required>
                            </div>
                            <div class="mb-3">
                                <input type="text" name="student_middlename" value="<?php echo $row['student_middlename']; ?>" class="form-control" placeholder="Middle Name">
                            </div>
                            <div class="mb-3">
                                <input type="text" name="student_lastname" value="<?php echo $row['student_lastname']; ?>" class="form-control" placeholder="Last Name" required>
                            </div>
                            <div class="mb-3">
                                <input type="text" name="student_suffix" value="<?php echo $row['student_suffix']; ?>" class="form-control" placeholder="Suffix">
                            </div>
                            <div class="mb-3">
                                <input type="text" name="student_section" value="<?php echo $row['student_section']; ?>" class="form-control" placeholder="Section" required>
                            </div>
                            <div class="mb-3">
                                <input type="text" name="student_department" value="<?php echo $row['student_department']; ?>" class="form-control" placeholder="Department" required>
                            </div>
                            <div class="mb-3">
                                <input type="text" name="student_username" value="<?php echo $row['student_username']; ?>" class="form-control" placeholder="Username" required>
                            </div>
                            <div class="mb-3">
                                <input type="password" name="student_password" class="form-control" placeholder="Password" required>
                            </div>
                            <div class="mb-3">
                                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                            </div>
                            <div class="mb-3">
                                <input type="file" name="profile_image" class="form-control">
                            </div>
                            <div class="mb-3">
                                <input type="file" name="id_image" class="form-control">
                            </div>
                            <button type="submit" name="student_update" class="btn btn-primary col">Update</button>
                        </form>
                        <?php
                        if (isset($_SESSION['UpdateMessage'])) {
                            echo $_SESSION['UpdateMessage'];
                            unset($_SESSION['UpdateMessage']);
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

