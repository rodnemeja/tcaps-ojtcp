<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Initialize statistics array
$stats = array(
    'total_patients' => 0,
    'total_doctors' => 0,
    'total_staff' => 0,
    'total_appointments' => 0,
    'today_appointments' => 0,
    'pending_appointments' => 0,
    'total_revenue' => 0,
    'completed_today' => 0,
    'confirmed_today' => 0,
    'cancelled_today' => 0,
    'total_completed' => 0,
    'total_approved' => 0,
    'new_appointments' => 0,
    'total_families' => 0,
    'avg_family_size' => 0,
    'pending_family_appointments' => 0
);

try {
    // Verify database connection
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    // Get new appointments count (last 24 hours)
    $sql_new = "SELECT COUNT(*) as count 
                FROM appointments 
                WHERE status = 'pending' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $result = mysqli_query($conn, $sql_new);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $stats['new_appointments'] = intval($row['count']);
        mysqli_free_result($result);
    }

    // Get all user counts in one query
    $sql_users = "SELECT 
        role, 
        COUNT(*) as count 
        FROM users 
        WHERE role IN ('patient', 'doctor', 'staff')
        GROUP BY role";
    
    $result = mysqli_query($conn, $sql_users);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $stats['total_' . $row['role'] . 's'] = intval($row['count']);
        }
        mysqli_free_result($result);
    }

    // Get today's appointment statistics
    $today = date('Y-m-d');
    $sql_today = "SELECT 
        COUNT(*) as total_today,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM appointments 
    WHERE DATE(appointment_date) >= CURDATE()";
    
    $result = mysqli_query($conn, $sql_today);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $stats['today_appointments'] = intval($row['total_today']);
        $stats['pending_appointments'] = intval($row['pending']);
        $stats['completed_today'] = intval($row['completed']);
        $stats['cancelled_today'] = intval($row['cancelled']);
        mysqli_free_result($result);
    }
    
    // Get total appointments (last 30 days)
    $sql_appointments = "SELECT COUNT(*) as count 
        FROM appointments 
        WHERE status != 'cancelled' 
        AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    
    $result = mysqli_query($conn, $sql_appointments);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $stats['total_appointments'] = intval($row['count']);
        mysqli_free_result($result);
    }

    // Get total completed appointments
    $sql_completed = "SELECT COUNT(*) as count 
        FROM appointments 
        WHERE status = 'completed'";
    
    $result = mysqli_query($conn, $sql_completed);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $stats['total_completed'] = intval($row['count']);
        mysqli_free_result($result);
    }

    // Get total revenue (last 7 days)
    $sql_revenue = "SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM invoices 
        WHERE payment_status = 'paid' 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    
    $result = mysqli_query($conn, $sql_revenue);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $stats['total_revenue'] = floatval($row['total']);
        mysqli_free_result($result);
    }

    // Get total approved appointments
    $sql_approved = "SELECT COUNT(*) as count 
        FROM appointments 
        WHERE status = 'approved'";

    $result = mysqli_query($conn, $sql_approved);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $stats['total_approved'] = intval($row['count']);
        mysqli_free_result($result);
    }

    // Get total pending appointments
