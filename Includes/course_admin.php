<?php

if (!isset($_SESSION)) {
	session_start();
}
include("conn.php");

if (isset($_POST['courseSave'])) {
     
	$courseName = $_POST['courseName'];
	$courseDepartment = $_POST['courseDepartment'];

	$query = "INSERT INTO courses (course_name, course_department) VALUES ('$courseName', '$courseDepartment')";
	$query_run = $query_run = mysqli_query($db, $query);

	if ($query_run) {
		$_SESSION['courseMessage'] = '
			
		<div class="alert alert-success alert-dismissable" id="flash-msg">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h5>Curriculum Added Successfully!</h5>
		</div>
	
	';

    echo "<script>window.location.href='../Admin/dep_courses';</script>";

	} else {
		$_SESSION['courseMessage'] = '
		<div class="alert alert-danger alert-dismissable" id="flash-msg">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h5>Some error occured, please try again!</h5>
		</div>
	';
		echo "<script>window.location.href='../Admin/dep_courses';</script>";
	}
	mysqli_close($db);
}

if (isset($_POST['update_course'])) {
    $course_id = $_POST['course_id'];
    $course_name = $_POST['course_name'];
    $course_department = $_POST['course_department'];

    $sql = "UPDATE courses 
            SET course_name = '$course_name', 
                course_department = '$course_department' 
            WHERE course_id = '$course_id'";

    if (mysqli_query($db, $sql)) {
        header("Location: ../Admin/dep_courses");
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}


// if (isset($_POST['cEdit'])) {
// 	$courseID = $_POST['courseIDd'];
//     $courseName = $_POST['courseName'];
// 	$courseDepartment = $_POST['courseDepartment'];


// 	$query = "UPDATE courses SET course_name = '$courseName', course_department = '$courseDepartment' WHERE course_id = $courseID";
// 	$query_run = mysqli_query($db, $query);
// 	if ($query_run) {
// 		$_SESSION['courseMessage'] = '
			
// 		<div class="alert alert-success alert-dismissable" id="flash-msg">
// 		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
// 		<h5>Coordinator updated successfully!</h5>
// 		</div>
	
// 	';

// 		echo "<script>window.location.href='../Admin/dep_courses';</script>";
// 	} else {
// 		$_SESSION['courseMessage'] = '
// 		<div class="alert alert-danger alert-dismissable" id="flash-msg">
// 		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
// 		<h5>Some error occured, please try again!</h5>
// 		</div>

// 	';

// 		echo "<script>window.location.href='../Admin/dep_courses';</script>";
// 	}
// 	mysqli_close($db);
// }
?>