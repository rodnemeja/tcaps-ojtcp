<?php

if (!isset($_SESSION)) {
	session_start();
}

include("conn.php");

//department ----------------------------------------------------

if (isset($_POST['deparmentSave'])) {
     
	$deparmentName = $_POST['departmentName'];
	 
 
	$query = "INSERT INTO department (department_name) VALUES ('$deparmentName')";
	$query_run = $query_run = mysqli_query($db, $query);
	if ($query_run) {
		$_SESSION['departmentMessage'] = '
			
		<div class="alert alert-success alert-dismissable" id="flash-msg">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h5>Department added successfully!</h5>
		</div>
	
	';

    echo "<script>window.location.href='../Admin/department';</script>";

	} else {
		$_SESSION['departmentMessage'] = '
		<div class="alert alert-danger alert-dismissable" id="flash-msg">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h5>Some error occured, please try again!</h5>
		</div>
	';
		echo "<script>window.location.href='../Admin/department';</script>";
	}
	mysqli_close($db);
}


if (isset($_POST['departmentEdit'])) {
	$departmentID = $_POST['departmentID'];
	$departmentName = $_POST['departmentName'];
 
	$query = "UPDATE department SET department_name = '$departmentName' WHERE department_id = $departmentID";
	$query_run = mysqli_query($db, $query);
	if ($query_run) {
		$_SESSION['departmentMessage'] = '
			
		<div class="alert alert-success alert-dismissable" id="flash-msg">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h5>Department updated successfully!</h5>
		</div>
	
	';

		echo "<script>window.location.href='../Admin/department';</script>";
	} else {
		$_SESSION['departmentMessage'] = '
		<div class="alert alert-danger alert-dismissable" id="flash-msg">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h5>Some error occured, please try again!</h5>
		</div>

	';

		echo "<script>window.location.href='../Admin/department';</script>";
	}
	mysqli_close($db);
}

if (isset($_POST['departmentDelete'])) {
	$departmentID = $_POST['departmentID'];
	$query = "DELETE FROM department WHERE department_id = $departmentID";
	$query_run = mysqli_query($db, $query);

	if ($query_run) {
		$_SESSION['departmentMessage'] = '
			
		<div class="alert alert-success alert-dismissable" id="flash-msg">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h5>Department deleted successfully!</h5>
		</div>
	';

		echo "<script>window.location.href='../Admin/department';</script>";
	} else {
		$_SESSION['departmentMessage'] = '
		<div class="alert alert-danger alert-dismissable" id="flash-msg">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h5>Some error occured, please try again!</h5>
		</div>
	';

		
		echo "<script>window.location.href='../Admin/department';</script>";
	}

	mysqli_close($db);
}

//dean ----------------------------------------------------

if (isset($_POST['deanSave'])) {
     
	$deanName = $_POST['deanName'];
	$deanDepartment = $_POST['deanDepartment'];

    $deanUsername = $_POST['deanUsername'];
	$deanPassword = $_POST['deanPassword'];
	$deanEmail = $_POST['deanEmail'];

	$query = "INSERT INTO dean (dean_name, dean_department, dean_username,dean_email, dean_password) VALUES ('$deanName', '$deanDepartment', '$deanUsername','$deanEmail', '$deanPassword')";
	$query_run = $query_run = mysqli_query($db, $query);

	if ($query_run) {
		$_SESSION['deanMessage'] = '
			
		<div class="alert alert-success alert-dismissable" id="flash-msg">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h5>Signup Successfully!</h5>
		</div>
	
	';

    echo "<script>window.location.href='../signin_dean';</script>";

	} else {
		$_SESSION['deanMessage'] = '
		<div class="alert alert-danger alert-dismissable" id="flash-msg">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h5>Some error occured, please try again!</h5>
		</div>
	';
		echo "<script>window.location.href='../Admin/dean';</script>";
	}
	mysqli_close($db);
}

// Course edit
if (isset($_POST['courseEdit'])) {
    // Sanitize and retrieve form inputs
    $courseID = intval($_POST['courseID']); // Ensures it's treated as an integer
    $courseName = mysqli_real_escape_string($db, trim($_POST['courseName'])); // Trim for extra whitespace
    $courseDepartment = intval($_POST['courseDepartment']); // Ensure department ID is integer

    // Construct the update query
    $query = "UPDATE courses 
              SET course_name = '$courseName', 
                  course_department = $courseDepartment 
              WHERE course_id = $courseID";

    // Execute the query
    if (mysqli_query($db, $query)) {
        $_SESSION['courseMessage'] = '
        <div class="alert alert-success alert-dismissable" id="flash-msg">
            <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
            <h5>Course updated successfully!</h5>
        </div>';

        // Redirect to the courses page
        echo "<script>window.location.href='../Admin/dep_courses';</script>";
    } else {
        $_SESSION['courseMessage'] = '
        <div class="alert alert-danger alert-dismissable" id="flash-msg">
            <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
            <h5>Some error occurred, please try again!</h5>
        </div>';

        // Redirect to the courses page
        echo "<script>window.location.href='../Admin/dep_courses';</script>";
    }

    // Close the database connection
    mysqli_close($db);
}


