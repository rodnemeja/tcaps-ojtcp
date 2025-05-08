<?php  
 if(isset($_POST["student_id"]))  
 {  
      $output = '';  

      $stud_id = $_POST["student_id"];
 
      $query_stud = "SELECT * FROM student WHERE student_id = '$stud_id'";  
      $run_query_stud = mysqli_query($db, $query_stud);
      $result_stud = mysqli_fetch_assoc($run_query_stud);
      $dept = $result_stud['student_department'];


      $query_upload= "SELECT * FROM upload WHERE upload_student_id = '$stud_id'";  
      $run_query_upload = mysqli_query($db, $query_upload);
      

      echo '
                <div class="profile-details text-center">
                    <img src="' . $result_stud['profile_image'] . '" alt="Profile Picture" class="rounded-circle" style="width: 200px; height: 200px; object-fit: cover;">
                    <h3 class="mt-3">' . $result_stud['student_name'] . ' ' . $result_stud['student_lastname'] . '</h3>
                </div>
            </div>


      </div>

      </div>

    ';

}
 ?>