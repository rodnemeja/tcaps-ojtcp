<?php 
session_start();

if(isset($_SESSION['dean_id'])){
  $user_id = $_SESSION['dean_id'];
}else{
  echo "<script>window.location.href='../index';</script>";
}

?>


<!-- Sidebar -->
   <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

<!-- Sidebar - Brand -->
<a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
<img   src="../Images/stiilogo.png" width="50rem;" height="50rem;">

  <div class=" mx-1" style="font-size: 0.8rem;">COORDINATOR</div>
</a>

<!-- Divider -->
<!-- Divider -->
<hr class="sidebar-divider bg-white">

<!-- Nav Item - Dashboard -->
<li class="nav-item">
  <a class="nav-link" href="index">
    <i class="fas fa-fw fa-home text-white"  style="font-size: 1.1rem;"></i>
    <span  style="font-size: 1rem;" class="text-white">Dashboard</span></a>
</li>

 



 
 
 
<!-- Divider -->
<hr class="sidebar-divider bg-white">
<!-- Nav Item - STaff Collapse Menu -->
<li class="nav-item">
  <a class="nav-link" href="student">
    <i class="fas fa-fw fa-users" style="font-size: 1.1rem;"></i>
    <span  style="font-size: 1rem;">Students</span></a>
</li>

<hr class="sidebar-divider bg-white">

<!-- Nav Item - Pages Collapse Menu -->
<li class="nav-item">
  <a class="nav-link" href="dean1">
    <i class="fas fa-fw fa-users" style="font-size: 1.1rem;"></i>
    <span  style="font-size: 0.9rem;">Research Coordinator</span></a>
</li>

<hr class="sidebar-divider bg-white">
<!-- Nav Item - STaff Collapse Menu -->
<li class="nav-item">
  <a class="nav-link" href="upload">
    <i class="fas fa-fw fa-file" style="font-size: 1.1rem;"></i>
    <span  style="font-size: 11px;">Thesis/Capstone Pending</span></a>
</li>

<!-- Divider -->
<hr class="sidebar-divider bg-white">
<!-- Nav Item - STaff Collapse Menu -->
<li class="nav-item">
  <a class="nav-link" href="upload1">
    <i class="fas fa-fw fa-file" style="font-size: 1.1rem;"></i>
    <span  style="font-size: 0.9rem;">Thesis/Capstone List</span></a>
</li>

<hr class="sidebar-divider bg-white">
<!-- Nav Item - STaff Collapse Menu -->
<li class="nav-item">
  <a class="nav-link" href="../Research_Coordinator/plag">
    <i class="fas fa-fw fa-wrench" style="font-size: 1.1rem;"></i>
    <span  style="font-size: 0.9rem;">Plagiarism Checker</span></a>
</li>

<!-- Nav Item - STaff Collapse Menu -->
<hr class="sidebar-divider bg-white">
<li class="nav-item">
  <a class="nav-link" href="../Research_Coordinator/reports">
    <i class="fas fa-fw  fa fa-clock" style="font-size: 1.1rem;"></i>
    <span  style="font-size: 1rem;">Reports</span></a>
</li>




<!-- Divider -->
<!-- Divider -->
<hr class="sidebar-divider bg-white">

</ul>
<!-- End of Sidebar -->

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

      <!-- Main Content -->
      <div id="content">

        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow-sm">

          <!-- Sidebar Toggle (Topbar) -->
          <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
            <i class="fa fa-bars"></i>
          </button>
         

          <!-- Topbar Navbar -->
          <ul class="navbar-nav ml-auto">

            <div class="topbar-divider d-none d-sm-block"></div>

            <!-- Nav Item - User Information -->
            <li class="nav-item dropdown no-arrow">
              <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-800"  style="font-size: 1rem;">
                  <?php
                  echo $_SESSION['dean_name'];
                  ?>
              
                  
                </span>
                <img class="img-profile rounded-circle" src="../Images/head.jpg">
              </a>

              <!-- Dropdown - User Information -->
              <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                
                <a class="dropdown-item" href="../Includes/coordinator_logout">
                  <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                  Logout
                </a>
              </div>
            </li>

          </ul>

        </nav>
        <!-- End of Topbar -->

 

  
