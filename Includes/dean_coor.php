<?php

if (!isset($_SESSION)) {
	session_start();
}
include("conn.php");

if (isset($_POST['coorSave'])) {
     
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
		<h5>Coordinator Added Successfully!</h5>
		</div>
	
	';

    echo "<script>window.location.href='../Research_Coordinator/dean1';</script>";

	} else {
		$_SESSION['deanMessage'] = '
		<div class="alert alert-danger alert-dismissable" id="flash-msg">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h5>Some error occured, please try again!</h5>
		</div>
	';
		echo "<script>window.location.href='../Research_Coordinator/dean1';</script>";
	}
	mysqli_close($db);
}


if (isset($_POST['deanEdit'])) {
	$deanID = $_POST['deanID'];
    $deanName = $_POST['deanName'];
	$deanDepartment = $_POST['deanDepartment'];

	$deanUsername = $_POST['deanUsername'];
	$deanPassword = $_POST['deanPassword'];
    $deanEmail = $_POST['deanEmail'];

	$query = "UPDATE dean SET dean_name = '$deanName', dean_department = '$deanDepartment', dean_username = '$deanUsername', dean_password = '$deanPassword' dean_email = '$deanEmail' WHERE dean_id = $deanID";
	$query_run = mysqli_query($db, $query);
	if ($query_run) {
		$_SESSION['deanMessage'] = '
			
		<div class="alert alert-success alert-dismissable" id="flash-msg">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h5>Coordinator updated successfully!</h5>
		</div>
	
	';

		echo "<script>window.location.href='../Research_Coordinator/dean1';</script>";
	} else {
		$_SESSION['deanMessage'] = '
		<div class="alert alert-danger alert-dismissable" id="flash-msg">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h5>Some error occured, please try again!</h5>
		</div>

	';

		echo "<script>window.location.href='../Research_Coordinator/dean1';</script>";
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

		 
		echo "<script>window.location.href='../Research_Coordinator/dean1';</script>";
	}

	mysqli_close($db);
}
?>