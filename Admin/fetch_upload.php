<?php
session_start();

include('../Includes/conn_pdo.php');
include('../Includes/conn.php');



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
SELECT * FROM upload
";

if($_POST['query'] != '')
{
  $query .= '
  WHERE upload_name LIKE "%'.str_replace(' ', '%', $_POST['query']).'%" ||
  upload_author LIKE "%'.str_replace(' ', '%', $_POST['query']).'%" ||
  upload_department IN (SELECT department_id FROM department WHERE department_name LIKE "%'.str_replace(' ', '%', $_POST['query']).'%")

  
  ';
}

$query .= 'ORDER BY upload_name ASC ';

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

 
  <td>Title</td>
  <td>Author</td>
  <td>File</td>
  <td>Department</td>
  <td>Uploaded by</td>
  <td>Status</td>
  <td>Action</td>

   </tr>
';
if($total_data > 0)
{
  foreach($result as $row)
  {
      $deptid= $row['upload_department'];
      $sql_query =  "SELECT * FROM department WHERE department_id = $deptid";
        $run_sql_query = mysqli_query($db, $sql_query);
        $row_dept = mysqli_fetch_assoc($run_sql_query);

        $studid= $row['upload_student_id'];
       $sql_query1 =  "SELECT * FROM student WHERE student_id = $studid";
        $run_sql_query1 = mysqli_query($db, $sql_query1);
        $row_stud = mysqli_fetch_assoc($run_sql_query1);

    
        $status = $row['status'];

        $full_name = trim($row_stud["student_name"] . ' ' . $row_stud["student_middlename"] . ' ' . $row_stud["student_lastname"]);

        $output .= '
 <tbody class="table-sm">
<tr data-href="#" class="_upload" id="' . $row["upload_id"] . '" style="cursor: pointer">
  <td class="d-none amen_id">' . $row["upload_id"] . '</td>
  <td class="py-2">' . $row["upload_name"] . ' </td>
  <td class="py-2">' . $row["upload_author"] . '</td>
  <td class="py-2">' . $row["upload_file"] . '</td>
  <td class="py-2">' . $row_dept["department_name"] . '</td>
  <td class="py-2">' . $full_name . '</td>
  <td class="py-2">' . ($status === "Approved" ? "Publish" : ($status === "Disapproved" ? "Unpublish" : $status)) . '</td>
  <td class="py-2">
  <div class="modal fade" id="modal-' . $row["upload_id"] . '" tabindex="-1" aria-labelledby="modalLabel-' . $row["upload_id"] . '" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel-' . $row["upload_id"] . '">Details of ' . $row["upload_name"] . '</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Author:</strong> ' . $row["upload_author"] . '</p>
                <p><strong>File:</strong> 
        <a href="view_PDF.php?file=' . $row["upload_file"] . '" target="_blank" class="text-primary">
            ' . $row["upload_file"] . '
        </a>
    </p>
                <p><strong>Department:</strong> ' . $row_dept["department_name"] . '</p>
                <p><strong>Submitted By:</strong> ' . $full_name . '</p>
                <p><strong>Status:</strong> ' . $status . '</p>
<br>
                <p><strong>Abstract:</strong> ' . $row["upload_abstract"] . '</p>
            </div>
        </div>
    </div>
</div>


            <div class="dropdown">
              <button class="btn btn-primary dropdown-toggle" type="button" id="actionDropdown-' . $row["upload_id"] . '" data-bs-toggle="dropdown" aria-expanded="false">
                Action
              </button>
              <ul class="dropdown-menu" aria-labelledby="actionDropdown-' . $row["upload_id"] . '">
                <li><a class="dropdown-item approve" href="#" data-id="' . $row["upload_id"] . '">Publish</a></li>
                <li><a class="dropdown-item disapprove" href="#" data-id="' . $row["upload_id"] . '">UnPublish</a></li>
                 <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modal-' . $row["upload_id"] . '">View Details</button></li>
              </ul>
            </div>
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


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.querySelectorAll('.approve').forEach(button => {
        button.addEventListener('click', function () {
            const upload_id = this.getAttribute('data-id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to Publish this Thesis/Capstone?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Publish it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('approve_admin.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ upload_id, action: 'approve' }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                'Publish Successful',
                                'The Thesis/Capstone File has been Publish.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                'Failed to Publish the Thesis/Capstone File.',
                                'error'
                            );
                        }
                    });
                }
            });
        });
    });

    document.querySelectorAll('.disapprove').forEach(button => {
        button.addEventListener('click', function () {
            const upload_id = this.getAttribute('data-id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to Unpublish this Thesis/Capstone File!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Unpublish it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('approve_admin.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ upload_id, action: 'disapprove' }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                'Unpublish Successful',
                                'The Thesis/Capstone File has been UnPublish.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                'Failed to unpublish.',
                                'error'
                            );
                        }
                    });
                }
            });
        });
    });

    $(document).on('click', '.view_upload', function() {
                    var upload_id = $(this).attr("id");
                    if (upload_id != '') {
                        $.ajax({
                            url: "upload_details.php",
                            method: "POST",
                            data: {
                                upload_id: upload_id
                            },
                            success: function(data) {
                                $('#upload_details').html(data);
                                $('#view_upload_details_modal').modal('show');
                            }
                        });
                    }
                    });

                    function load_data(page, query = '') {
                        $.ajax({
                            url: "fetch_upload.php",
                            method: "POST",
                            data: {
                                page: page,
                                query: query
                            },
                            success: function(data) {
                                $('#dynamic_content').html(data);
                            }
                        });
                    }
</script>