if (isset($_POST['deanEdit'])) {
	$deanID = $_POST['deanID'];
    $deanName = $_POST['deanName'];
	$deanDepartment = $_POST['deanDepartment'];

	$deanUsername = $_POST['deanUsername'];
	$deanPassword = $_POST['deanPassword'];
    $deanEmail = $_POST['deanEmail'];

	$query = "UPDATE dean SET dean_name = '$deanName', dean_department = '$deanDepartment', dean_username = '$deanUsername', dean_password = '$deanPassword', dean_email = '$deanEmail' WHERE dean_id = $deanID";
	$query_run = mysqli_query($db, $query);
	if ($query_run) {
		$_SESSION['deanMessage'] = '
			
		<div class="alert alert-success alert-dismissable" id="flash-msg">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h5>Coordinator updated successfully!</h5>
		</div>
	
	';

		echo "<script>window.location.href='../Admin/dean';</script>";
	} else {
		$_SESSION['deanMessage'] = '
		<div class="alert alert-danger alert-dismissable" id="flash-msg">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h5>Some error occured, please try again!</h5>
		</div>

	';

		echo "<script>window.location.href='../Admin/dean';</script>";
	}
	mysqli_close($db);
}

if (isset($_POST['deanDelete'])) {
	$deanID= $_POST['deanID'];
	$query = "DELETE FROM dean WHERE dean_id = $deanID";
	$query_run = mysqli_query($db, $query);

	if ($query_run) {
		$_SESSION['deanMessage'] = '
			
		<div class="alert alert-success alert-dismissable" id="flash-msg">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h5>Coordinator deleted successfully!</h5>
		</div>
	';

		echo "<script>window.location.href='../Admin/dean';</script>";
	} else {
		$_SESSION['deanMessage'] = '
		<div class="alert alert-danger alert-dismissable" id="flash-msg">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h5>Some error occured, please try again!</h5>
		</div>
	';

		 
		echo "<script>window.location.href='../Admin/dean';</script>";
	}

	mysqli_close($db);
}


//backup
//signup student-------------------------------------------------
if (isset($_POST['student_signup'])) {
    // Collecting data from the form
    $stud_name = mysqli_real_escape_string($db, $_POST['student_name']);
    $stud_middlename = mysqli_real_escape_string($db, $_POST['student_middlename']);
    $stud_lastname = mysqli_real_escape_string($db, $_POST['student_lastname']);
    $stud_suffix = mysqli_real_escape_string($db, $_POST['student_suffix']);
    $stud_department = mysqli_real_escape_string($db, $_POST['student_department']);
    $stud_username = mysqli_real_escape_string($db, $_POST['student_username']);
    $stud_password = mysqli_real_escape_string($db, $_POST['student_password']);
    $stud_status = 'Pending';
    $stud_section = mysqli_real_escape_string($db, $_POST['student_section']);

    $target_dir = "../Upload/";
    $profile_image = $target_dir . basename($_FILES["profile_image"]["name"]);
    $id_image = $target_dir . basename($_FILES["id_image"]["name"]);

    $_SESSION['form_data'] = $_POST;

    // Check if username already exists
    $checkEmailQuery = "SELECT * FROM student WHERE student_username = '$stud_username'";
    $result = mysqli_query($db, $checkEmailQuery);

    if (mysqli_num_rows($result) > 0) {
        $_SESSION['SignupMessage'] = '
            <script>
                $(document).ready(function(){
                    Swal.fire({
                        icon: "error",
                        title: "Email Already Registered",
                        text: "Please use a different email address.",
                        timer: 3000,
                        showConfirmButton: false
                    });
                });
            </script>
        ';
        header('Location: ../signup.php');
        exit();
    }

    // Handle file upload
    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $profile_image) && move_uploaded_file($_FILES["id_image"]["tmp_name"], $id_image)) {
        // Insert student data into the database
        $stud_signup = "INSERT INTO student (student_name, student_middlename, student_lastname, student_suffix, student_section, student_department, student_username, student_password, student_status, profile_image, id_image) 
                        VALUES ('$stud_name', '$stud_middlename', '$stud_lastname', '$stud_suffix', '$stud_section', '$stud_department', '$stud_username', '$stud_password', '$stud_status', '$profile_image', '$id_image')";

        if (mysqli_query($db, $stud_signup)) {
            $student_id = mysqli_insert_id($db); // Store the student ID
            $_SESSION['student_id'] = $student_id;

            // Insert a notification for the new student
            $notification_message = "Your account has been successfully created and is pending admin approval.";
            $insert_notification = "INSERT INTO notifications (user_id, message) VALUES ('$student_id', '$notification_message')";
            mysqli_query($db, $insert_notification);

            // Show success message
            $_SESSION['SignupMessage'] = '
                <script>
                    $(document).ready(function(){
                        Swal.fire({
                            icon: "success",
                            title: "Signup Successful",
                            text: "Your account is pending approval by admin. You will be logged in automatically.",
                            timer: 3000,
                            showConfirmButton: false
                        }).then(function() {
                            window.location.href = "index1.php";
                        });
                    });
                </script>
            ';
            header('Location: ../signup.php');
            exit();
        } else {
            $_SESSION['SignupMessage'] = '
                <script>
                    $(document).ready(function(){
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "Some error occurred, please try again!",
                            timer: 3000,
                            showConfirmButton: false
                        });
                    });
                </script>
            ';
            header('Location: ../signup.php');
            exit();
        }
    } else {
        // File upload error
        $_SESSION['SignupMessage'] = '
            <script>
                $(document).ready(function(){
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Failed to upload files. Please try again!",
                        timer: 3000,
                        showConfirmButton: false
                    });
                });
            </script>
        ';
        header('Location: ../signup.php');
        exit();
    }

    mysqli_close($db);
}



