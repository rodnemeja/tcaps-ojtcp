<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

$page_title = "Appointments";
$current_page = "appointments";

// Pagination settings
$appointments_per_page = 10;
$current_page_num = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page_num - 1) * $appointments_per_page;

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base SQL for WHERE clause
$where_clause = "a.appointment_date >= CURDATE()";

// Add status filter
if ($status_filter !== 'all') {
    $where_clause .= " AND a.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

// Add search filter
if (!empty($search_term)) {
    $search_term_escaped = mysqli_real_escape_string($conn, $search_term);
    $where_clause .= " AND (
        u.first_name LIKE '%{$search_term_escaped}%' OR
        u.last_name LIKE '%{$search_term_escaped}%' OR
        u.email LIKE '%{$search_term_escaped}%' OR
        u.phone LIKE '%{$search_term_escaped}%' OR
        s.name LIKE '%{$search_term_escaped}%' OR
        CONCAT(du.first_name, ' ', du.last_name) LIKE '%{$search_term_escaped}%'
    )";
}

// Get total appointments count for pagination with filters applied
$count_sql = "SELECT COUNT(*) AS total 
              FROM appointments a
              LEFT JOIN patients p ON a.patient_id = p.id
              LEFT JOIN users u ON p.user_id = u.id
              LEFT JOIN doctors d ON a.doctor_id = d.id
              LEFT JOIN users du ON d.user_id = du.id
              LEFT JOIN services s ON a.service_id = s.id
              WHERE {$where_clause}";
$count_result = mysqli_query($conn, $count_sql);
$total_appointments = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_appointments / $appointments_per_page);

// Get all appointments with patient, doctor and service details
$sql = "SELECT 
    a.*, 
    p.id as patient_id,
    p.family_code,
    (SELECT name FROM family_codes WHERE code = p.family_code) as family_name,
    u.first_name,
    u.middle_name,
    u.last_name,
    u.email as patient_email,
    u.phone as patient_phone,
    CONCAT(du.first_name, ' ', du.last_name) as doctor_name,
    d.specialization as doctor_specialization,
    s.name as service_name,
    s.duration as service_duration,
    s.price as service_price,
    a.status as appointment_status,
    a.created_at
FROM appointments a
LEFT JOIN patients p ON a.patient_id = p.id
LEFT JOIN users u ON p.user_id = u.id
LEFT JOIN doctors d ON a.doctor_id = d.id
LEFT JOIN users du ON d.user_id = du.id
LEFT JOIN services s ON a.service_id = s.id
WHERE {$where_clause}
ORDER BY a.created_at ASC, a.appointment_date ASC, a.appointment_time ASC
LIMIT ? OFFSET ?";

// Using prepared statement for pagination
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $appointments_per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get calendar data
$calendar_sql = "SELECT 
    a.id,
    a.appointment_date,
    a.appointment_time,
    a.status,
    u.first_name,
    u.middle_name,
    u.last_name,
    p.family_code,
    (SELECT name FROM family_codes WHERE code = p.family_code) as family_name,
    CONCAT(du.first_name, ' ', du.last_name) as doctor_name,
    s.name as service_name,
    a.created_at
FROM appointments a
LEFT JOIN patients p ON a.patient_id = p.id
LEFT JOIN users u ON p.user_id = u.id
LEFT JOIN doctors d ON a.doctor_id = d.id
LEFT JOIN users du ON d.user_id = du.id
LEFT JOIN services s ON a.service_id = s.id
WHERE a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
ORDER BY a.created_at ASC, a.appointment_date ASC, a.appointment_time ASC";

$calendar_result = mysqli_query($conn, $calendar_sql);
$calendar_events = array();

