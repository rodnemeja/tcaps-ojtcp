<?php
include_once("./Includes/conn.php");
include("Includes/navbar.php");

$username = $password = "";
$usernameErr = $passwordErr = $error = $successMessage = "";

// Process login form when submitted
if (isset($_POST['btnlogin'])) {
    if (empty($_POST['username'])) {
        $usernameErr = "Username is required!";
    } else {
        $username = mysqli_real_escape_string($db, $_POST['username']);
    }

    if (empty($_POST['password'])) {
        $passwordErr = "Password is required!";
    } else {
        $password = mysqli_real_escape_string($db, $_POST['password']);
    }

    if ($username && $password) {
        // Query to check user in the 'user' table
        $userQuery = mysqli_query($db, "SELECT * FROM user WHERE username = '$username'");
        
        // Query to check student in the 'student' table
        $studentQuery = mysqli_query($db, "SELECT * FROM student WHERE student_username = '$username'");
        
        // Query to check dean in the 'dean' table
        $deanQuery = mysqli_query($db, "SELECT * FROM dean WHERE dean_username = '$username'");
        
        // Query to check teacher in the 'teacher' table
        $teacherQuery = mysqli_query($db, "SELECT * FROM teacher WHERE teacher_username = '$username'");

        // Check if the user is found in any of the tables
        if (mysqli_num_rows($userQuery) > 0) {
            $row = mysqli_fetch_assoc($userQuery);
            $db_password = $row["password"];
            $accessType = $row["access"];
            $user_id = $row["id"];
            $name = $row['name'];

            if ($password == $db_password) {
                $_SESSION['id'] = $user_id;
                $_SESSION['name'] = $name;

                if ($accessType == "Administrator") {
                    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                    echo "<script>
                        Swal.fire({
                            title: 'Login as Librarian',
                            text: 'Click ok button to proceed',
                            icon: 'success',
                            showConfirmButton: true,
                            timer: 5000
                        }).then(() => {
                            window.location.href = 'Admin/index.php';
                        });
                    </script>";
                }
            } else {
                $usernameErr = "Invalid email/username or password. Please try again.";
            }

        } elseif (mysqli_num_rows($studentQuery) > 0) {
            $row = mysqli_fetch_assoc($studentQuery);
            $db_password = $row["student_password"];
            $status = $row['student_status'];
            $user_id = $row["student_id"];
            $name = $row['student_name'];

            if ($password == $db_password) {
                $_SESSION['student_id'] = $user_id;

                if ($status == "Approved" || $status == "Pending" ) {
                    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                    echo "<script>
                        Swal.fire({
                            title: 'Login Successful',
                            text: 'Welcome, Enjoy Searching!',
                            icon: 'success',
                            showConfirmButton: true,
                            timer: 10000
                        }).then(() => {
                            window.location.href = 'index1.php';
                        });
                    </script>";
                } else {
                    $error = "Your account has been disapproved by the admin. You cannot login at this time.";
                }
            } else {
                $usernameErr = "Invalid email/username or password. Please try again.";
            }

        } elseif (mysqli_num_rows($deanQuery) > 0) {
            $row = mysqli_fetch_assoc($deanQuery);
            $db_password = $row["dean_password"];
            $user_id = $row["dean_id"];
            $name = $row['dean_name'];

            if ($password == $db_password) {
                $_SESSION['dean_id'] = $user_id;
                $_SESSION['dean_name'] = $name;

                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                echo "<script>
                    Swal.fire({
                        title: 'Login as Research Coordinator',
                        text: 'Welcome, Research Coordinator',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        window.location.href = 'Research_Coordinator/index.php';
                    });
                </script>";
            } else {
                $usernameErr = "Invalid email/username or password. Please try again.";
            }

        } elseif (mysqli_num_rows($teacherQuery) > 0) {
            // Teacher login
            $row = mysqli_fetch_assoc($teacherQuery);
            $db_password = $row["teacher_password"];
            $user_id = $row["teacher_id"];
            $name = $row['teacher_name'];
            $access = $row['access'];  // Always "Teacher"

            if ($password == $db_password) {
                $_SESSION['teacher_id'] = $user_id;
                $_SESSION['teacher_name'] = $name;

                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                echo "<script>
                    Swal.fire({
                        title: 'Login Successful',
                        text: 'Welcome, Teacher',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        window.location.href = 'index1.php';
                    });
                </script>";
            } else {
                $usernameErr = "Invalid email/username or password. Please try again.";
            }

        } else {
            $error = "Invalid email/username or password. Please try again.";
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
    <title>STII TCAPS</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="Css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        body { background-image: url("images/stiibg1.jpg"); background-size: cover; background-position: center; }
        .card { opacity: 0.9; margin-top: 150px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card shadow text-gray-700">
                <div class="card-body text-center">
                    <img src="images/stiilogo.png" alt="Login Image" style="width: 150px; height: 150px;">
                    <h3 class="text-primary">LOGIN</h3>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php elseif ($usernameErr): ?>
                        <div class="alert alert-danger"><?php echo $usernameErr; ?></div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" class="form-control" placeholder="Email or Username" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control" placeholder="Password" required>
                        </div>
                        <button type="submit" name="btnlogin" class="btn btn-primary col py-2 ">Login</button>
                    </form>

                    <div class="mt-2">
                        <a href="forgot-password/forgot_password.php" class="text-primary">Forgot Password?</a>
                    </div>
                    <div class="mt-2">
                        <h5>Don't have an account? <a href="signup">Signup</a></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
