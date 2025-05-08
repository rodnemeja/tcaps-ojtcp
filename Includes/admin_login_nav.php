<?php
include('Includes/conn.php'); 
session_start();

if(isset($_SESSION['id'])){
    $id = $_SESSION['id'];
  
    $query_student = "SELECT * FROM user WHERE id = $id";
    $run_query_student = mysqli_query($db, $query_student);
    $result_student = mysqli_fetch_assoc($run_query_student);
  
    $name = $result_student['name'];

    $btn = '
    <li class="nav-item dropdown no-arrow">
        <a class="nav-link text-dark font-weight-bold dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            '.$name.'
            <img class="ml-2 rounded-circle" src="Images/head.jpg" width="28rem;" height="28rem;">
        </a>

        <!-- Dropdown - User Information -->
        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
            <!-- Logout link that triggers modal -->
            <a class="dropdown-item" href="#" id="logoutButton">
                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                Logout
            </a>
        </div>
    </li>
    ';
}
?>

<!-- Modal for logout confirmation -->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to logout?
            </div>
            <div class="modal-footer">
                <!-- Cancel button, closes the modal -->
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <!-- Logout button, redirects to the logout page -->
                <a href="logout_student" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap & jQuery -->
<!-- Include these at the bottom of your HTML body (before closing </body> tag) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<!-- Custom Script to trigger modal -->
<script>
    $(document).ready(function() {
        // When the logout button is clicked, trigger the modal
        $('#logoutButton').on('click', function(e) {
            e.preventDefault(); // Prevent default logout action (no redirection)
            $('#logoutModal').modal('show'); // Show the modal
        });
    });
</script>
