<?php
include('../Includes/conn.php'); 
session_start();

if(isset($_SESSION['student_id'])){
  $user_id = $_SESSION['student_id'];

  $query = "SELECT profile_image, student_name, student_lastname FROM student WHERE student_id = ?";
  $stmt = $db->prepare($query);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();

  $name = $row['student_name'] . ' ' . $row['student_lastname'];
  $profile_image_path = !empty($row['profile_image']) ? "../Upload/" . $row['profile_image'] : "../Images/head.jpg"; // Default image if no profile image is set

}else{
  echo "<script>window.location.href='../index';</script>";
}
?>
 
<link rel="shortcut icon" href="../Images/stii-shorcut.jpg">

<nav class="navbar navbar-expand-lg navbar-dark fixed-top bg-light px-3">
    <a class="navbar-brand" href="#">
        <div class="d-flex text-dark">
            <div>
                <img src="../Images/stiilogo.png" style="width: 40px; height: 40px;">
            </div>
            <div class="ml-2 mt-1">
                <b>THESIS AND CAPSTONE ARCHIVING SYSTEM</b>
                <p style="font-size: 12px;"><b></b></p>
            </div>
        </div>
    </a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="">
        <ul class="navbar-nav ">
        <li class="nav-item">
                <a class="nav-link text-dark" href="../index1">HOME <span class="sr-only">(current)</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark" href="../thesis">THESIS/CAPSTONE</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark" href="../index1#Abouts">ABOUT</a>
            </li>

        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle text-dark font-weight-bold" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <?php echo $name; ?>
                <img class="ml-2 rounded-circle" src="<?php echo $profile_image_path; ?>" width="28rem" height="28rem">
            </a>

            <!-- Dropdown - User Information -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
            <a class="dropdown-item" href="../Student/edit_stud.php">
                <i class="fas fa-sm fa-fw mr-2 text-gray-400"></i>
                Profile Info
            </a>
                <a class="dropdown-item" href="../Includes/logout">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Logout
                </a>
            </div>
        </li>
        </ul>
        </ul>
        </div>
    </div>

    <div class="modal fade" id="editmodal" tabindex="-1" aria-labelledby="editmodal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editmodal">Edit Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="../Includes/server.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body px-4">
                        <input type="hidden" id="deanID" name="deanID">
                        <div class="mb-3">
                            <label>Department</label>
                            <select name="deanDepartment" id="deanDepartment" class="form-select">
                                <option disabled selected hidden>Select Department</option>
                                <?php include('select_option_dean.php'); ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Name</label>
                            <input type="text" name="deanName" id="deanName" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" name="deanUsername" id="deanUsername" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Password</label>
                            <input type="text" name="deanPassword" id="deanPassword" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger w3-text-white px-3" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="deanEdit" class="btn btn-primary px-3">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</nav>