$sql_pending = "SELECT COUNT(*) as count 
        FROM appointments 
        WHERE status = 'pending' 
        AND DATE(appointment_date) >= CURDATE()";

    $result = mysqli_query($conn, $sql_pending);
    if ($result && $row = mysqli_fetch_assoc($result)) {
    $stats['pending_appointments'] = intval($row['count']);
        mysqli_free_result($result);
    }

    // Get recent appointments
    $recent_appointments_sql = "SELECT 
        a.*,
        CONCAT(u.first_name, ' ', u.last_name) as patient_name,
        CONCAT(du.first_name, ' ', du.last_name) as doctor_name,
        (
            SELECT GROUP_CONCAT(s.name SEPARATOR ', ')
            FROM appointment_services aps
            JOIN services s ON aps.service_id = s.id
            WHERE aps.appointment_id = a.id
        ) as services
    FROM appointments a
    LEFT JOIN patients p ON a.patient_id = p.id
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN users du ON d.user_id = du.id
    GROUP BY a.id, a.appointment_date, a.appointment_time, a.status, a.created_at, 
             u.first_name, u.last_name, du.first_name, du.last_name
    ORDER BY 
        CASE 
            WHEN a.status = 'pending' THEN 0
            ELSE 1
        END,
        a.created_at DESC 
    LIMIT 5";

    $recent_appointments = [];
    $result_recent = mysqli_query($conn, $recent_appointments_sql);
    if ($result_recent) {
        while ($row = mysqli_fetch_assoc($result_recent)) {
            $recent_appointments[] = $row;
        }
        mysqli_free_result($result_recent);
    }

    // Calendar data query
    $calendar_sql = "SELECT 
        a.id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) as patient_name,
        GROUP_CONCAT(s.name SEPARATOR ', ') as services
    FROM appointments a
    LEFT JOIN patients p ON a.patient_id = p.id
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN appointment_services aps ON a.id = aps.appointment_id
    LEFT JOIN services s ON aps.service_id = s.id
    WHERE a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
    AND a.status = 'approved'
    GROUP BY a.id, a.appointment_date, a.appointment_time, a.status, u.first_name, u.middle_name, u.last_name
    ORDER BY a.appointment_date ASC, a.appointment_time ASC";

    $calendar_result = mysqli_query($conn, $calendar_sql);
    $calendar_events = array();

    if ($calendar_result) {
        while ($row = mysqli_fetch_assoc($calendar_result)) {
            $calendar_events[] = array(
                'id' => $row['id'],
                'title' => $row['patient_name'] . ' - ' . $row['services'],
                'start' => $row['appointment_date'] . 'T' . $row['appointment_time'],
                'end' => date('Y-m-d H:i:s', strtotime($row['appointment_date'] . ' ' . $row['appointment_time'] . ' +1 hour')),
                'status' => $row['status']
            );
        }
        mysqli_free_result($calendar_result);
    }

    // Get family statistics
    $sql_families = "SELECT COUNT(*) as total_families FROM family_codes";
    $result = mysqli_query($conn, $sql_families);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $stats['total_families'] = intval($row['total_families']);
        mysqli_free_result($result);
    }
    
    // Get average family size
    $sql_avg_size = "SELECT AVG(member_count) as avg_size FROM (
        SELECT family_code, COUNT(*) as member_count 
        FROM patients 
        WHERE family_code IS NOT NULL AND family_code != '' 
        GROUP BY family_code
    ) as family_sizes";
    $result = mysqli_query($conn, $sql_avg_size);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $stats['avg_family_size'] = round(floatval($row['avg_size']), 1);
        mysqli_free_result($result);
    }
    
    // Get pending appointments for families
    $sql_family_appointments = "SELECT COUNT(*) as count 
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE p.family_code IS NOT NULL AND p.family_code != ''
        AND a.status = 'pending' 
        AND a.appointment_date >= CURDATE()";
    $result = mysqli_query($conn, $sql_family_appointments);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $stats['pending_family_appointments'] = intval($row['count']);
        mysqli_free_result($result);
    }
    
    // Get family appointment activity for chart (last 7 days)
    $sql_family_activity = "SELECT 
        DATE(a.appointment_date) as date,
        COUNT(*) as total,
        COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as completed,
        COUNT(CASE WHEN a.status = 'cancelled' THEN 1 END) as cancelled
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE p.family_code IS NOT NULL 
        AND a.appointment_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
        GROUP BY DATE(a.appointment_date)
        ORDER BY date ASC";
        
    $family_activity = [];
    $result = mysqli_query($conn, $sql_family_activity);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $family_activity[] = $row;
        }
        mysqli_free_result($result);
    }
    
    // Get family role distribution for pie chart
    $sql_family_roles = "SELECT 
        family_role as role, 
        COUNT(*) as count 
        FROM patients 
        WHERE family_code IS NOT NULL AND family_code != '' AND family_role IS NOT NULL AND family_role != ''
        GROUP BY family_role
        ORDER BY count DESC";
        
    $family_roles = [];
    $role_labels = [];
    $role_counts = [];
    $role_colors = [
        'Parent' => 'rgba(78, 115, 223, 0.8)',
        'Child' => 'rgba(54, 185, 204, 0.8)',
        'Spouse' => 'rgba(246, 194, 62, 0.8)',
        'Grandparent' => 'rgba(231, 74, 59, 0.8)',
        'Sibling' => 'rgba(28, 200, 138, 0.8)',
        'Other' => 'rgba(133, 135, 150, 0.8)'
    ];
    $chart_colors = [];
    
    $result = mysqli_query($conn, $sql_family_roles);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $family_roles[] = $row;
            $role_labels[] = $row['role'] . ' (' . $row['count'] . ')';
            $role_counts[] = intval($row['count']);
            
            // Set color based on role or use default gray
            if (isset($role_colors[$row['role']])) {
                $chart_colors[] = $role_colors[$row['role']];
            } else {
                $chart_colors[] = 'rgba(133, 135, 150, 0.8)';
            }
        }
        mysqli_free_result($result);
    }
    
    // Format data for chart
    $activity_dates = [];
    $activity_totals = [];
    $activity_completed = [];
    $activity_cancelled = [];
    
    // Fill dates for the last 7 days
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $formatted_date = date('M d', strtotime($date));
        $activity_dates[] = $formatted_date;
        
        // Default values
        $found = false;
        foreach ($family_activity as $activity) {
            if ($activity['date'] == $date) {
                $activity_totals[] = intval($activity['total']);
                $activity_completed[] = intval($activity['completed']);
                $activity_cancelled[] = intval($activity['cancelled']);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $activity_totals[] = 0;
            $activity_completed[] = 0;
            $activity_cancelled[] = 0;
        }
    }
    
    // Get recent families
    $sql_recent_families = "SELECT 
        fc.id, 
        fc.name, 
        fc.code, 
        fc.created_at, 
        COUNT(p.id) as member_count,
        CONCAT(u.first_name, ' ', u.last_name) as created_by_name
        FROM family_codes fc
        LEFT JOIN patients p ON fc.code = p.family_code
        LEFT JOIN patients creator ON fc.created_by = creator.id
        LEFT JOIN users u ON creator.user_id = u.id
        GROUP BY fc.id, fc.name, fc.code, fc.created_at, u.first_name, u.last_name
        ORDER BY fc.created_at DESC
        LIMIT 5";
    
    $recent_families = [];
    $result = mysqli_query($conn, $sql_recent_families);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $recent_families[] = $row;
        }
        mysqli_free_result($result);
    }

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $error_message = "An error occurred while loading the dashboard. Please try again later.";
}

