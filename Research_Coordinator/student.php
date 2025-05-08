<?php
include('../Includes/header.php');
include('../Includes/conn.php');

include('../Includes/dean_navbar.php');
?>

<?php

if(isset($_POST['confirm_student_btn'])){
    $studID = $_POST['studentID'];
    $status = $_POST['status'];

    $query = "UPDATE student SET student_status = '$status' WHERE student_id = $studID";
	$query_run = mysqli_query($db, $query);

    mysqli_close($db);

}

if (isset($_POST['disapprove_student_btn'])) {
    $studID3 = $_POST['student_ID'];
    $status = $_POST['status'];

    $query3 = "UPDATE student SET student_status = '$status' WHERE student_id = $studID3";
    $query_run3 = mysqli_query($db, $query3);

    mysqli_close($db);
}




if(isset($_POST['delete_student_btn'])){
    $studID2 = $_POST['student_ID'];
     
    $query2 = "DELETE FROM student WHERE student_id = $studID2";
	$query_run2 = mysqli_query($db, $query2);

    mysqli_close($db);

}
?>

<!-- Begin Page Content -->
<div class="container-fluid">



    <!-- Room Tables -->
    <div class="card w3-white" style="margin-top: 10px; box-shadow: 0 1px 3px rgb(0 0 0 / 0.2);">

        <div class="">
            <div>
                <div class="d-flex justify-content-lg-between align-items-lg-baseline border-bottom-primary px-4 pt-3">
                    <p style="font-size: 1.4rem;" class="w3-left text-primary "><b>Students</b></p>

                    <div class="d-flex">

                        <input type="text" name="search_box" id="search_box" class="form-control" placeholder="Search..." />

                         
                    </div>
                </div>


                <div class="px-3 py-3">
                    <?php
                    if (isset($_SESSION['studentMessage'])) {
                        echo $_SESSION['studentMessage'];
                        unset($_SESSION['studentMessage']);
                    }

                    ?>


                    <div class="table" id="dynamic_content">
                    </div>
                </div>

            </div>
            <!-- End room tables -->
      

            <!-- View student details Modal -->
        <div id="view_student_details_modal" class="modal fade" tabindex="-1" aria-labelledby="view_details_modal_reservation" aria-hidden="true">
            <div class="modal-dialog ">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-gray-700" id="exampleModalLabel">Student Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body" id="student_details">

                    </div>
                </div>
            </div>
        </div>
        <!-- End of View student Details Modal -->

        <!-- Confirm  Modal -->
        <div class="modal fade" id="confirmmodal" tabindex="-1" aria-labelledby="confirmmodal" aria-hidden="true">
            <div class="modal-dialog ">
                <div class="modal-content">

                    <form action="#" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="studentID" id="confirm_stud_id">
                        <input type="hidden" name="status" value="Approved">

                        <div class="modal-body px-4 w3-center">
                            <i class="fa fa-check text-gray-400 fa-3x py-3"></i>
                            <h4> Are you sure to approve this student?</h4>
                            <h4 class="text-warning">This action cannot be undone!</h4>
                        </div>
                        <div class="pb-4 w3-center">
                            <button type="button" class="btn btn-danger w3-text-white px-5" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="confirm_student_btn" class="btn btn-primary px-5">Confirm</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <!-- Disapprove Modal -->
<div class="modal fade" id="disapprove_modal" tabindex="-1" aria-labelledby="disapprovemodal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="#" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="student_ID" id="disapprove_stud_id">
                <input type="hidden" name="status" value="Disapproved">
                <div class="modal-body px-4 w3-center">
                    <i class="fa fa-times text-gray-400 fa-3x py-3"></i>
                    <h4>Are you sure you want to disapprove this student?</h4>
                    <h4 class="text-warning">This action cannot be undone!</h4>
                </div>
                <div class="pb-4 w3-center">
                    <button type="button" class="btn btn-warning w3-text-white px-5" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="disapprove_student_btn" class="btn btn-danger px-5">Disapprove</button>
                </div>
            </form>
        </div>
    </div>
</div>



        <!-- Delete  Modal -->
        <div class="modal fade" id="delete_modal" tabindex="-1" aria-labelledby="deletemodal" aria-hidden="true">
            <div class="modal-dialog ">
                <div class="modal-content">

                    <form action="#" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="student_ID" id="delete_stud_id">
                        <div class="modal-body px-4 w3-center">
                            <i class="fa fa-check text-gray-400 fa-3x py-3"></i>
                            <h4> Are you sure to delete the student?</h4>
                            <h4 class="text-warning">This action cannot be undone!</h4>
                        </div>
                        <div class="pb-4 w3-center">
                            <button type="button" class="btn btn-warning w3-text-white px-5" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_student_btn" class="btn btn-primary px-5">Confirm</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>



            <?php
            include('../Includes/script.php');

            ?>




            <script>
                $(document).ready(function() {

                    load_data(1);

                    function load_data(page, query = '') {
                        $.ajax({
                            url: "fetch_student.php",
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


                    $(document).on('click', '.page-link', function() {
                        var page = $(this).data('page_number');
                        var query = $('#search_box').val();
                        load_data(page, query);
                    });



                    $('#search_box').keyup(function() {
                        var query = $('#search_box').val();
                        load_data(1, query);
                    });


                    $(document).on('click', '.editbtn', function() {
                        $('#editmodal').modal('show');

                        $tr = $(this).closest('tr');

                        var data = $tr.children("td").map(function() {
                            return $(this).text();
                        }).get();


                        console.log(data);
                        $('#deanID').val(data[0]);
                        $('#deanName').val(data[1]);
                        $('#deanUsername').val(data[2]);
                        $('#deanPassword').val(data[3]);

                    });

 


                    $(document).on('click', '.view_student', function() {
                    var student_id = $(this).attr("id");
                    if (student_id != '') {
                        $.ajax({
                            url: "student_details.php",
                            method: "POST",
                            data: {
                                student_id: student_id
                            },
                            success: function(data) {
                                $('#student_details').html(data);
                                $('#view_student_details_modal').modal('show');
                            }
                        });
                    }
                    });


                    $(document).on('click', '.confirm_btn', function(e) {
                    e.preventDefault();

                    var studentID = $(this).closest('tr').find('.student_id').text();
                    //console.log(staffid);
                    $('#confirm_stud_id').val(studentID);
                    $('#confirmmodal').modal('show');
                    });


                    $(document).on('click', '.delete_btn', function(e) {
                    e.preventDefault();

                    var student_ID = $(this).closest('tr').find('.student_id').text();
                    //console.log(staffid);
                    $('#delete_stud_id').val(student_ID);
                    $('#delete_modal').modal('show');
                    });

                    $(document).on('click', '.disapprove_btn', function(e) {
    e.preventDefault();

    var student_ID = $(this).closest('tr').find('.student_id').text();
    $('#disapprove_stud_id').val(student_ID);
    $('#disapprove_modal').modal('show');
});





                });

                $(document).ready(function() {
                    $("#flash-msg").delay(2000).fadeOut("slow");
                });
            </script>