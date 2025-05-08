<?php
include('../Includes/header.php');
include('../Includes/conn.php');

include('../Includes/admin_navbar.php');
?>




<!-- Begin Page Content -->
<div class="container-fluid">



    <!-- Room Tables -->
    <div class="card w3-white" style="margin-top: 10px; box-shadow: 0 1px 3px rgb(0 0 0 / 0.2);">

        <div class="">
            <div>
                <div class="d-flex justify-content-lg-between align-items-lg-baseline border-bottom-primary px-4 pt-3">
                    <p style="font-size: 1.4rem;" class="w3-left text-primary "><b>Curriculum List</b></p>

                    <div class="d-flex">

                        <input type="text" name="search_box" id="search_box" class="form-control" placeholder="Search..." />

                        <button style="margin-left: 10px;" type="button" class=" btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                            +New
                        </button>
                    </div>
                </div>


                <div class="px-3 py-3">
                    <?php
                    if (isset($_SESSION['courseMessage'])) {
                        echo $_SESSION['courseMessage'];
                        unset($_SESSION['courseMessage']);
                    }

                    ?>


                    <div class="table" id="dynamic_content">
                    </div>
                </div>

            </div>
            <!-- End room tables -->

            <!-- Add Modal -->
            <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog  ">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add New Curriculum</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form action="../Includes/course_admin.php" method="POST" enctype="multipart/form-data">

                            <div class="modal-body px-4">
                                <input type="hidden" id="courseID" name="courseID">

                                <div class="mb-3">
                                    <label>Department</label>
                                    <select name="courseDepartment"  class="form-select">
                                        <option disabled selected hidden>Select Department</option>
                                        <?php 
                                         include('select_option_dean.php')
                                        ?>
                                    </select>
                                 </div>

                                 <div class="mb-3">
                                    <label>Curriculum Name</label>
                                    <input type="text" name="courseName"  class="form-control" required>
                                </div>


                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger w3-text-white px-4" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="courseSave" class="btn btn-primary px-4">Save</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
            <!-- End Add Modal -->


 <!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Curriculum</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="../Includes/course_admin.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="editCourseID" name="course_id">

                    <div class="mb-3">
                        <label>Course Name</label>
                        <input type="text" id="editCourseName" name="course_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Course Department</label>
                        <input type="number" id="editCourseDepartment" name="course_department" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_course" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

            <!-- End Edit Modal -->

            <!-- Edit Modal -->
            <!-- <div class="modal fade" id="editmodal" tabindex="-1" aria-labelledby="editmodal" aria-hidden="true">
                <div class="modal-dialog  ">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editmodal">Edit Curriculum Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form action="../Includes/course_admin.php" method="POST" enctype="multipart/form-data">

                            <div class="modal-body px-4">

                                <div class="mb-3">
                                    <label>Department</label>
                                    <select name="courseDepartment" id="courseDepartment" class="form-select">
                                        <option  disabled selected hidden>Select Department</option>
                                        <?php 
                                         include('select_option_dean.php')
                                        ?>
                                    </select>
                                 </div>

                                 <div class="mb-3">
                                    <label>Curriculum Name</label>
                                    <input type="text" name="courseName" id="courseName" class="form-control">
                                </div>

                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger w3-text-white px-3" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="cEdit" class="editbtn btn-primary px-3">Update</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div> -->
            <!-- End Edit Modal -->




            <?php
            include('../Includes/script.php');
            ?>




            <script>
                $(document).ready(function() {

                    load_data(1);

                    function load_data(page, query = '') {
                        $.ajax({
                            url: "fetch_courses.php",
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
                        $('#courseID').val(data[1]);
                        $('#courseDepartment').val(data[0]);
                        $('#courseName').val(data[2]);

                    });


                    $(document).on('click', '.delete_btn', function(e) {
                        e.preventDefault();

                        var amenID = $(this).closest('tr').find('.amen_id').text();
                        //console.log(roomid);
                        $('#amen_delete_id').val(amenID);
                        $('#deletemodal').modal('show');
                    });


                });

                $(document).ready(function() {
                    $("#flash-msg").delay(2000).fadeOut("slow");
                });

                function openEditModal(courseID, courseName, courseDepartment) {
    // Populate modal fields
    document.getElementById('editCourseID').value = courseID;
    document.getElementById('editCourseName').value = courseName;
    document.getElementById('editCourseDepartment').value = courseDepartment;

    // Show the modal
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

            </script>