$page_title = "Dashboard";
$current_page = "dashboard";
require_once "includes/header.php";
?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <?php if (isset($error_message)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        <div class="d-flex align-items-center">
            <!-- Notification Icon -->
            <div class="dropdown me-3">
                <button class="btn btn-link text-gray-600 position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell fa-lg"></i>
                    <?php if ($stats['new_appointments'] > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?php echo $stats['new_appointments']; ?>
                    </span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                    <div class="dropdown-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Recent Appointments</h6>
                        <a href="appointments.php" class="btn btn-sm btn-link text-primary p-0">View All</a>
                    </div>
                    <div class="notification-list">
                        <?php
                        $recent_appointments_result = mysqli_query($conn, $recent_appointments_sql);
                        if(mysqli_num_rows($recent_appointments_result) > 0):
                            while($appointment = mysqli_fetch_assoc($recent_appointments_result)):
                                $is_new = (strtotime($appointment['created_at']) > strtotime('-1 hour'));
                                $status_color = $appointment['status'] === 'completed' ? 'success' : 
                                              ($appointment['status'] === 'cancelled' ? 'danger' : 
                                              ($appointment['status'] === 'pending' ? 'warning' : 'info'));
                                $is_pending = $appointment['status'] === 'pending';
                        ?>
                            <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" 
                               class="dropdown-item notification-item <?php echo $is_pending ? 'pending-notification' : ''; ?>">
                                <div class="d-flex align-items-center">
                                    <div class="notification-icon me-2 <?php echo $is_pending ? 'bg-warning-subtle' : ''; ?>">
                                        <i class="fas fa-calendar-check <?php echo $is_pending ? 'text-warning' : 'text-primary'; ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0 small <?php echo $is_pending ? 'text-warning' : ''; ?>">
                                                <?php echo htmlspecialchars($appointment['patient_name']); ?>
                                                <?php if ($is_new): ?>
                                                    <span class="badge bg-danger ms-2 small">New</span>
                                                <?php endif; ?>
                                            </h6>
                                            <span class="badge bg-<?php echo $status_color; ?> small">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                        </div>
                                        <small class="text-muted d-block">
                                            <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?> at 
                                            <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-user-md"></i> <?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Not assigned'); ?>
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($appointment['services'] ?? 'No services selected'); ?>
                                        </small>
                                    </div>
                                </div>
                            </a>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <div class="dropdown-item text-center py-3 text-muted">No recent appointments</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <a href="reports.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4">
        <!-- Patients Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm rounded-lg bg-white">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="icon-circle bg-primary-subtle">
                            <i class="fas fa-users text-primary"></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link text-muted p-0" type="button">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase fs-12 fw-semibold mb-2">Total Patients</h6>
                        <h3 class="mb-0 text-primary"><?php echo $stats['total_patients']; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approved Appointments Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm rounded-lg bg-white">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="icon-circle bg-info-subtle">
                            <i class="fas fa-calendar-check text-info"></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link text-muted p-0" type="button">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase fs-12 fw-semibold mb-2">Approved Appointments</h6>
                        <h3 class="mb-0 text-info"><?php echo $stats['total_approved']; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completed Appointments Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm rounded-lg bg-white">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="icon-circle bg-success-subtle">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link text-muted p-0" type="button">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase fs-12 fw-semibold mb-2">Completed Appointments</h6>
                        <h3 class="mb-0 text-success"><?php echo $stats['total_completed']; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weekly Revenue Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm rounded-lg bg-white">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="icon-circle bg-warning-subtle">
                            <i class="fas fa-peso-sign text-warning"></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link text-muted p-0" type="button">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase fs-12 fw-semibold mb-2">Weekly Revenue</h6>
                        <h3 class="mb-0 text-warning">₱<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Appointments Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm rounded-lg bg-white">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="icon-circle bg-warning-subtle">
                            <i class="fas fa-clock text-warning"></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link text-muted p-0" type="button">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase fs-12 fw-semibold mb-2">
                            Pending Appointments
                            <?php if ($stats['new_appointments'] > 0): ?>
                            <span class="badge bg-danger ms-2 blink">New</span>
                            <?php endif; ?>
                        </h6>
                        <h3 class="mb-0 text-warning"><?php echo $stats['pending_appointments']; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Families Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm rounded-lg bg-white">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="icon-circle bg-purple-subtle">
                            <i class="fas fa-users text-purple"></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link text-muted p-0" type="button">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase fs-12 fw-semibold mb-2">Total Families</h6>
                        <h3 class="mb-0 text-purple"><?php echo $stats['total_families']; ?></h3>
                        <small class="text-muted">Avg: <?php echo $stats['avg_family_size']; ?> members</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Family Appointments Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm rounded-lg bg-white">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="icon-circle bg-teal-subtle">
                            <i class="fas fa-calendar-day text-teal"></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link text-muted p-0" type="button">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase fs-12 fw-semibold mb-2">Family Appointments</h6>
                        <h3 class="mb-0 text-teal"><?php echo $stats['pending_family_appointments']; ?></h3>
                        <p class="text-muted mb-0">Pending appointments from family members</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Section -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Appointment Calendar</h6>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary active" onclick="changeView('dayGridMonth')">Month</button>
                        <button class="btn btn-sm btn-outline-primary" onclick="changeView('timeGridWeek')">Week</button>
                        <button class="btn btn-sm btn-outline-primary" onclick="changeView('timeGridDay')">Day</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>  
    <!-- Recent Families -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Families</h6>
                    <a href="family_profiles.php" class="btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-users fa-sm text-white-50"></i> View All Families
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Family Name</th>
                                    <th>Code</th>
                                    <th>Members</th>
                                    <th>Created By</th>
                                    <th>Created Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_families)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No families found</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_families as $family): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($family['name']); ?></td>
                                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($family['code']); ?></span></td>
                                        <td><?php echo $family['member_count']; ?></td>
                                        <td><?php echo htmlspecialchars($family['created_by_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($family['created_at'])); ?></td>
                                        <td>
                                            <a href="view_family.php?code=<?php echo urlencode($family['code']); ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- End of Main Content -->

<!-- FullCalendar Scripts -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.js'></script>

<style>
/* Modern Dashboard Styles */
:root {
    --primary-rgb: 78, 115, 223;
    --info-rgb: 54, 185, 204;
    --success-rgb: 28, 200, 138;
    --warning-rgb: 246, 194, 62;
    --danger-rgb: 231, 74, 59;
}

body {
    background-color: #f8f9fc;
}

.card {
    transition: all 0.3s ease;
    border: none !important;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.icon-circle {
    height: 45px;
    width: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.bg-primary-subtle { background-color: rgba(var(--primary-rgb), 0.1); }
.bg-info-subtle { background-color: rgba(var(--info-rgb), 0.1); }
.bg-success-subtle { background-color: rgba(var(--success-rgb), 0.1); }
.bg-warning-subtle { background-color: rgba(var(--warning-rgb), 0.1); }
.bg-danger-subtle { background-color: rgba(var(--danger-rgb), 0.1); }

.fs-12 {
    font-size: 0.75rem;
}

.fw-semibold {
    font-weight: 600;
}

h3 {
    font-size: 1.75rem;
    font-weight: 700;
}

/* Calendar Modernization */
.card.shadow {
    border-radius: 1rem !important;
    overflow: hidden;
}

.card-header {
    background-color: #fff !important;
    border-bottom: 1px solid rgba(0,0,0,0.05) !important;
    padding: 1.25rem !important;
}

.btn-outline-primary {
    border-radius: 50rem !important;
    padding: 0.5rem 1.25rem !important;
    font-weight: 500;
}

.table {
    border: none;
    margin-bottom: 0;
}

.table th {
    border-top: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    padding: 1rem;
    background-color: #f8f9fc;
}

.table td {
    vertical-align: middle;
    padding: 1rem;
    border-color: rgba(0,0,0,0.05);
}

.badge {
    padding: 0.5em 1em;
    border-radius: 50rem;
    font-weight: 500;
}

#calendar {
    margin: 0 auto;
    padding: 10px;
    width: 100%;
    min-height: 400px;
}

.fc-toolbar-title {
    font-size: 1.25em !important;
    font-weight: 600 !important;
}

.fc-button-primary {
    background-color: #4e73df !important;
    border-color: #4e73df !important;
}

.fc-button-primary:hover {
    background-color: #2e59d9 !important;
    border-color: #2e59d9 !important;
}

.fc-daygrid-day {
    min-height: 60px !important;
}

.fc-event {
    margin: 1px 0;
    padding: 1px 3px;
    cursor: pointer;
    border-radius: 2px;
    font-size: 0.85em;
}

.fc-daygrid-event {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    font-size: 0.85em;
}

.status-completed {
    background-color: #1cc88a !important; 
    border-color: #1cc88a !important;
}

.status-scheduled {
    background-color: #f6c23e !important; 
    border-color: #f6c23e !important;
}

.status-cancelled {
    background-color: #e74a3b !important; 
    border-color: #e74a3b !important;
}

.status-pending {
    background-color: #4e73df !important;
    border-color: #4e73df !important;
}

.fc-day-today {
    background-color: rgba(78, 115, 223, 0.1) !important;
}

.fc-day-today .fc-daygrid-day-number {
    background-color: #4e73df;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 3px;
    font-size: 0.9em;
}

@media screen and (max-width: 768px) {
    #calendar {
        padding: 8px;
        min-height: 350px;
    }

    .fc {
        font-size: 0.85em;
    }

    .fc-toolbar-title {
        font-size: 1em !important;
    }

    .fc-button {
        padding: 0.15rem 0.3rem !important;
        font-size: 0.85em !important;
    }
}

@media screen and (max-width: 480px) {
    #calendar {
        padding: 5px;
        min-height: 250px;
    }

    .fc {
        font-size: 0.75em;
    }

    .fc-toolbar-title {
        font-size: 0.9em !important;
    }

    .fc-button {
        padding: 0.1rem 0.25rem !important;
        font-size: 0.75em !important;
    }
}

.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    padding: 4px 8px;
    border-radius: 50%;
    background-color: #dc3545;
    color: white;
    font-size: 12px;
    font-weight: bold;
}
.new-appointment {
    position: relative;
}
.new-appointment::after {
    content: 'New';
    position: absolute;
    top: 0;
    right: 0;
    background-color: #dc3545;
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
}
.blink {
    animation: blink-animation 1s steps(5, start) infinite;
}
@keyframes blink-animation {
    to {
        visibility: hidden;
    }
}

