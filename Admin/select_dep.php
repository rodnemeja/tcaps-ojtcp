<?php
include("../Includes/conn.php");

$dean_query = "SELECT * FROM department";
$run_dean_query = mysqli_query($db, $dean_query);

if(mysqli_num_rows($run_dean_query) > 0){
    while ($row= mysqli_fetch_array($run_dean_query)) {

        $out = '<option value="'.$row["department_id"].'"> '.$row['department_name'].'</option>';
        echo $out;
    
    }
}else{
    
}
 

?>