if ($calendar_result) {
    while ($row = mysqli_fetch_assoc($calendar_result)) {
        $patient_name = $row['last_name'] . ', ' . $row['first_name'];
        if (!empty($row['middle_name'])) {
            $patient_name .= ' ' . $row['middle_name'];
        }
        $calendar_events[] = array(
            'id' => $row['id'],
            'title' => $patient_name . ' - ' . $row['service_name'],
            'start' => $row['appointment_date'] . 'T' . $row['appointment_time'],
            'end' => date('Y-m-d H:i:s', strtotime($row['appointment_date'] . ' ' . $row['appointment_time'] . ' +1 hour')),
            'status' => $row['status'],
            'description' => 'Doctor: ' . $row['doctor_name'] . 
                            (!empty($row['family_code']) ? ' | Family: ' . $row['family_name'] : '')
        );
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Vibrant Smile Dental Management System</title>
    
    <!-- FullCalendar Dependencies -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Appointments</h1>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#calendarModal">
                    <i class="fas fa-calendar me-2"></i>View Calendar
                </button>
                <a href="appointment_form.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>New Appointment
                </a>
            </div>
        </div>

        <!-- Calendar Modal -->
        <div class="modal fade" id="calendarModal" tabindex="-1" aria-labelledby="calendarModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title" id="calendarModalLabel">Appointment Calendar</h5>
                        <div class="btn-group mx-3">
                            <button class="btn btn-sm btn-outline-primary" onclick="changeView('dayGridMonth')">Month</button>
                            <button class="btn btn-sm btn-outline-primary" onclick="changeView('timeGridWeek')">Week</button>
                            <button class="btn btn-sm btn-outline-primary" onclick="changeView('timeGridDay')">Day</button>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add a modal for viewing appointment details -->
        <div class="modal fade" id="viewAppointmentModal" tabindex="-1" aria-labelledby="viewAppointmentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title" id="viewAppointmentModalLabel">Appointment Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div id="appointmentDetailsContent">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading appointment details...</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="d-flex justify-content-between w-100">
                            <div class="btn-group" id="appointmentActionButtons">
                                <!-- Action buttons will be added dynamically -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Appointments Table (Enhanced) -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Upcoming Appointments</h6>
                <div class="d-flex align-items-center">
                    <div class="status-filter me-3">
                        <select id="statusFilter" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="search-box">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-primary"></i>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 ps-0" 
                                   placeholder="Search appointments..." style="max-width: 250px;">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="appointmentsTable">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Contact</th>
                                <th>Date & Time</th>
                                <th>Service</th>
                                <th>Doctor</th>
                                <th>Cost</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if($result && mysqli_num_rows($result) > 0):
                                while($appointment = mysqli_fetch_assoc($result)):
                                    $status_class = match($appointment['status']) {
                                        'pending' => 'bg-warning text-dark',
                                        'scheduled' => 'bg-info text-dark',
                                        'approved' => 'bg-success',
                                        'completed' => 'bg-primary',
                                        'cancelled' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                            ?>
                            <tr class="searchable-row" data-status="<?php echo $appointment['status']; ?>">
                                <td class="searchable">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-2 bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 35px; height: 35px;">
                                            <span class="text-white fw-bold">
                                                <?php echo strtoupper(substr($appointment['first_name'], 0, 1) . substr($appointment['last_name'], 0, 1)); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong><?php 
                                                $patient_name = $appointment['last_name'] . ', ' . $appointment['first_name'];
                                                if (!empty($appointment['middle_name'])) {
                                                    $patient_name .= ' ' . $appointment['middle_name'];
                                                }
                                                echo htmlspecialchars($patient_name); 
                                            ?></strong>
                                            <div class="small text-muted">ID: P-<?php echo $appointment['patient_id']; ?></div>
                                            <?php if (!empty($appointment['family_code'])): ?>
                                            <div class="small badge bg-info text-dark">
                                                <i class="fas fa-users me-1"></i> <?php echo htmlspecialchars($appointment['family_name']); ?> Family
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="searchable">
                                    <div><i class="fas fa-envelope text-muted me-1"></i> <?php echo htmlspecialchars($appointment['patient_email']); ?></div>
                                    <div><i class="fas fa-phone text-muted me-1"></i> <?php echo htmlspecialchars($appointment['patient_phone'] ?? 'N/A'); ?></div>
                                </td>
                                <td class="searchable">
                                    <div><i class="fas fa-calendar-day text-primary me-1"></i> <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></div>
                                    <div><i class="fas fa-clock text-primary me-1"></i> <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></div>
                                </td>
                                <td class="searchable">
                                    <div><?php echo htmlspecialchars($appointment['service_name'] ?? 'No service listed'); ?></div>
                                    <div class="small text-muted">
                                        Duration: <?php echo htmlspecialchars($appointment['service_duration'] ?? 'N/A'); ?> min
                                    </div>
                                </td>
                                <td class="searchable">
                                    <div><?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Not assigned'); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($appointment['doctor_specialization'] ?? ''); ?></div>
                                </td>
                                <td class="searchable">
                                    <div class="badge bg-light text-dark border border-success px-3 py-2">
                                        <i class="fas fa-tag text-success me-1"></i>
                                        <span class="fw-bold">₱<?php echo number_format($appointment['service_price'] ?? 0, 2); ?></span>
                                    </div>
                                </td>
                                <td class="searchable">
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" 
                                                class="btn btn-sm btn-info" 
                                                onclick="viewAppointment(<?php echo $appointment['id']; ?>)"
                                                title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="appointment_form.php?edit=<?php echo $appointment['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if($appointment['status'] == 'pending'): ?>
                                            <button type="button" 
                                                class="btn btn-sm btn-success approve-btn" 
                                                data-id="<?php echo $appointment['id']; ?>"
                                                title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if($appointment['status'] == 'scheduled'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-success approve-btn" 
                                                data-id="<?php echo $appointment['id']; ?>"
                                                title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if($appointment['status'] == 'approved'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-primary complete-btn" 
                                                data-id="<?php echo $appointment['id']; ?>"
                                                title="Mark as Completed">
                                            <i class="fas fa-check-double"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if($appointment['status'] != 'cancelled' && $appointment['status'] != 'completed'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger delete-btn" 
                                                data-id="<?php echo $appointment['id']; ?>"
                                                title="Cancel">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr id="noResultsRow" style="display: none;">
                                <td colspan="8" class="text-center py-4">No appointments found matching your search</td>
                            </tr>
                            <tr id="noAppointmentsRow">
                                <td colspan="8" class="text-center py-4">No upcoming appointments found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
        <div class="d-flex justify-content-center mt-4">
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <!-- Previous page link -->
                    <li class="page-item <?php echo ($current_page_num <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $current_page_num - 1; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                            <span class="sr-only">Previous</span>
                        </a>
                    </li>
                    
                    <!-- Page numbers -->
                    <?php 
                    // Display a limited number of pages
                    $start_page = max(1, $current_page_num - 2);
                    $end_page = min($total_pages, $current_page_num + 2);
                    
                    // Always show first page
                    if($start_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1">1</a>
                        </li>
                        <?php if($start_page > 2): ?>
                            <li class="page-item disabled"><a class="page-link" href="#">...</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Page links -->
                    <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?php echo ($i == $current_page_num) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Always show last page -->
                    <?php if($end_page < $total_pages): ?>
                        <?php if($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled"><a class="page-link" href="#">...</a></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Next page link -->
                    <li class="page-item <?php echo ($current_page_num >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $current_page_num + 1; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                            <span class="sr-only">Next</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>

    <style>
    /* Modal Styles */
    #calendarModal .modal-dialog {
        max-width: 1000px;
        margin: 1.75rem auto;
    }

    #calendarModal .modal-content {
        border-radius: 0.5rem;
        overflow: hidden;
    }

    #calendarModal .modal-header {
        background-color: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
    }

    #calendarModal .modal-body {
        padding: 1rem;
        height: 550px;
    }

    /* Calendar Styles */
    #calendar {
        width: 100%;
        height: 100%;
        background: white;
    }

    .fc {
        height: 100% !important;
        font-size: 0.85rem !important;
    }

    .fc .fc-toolbar {
        margin-bottom: 0.5rem !important;
    }

    .fc .fc-toolbar-title {
        font-size: 1.1rem !important;
        font-weight: 600;
    }

    .fc .fc-button {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.8rem !important;
    }

    .fc .fc-button-primary {
        background-color: #4e73df !important;
        border-color: #4e73df !important;
        color: white !important;
    }

    .fc .fc-button-primary:hover {
        background-color: #2e59d9 !important;
        border-color: #2e59d9 !important;
    }

    .fc-daygrid-day {
        height: 70px !important;
    }

    .fc .fc-daygrid-day-number {
        font-size: 0.9rem !important;
        padding: 0.3rem !important;
        color: #2c3e50;
    }

    .fc .fc-col-header-cell-cushion {
        padding: 0.3rem !important;
        color: #2c3e50;
        font-weight: 600;
        font-size: 0.9rem !important;
    }

    .fc-theme-standard td, 
    .fc-theme-standard th,
    .fc-theme-standard .fc-scrollgrid {
        border-color: #e3e6f0 !important;
    }

    .fc-day-today {
        background-color: #f8f9fc !important;
    }

    .fc-event {
        border: none !important;
        padding: 1px 3px !important;
        font-size: 0.8rem !important;
        border-radius: 2px !important;
        margin: 1px 0 !important;
    }

    .fc-daygrid-event-dot {
        display: none !important;
    }

    /* Event Status Colors */
    .fc-event.status-completed { 
        background-color: #1cc88a !important; 
        border-color: #1cc88a !important;
    }
    .fc-event.status-pending { 
        background-color: #f6c23e !important; 
        border-color: #f6c23e !important;
    }
    .fc-event.status-scheduled { 
        background-color: #36b9cc !important; 
        border-color: #36b9cc !important;
    }
    .fc-event.status-approved { 
        background-color: #4e73df !important; 
        border-color: #4e73df !important;
    }
    .fc-event.status-cancelled { 
        background-color: #e74a3b !important; 
        border-color: #e74a3b !important;
    }

    /* Search Box Styles */
    .search-box .input-group {
        border-radius: 50rem;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    }

    .search-box .input-group-text {
        border-radius: 50rem 0 0 50rem;
        border: 1px solid #e3e6f0;
        padding: 0.6rem 1rem;
    }

    .search-box .form-control {
        border-radius: 0 50rem 50rem 0;
        border: 1px solid #e3e6f0;
        padding: 0.6rem 1rem;
    }

    .search-box .form-control:focus {
        border-color: #4e73df;
        box-shadow: none;
    }

    .search-box .input-group-text,
    .search-box .form-control {
        background-color: #fff;
    }

    /* Hide default focus outline for better aesthetics */
    .search-box .form-control:focus {
        outline: none;
    }

    /* Enhanced Table Styles */
    .table th {
        vertical-align: middle;
        font-weight: 600;
        color: #4e73df;
        border-top: none;
        border-bottom: 2px solid #e3e6f0;
        background-color: #f8f9fc;
        padding: 0.75rem;
    }

    .table td {
        vertical-align: middle;
        padding: 0.75rem;
        border-color: #e3e6f0;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(78, 115, 223, 0.05);
    }

    .badge {
        font-weight: 500;
        padding: 0.4em 0.7em;
        border-radius: 30px;
    }

    .status-filter .form-select {
        border-radius: 50rem;
        border: 1px solid #e3e6f0;
        padding: 0.5rem 2rem 0.5rem 1rem;
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        min-width: 160px;
    }

    .avatar {
        font-size: 14px;
    }

    /* New CSS for appointments table */
    #appointmentsTable .btn-group .btn {
        margin: 0 2px;
        border-radius: 4px;
    }

    #appointmentsTable .btn-group .btn i {
        font-size: 0.9rem;
    }

    /* Appointment View Modal Styles */
    #viewAppointmentModal .modal-dialog {
        max-width: 800px;
    }

    #viewAppointmentModal .modal-content {
        border: none;
        border-radius: 0.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    #viewAppointmentModal .modal-header {
        background: linear-gradient(to right, #4e73df, #36b9cc);
        color: white;
        border-bottom: none;
        padding: 1rem 1.5rem;
    }

    #viewAppointmentModal .modal-title {
        font-weight: 600;
    }

    #viewAppointmentModal .modal-body {
        padding: 1.5rem;
    }

    #viewAppointmentModal .modal-footer {
        border-top: 1px solid #e3e6f0;
        padding: 1rem 1.5rem;
    }

    #viewAppointmentModal .card {
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 1.5rem;
        border-radius: 0.5rem;
        overflow: hidden;
    }

    #viewAppointmentModal .card-header {
        padding: 0.75rem 1.25rem;
        font-weight: 600;
        border-bottom: none;
    }

    #viewAppointmentModal .card-body {
        padding: 1.25rem;
    }

    #viewAppointmentModal h6 {
        color: #4e73df;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    #viewAppointmentModal .badge {
        font-weight: 500;
        padding: 0.5em 1em;
        font-size: 0.85rem;
    }

    #viewAppointmentModal .btn-group .btn {
        margin: 0 2px;
    }

    #viewAppointmentModal .avatar {
        background: linear-gradient(to right, #4e73df, #36b9cc);
        box-shadow: 0 3px 5px rgba(0,0,0,0.1);
    }

    #appointmentActionButtons .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 1rem;
        font-weight: 500;
        border-radius: 0.25rem;
        transition: all 0.2s;
    }

    #appointmentActionButtons .btn i {
        margin-right: 5px;
    }

    #appointmentActionButtons .btn-primary {
        background: linear-gradient(to right, #4e73df, #224abe);
        border: none;
    }

    #appointmentActionButtons .btn-info {
        background: linear-gradient(to right, #36b9cc, #258391);
        border: none;
    }

    #appointmentActionButtons .btn-success {
        background: linear-gradient(to right, #1cc88a, #169a67);
        border: none;
    }

    #appointmentActionButtons .btn-warning {
        background: linear-gradient(to right, #f6c23e, #dfa408);
        border: none;
    }

    #appointmentActionButtons .btn-danger {
        background: linear-gradient(to right, #e74a3b, #be2617);
        border: none;
    }

    /* Pagination Styles */
    .pagination {
        margin-bottom: 2rem;
    }

    .pagination .page-item .page-link {
        color: #4e73df;
        border-color: #e3e6f0;
        padding: 0.5rem 0.75rem;
        min-width: 38px;
        text-align: center;
    }

    .pagination .page-item.active .page-link {
        background-color: #4e73df;
        border-color: #4e73df;
        color: white;
    }

    .pagination .page-item.disabled .page-link {
        color: #858796;
    }

    .pagination .page-item:first-child .page-link {
        border-top-left-radius: 0.35rem;
        border-bottom-left-radius: 0.35rem;
    }

    .pagination .page-item:last-child .page-link {
        border-top-right-radius: 0.35rem;
        border-bottom-right-radius: 0.35rem;
    }

    .pagination .page-link:hover {
        background-color: #eaecf4;
        color: #224abe;
    }

    .pagination .page-link:focus {
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
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
            dayMaxEvents: 3,
            dayMaxEventRows: 3,
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
                return ['status-' + arg.event.extendedProps.status];
            },
            dateClick: function(info) {
                window.location.href = `appointment_form.php?date=${info.dateStr}`;
            },
            eventClick: function(info) {
                viewAppointment(info.event.id);
            },
            moreLinkContent: function(args) {
                return '+' + args.num + ' more';
            }
        });
    });

    // Initialize calendar when modal is shown
    document.getElementById('calendarModal').addEventListener('shown.bs.modal', function () {
        if (calendar) {
            calendar.render();
            setTimeout(function() {
                calendar.updateSize();
            }, 100);
        }
    });

    // Function to change calendar view
    function changeView(viewName) {
        if (calendar) {
            calendar.changeView(viewName);
            calendar.updateSize();
        }
    }

    // Function to handle appointment status updates
    function handleStatusUpdate(id, status, title, text, successTitle) {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: status === 'completed' ? '#4e73df' : 
                               status === 'approved' ? '#28a745' :
                               status === 'pending' ? '#ffc107' : '#e74a3b',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Processing...',
                    text: 'Updating appointment status',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Make the API call
                fetch('update_appointment_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}&status=${status}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if(data.success) {
                        let message = data.message || `The appointment has been ${status === 'cancelled' ? 'cancelled' : 
                                       status === 'completed' ? 'marked as completed' : 
                                       status === 'approved' ? 'approved' : 
                                       status === 'pending' ? 'marked as pending' : 'updated'}.`;
                        
                        Swal.fire({
                            title: successTitle,
                            text: message,
                            icon: 'success',
                            confirmButtonColor: status === 'completed' ? '#4e73df' : 
                                               status === 'approved' ? '#28a745' :
                                               status === 'pending' ? '#ffc107' : '#e74a3b'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        throw new Error(data.message || 'Could not update the appointment.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: error.message || 'An error occurred while updating the appointment.',
                        icon: 'error',
                        confirmButtonColor: '#d33'
                    });
                });
            }
        });
    }

    // Add event listeners when the document is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Handle approve button clicks
        document.querySelectorAll('.approve-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const id = this.dataset.id;
                handleStatusUpdate(
                    id, 
                    'approved', 
                    'Approve Appointment?', 
                    'This will approve the appointment.', 
                    'Approved!'
                );
            });
        });

        // Handle complete button clicks
        document.querySelectorAll('.complete-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const id = this.dataset.id;
                handleStatusUpdate(
                    id, 
                    'completed', 
                    'Mark as Completed?', 
                    'This will mark the appointment as completed.', 
                    'Completed!'
                );
            });
        });

        // Handle delete/cancel button clicks
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const id = this.dataset.id;
                handleStatusUpdate(
                    id, 
                    'cancelled', 
                    'Cancel Appointment?', 
                    'This appointment will be cancelled. This action cannot be undone.', 
                    'Cancelled!'
                );
            });
        });

        // Get the search input and status filter elements
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const appointmentsTable = document.getElementById('appointmentsTable');
        const rows = appointmentsTable.getElementsByTagName('tr');
        const noResultsRow = document.getElementById('noResultsRow');
        const noAppointmentsRow = document.getElementById('noAppointmentsRow');

        // Function to filter appointments
        function filterAppointments() {
            const searchTerm = searchInput.value.toLowerCase();
            const statusValue = statusFilter.value.toLowerCase();
            let hasVisibleRows = false;

            // Start from index 1 to skip the header row
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const searchableContent = row.querySelector('.searchable')?.textContent.toLowerCase() || '';
                const status = row.getAttribute('data-status')?.toLowerCase() || '';
                
                const matchesSearch = searchableContent.includes(searchTerm);
                const matchesStatus = statusValue === '' || status === statusValue;
                
                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                    hasVisibleRows = true;
                } else {
                    row.style.display = 'none';
                }
            }

            // Show/hide the "no results" message
            if (noResultsRow) {
                noResultsRow.style.display = hasVisibleRows ? 'none' : '';
            }
            if (noAppointmentsRow) {
                noAppointmentsRow.style.display = hasVisibleRows ? 'none' : '';
            }
        }

        // Add event listeners
        searchInput.addEventListener('input', filterAppointments);
        statusFilter.addEventListener('change', filterAppointments);
    });

    // Add the viewAppointment function
    function viewAppointment(id) {
        // Show modal with loading state
        const modal = new bootstrap.Modal(document.getElementById('viewAppointmentModal'));
        modal.show();
        
        // Clear previous action buttons
        document.getElementById('appointmentActionButtons').innerHTML = '';
        
        // Fetch appointment details
        fetch(`get_appointment_details.php?id=${id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Generate action buttons based on current status
                    const actionButtons = generateActionButtons(id, data.appointment.status);
                    document.getElementById('appointmentActionButtons').innerHTML = actionButtons;
                    
                    // Format the appointment details
                    let appointmentDate = new Date(data.appointment.appointment_date);
                    let formattedDate = appointmentDate.toLocaleDateString('en-US', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                    
                    // Format time
                    let timeStr = data.appointment.appointment_time;
                    let timeParts = timeStr.split(':');
                    let hours = parseInt(timeParts[0]);
                    let ampm = hours >= 12 ? 'PM' : 'AM';
                    hours = hours % 12;
                    hours = hours ? hours : 12; // Convert '0' to '12'
                    let formattedTime = hours + ':' + timeParts[1] + ' ' + ampm;
                    
                    // Get status class for badge
                    let statusClass = '';
                    switch(data.appointment.status) {
                        case 'pending': statusClass = 'bg-warning text-dark'; break;
                        case 'scheduled': statusClass = 'bg-info text-dark'; break;
                        case 'approved': statusClass = 'bg-success'; break;
                        case 'completed': statusClass = 'bg-primary'; break;
                        case 'cancelled': statusClass = 'bg-danger'; break;
                        default: statusClass = 'bg-secondary';
                    }
                    
                    // Create appointment details HTML
                    const detailsHTML = `
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">Appointment Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3 d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Status:</h6>
                                            <span class="badge ${statusClass} px-3 py-2">${data.appointment.status.charAt(0).toUpperCase() + data.appointment.status.slice(1)}</span>
                                        </div>
                                        <div class="mb-3">
                                            <h6>Date & Time:</h6>
                                            <p class="mb-0">
                                                <i class="fas fa-calendar-day text-primary me-2"></i>${formattedDate}<br>
                                                <i class="fas fa-clock text-primary me-2"></i>${formattedTime}
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <h6>Service:</h6>
                                            <p class="mb-0">${data.appointment.service_name}</p>
                                            <small class="text-muted">
                                                Duration: ${data.appointment.service_duration} min
                                            </small>
                                        </div>
                                        <div class="mb-3">
                                            <h6>Cost:</h6>
                                            <div class="d-block px-3 py-2 border border-success rounded bg-light text-center mb-2">
                                                <span class="fs-4 fw-bold text-success">
                                                    <i class="fas fa-tag me-2"></i>₱${parseFloat(data.appointment.service_price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="mb-0">
                                            <h6>Notes:</h6>
                                            <p class="mb-0">${data.appointment.notes || 'No notes provided'}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">Patient Details</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar me-3 bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 50px; height: 50px;">
                                                <span class="text-white fw-bold fs-4">
                                                    ${data.appointment.first_name.charAt(0)}${data.appointment.last_name.charAt(0)}
                                                </span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">${data.appointment.last_name}, ${data.appointment.first_name} ${data.appointment.middle_name || ''}</h6>
                                                <small class="text-muted">Patient ID: P-${data.appointment.patient_id}</small>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <i class="fas fa-envelope text-muted me-2"></i> ${data.appointment.patient_email}
                                        </div>
                                        <div class="mb-0">
                                            <i class="fas fa-phone text-muted me-2"></i> ${data.appointment.patient_phone || 'N/A'}
                                        </div>
                                        ${data.appointment.family_code ? `
                                        <hr>
                                        <div class="mb-0">
                                            <i class="fas fa-users text-info me-2"></i> 
                                            <span class="fw-bold">${data.appointment.family_name} Family</span>
                                            <div class="d-flex align-items-center mt-1">
                                                <span class="badge bg-info text-dark me-2">${data.appointment.family_code}</span>
                                                <small>${data.appointment.family_members_count} member${data.appointment.family_members_count != 1 ? 's' : ''}</small>
                                            </div>
                                            <div class="mt-2">
                                                <a href="view_family.php?code=${data.appointment.family_code}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i> View Family
                                                </a>
                                            </div>
                                        </div>
                                        ` : ''}
                                    </div>
                                </div>
                                
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">Doctor</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <h6 class="mb-0">${data.appointment.doctor_name || 'Not assigned'}</h6>
                                            <small class="text-muted">${data.appointment.doctor_specialization || ''}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Update modal content
                    document.getElementById('appointmentDetailsContent').innerHTML = detailsHTML;
                    
                } else {
                    document.getElementById('appointmentDetailsContent').innerHTML = `                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            ${data.message || 'Failed to load appointment details'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error fetching appointment details:', error);
                document.getElementById('appointmentDetailsContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        An error occurred while loading appointment details
                    </div>
                `;
            });
    }

    // Function to generate action buttons based on appointment status
    function generateActionButtons(id, status) {
        let buttons = '';
        
        // Common button for editing
        buttons += `<a href="appointment_form.php?edit=${id}" class="btn btn-primary">
            <i class="fas fa-edit me-1"></i> Edit
        </a>`;
        
        // Status-specific buttons
       
        // Cancel button (not for completed or cancelled appointments)
        
        return buttons;
    }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
