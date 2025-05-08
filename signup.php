<?php
session_start();
include_once("Includes/conn.php");
include("Includes/navbar.php");

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
    <title>STII TCAPS</title>

    <link rel="shortcut icon" href="./images/stii-shorcut.jpg">
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
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            margin: 0;
            background-image: url("images/stiibg1.jpg");
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            background-attachment: fixed;

        }
        .card {
            opacity: 0.9;
        }
        h2 {
            font-family: fantasy;
        }
        
    </style>
</head>

<body style="margin: 0">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-4"></div>
            <div class="col-lg-4">
                <div class="card shadow text-gray-700" style="margin-top: 150px;">
                    <?php
                    if (isset($_SESSION['SignupMessage'])) {
                        echo $_SESSION['SignupMessage'];
                        unset($_SESSION['SignupMessage']);
                    }
                    ?>
                    <div class="card-header text-primary">
                        <h2 style="text-align: center;">SIGNUP</h2>
                    </div>
                    <div class="mb-3">
    <label class="text-dark font-weight-bold">Signup as</label>
    <select id="roleSelect" class="form-select mb-2" onchange="switchForm()">
        <option value="student">Student</option>
        <option value="teacher">Teacher</option>
        <option value="coordinator">Coordinator</option>
    </select>
</div>
<div id="studentForm">

                    <div class="card-body">
                        <form action="Includes/server.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="text-dark font-weight-bold">First name</label>
                                <input type="text" name="student_name" class="text-gray-700 form-control" placeholder="" required>
                            </div>
                            <div class="mb-3">
                                <label class="text-dark font-weight-bold">Middle name</label>
                                <input type="text" name="student_middlename" class="text-gray-700 form-control" placeholder="(Optional)">
                            </div>
                            <div class="mb-3">
                                <label class="text-dark font-weight-bold">Last name</label>
                                <input type="text" name="student_lastname" class="text-gray-700 form-control" placeholder="" required>
                            </div>
                            <div class="mb-3">
                                <label class="text-dark font-weight-bold">Suffix</label>
                                <select name="student_suffix" class="text-gray-700 form-select">
                                    <option disabled selected hidden>Select Suffix (Optional)</option>
                                    <option value="Sr.">Sr.</option>
                                    <option value="Jr.">Jr.</option>
                                    <option value="3rd">III</option>
                                    <option value="2nd">II</option>
                                </select>
                            </div>
                            <!-- <div class="mb-3">
                                <label class="text-dark font-weight-bold">Course & Year</label>
                                <input type="text" name="student_section" class="text-gray-700 form-control" placeholder="" required>
                            </div> -->

                            <div class="mb-3">
    <label class="text-dark font-weight-bold">Program</label>
    <select name="student_section" class="text-gray-700 form-select" id="programSelect" required>
        <option disabled selected hidden>Select Program</option>
        <?php
        include("../Includes/conn.php");
        $dean_query = "SELECT * FROM courses";
        $run_dean_query = mysqli_query($db, $dean_query);
        if(mysqli_num_rows($run_dean_query) > 0){
            while ($row = mysqli_fetch_array($run_dean_query)) {
                echo '<option value="'.$row["course_name"].'">'.$row['course_name'].'</option>';
            }
        }
        ?>
        <option value="other">Other (Specify)</option>
    </select>
    <!-- Hidden input for custom "Other" course if selected -->
    <input type="text" name="student_section_s" class="text-gray-700 form-control mt-2" id="programInput" placeholder="Enter Program" style="display: none;">
</div>


<script>
    // Show input field if "Other" is selected
    document.getElementById('programSelect').addEventListener('change', function() {
        var selectValue = this.value;
        var inputField = document.getElementById('programInput');

        if (selectValue === 'other') {
            inputField.style.display = 'block'; // Show input field for "Other"
        } else {
            inputField.style.display = 'none'; // Hide input field when another program is selected
        }
    });

    // Ensure the correct value is sent when the form is submitted
    $('form').submit(function() {
        var programSelectValue = $('#programSelect').val(); // Value from dropdown
        var programInputValue = $('#programInput').val(); // Value from text input

        // If 'Other' is selected and there's input in the text field, override the select value
        if (programSelectValue === 'other' && programInputValue) {
            $('#programSelect').val(programInputValue); // Override select value with input value
        }

        // If no input for 'Other', you can either set the value to an empty string or the default value
        if (programSelectValue === 'other' && !programInputValue) {
            $('#programSelect').val(''); // Optionally set to empty if no input is given
        }
    });
