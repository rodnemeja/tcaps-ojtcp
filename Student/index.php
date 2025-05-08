<?php
include('../Includes/header.php');
include('../Includes/student_navbar.php');


if(isset($_SESSION['student_id'])){
  $user_id = $_SESSION['student_id'];

  $query_student = "SELECT * FROM student WHERE student_id = $user_id";
  $run_query_student = mysqli_query($db, $query_student);
  $result_student = mysqli_fetch_assoc($run_query_student);

  $dept_id = $result_student['student_department'];
}
?>

<div class="container-fluid">
  <div class="row">
    <div class="col-lg-2"></div>
    <div class="col-lg-8">
      <div class="card shadow text-gray-700" style="margin-top: 110px;">
       
        <div class="card-header text-primary">
          <h2>Submit New Thesis/Capstone</h2>
        </div>
        <?php
        if (isset($_SESSION['UploadMessage'])) {
          echo $_SESSION['UploadMessage'];
          unset($_SESSION['UploadMessage']);
        }

        ?>
        <div class="card-body">

          <form action="../Includes/server.php" method="POST" enctype="multipart/form-data">

            <div class="mb-3">
              <label>Title</label>
              <input type="text" name="upload_name" class="text-gray-700 form-control" placeholder="Enter Thesis/Capstone Title" required>
            </div>

            <div class="mb-3">
              <label>Author/s</label>
              <input type="text" name="upload_author" class="text-gray-700 form-control" placeholder="Enter Author/s Complete name" required>
            </div>

            <div class="mb-3">
              <label>Abstract</label>
              <textarea name="upload_abstract" rows="8" class="text-gray-700 form-control" placeholder="Paste here your abstract  " required></textarea>
            </div>

             

            <div class="mb-3">
            <label class="text-dark font-weight-bold">
        Thesis/Capstone File 
        <span style="color: red; font-size: 12px;">(file must be PDF)</span>
    </label>
              <input type="file" name="upload_file" class="text-gray-700 form-control" placeholder="" required >
            </div>
 
            <input type="hidden" name="upload_student_id" value="<?php echo $user_id; ?>">
            <input type="hidden" name="upload_department" value="<?php echo $dept_id; ?>" >

 
            <input type="submit" name="upload_btn" value="Submit" class="btn btn-primary col py-2">

                        </form>
                    </div>
                </div>
            </div>
            <div class=" col-lg-2">
        </div>

      </div>





    </div>

    <?php
    include('../Includes/script.php');
    

    ?>
    
    <script>
        $(document).ready(function() {
                    $("#flash-msg").delay(2000).fadeOut("slow");
                });
    </script>