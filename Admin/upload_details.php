<?php  
 if(isset($_POST["upload_id"]))  
 {  
      $output = '';  
      include('../Includes/conn.php');

      $upload_id = $_POST["upload_id"];
 
      $query_up = "SELECT * FROM upload WHERE upload_id = '$upload_id'";  
      $run_query_up = mysqli_query($db, $query_up);
      $result_up = mysqli_fetch_assoc($run_query_up);
      $dept = $result_up['upload_department'];

      $query_dept= "SELECT * FROM department WHERE department_id = '$dept'";  
      $run_query_dept = mysqli_query($db, $query_dept);
      $result_dept = mysqli_fetch_assoc($run_query_dept);

      echo '
        <div class="pb-3">
            <p class="card-text">
            <span class="text-gray-600">Title: </span>'.$result_up['upload_name'].' <br> 
            <span class="text-gray-600">Author: </span>'.$result_up['upload_author'].'<br>
            <span class="text-gray-600">Department: </span>'.$result_dept['department_name'].'<br> 
            <span class="text-gray-600">Abstract:</span><br> '.$result_up['upload_abstract'].'<br> <br>
             <table>
                <tr>
                    <td><span class="text-gray-600  ">PDF File Name: </span><a href="view_PDF.php?file='.$result_up['upload_file'].'">'.$result_up['upload_file'].'</a> 
                </tr>
            </table>
 
            </p>
        </div>

        <div>
 
        <table class="table">
            <tr>
                <td class="d-none upload_id">'.$result_up['upload_id'].'</td>
            <td class="pl-2" align="center"> <a class="px-5 btn btn-info"  href="../viewPDF.php?file='.$result_up['upload_file'].'">View</a>
                 <td class="pl-2" align="center"> <a class="px-5 btn btn-primary"  href="../download.php?file='.$result_up['upload_file'].'">Download File</a>
                </td>
                 </td>
                
                 </td>
            </tr>
        </table>   
        
         

        </div>
        
        
        
        
        
        </div>
      ';

 }
 ?>