if (isset($_POST['teacher_signup'])) {
    // Collecting data from the form (Teacher specific)
    $teacher_name = mysqli_real_escape_string($db, $_POST['teacher_name']);
    $teacher_middlename = mysqli_real_escape_string($db, $_POST['teacher_middlename']);
    $teacher_lastname = mysqli_real_escape_string($db, $_POST['teacher_lastname']);
    $teacher_suffix = mysqli_real_escape_string($db, $_POST['teacher_suffix']);
    $teacher_department = mysqli_real_escape_string($db, $_POST['teacher_department']);
    $teacher_username = mysqli_real_escape_string($db, $_POST['teacher_username']);
    $teacher_password = mysqli_real_escape_string($db, $_POST['teacher_password']);
    $access = 'Teacher';  // Assign "Teacher" to the access field

    // Handle department and profile picture uploads
    $target_dir = "../Upload/";
    $profile_image = $target_dir . basename($_FILES["profile_image"]["name"]);
    $id_image = $target_dir . basename($_FILES["id_image"]["name"]);

    // Store form data in the session for validation or recovery
    $_SESSION['form_data'] = $_POST;

    // Check if the username already exists in the teacher database
    $checkUsernameQuery = "SELECT * FROM teacher WHERE teacher_username = '$teacher_username'";
    $result = mysqli_query($db, $checkUsernameQuery);

    if (mysqli_num_rows($result) > 0) {
        // Username already exists, show an error message
        $_SESSION['SignupMessage'] = '
            <script>
                $(document).ready(function(){
                    Swal.fire({
                        icon: "error",
                        title: "Username Already Registered",
                        text: "Please use a different username.",
                        timer: 3000,
                        showConfirmButton: false
                    });
                });
            </script>
        ';
        header('Location: ../signup.php');
        exit();
    }

    // Handle file upload (profile picture and ID)
    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $profile_image) && move_uploaded_file($_FILES["id_image"]["tmp_name"], $id_image)) {
        // Prepare the SQL query to insert the teacher data into the database
        $teacher_signup = "INSERT INTO teacher (teacher_name, teacher_middlename, teacher_lastname, teacher_suffix, teacher_department, teacher_username, teacher_password, access, profile_image, id_image) 
                        VALUES ('$teacher_name', '$teacher_middlename', '$teacher_lastname', '$teacher_suffix', '$teacher_department', '$teacher_username', '$teacher_password', '$access', '$profile_image', '$id_image')";

        // Insert data into the teacher table
        if (mysqli_query($db, $teacher_signup)) {
            $_SESSION['teacher_id'] = mysqli_insert_id($db); // Store the teacher ID in the session
            $_SESSION['SignupMessage'] = '
                <script>
                    $(document).ready(function(){
                        Swal.fire({
                            icon: "success",
                            title: "Signup Successful",
                            text: "Your account is pending approval by admin. You will be logged in automatically.",
                            timer: 3000,
                            showConfirmButton: false
                        }).then(function() {
                            window.location.href = "index1.php";
                        });
                    });
                </script>
            ';
            header('Location: index1.php');
            exit();
        } else {
            $_SESSION['SignupMessage'] = '
                <script>
                    $(document).ready(function(){
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "Some error occurred, please try again!",
                            timer: 3000,
                            showConfirmButton: false
                        });
                    });
                </script>
            ';
            header('Location: ../signup.php');
            exit();
        }
    } else {
        // If file upload fails, show an error
        $_SESSION['SignupMessage'] = '
            <script>
                $(document).ready(function(){
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Failed to upload files. Please try again!",
                        timer: 3000,
                        showConfirmButton: false
                    });
                });
            </script>
        ';
        header('Location: ../signup.php');
        exit();
    }

    // Close the database connection
    mysqli_close($db);
}



