<?php
session_start();

error_reporting(0);
ini_set('display_errors', 0);


include('./Includes/conn_pdo.php');
include('./Includes/conn.php');

$limit = 10;
$page = 1;
if ($_POST['page'] > 1) {
    $start = (($_POST['page'] - 1) * $limit);
    $page = $_POST['page'];
} else {
    $start = 0;
}

// Base query: Only fetch approved theses
$query = "SELECT * FROM upload WHERE status = 'approved'";

if ($_POST['query'] != '') {
    $query .= ' AND (upload_name LIKE "%' . str_replace(' ', '%', $_POST['query']) . '%" 
                  OR upload_author LIKE "%' . str_replace(' ', '%', $_POST['query']) . '%"
                  OR upload_department IN (SELECT department_id FROM department WHERE department_name LIKE "%' . str_replace(' ', '%', $_POST['query']) . '%"))';
}

if ($_POST['department'] != '') {
    $query .= ' AND upload_department = "' . $_POST['department'] . '"';
}

$query .= ' ORDER BY upload_name ASC';

$filter_query = $query . ' LIMIT ' . $start . ', ' . $limit;

$statement = $connect->prepare($query);
$statement->execute();
$total_data = $statement->rowCount();

$statement = $connect->prepare($filter_query);
$statement->execute();
$result = $statement->fetchAll();
$total_filter_data = $statement->rowCount();

$output = '
<table class="table table-bordered table-hover">
<tr class="bg-primary text-gray-100">
  <td>Title</td>
  <td>Author</td>
  <td>Department</td>
</tr>
';

if ($total_data > 0) {
    foreach ($result as $row) {
        $deptid = $row['upload_department'];
        $sql_query = "SELECT * FROM department WHERE department_id = $deptid";
        $run_sql_query = mysqli_query($db, $sql_query);
        $row_dept = mysqli_fetch_assoc($run_sql_query);

        $output .= '
        <tbody class="table-sm">
        <tr data-href="#" class="view_upload" id="' . $row["upload_id"] . '" style="cursor: pointer">
          <td class="d-none amen_id">' . $row["upload_id"] . '</td>
          <td class="py-2">' . $row["upload_name"] . '</td>
          <td class="py-2">' . $row["upload_author"] . '</td>
          <td class="py-2">' . $row_dept["department_name"] . '</td>
        </tr>
        ';
    }
} else {
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
<label class="text-gray-600">Total Records:  ' . $total_data . '</label>
<div align="center">
  <ul class="pagination">
';

// Pagination logic remains unchanged
$total_links = ceil($total_data / $limit);
$previous_link = '';
$next_link = '';
$page_link = '';

if ($total_links > 4) {
    if ($page < 5) {
        for ($count = 1; $count <= 5; $count++) {
            $page_array[] = $count;
        }
        $page_array[] = '...';
        $page_array[] = $total_links;
    } else {
        $end_limit = $total_links - 5;
        if ($page > $end_limit) {
            $page_array[] = 1;
            $page_array[] = '...';
            for ($count = $end_limit; $count <= $total_links; $count++) {
                $page_array[] = $count;
            }
        } else {
            $page_array[] = 1;
            $page_array[] = '...';
            for ($count = $page - 1; $count <= $page + 1; $count++) {
                $page_array[] = $count;
            }
            $page_array[] = '...';
            $page_array[] = $total_links;
        }
    }
} else {
    for ($count = 1; $count <= $total_links; $count++) {
        $page_array[] = $count;
    }
}

if ($total_data > $limit) {
    for ($count = 0; $count < count($page_array); $count++) {
        if ($page == $page_array[$count]) {
            $page_link .= '
            <li class="page-item disabled">
              <a class="page-link" href="#">' . $page_array[$count] . ' <span class="sr-only">(current)</span></a>
            </li>
            ';

            $previous_id = $page_array[$count] - 1;
            if ($previous_id > 0) {
                $previous_link = '<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page_number="' . $previous_id . '">Previous</a></li>';
            } else {
                $previous_link = '
                <li class="page-item disabled">
                  <a class="page-link" href="#">Previous</a>
                </li>
                ';
            }
            $next_id = $page_array[$count] + 1;
            if ($next_id > $total_links) {
                $next_link = '
                <li class="page-item disabled">
                  <a class="page-link" href="#">Next</a>
                </li>
                ';
            } else {
                $next_link = '<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page_number="' . $next_id . '">Next</a></li>';
            }
        } else {
            if ($page_array[$count] == '...') {
                $page_link .= '
                <li class="page-item disabled">
                    <a class="page-link" href="#">...</a>
                </li>
                ';
            } else {
                $page_link .= '
                <li class="page-item"><a class="page-link" href="javascript:void(0)" data-page_number="' . $page_array[$count] . '">' . $page_array[$count] . '</a></li>
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