/* Add these styles after your existing styles */
.notification-dropdown {
    width: 350px;
    max-height: 500px;
    padding: 0;
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    display: none; /* Hide by default */
}

.notification-dropdown.show {
    display: block; /* Show when dropdown is active */
}

.notification-dropdown .dropdown-header {
    background-color: #f8f9fc;
    padding: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.notification-list {
    max-height: 400px;
    overflow-y: auto;
}

.notification-item {
    padding: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    transition: background-color 0.3s;
}

.notification-item:hover {
    background-color: #f8f9fc;
    text-decoration: none;
}

.new-notification {
    background-color: rgba(var(--primary-rgb), 0.05);
}

.new-notification::after {
    content: 'New';
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background-color: #dc3545;
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 3px;
    font-size: 0.75rem;
}

.notification-item h6 {
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
    color: #4e73df;
}

.notification-item small {
    font-size: 0.75rem;
}

.notification-item p {
    font-size: 0.8125rem;
    margin-bottom: 0;
    color: #666;
}

.notification-item .badge {
    font-size: 0.75rem;
    padding: 0.25em 0.5em;
}

/* Remove the old Recent Appointments card styles */
.new-appointment::after {
    display: none;
}

.dropdown-menu {
    margin-top: 0.5rem !important;
}

.notification-dropdown {
    min-width: 350px;
    max-width: 350px;
    padding: 0;
}

.notification-list {
    max-height: 400px;
    overflow-y: auto;
}

.notification-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    text-decoration: none !important;
}

