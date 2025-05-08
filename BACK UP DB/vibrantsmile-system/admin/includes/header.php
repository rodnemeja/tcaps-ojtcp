<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Vibrant Smile Dental Clinic Management System</title>
    
    <!-- Bootstrap and other core dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- FullCalendar Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    
    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    
    <style>
        body {
            background: #f8f9fc;
            overflow-x: hidden;
        }
        .sidebar {
            min-height: 100vh;
            background: #4e73df;
            color: white;
            padding: 1rem;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
            width: 250px;
            overflow-y: auto;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 0.75rem 1rem;
            border-radius: 0.35rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
        }
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 0.5rem;
        }
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
            transform: translateX(5px);
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,.1);
            color: white;
            font-weight: 500;
        }
        .main-content {
            padding: 2rem;
            margin-left: 250px;
            transition: all 0.3s ease;
            min-height: 100vh;
            position: relative;
        }
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem;
        }
        .table-responsive {
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .table th {
            background-color: #f8f9fc;
            border-bottom: 2px solid #e3e6f0;
            white-space: nowrap;
        }
        .table td {
            vertical-align: middle;
        }
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .stats-card {
            border-left: 4px solid;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-card.primary {
            border-left-color: #4e73df;
        }
        .stats-card.success {
            border-left-color: #1cc88a;
        }
        .stats-card.info {
            border-left-color: #36b9cc;
        }
        .stats-card.warning {
            border-left-color: #f6c23e;
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .logo-container {
            padding: 1rem;
            margin-bottom: 1rem;
            text-align: center;
            border-radius: 0.5rem;
        }
        .logo-container img {
            max-width: 180px;
            height: auto;
            margin-bottom: 0.5rem;
            transition: transform 0.3s ease;
        }
        .logo-container img:hover {
            transform: scale(1.05);
        }
        .logo-container h4 {
            margin: 0;
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }
        .logo-container .admin-label {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.9);
            padding: 0.2rem 0.8rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            margin-top: 0.3rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        @media (min-width: 992px) {
            .sidebar {
                transform: none !important;
                display: block !important;
                width: 250px !important;
                position: fixed !important;
                height: 100vh !important;
                z-index: 1000 !important;
            }
            
            .main-content {
                margin-left: 250px !important;
                width: calc(100% - 250px) !important;
            }
        }
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .navbar-toggler {
                display: block;
                position: fixed;
                top: 1rem;
                left: 1rem;
                z-index: 1001;
                background: #4e73df;
                border: none;
                padding: 0.5rem;
                border-radius: 0.35rem;
                color: white;
            }
            .navbar-toggler:focus {
                box-shadow: none;
            }
            .sidebar-backdrop {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }
            .sidebar-backdrop.show {
                display: block;
            }
        }
        .admin-profile {
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
        }
        .admin-profile-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }
        .admin-profile h4 {
            font-size: 1rem;
            font-weight: 500;
        }
        .logo-img {
            max-width: 120px;
            margin: 0 auto;
            display: block;
        }
    </style>
</head>
<body>
    <!-- Mobile Navigation Toggle -->
    <button class="navbar-toggler d-lg-none" type="button" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 px-0 sidebar" id="sidebar">
                <div class="logo-container">
                    <img src="../assets/images/logo_vibrant.png" alt="Vibrant SmileDental Logo" class="img-fluid logo-img">
                    <?php
                    // Get current user's info
                    $admin_id = $_SESSION["id"];
                    $admin_query = "SELECT first_name, last_name, profile_picture FROM users WHERE id = ?";
                    $admin_stmt = mysqli_prepare($conn, $admin_query);
                    mysqli_stmt_bind_param($admin_stmt, "i", $admin_id);
                    mysqli_stmt_execute($admin_stmt);
                    $admin_result = mysqli_stmt_get_result($admin_stmt);
                    $admin_info = mysqli_fetch_assoc($admin_result);
                    $admin_name = $admin_info['first_name'] . ' ' . $admin_info['last_name'];
                    $profile_pic = $admin_info['profile_picture'] ? '../' . $admin_info['profile_picture'] : '../assets/img/default-profile.png';
                    ?>
                    <div class="admin-profile mb-3">
                        <div class="d-flex align-items-center justify-content-center">
                            <img src="<?php echo $profile_pic; ?>" alt="Admin Profile" class="admin-profile-img rounded-circle me-3">
                            <div class="text-start">
                                <h4 class="text-white mb-0"><?php echo htmlspecialchars($admin_name); ?></h4>
                                <span class="admin-label">Admin</span>
                            </div>
                        </div>
                    </div>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-home me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'appointments' ? 'active' : ''; ?>" href="appointments.php">
                            <i class="fas fa-calendar me-2"></i> Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'patients' ? 'active' : ''; ?>" href="patients.php">
                            <i class="fas fa-users me-2"></i> Patients
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'family_profiles' ? 'active' : ''; ?>" href="family_profiles.php">
                            <i class="fas fa-user-friends me-2"></i> Family Profiles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'staff' ? 'active' : ''; ?>" href="staff.php">
                            <i class="fas fa-user-md me-2"></i> Staff
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'services' ? 'active' : ''; ?>" href="services.php">
                            <i class="fas fa-list me-2"></i> Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'invoices' ? 'active' : ''; ?>" href="invoices.php">
                            <i class="fas fa-file-invoice-dollar me-2"></i> Invoices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'transactions' ? 'active' : ''; ?>" href="transactions.php">
                            <i class="fas fa-exchange-alt me-2"></i> Staff Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'messaging' ? 'active' : ''; ?>" href="messaging.php">
                            <i class="fas fa-comments me-2"></i> Messaging
                            <span class="badge bg-primary rounded-pill" id="message-count-admin" style="display:none; font-size: 0.7rem; padding: 0.25rem 0.5rem; margin-left: 0.5rem; vertical-align: middle;">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'profile' ? 'active' : ''; ?>" href="profile.php">
                            <i class="fas fa-user-circle me-2"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0);" onclick="confirmLogout()">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 main-content" id="mainContent">

