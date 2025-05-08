<?php
include('../Includes/header.php');
include('../Includes/conn.php');

include('../Includes/dean_navbar.php');
?>

<?php


if(isset($_POST['delete_upload_btn'])){
    $studID2 = $_POST['upload_ID'];
    $query2 = "DELETE FROM upload WHERE upload_id = $studID2";
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
                    <p style="font-size: 1.4rem;" class="w3-left text-primary "><b>Uploaded Thesis and Capstone</b></p>

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
      

            <!-- View upload details Modal -->
        <div id="view_upload_details_modal" class="modal fade" tabindex="-1" aria-labelledby="view_details_modal_reservation" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-gray-700" id="exampleModalLabel">Thesis/Capstone Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body" id="upload_details">

                    </div>
                </div>
            </div>
        </div>
        <!-- End of View student Details Modal -->

        


        <!-- Delete  Modal -->
        <div class="modal fade" id="delete_modal" tabindex="-1" aria-labelledby="deletemodal" aria-hidden="true">
            <div class="modal-dialog ">
                <div class="modal-content">

                    <form action="#" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="upload_ID" id="delete_stud_id">
                        <div class="modal-body px-4 w3-center">
                            <i class="fa fa-check text-gray-400 fa-3x py-3"></i>
                            <h4> Are you sure to delete the uploaded Research Paper?</h4>
                            <h4 class="text-warning">This action cannot be undone!</h4>
                        </div>
                        <div class="pb-4 w3-center">
                            <button type="button" class="btn btn-warning w3-text-white px-5" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_upload_btn" class="btn btn-primary px-5">Confirm</button>
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
                            url: "fetch_upload1.php",
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


                    $(document).on('click', '.confirm_btn', function(e) {
                    e.preventDefault();

                    var uploadID = $(this).closest('tr').find('.upload_id').text();
                    //console.log(staffid);
                    $('#confirm_stud_id').val(uploadID);
                    $('#confirmmodal').modal('show');
                    });


                    $(document).on('click', '.delete_btn', function(e) {
                    e.preventDefault();

                    var upload_ID = $(this).closest('tr').find('.upload_id').text();
                    //console.log(staffid);
                    $('#delete_stud_id').val(upload_ID);
                    $('#delete_modal').modal('show');
                    });




                });

                $(document).ready(function() {
                    $("#flash-msg").delay(2000).fadeOut("slow");
                });
            </script>