.notification-item:hover {
    background-color: rgba(var(--primary-rgb), 0.05);
}

.notification-item h6 {
    color: var(--bs-primary);
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
}

.notification-item small {
    display: block;
    color: var(--bs-gray-600);
    font-size: 0.75rem;
}

.notification-item p {
    margin: 0.25rem 0 0;
    color: var(--bs-gray-700);
    font-size: 0.8125rem;
}

.dropdown-header {
    background-color: var(--bs-light);
    padding: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.dropdown-header h6 {
    color: var(--bs-gray-700);
    font-weight: 600;
}

.new-notification {
    background-color: rgba(var(--primary-rgb), 0.05);
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Recent Appointments Styles */
.list-group-item-action {
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}

.list-group-item-action:hover {
    border-left-color: var(--bs-primary);
    background-color: rgba(var(--primary-rgb), 0.05);
    transform: translateX(5px);
}

.list-group-item-action.bg-light {
    border-left-color: var(--bs-danger);
    background-color: rgba(var(--danger-rgb), 0.05) !important;
}

.list-group-item-action.bg-light:hover {
    background-color: rgba(var(--danger-rgb), 0.1) !important;
}

.list-group-item h6 {
    font-size: 0.875rem;
}

.list-group-item .badge {
    padding: 0.35em 0.65em;
}

.card-footer {
    background-color: transparent !important;
    padding: 0.5rem !important;
}

.card-footer a:hover {
    text-decoration: underline !important;
}

/* Custom scrollbar for the appointments list */
.card-body::-webkit-scrollbar {
    width: 6px;
}

.card-body::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.card-body::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.card-body::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Add these styles to your existing styles */
.swal2-popup-custom {
    border-radius: 15px;
    padding: 2rem;
}

.swal2-title-custom {
    color: #4e73df;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.swal2-html-container-custom {
    text-align: left;
    font-size: 1rem;
    color: #5a5c69;
}

.swal2-html-container-custom strong {
    color: #4e73df;
    font-weight: 600;
}

.swal2-html-container-custom .badge {
    font-size: 0.875rem;
    font-weight: 500;
}

/* Notification Styles */
.notification-item {
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}

.notification-item:hover {
    background-color: rgba(78, 115, 223, 0.05);
    border-left-color: #4e73df;
}

.notification-item.bg-light {
    background-color: rgba(220, 53, 69, 0.05) !important;
    border-left-color: #dc3545;
}

.notification-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: rgba(78, 115, 223, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
}

.notification-item h6 {
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.notification-item small {
    font-size: 0.75rem;
}

.notification-item .badge {
    font-size: 0.75rem;
    padding: 0.25em 0.5em;
}

/* Custom scrollbar */
.card-body::-webkit-scrollbar {
    width: 4px;
}

.card-body::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.card-body::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 2px;
}

.card-body::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Floating Window Styles */
.notification-item {
    transition: all 0.2s ease;
    border-left: 2px solid transparent;
    font-size: 0.85rem;
}

.notification-item:hover {
    background-color: rgba(78, 115, 223, 0.05);
    border-left-color: #4e73df;
}

.notification-item.bg-light {
    background-color: rgba(220, 53, 69, 0.05) !important;
    border-left-color: #dc3545;
}

.notification-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background-color: rgba(78, 115, 223, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}

.notification-item h6 {
    font-size: 0.8rem;
    margin-bottom: 0.2rem;
}

.notification-item small {
    font-size: 0.7rem;
}

.notification-item .badge {
    font-size: 0.7rem;
    padding: 0.2em 0.4em;
}

/* Custom scrollbar */
.card-body::-webkit-scrollbar {
    width: 3px;
}

.card-body::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.card-body::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 2px;
}

.card-body::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Card styles */
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .card {
        position: static !important;
        width: 100% !important;
        margin-bottom: 1rem;
    }
}

