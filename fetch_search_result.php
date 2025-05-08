<?php
include('Includes/conn_pdo.php');

if($_POST['query'] != '')
{
  $query = '
  SELECT * FROM upload WHERE upload_name LIKE "%'.str_replace(' ', '%', $_POST['query']).'%" OR
  upload_abstract LIKE "%'.str_replace(' ', '%', $_POST['query']).'%"
  ';

$statement = $connect->prepare($query);
$statement->execute();
$total_data = $statement->rowCount();
 $result = $statement->fetchAll();
 
$output = '

<table class="table table-hover ">
 
';
if($total_data > 0)
{
  $output .= '<span class="text-warning pb-1">Result:</span>';
  foreach($result as $row)
  {
    $output .= '
    <tbody class="">
    <tr data-href="#" class="view_upload" id="'.$row["upload_id"].'" style="cursor: pointer">

      <td class="py-2 " align="lr=eft"><a href="#"> '.$row["upload_name"].' </a></td>
   
    </tr>
    ';
  }
}
else
{
  $output .= '
  <tr>
    <td colspan="8" align="center">No Result</td>
  </tr>
  </tbody>
  ';
}

$output .= '
</table>
 
';

echo $output;
}

?>