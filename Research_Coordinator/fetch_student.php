<?php
session_start();


include('../Includes/conn_pdo.php');
include('../Includes/conn.php');

$dean_id = $_SESSION['dean_id'];
$sql_dean = "SELECT * FROM dean WHERE dean_id = $dean_id";
$run_sql_dean = mysqli_query($db, $sql_dean);
$res_dean = mysqli_fetch_assoc($run_sql_dean);
$dept_id = $res_dean['dean_department'];

$limit = '10';
$page = 1;
if($_POST['page'] > 1)
{
  $start = (($_POST['page'] - 1) * $limit);
  $page = $_POST['page'];
}
else
{
  $start = 0;
}

$query = "
SELECT * FROM student WHERE student_department = '$dept_id'
";

if($_POST['query'] != '')
{
  $query .= '
  AND  student_name LIKE "%'.str_replace(' ', '%', $_POST['query']).'%" ||
  student_department IN (SELECT department_id FROM department WHERE department_name LIKE "%'.str_replace(' ', '%', $_POST['query']).'%") AND student_department = "'.$dept_id.'"

  
  ';
}

$query .= 'ORDER BY student_id ASC ';

$filter_query = $query . 'LIMIT '.$start.', '.$limit.'';

$statement = $connect->prepare($query);
$statement->execute();
$total_data = $statement->rowCount();

$statement = $connect->prepare($filter_query);
$statement->execute();
$result = $statement->fetchAll();
$total_filter_data = $statement->rowCount();

$output = '

<table class="table table-bordered table-hover">
<tr  class="bg-primary text-gray-100">
 
  <td>Name</td>
  <td>Section</td>
  <td>Department</td>
  <td>Email</td>
   <td>Status</td>
   </tr>
';
if($total_data > 0)
{
  foreach($result as $row)
  {

    $dID = $row["student_department"];
    $sql = "SELECT * FROM department WHERE department_id = $dID";
    $run_sql = mysqli_query($db, $sql);
    $res = mysqli_fetch_assoc($run_sql);

    
    $output .= '
    <tbody class="table-sm">
    <tr data-href="#" class="view_student" id="'.$row["student_id"].'" style="cursor: pointer">
        <td class="py-2">'.$row["student_name"].' </td>
       <td class="py-2">'.$row["student_section"].'</td>
       <td class="py-2">'.$res["department_name"].'</td>
       <td class="py-2">'.$row["student_username"].'</td>
        <td class="py-2">'.$row["student_status"].'</td>
 

    </tr>
    ';
  }
}
else
{
  $output .= '
  <tr>
    <td colspan="8" align="center">No Record</td>
  </tr>
  </tbody>
  ';
}

$output .= '
</table>
<div class="d-flex justify-content-lg-between">
<label class="text-gray-600">Total Records:  '.$total_data.'</label>

<div <align="center">
  <ul class="pagination">
';


$total_links = ceil($total_data/$limit);
$previous_link = '';
$next_link = '';
$page_link = '';

//echo $total_links;

if($total_links > 4)
{
  if($page < 5)
  {
    for($count = 1; $count <= 5; $count++)
    {
      $page_array[] = $count;
    }
    $page_array[] = '...';
    $page_array[] = $total_links;
  }
  else
  {
    $end_limit = $total_links - 5;
    if($page > $end_limit)
    {
      $page_array[] = 1;
      $page_array[] = '...';
      for($count = $end_limit; $count <= $total_links; $count++)
      {
        $page_array[] = $count;
      }
    }
    else
    {
      $page_array[] = 1;
      $page_array[] = '...';
      for($count = $page - 1; $count <= $page + 1; $count++)
      {
        $page_array[] = $count;
      }
      $page_array[] = '...';
      $page_array[] = $total_links;
    }
  }
}
else
{
  for($count = 1; $count <= $total_links; $count++)
  {
    $page_array[] = $count;
  }
}


if($total_data > $limit){
for($count = 0; $count < count($page_array); $count++)
{
  if($page == $page_array[$count])
  {
    $page_link .= '
    <li class="page-item disabled">
      <a class="page-link" href="#">'.$page_array[$count].' <span class="sr-only">(current)</span></a>
    </li>
    ';

    $previous_id = $page_array[$count] - 1;
    if($previous_id > 0)
    {
      $previous_link = '<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page_number="'.$previous_id.'">Previous</a></li>';
    }
    else
    {
      $previous_link = '
      <li class="page-item disabled">
        <a class="page-link" href="#">Previous</a>
      </li>
      ';
    }
    $next_id = $page_array[$count] + 1;
    if($next_id > $total_links)
    {
      $next_link = '
      <li class="page-item disabled">
        <a class="page-link" href="#">Next</a>
      </li>
        ';
    }
    else
    {
      $next_link = '<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page_number="'.$next_id.'">Next</a></li>';
    }
  }
  else
  {
    if($page_array[$count] == '...')
    {
      $page_link .= '
      <li class="page-item disabled">
          <a class="page-link" href="#">...</a>
      </li>
      ';
    }
    else
    {
      $page_link .= '
      <li class="page-item"><a class="page-link" href="javascript:void(0)" data-page_number="'.$page_array[$count].'">'.$page_array[$count].'</a></li>
      ';
    }
  }
}
}

$output .= $previous_link . $page_link . $next_link;
$output .= '
  </ul>

</div>
</div>
';




  echo $output;





?>