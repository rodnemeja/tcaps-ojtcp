<?php
include('./Includes/conn.php'); 
session_start();

$verification_message = "";

if (isset($_SESSION['student_id'])) {
    $user_id = $_SESSION['student_id'];
  
    $query_student = "SELECT * FROM student WHERE student_id = $user_id";
    $run_query_student = mysqli_query($db, $query_student);
    $result_student = mysqli_fetch_assoc($run_query_student);

    if ($result_student['is_verified'] == 0) {
  
    }

    $query = "SELECT profile_image FROM student WHERE student_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
  
    $name = $result_student['student_name'] . ' ' . $result_student['student_lastname'];

    $profile_image_path = !empty($row['profile_image']) ? "./Upload/" . $row['profile_image'] : "Images/head.jpg"; // Default image if no profile image is set

    $btn = '
    <li class="nav-item dropdown no-arrow">
        <a class=" nav-link text-dark font-weight-bold dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            '.$name.'
           <img class="ml-2 rounded-circle" src="' . $profile_image_path . '" width="30rem" height="30rem">
        </a>
        <!-- Dropdown - User Information -->
        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
            <a class="dropdown-item" href="./Student/edit_stud.php">
                <i class="fas fa-sm fa-fw mr-2 text-black-400"></i>
                Profile Info
            </a>
            <a class="dropdown-item" href="logout_student">
                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-black-400"></i>
                Logout
            </a>
            '.(!empty($verification_message) ? '<div class="dropdown-item text-warning">'.$verification_message.'</div>' : '').'
        </div>
    </li>';
} else {
    $btn = '
    <li class="nav-item dropdown no-arrow">
        <a class=" nav-link text-dark dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            ACCOUNT
            <img class="ml-2 rounded-circle" src="Images/head.jpg" width="28rem;" height="28rem;">
        </a>
        <!-- Dropdown - User Information -->
        <div class="dropdown-menu text-dark dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
            <a class="dropdown-item" href="signin">
                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                ADMIN LOGIN
            </a>
            <a class="dropdown-item" href="signin_dean">
                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                RESEARCH COORDINATOR LOGIN
            </a>
        </div>
    </li>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
  <link rel="shortcut icon" href="images/stii-shorcut.jpg">
  <style>
    .navbar-toggler {
        border-color: blue; /* Blue border */
        color: blue; /* Blue text color */
        background-color:skyblue;
    }
    .navbar {
        color: blue; /* Blue icon color */
    }
    /* Target the navbar links with the hover pseudo-class */
    .navbar-nav .nav-link:hover,
    .navbar-nav .nav-link.active {  /* Add active class for clicked state */
      background-image: linear-gradient(to right, #007bff, #00b894); /* Gradient blue colors */
      color: white; /* Text color on hover */
      text-decoration: none; /* Remove underline on hover */
    }
  </style>
</head>
<body>
 <nav class="navbar navbar-expand-lg navbar-dark bg-light fixed-top px-3">
     <a class="navbar-brand" href="index1">
         <div class="d-flex text-dark">
             <div>
                 <img src="Images/stiilogo.png" style="width: 40px; height: 40px;" href="index1">
             </div>
             <div class="ml-2 mt-1">
                 <b style="font-size: 20px;" href="index1">THESIS AND CAPSTONE ARCHIVING SYSTEM</b>
             </div>
         </div>
     </a>
     <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
         <span class="navbar-toggler-icon"></span>
     </button>
     <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link text-dark" href="index1">HOME <span class="sr-only">(current)</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark" href="thesis">THESIS/CAPSTONE</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark" href="index1#Abouts">ABOUT</a>
            </li>
            <?php echo $btn; ?>
        </ul>
    </div>
 </nav>

<script>
  const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

  navLinks.forEach(link => {
    link.addEventListener('click', function() {
      navLinks.forEach(otherLink => otherLink.classList.remove('active'));  // Remove active from all links
      this.classList.add('active');  // Add active to the clicked link
    });
  });
</script>
</body>
</html>
