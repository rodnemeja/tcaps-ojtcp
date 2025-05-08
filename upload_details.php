<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
    <title>THESIS AND CAPSTONE ARCHIVING SYSTEM</title>
    <style>
        /* Basic styling for the PDF container */
        .pdf-viewer {
            width: 100%;
            height: 500px;
            max-width: 800px; /* Maximum width for the container */
            max-height: 1000px; /* Maximum height for the container */
            border: 1px solid #ccc;
            margin: 20px auto; /* Center the container horizontally */
            overflow: hidden; /* Prevent scrollbars outside the iframe */
        }
        .pdf-viewer iframe,
        .pdf-viewer object {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Media query for smaller screens (mobile view) */
        @media (max-width: 768px) {
            .pdf-viewer {
                max-width: 100%; /* Full width for mobile screens */
                max-height: 600px; /* Adjust height for smaller screens */
            }
        }

        @media (max-width: 480px) {
            .pdf-viewer {
                max-width: 100%; /* Full width for very small screens */
                max-height: 400px; /* Further reduce height for small devices */
            }
        }

        .download-btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 0;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .download-btn:hover {
            background-color: #0056b3;
        }
        .bold-title {
    font-weight: bold;
    font-size: 20px; /* Adjust the font size as needed */
}

.justify-text {
    text-align: justify;
    text-indent: 3em;
    font-size: 18px;
}
.text-dark{
    font-size: 15px;
    font-weight: 12px;
}


    </style>
</head>
<body>
    
<?php  
 if(isset($_POST["upload_id"])) {  
    $output = '';  
    include('Includes/conn.php');
    session_start();

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
            <p class="card-text text-dark fs-5">
<span class="text-darks ">Thesis/Capstone title </span><br>' . 
'<span class="bold-title">' .'"'. $result_up['upload_name'] .'"'. '</span>' . ' <br><br>

                <span class="text-dark ">Author(s): 
                </span><br>'.$result_up['upload_author'].'<br><br>
                <span class="text-dark ">Department: </span><br>'.$result_dept['department_name'].'<br><br>
                <span class="text-dark ">Abstract:</span><br>
<p class="justify-text">' . $result_up['upload_abstract'] . '</p>

            </p>
        </div>';

    if (isset($_SESSION['student_id'])) {
        $student_id = $_SESSION['student_id'];

        $query_status = "SELECT student_status FROM student WHERE student_id = '$student_id'";
        $run_query_status = mysqli_query($db, $query_status);
        $result_status = mysqli_fetch_assoc($run_query_status);
        $status = $result_status['student_status'];

        if ($status == 'Approved') {
            echo '
            <div class="pdf-viewer">
                <!-- Using object tag for better compatibility with mobile view -->
                <object data="Upload/'.$result_up['upload_file'].'" type="application/pdf" width="100%" height="100%">
                    <p>Your browser does not support embedded PDFs. You can <a href="Upload/'.$result_up['upload_file'].'">download the PDF</a> instead.</p>
                </object>
            </div>';

            echo '
            <a href="download.php?file='.$result_up['upload_file'].'" class="download-btn">
                <i class="bi bi-download"></i> Download PDF
            </a>';
        } else {
            echo '<p class="text-danger">You do not have permission to view this document.</p>';
        }
    }
 }
?>

</body>
</html>
