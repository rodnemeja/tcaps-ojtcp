<?php
include('../Includes/header.php');
include('../Includes/conn.php');

include('../Includes/dean_navbar.php');
?>


<!-- Begin Page Content -->
<div class="container-fluid">



    <!-- Room Tables -->
    <div class="card w3-white" style="margin-top: 10px; box-shadow: 0 1px 3px rgb(0 0 0 / 0.2);">

        <div class="">
            <div>
                <div class="d-flex justify-content-lg-between align-items-lg-baseline border-bottom-primary px-4 pt-3">
                    <p style="font-size: 1.4rem;" class="w3-left text-primary "><b>Research Coordinator</b></p>

                    <div class="d-flex">

                        <input type="text" name="search_box" id="search_box" class="form-control" placeholder="Search..." />

                        <button style="margin-left: 10px;" type="button" class=" btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                            Add
                        </button>
                    </div>
                </div>


                <div class="px-3 py-3">
                    <?php
                    if (isset($_SESSION['deanMessage'])) {
                        echo $_SESSION['deanMessage'];
                        unset($_SESSION['deanMessage']);
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
                            <h5 class="modal-title" id="exampleModalLabel">Add New Coordinator</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form action="../Includes/dean_coor.php" method="POST" enctype="multipart/form-data">

                            <div class="modal-body px-4">
                                <input type="hidden" name="departmentID">

                            <div class="mb-3">
                                <label>Department</label>
                                <select name="deanDepartment" class="form-select" required>
                                    <option value="" disabled selected hidden>Select Department</option>
                                    <?php 
                                    include('select_option_dean.php');
                                    ?>
                                </select>
                            </div>


                                 <div class="mb-3">
                                    <label>Name</label>
                                    <input type="text" name="deanName"  class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label>Username</label>
                                    <input type="text" name="deanUsername" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label>Password</label>
                                    <input type="text" name="deanPassword" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="deanEmail" class="text-gray-700 form-control"  required>
                            </div>

                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger w3-text-white px-4" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="coorSave" class="btn btn-primary px-4">Save</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
            <!-- End Add Modal -->



            <!-- Edit Modal -->
            <div class="modal fade" id="editmodal" tabindex="-1" aria-labelledby="editmodal" aria-hidden="true">
                <div class="modal-dialog  ">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editmodal">Edit Coordinator Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form action="../Includes/server.php" method="POST" enctype="multipart/form-data">

                            <div class="modal-body px-4">
                                <input type="hidden" id="deanID" name="deanID">

                                <div class="mb-3">
                                    <label>Department</label>
                                    <select name="deanDepartment" id="deanDepartment" class="form-select">
                                        <option disabled selected hidden>Select Department</option>
                                        <?php 
                                         include('select_option_dean.php')
                                        ?>
                                    </select>
                                 </div>

                                 <div class="mb-3">
                                    <label>Name</label>
                                    <input type="text" name="deanName" id="deanName" class="form-control" required>
                                </div>

                                 <div class="mb-3">
                                    <label>Username</label>
                                    <input type="text" name="deanUsername" id="deanUsername" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label>Password</label>
                                    <input type="text" name="deanPassword" id="deanPassword" class="form-control">
                                </div>
                                <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="deanEmail" id="deanEmail" class="form-control"  required>
                            </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger w3-text-white px-3" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="deanEdit" class="btn btn-primary px-3">Update</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
            <!-- End Edit Modal -->

            <!-- Delete Modal -->
            <div class="modal fade" id="deletemodal" tabindex="-1" aria-labelledby="editmodal" aria-hidden="true">
                <div class="modal-dialog ">
                    <div class="modal-content">


                        <form action="../Includes/dean_coor.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="deanID" id="amen_delete_id">
                            <div class="modal-body px-4 w3-center">
                                <i class="fa fa-trash text-gray-400 fa-3x py-3"></i>
                                <h4> Are you sure to delete the Account?</h4>
                                <h4 class="text-danger">This action cannot be undone!</h4>
                            </div>
                            <div class="pb-4 w3-center">
                                <button type="button" class="btn btn-danger w3-text-white px-5" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="deanDelete" class="btn btn-primary px-5">Confirm</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
            <!-- End Delete Modal -->



            <?php
            include('../Includes/script.php');
            ?>




            <script>
                $(document).ready(function() {

                    load_data(1);

                    function load_data(page, query = '') {
                        $.ajax({
                            url: "fetch_dean.php",
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
                        $('#deanDepartment').val(data[5]);
                        $('#deanName').val(data[1]);
                        $('#deanUsername').val(data[4]);
                        $('#deanPassword').val(data[7]);
                        $('#deanEmail').val(data[2]);

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
            </script>