<!-- Add this script before closing body tag -->
<script>
function confirmLogout() {
    Swal.fire({
        title: 'Ready to Leave?',
        text: "Are you sure you want to logout?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4e73df',
        cancelButtonColor: '#858796',
        confirmButtonText: 'Yes, Logout',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '../logout.php';
        }
    });
}

// Check for unread messages (admin)
document.addEventListener('DOMContentLoaded', function() {
    const messageCountBadge = document.getElementById('message-count-admin');
    if (!messageCountBadge) return;
    
    let previousCount = 0;
    
    // Add CSS for animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }
        .badge-pulse {
            animation: pulse 0.5s ease-in-out;
        }
    `;
    document.head.appendChild(style);
    
    function checkNewMessages() {
        // Skip if we're already on the messaging page
        if (window.location.pathname.endsWith('messaging.php')) return;
        
        fetch('messaging.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=check_new_messages'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let totalCount = 0;
                
                // Count all unread messages
                for (const userId in data.data) {
                    totalCount += parseInt(data.data[userId].count);
                }
                
                // Update the badge
                if (totalCount > 0) {
                    messageCountBadge.textContent = totalCount;
                    messageCountBadge.style.display = 'inline-block';
                    
                    // If the count increased, add animation
                    if (totalCount > previousCount) {
                        messageCountBadge.classList.add('badge-pulse');
                        setTimeout(() => {
                            messageCountBadge.classList.remove('badge-pulse');
                        }, 1000);
                    }
                    
                    // Update document title
                    document.title = `(${totalCount}) ${document.title.replace(/^\(\d+\)\s/, '')}`;
                } else {
                    messageCountBadge.textContent = '';
                    messageCountBadge.style.display = 'none';
                    document.title = document.title.replace(/^\(\d+\)\s/, '');
                }
                
                previousCount = totalCount;
            }
        })
        .catch(error => {
            console.error('Error checking for messages:', error);
        });
    }
    
    // Check immediately and then every 30 seconds
    checkNewMessages();
    setInterval(checkNewMessages, 30000);
});
</script> 