</script>


                            <div class="mb-3">
                                <label class="text-dark font-weight-bold">Department</label>
                                <select name="student_department" class="text-gray-700 form-select" required>
                                    <option disabled selected hidden>Select Department</option>
                                    <?php
                                    include("../Includes/conn.php");
                                    $dean_query = "SELECT * FROM department WHERE status = 'active'";
                                    $run_dean_query = mysqli_query($db, $dean_query);
                                    if(mysqli_num_rows($run_dean_query) > 0){
                                        while ($row = mysqli_fetch_array($run_dean_query)) {
                                            $out = '<option value="'.$row["department_id"].'"> '.$row['department_name'].'</option>';
                                            echo $out;
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="text-dark font-weight-bold">Email address</label>
                                <input type="email" name="student_username" class="text-gray-700 form-control" placeholder="" required>
                            </div>
                            <div class="mb-3">
                                <label class="text-dark font-weight-bold">Password</label>
                                <input type="password" name="student_password" class="text-gray-700 form-control" placeholder=" " required>
                            </div>
                            <div class="mb-3">
                                <label class="text-dark font-weight-bold">Upload Profile Picture</label>
                                <input type="file" name="profile_image" class="text-gray-700 form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="text-dark font-weight-bold">Identity Confirmation(School Id)</label>
                                <input type="file" name="id_image" class="text-gray-700 form-control" required>
                            </div>
                            <input type="hidden" name="student_status" value="Pending">
                            <input type="submit" name="student_signup" value="Signup" class="btn btn-primary col py-2">
                            <div class="w3-center pb-5">
                                <h5>Already have an account? <a href="signin_student">Signin</a></h5>
                            </div>
                            
                        </form>
                    </div>
                    
                </div>
            </div>
            <div class="col-lg-4"></div>
        </div>
    </div>
    </div>
    <section>
        <div class="px-5"><div class="px-5"></div></div>
    </section>
<!-- Teacher Signup Form -->
<div id="teacherForm" style="display: none;">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-4"></div>
            <div class="col-lg-4">
                <div class="card shadow text-gray-700" style="margin-top: 0px;">
                    <div class="card-body">
                        <form action="Includes/server.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="text-dark font-weight-bold">Full Name</label>
                                <input type="text" name="teacher_name" class="text-gray-700 form-control" placeholder="Full Name" required>
                            </div>
                            <div class="mb-3">
                                <label class="text-dark font-weight-bold">Username</label>
                                <input type="text" name="teacher_username" class="text-gray-700 form-control" placeholder="Full Name" required>
                            </div>
                            <div class="mb-3">
                                <label class="text-dark font-weight-bold">Email Address</label>
                                <input type="email" name="teacher_email" class="text-gray-700 form-control" placeholder="Email Address" required>
                            </div>
                            <div class="mb-3">
                                <label class="text-dark font-weight-bold">Password</label>
                                <input type="password" name="teacher_password" class="text-gray-700 form-control" placeholder="Password" required>
                            </div>
                            <div class="mb-3">
                                <label class="text-dark font-weight-bold">Department</label>
                                <select name="teacher_department" class="text-gray-700 form-select" required>
                                    <option disabled selected hidden>Select Department</option>
                                    <?php
                                    // Fetch active departments from the database
                                    $dean_query = "SELECT * FROM department WHERE status = 'active'";
                                    $run_dean_query = mysqli_query($db, $dean_query);
                                    if (mysqli_num_rows($run_dean_query) > 0) {
                                        while ($row = mysqli_fetch_array($run_dean_query)) {
                                            echo '<option value="' . $row["department_id"] . '">' . $row['department_name'] . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="text-dark font-weight-bold">Identity Picture</label>
                                <input type="file" name="id_image" class="text-gray-700 form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="text-dark font-weight-bold">Upload Profile Picture</label>
                                <input type="file" name="profile_image" class="text-gray-700 form-control" required>
                            </div>
                            <input type="hidden" name="access" value="Teacher">
                            <input type="submit" name="teacher_signup" value="Signup" class="btn btn-primary col py-2">
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-4"></div>
        </div>
    </div>
</div>


    <div id="coordinatorForm" style="display: none;">

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-4"></div>
            <div class="col-lg-4 ">
                <div class="card shadow text-gray-700" style="margin-top: 0px;">
                    <div class="card-body">
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
<script>
    function switchForm() {
        const role = document.getElementById("roleSelect").value;
        if (role === "coordinator") {
            window.location.href = "coordinator_signup.php"; // Replace with the actual URL of your coordinator signup form
        }
    }
</script>


<script>
function switchForm() {
    const role = document.getElementById("roleSelect").value;
    const studentForm = document.getElementById("studentForm");
    const coordinatorForm = document.getElementById("coordinatorForm");
    const teacherForm = document.getElementById("teacherForm");

    if (role === "student") {
        studentForm.style.display = "block";
        coordinatorForm.style.display = "none";
        teacherForm.style.display = "none";
    } else if (role === "coordinator") {
        studentForm.style.display = "none";
        coordinatorForm.style.display = "block";
        teacherForm.style.display = "none";
    } else if (role === "teacher") {
        studentForm.style.display = "none";
        coordinatorForm.style.display = "none";
        teacherForm.style.display = "block";
    }
}

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
        $("#flash-msg").delay(2000).fadeOut("slow");
    });
</script>
