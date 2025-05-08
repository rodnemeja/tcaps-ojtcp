<?php  
if (isset($_POST["student_id"])) {  
    $output = '';  
    include('../Includes/conn.php');

    $stud_id = $_POST["student_id"];
    $query_stud = "SELECT * FROM student WHERE student_id = '$stud_id'";  
    $run_query_stud = mysqli_query($db, $query_stud);
    $result_stud = mysqli_fetch_assoc($run_query_stud);
    $dept = $result_stud['student_department'];

    $query_dept = "SELECT * FROM department WHERE department_id = '$dept'";  
    $run_query_dept = mysqli_query($db, $query_dept);
    $result_dept = mysqli_fetch_assoc($run_query_dept);

    $query_upload = "SELECT * FROM upload WHERE upload_student_id = '$stud_id'";  
    $run_query_upload = mysqli_query($db, $query_upload);

    // Set default images if files are missing
    $profile_image_path = "../Upload/" . $result_stud['profile_image'];
    if (!file_exists($profile_image_path) || empty($result_stud['profile_image'])) {
        $profile_image_path = "path/to/default/profile/image.jpg";
    }

    $id_image_path = "../Upload/" . $result_stud['id_image'];
    if (!file_exists($id_image_path) || empty($result_stud['id_image'])) {
        $id_image_path = "path/to/default/id/image.jpg";
    }

    echo '
        <div class="profile-details text-center">
            <img src="' . $profile_image_path . '" alt="Profile Picture" class="rounded-circle" style="width: 200px; height: 200px; object-fit: cover;">
            <h3 class="mt-3">' . $result_stud['student_name'] . ' ' . $result_stud['student_lastname'] . '</h3>
            <p>Student ID: ' . $result_stud['student_id'] . '</p>
            <p>Course: ' . $result_stud['student_section'] . '</p>
            <p>Department: ' . $result_dept['department_name'] . '</p>
            <p>Email: ' . $result_stud['student_username'] . '</p>
        </div>
        <p class="text-muted">Verification</p>
        <div class="id-image text-center mt-3">
            <img src="' . $id_image_path . '" alt="School Id" class="img-thumbnail" style="width: 200px; height: 300px; object-fit: cover;" onclick="zoomImage(this)">
        </div>
        <center>
            <table>
                <tr>
                    <td class="d-none student_id">' . $result_stud['student_id'] . '</td>
                    <td class="pl-2"><button type="button" class="btn btn-danger disapprove_btn">Disapprove</button>
</td>
                    <td class="pl-2"><button type="button" class="px-3 btn btn-primary confirm_btn" data-bs-dismiss="modal">Approve</button></td>
                </tr>
            </table>  
        </center>
        <style>
            .zoom-modal {
                display: none;
                position: fixed;
                z-index: 1050;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0, 0, 0, 0.9);
                justify-content: center;
                align-items: center;
            }
            .zoom-modal img {
                margin: auto;
                display: block;
                width: 20%;
                max-width: 90%;
                max-height: 90%;
            }
            .zoom-modal .close {
                position: absolute;
                top: 15px;
                right: 35px;
                color: #f1f1f1;
                font-size: 40px;
                font-weight: bold;
                cursor: pointer;
            }
        </style>
        <div id="zoomModal" class="zoom-modal">
            <span class="close" onclick="closeZoom()">&times;</span>
            <img class="zoom-content" id="zoomedImage">
        </div>
        <script>
            function zoomImage(img) {
                var modal = document.getElementById("zoomModal");
                var zoomedImg = document.getElementById("zoomedImage");
                zoomedImg.src = img.src;
                modal.style.display = "flex";
            }

            function closeZoom() {
                var modal = document.getElementById("zoomModal");
                modal.style.display = "none";
            }
        </script>
    ';
}
?>