.pending-notification {
    background-color: rgba(246, 194, 62, 0.05) !important;
    border-left: 3px solid #f6c23e !important;
}

.pending-notification:hover {
    background-color: rgba(246, 194, 62, 0.1) !important;
}

.notification-item {
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}

.notification-item:hover {
    background-color: rgba(78, 115, 223, 0.05);
    border-left-color: #4e73df;
}

.notification-item.pending-notification:hover {
    background-color: rgba(246, 194, 62, 0.1) !important;
    border-left-color: #f6c23e !important;
}

.notification-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.bg-warning-subtle {
    background-color: rgba(246, 194, 62, 0.1);
}

.text-warning {
    color: #f6c23e !important;
}

/* Remove the new-notification styles */
.new-notification::after {
    display: none;
}

.badge.bg-danger {
    font-size: 0.7rem;
    padding: 0.2em 0.4em;
    font-weight: 500;
}

.notification-item small {
    font-size: 0.75rem;
    line-height: 1.4;
}

.notification-item .badge {
    font-size: 0.7rem;
    padding: 0.2em 0.4em;
}

/* Remove the old new-notification styles */
.new-notification::after {
    display: none;
}

/* Update calendar event styles */
.status-approved {
    background-color: #1cc88a !important;
    border-color: #1cc88a !important;
}

