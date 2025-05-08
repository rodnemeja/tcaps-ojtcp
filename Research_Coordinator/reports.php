<?php
include('../Includes/header.php');
include('../Includes/dean_navbar.php');

$con = mysqli_connect("localhost", "root", "", "tcaps_g8_system");

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
} else {
    // Get the document count
    $sql_documents = "SELECT COUNT(*) AS document_count FROM upload";
    $result_documents = mysqli_query($con, $sql_documents);
    if (!$result_documents) {
        die("Error fetching document count: " . mysqli_error($con));
    }
    $document_count = mysqli_fetch_assoc($result_documents)['document_count'];

    // Get the user count
    $sql_users = "SELECT COUNT(*) AS user_count FROM student";
    $result_users = mysqli_query($con, $sql_users);
    if (!$result_users) {
        die("Error fetching user count: " . mysqli_error($con));
    }
    $user_count = mysqli_fetch_assoc($result_users)['user_count'];

    // Get the pending count
    $sql_pending = "SELECT COUNT(*) AS pending_count FROM upload WHERE status = 'pending'";
    $result_pending = mysqli_query($con, $sql_pending);
    if (!$result_pending) {
        die("Error fetching pending count: " . mysqli_error($con));
    }
    $pending_count = mysqli_fetch_assoc($result_pending)['pending_count'];

    // Get student count per department (Updated query)
    // Adjust 'student.dept_id' based on your actual column name for department in the 'student' table
    $sql_departments = "
        SELECT department.department_name AS department_name, COUNT(student.student_id) AS student_count
        FROM department
        LEFT JOIN student ON department.department_id = student.student_department  -- Change 'dept_id' as needed
        GROUP BY department.department_id";
    
    $result_departments = mysqli_query($con, $sql_departments);

    // Check if the query was successful
    if (!$result_departments) {
        die("Error fetching department data: " . mysqli_error($con));
    }

    // Prepare data for chart
    $department_names = [];
    $student_counts = [];
    while ($row = mysqli_fetch_assoc($result_departments)) {
        $department_names[] = $row['department_name'];
        $student_counts[] = $row['student_count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Reports</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <style>
   /* Media query for print */
@media print {

    h1{
        color: whitesmoke;
    }
    body * {
        visibility: hidden; /* Hide all elements */
    }

    .container, .card {
        visibility: visible; /* Ensure the report content is visible */
        position: center;
    }

    .card-body, .card-header {
        visibility: visible; /* Ensure card content is visible */
    }

    #chartjs_bar {
        visibility: visible; /* Ensure the chart is visible */
        width: 100% !important;
        height: 300px !important; /* Adjust chart height */
    }

    /* Fix layout if elements are not fitting properly */
    .container {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        width: 100%;
        padding: 10px;
    }

    .card {
        margin-top: 200px;
        width: 100%;  /* Make sure cards are side-by-side */
        margin-bottom: 10px;
        border: 1px solid #007bff;
        padding: 100px;
    }

    .no-print {
        display: none; /* Hide print button during printing */
    }
}


    </style>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h1>Reports</h1>
                    </div>
                    <div class="card-body">
                        <!-- Print Button -->
                        <button class="btn btn-success no-print" onclick="window.print();">Print Report</button>

                        <!-- Chart -->
                        <canvas id="chartjs_bar"></canvas>

                        <!-- Summary -->
                        <div class="mt-4">
                            <h4>Summary</h4>
                            <ul>
                                <li><strong>Thesis/Capstone File Uploaded:</strong> <?php echo $document_count; ?></li>
                                <li><strong>Users:</strong> <?php echo $user_count; ?></li>
                                <li><strong>Pending Thesis/Capstone File:</strong> <?php echo $pending_count; ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.min.js"></script>
    <script type="text/javascript">
        var ctx = document.getElementById("chartjs_bar").getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($department_names); ?>,
                datasets: [{
                    label: 'Number of Students',
                    backgroundColor: [
                        "#ffd322", "#5945fd", "#25d5f2", "#2ec551", "#ff344e",
                    ],
                    data: <?php echo json_encode($student_counts); ?>
                }]
            },
            options: {
                responsive: true,
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        fontColor: '#71748d',
                        fontFamily: 'Circular Std Book',
                        fontSize: 14,
                    }
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });
    </script>

    <script>
        $(document).ready(function () {
            load_data(1);

            function load_data(page, query = '') {
                $.ajax({
                    url: "fetch_upload.php",
                    method: "POST",
                    data: { page: page, query: query },
                    success: function (data) {
                        $('#dynamic_content').html(data);
                    }
                });
            }

            $(document).on('click', '.page-link', function () {
                var page = $(this).data('page_number');
                var query = $('#search_box').val();
                load_data(page, query);
            });

            $('#search_box').keyup(function () {
                var query = $('#search_box').val();
                load_data(1, query);
            });
        });
        window.onbeforeprint = function() {
    // Trigger re-render of the chart before printing
    myChart.update();
};

    </script>

</body>

</html>

<?php
include('../Includes/script.php');
?>
