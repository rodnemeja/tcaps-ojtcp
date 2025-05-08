<?php

require_once('../Includes/conn_pdo.php');


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
SELECT * FROM department
";

if($_POST['query'] != '')
{
  $query .= 'WHERE department_name LIKE "%'.str_replace(' ', '%', $_POST['query']).'%" ';
}

$query .= 'ORDER BY department_id ASC ';


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
<td class="d-none">RoomID</td>
    <td class="py-2">Departments</td>
  <td class="py-2">Status</td>
  </tr>
';
if($total_data > 0)
{
  foreach($result as $row)
  {
    $status = $row["status"] == 'active' ? 'Active' : 'Inactive';
    $toggleButton = $row["status"] == 'active' ? 'Inactive' : 'Activate';
    
    $output .= '
    <tbody class="table-sm">
    <tr class="'.($row["status"] == 'inactive' ? 'table-danger' : '').'">
      <td class="d-none amen_id">'.$row["department_id"].'</td>
      <td class="py-2">'.$row["department_name"].'</td>
      <td class="py-2">
        <select class="form-control status-select" data-id="'.$row["department_id"].'" style="background-color: '.($row["status"] == 'active' ? '#d4edda' : '#f8d7da').'; color: '.($row["status"] == 'active' ? '#155724' : '#721c24').'">
          <option value="active" '.($row["status"] == 'active' ? 'selected' : '').' style="background-color: #d4edda; color: #155724;">Active</option>
          <option value="inactive" '.($row["status"] == 'inactive' ? 'selected' : '').' style="background-color: #f8d7da; color: #721c24;">Inactive</option>
        </select>
      </td>

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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
  $('.status-select').change(function(){
    var department_id = $(this).data('id');
    var status = $(this).val();
    if(confirm("Are you sure you want to change the status to " + status + "?")) {
      $.ajax({
        url: 'toggle_department.php',
        method: 'POST',
        data: {department_id: department_id, status: status},
        success: function(response){
          alert(response);
          location.reload();
        }
      });
    }
  });
});
</script>