.fc-event {
    background-color: #1cc88a !important;
    border-color: #1cc88a !important;
}

.fc-event-title {
    color: white !important;
}

.fc-event-time {
    color: white !important;
}

/* Remove other status colors since we only show approved appointments */
.status-completed,
.status-scheduled,
.status-cancelled,
.status-pending {
    display: none;
}
</style>

<script>
var calendar = null;

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    
    calendar = new FullCalendar.Calendar(calendarEl, {
        themeSystem: 'bootstrap5',
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        events: <?php echo json_encode($calendar_events); ?>,
        editable: false,
        selectable: true,
        selectMirror: true,
        dayMaxEvents: 2,
        dayMaxEventRows: 2,
        height: 'auto',
        contentHeight: 500,
        navLinks: true,
        displayEventTime: true,
        displayEventEnd: true,
        firstDay: 0,
        slotMinTime: "08:00:00",
        slotMaxTime: "18:00:00",
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            meridiem: 'short'
        },
        eventDisplay: 'block',
        eventClassNames: function(arg) {
            return ['status-approved'];
        },
        eventColor: '#1cc88a', // Green color for all events
        dateClick: function(info) {
            window.location.href = `appointment_form.php?date=${info.dateStr}`;
        },
        eventClick: function(info) {
            // Get the event data
            const event = info.event;
            const status = event.extendedProps.status;

            // Format the date and time
            const startDate = new Date(event.start);
            const formattedDate = startDate.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            const formattedTime = startDate.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });

            // Show SweetAlert with appointment details
            Swal.fire({
                title: event.title,
                html: `
                    <div class="text-start">
                        <div class="mb-3">
                            <strong>Date:</strong><br>
                            ${formattedDate}
                        </div>
                        <div class="mb-3">
                            <strong>Time:</strong><br>
                            ${formattedTime}
                        </div>
                        <div class="mb-3">
                            <strong>Status:</strong><br>
                            <span class="badge bg-success">
                                Approved
                            </span>
                        </div>
                    </div>
                `,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#4e73df',
                cancelButtonColor: '#858796',
                confirmButtonText: 'View Details',
                cancelButtonText: 'Close',
                customClass: {
                    popup: 'swal2-popup-custom',
                    title: 'swal2-title-custom',
                    htmlContainer: 'swal2-html-container-custom'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `view_appointment.php?id=${event.id}`;
                }
            });
        },
        moreLinkContent: function(args) {
            return '+' + args.num + ' more';
        }
    });
    
    calendar.render();
});