if (isset($_POST['upload_btn'])) {
    $upload_name = mysqli_real_escape_string($db, $_POST['upload_name']);
    $upload_author = mysqli_real_escape_string($db, $_POST['upload_author']);
    $upload_abstract = mysqli_real_escape_string($db, $_POST['upload_abstract']);
    $upload_student_id = $_POST['upload_student_id'];
    $upload_department = $_POST['upload_department'];
    $status = 'Pending'; // Set default status as Pending

    // File upload handling
    $targetDir = "../Upload/";
    $fileName = basename($_FILES["upload_file"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    // Allow certain file formats
    $allowTypes = array('pdf');
    if (in_array($fileType, $allowTypes)) {
        // Upload file to server
        if (move_uploaded_file($_FILES["upload_file"]["tmp_name"], $targetFilePath)) {
            // Insert file info into database
            $query = "INSERT INTO upload (upload_name, upload_author, upload_abstract, upload_file, upload_student_id, upload_department, status)
                      VALUES ('$upload_name', '$upload_author', '$upload_abstract', '$fileName', '$upload_student_id', '$upload_department', '$status')";

            if (mysqli_query($db, $query)) {
                $_SESSION['UploadMessage'] = '<div id="flash-msg" class="alert alert-warning">Thesis/Capstone Submitted Successfully! Your thesis/capstone file is pending approval.</div>';
                header('Location: ../Student/index.php');
            } else {
                $_SESSION['UploadMessage'] = '<div id="flash-msg" class="alert alert-danger">Error submitting thesis/capstone.</div>';
                header('Location: ../Student/index.php');
            }
        } else {
            $_SESSION['UploadMessage'] = '<div id="flash-msg" class="alert alert-danger">Sorry, there was an error uploading your file.</div>';
            header('Location: ../Student/index.php');
        }
    } else {
        $_SESSION['UploadMessage'] = '<div id="flash-msg" class="alert alert-danger">Sorry, only PDF files are allowed to upload.</div>';
        header('Location: ../Student/index.php');
    }
    exit();
}

// Handle approval and disapproval actions
if (isset($_POST['action']) && ($_POST['action'] == 'approve' || $_POST['action'] == 'disapprove')) {
    $upload_id = $_POST['upload_id'];
    $action = $_POST['action'];
    $status = ($action == 'approve') ? 'Approved' : 'Disapproved';

    $sql = "UPDATE upload SET status = '$status' WHERE upload_id = $upload_id";

    if (mysqli_query($db, $sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($db)]);
    }
    exit();
}
?>
<script>
    $(document).ready(function() {
        $('.approve_btn').on('click', function() {
            var upload_id = $(this).data('upload_id');
            $.ajax({
                url: '../Includes/server.php',
                type: 'POST',
                data: { action: 'approve', upload_id: upload_id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Thesis/Capstone approved successfully.');
                        // Refresh or update UI as needed
                    } else {
                        alert('Failed to approve thesis/capstone.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error: ' + status, error);
                }
            });
        });

        $('.disapprove_btn').on('click', function() {
            var upload_id = $(this).data('upload_id');
            $.ajax({
                url: '../Includes/server.php',
                type: 'POST',
                data: { action: 'disapprove', upload_id: upload_id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Thesis/Capstone disapproved successfully.');
                        // Refresh or update UI as needed
                    } else {
                        alert('Failed to disapprove thesis/capstone.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error: ' + status, error);
                }
            });
        });
    });
</script>