// Function to change calendar view
function changeView(viewName) {
    if (calendar) {
        calendar.changeView(viewName);
        calendar.updateSize();
    }
}
</script>

<!-- Add Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Family Activity Chart JS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart for Family Appointment Activity
    var ctx = document.getElementById('familyActivityChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($activity_dates); ?>,
                datasets: [
                    {
                        label: 'Total',
                        backgroundColor: 'rgba(78, 115, 223, 0.8)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        data: <?php echo json_encode($activity_totals); ?>,
                    },
                    {
                        label: 'Completed',
                        backgroundColor: 'rgba(40, 167, 69, 0.8)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        data: <?php echo json_encode($activity_completed); ?>,
                    },
                    {
                        label: 'Cancelled',
                        backgroundColor: 'rgba(220, 53, 69, 0.8)',
                        borderColor: 'rgba(220, 53, 69, 1)',
                        data: <?php echo json_encode($activity_cancelled); ?>,
                    }
                ],
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        ticks: {
                            beginAtZero: true,
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + ' appointments';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Chart for Family Role Distribution
    var roleCtx = document.getElementById('familyRoleChart');
    if (roleCtx) {
        new Chart(roleCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($role_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($role_counts); ?>,
                    backgroundColor: <?php echo json_encode($chart_colors); ?>,
                    hoverBackgroundColor: <?php echo json_encode($chart_colors); ?>,
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label;
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap dropdowns
    var dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(function(dropdown) {
        new bootstrap.Dropdown(dropdown);
    });

    // Add click event listener to notification icon
    const notificationDropdown = document.getElementById('notificationDropdown');
    const dropdownMenu = notificationDropdown.nextElementSibling;

    notificationDropdown.addEventListener('click', function(e) {
        e.preventDefault();
        dropdownMenu.classList.toggle('show');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!notificationDropdown.contains(e.target) && !dropdownMenu.contains(e.target)) {
            dropdownMenu.classList.remove('show');
        }
    });
});
</script>
</body>
</html>
<?php require_once "includes/footer.php"; ?> 