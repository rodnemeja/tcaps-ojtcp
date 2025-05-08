<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Get date range from request
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get total revenue
$sql = "SELECT COALESCE(SUM(total_amount), 0) as total_revenue 
        FROM invoices 
        WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status = 'paid'";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $revenue = mysqli_fetch_assoc($result)['total_revenue'];
}

// Get total appointments
$sql = "SELECT COUNT(*) as total_appointments 
        FROM appointments 
        WHERE DATE(appointment_date) BETWEEN ? AND ?";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $appointments = mysqli_fetch_assoc($result)['total_appointments'];
}

// Get total patients
$sql = "SELECT COUNT(*) as total_patients FROM patients";
$result = mysqli_query($conn, $sql);
$patients = mysqli_fetch_assoc($result)['total_patients'];

// Get total families
$sql = "SELECT COUNT(*) as total_families FROM family_codes";
$result = mysqli_query($conn, $sql);
$families = mysqli_fetch_assoc($result)['total_families'];

// Get family vs individual appointments
$sql = "SELECT 
    CASE 
        WHEN p.family_code IS NOT NULL AND p.family_code != '' THEN 'Family' 
        ELSE 'Individual' 
    END as appointment_type,
    COUNT(*) as count
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE DATE(a.appointment_date) BETWEEN ? AND ?
    GROUP BY appointment_type";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $family_vs_individual = mysqli_stmt_get_result($stmt);
    
    $family_individual_data = array('labels' => array(), 'values' => array());
    while($row = mysqli_fetch_assoc($family_vs_individual)) {
        $family_individual_data['labels'][] = $row['appointment_type'];
        $family_individual_data['values'][] = intval($row['count']);
    }
}

// Get family appointment status distribution
$sql = "SELECT 
    a.status,
    COUNT(*) as count
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE DATE(a.appointment_date) BETWEEN ? AND ?
    AND p.family_code IS NOT NULL AND p.family_code != ''
    GROUP BY a.status
    ORDER BY count DESC";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $family_appointment_status = mysqli_stmt_get_result($stmt);
    
    $family_status_data = array('labels' => array(), 'values' => array());
    while($row = mysqli_fetch_assoc($family_appointment_status)) {
        $family_status_data['labels'][] = ucfirst($row['status']);
        $family_status_data['values'][] = intval($row['count']);
    }
}

// Get revenue by service
$sql = "SELECT s.name, COALESCE(SUM(ii.quantity * ii.unit_price), 0) as total_revenue 
        FROM services s 
        LEFT JOIN invoice_items ii ON s.id = ii.service_id 
        LEFT JOIN invoices i ON ii.invoice_id = i.id 
        WHERE (i.created_at IS NULL OR DATE(i.created_at) BETWEEN ? AND ?) 
        AND (i.payment_status IS NULL OR i.payment_status = 'paid')
        GROUP BY s.id, s.name 
        ORDER BY total_revenue DESC";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $revenue_by_service = mysqli_stmt_get_result($stmt);
}

// Get payment status distribution
$sql = "SELECT 
            payment_status, 
            COUNT(*) as count 
        FROM invoices 
        WHERE DATE(created_at) BETWEEN ? AND ? 
        GROUP BY payment_status";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $payment_status = mysqli_stmt_get_result($stmt);
}

// Get daily revenue
$sql = "SELECT 
            DATE(created_at) as date, 
            COALESCE(SUM(total_amount), 0) as revenue 
        FROM invoices 
        WHERE DATE(created_at) BETWEEN ? AND ? 
        AND payment_status = 'paid' 
        GROUP BY DATE(created_at) 
        ORDER BY date";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $daily_revenue = mysqli_stmt_get_result($stmt);
}

// Prepare chart data
$revenue_by_service_data = array('labels' => array(), 'values' => array());
if ($revenue_by_service) {
    while($row = mysqli_fetch_assoc($revenue_by_service)) {
        $revenue_by_service_data['labels'][] = $row['name'];
        $revenue_by_service_data['values'][] = round(floatval($row['total_revenue']), 2);
    }
}

$payment_status_data = array('labels' => array(), 'values' => array());
if ($payment_status) {
    while($row = mysqli_fetch_assoc($payment_status)) {
        $payment_status_data['labels'][] = ucfirst($row['payment_status']);
        $payment_status_data['values'][] = intval($row['count']);
    }
}

$daily_revenue_data = array('labels' => array(), 'values' => array());
if ($daily_revenue) {
    while($row = mysqli_fetch_assoc($daily_revenue)) {
        $daily_revenue_data['labels'][] = date('M d', strtotime($row['date']));
        $daily_revenue_data['values'][] = round(floatval($row['revenue']), 2);
    }
}

$page_title = "Reports";
$current_page = "reports";
require_once "includes/header.php";
?>

<!-- Add Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<!-- Add SheetJS for Excel export - Use a CDN that's reliable -->
<script src="https://unpkg.com/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<!-- Add FileSaver.js for better browser compatibility when saving files -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Reports</h2>
                    <div class="d-flex gap-2">
                        <form action="" method="get" class="d-flex gap-2">
                            <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                            <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                        </form>
                        <button id="printReport" class="btn btn-info text-white">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                        <button id="exportExcel" class="btn btn-success">
                            <i class="fas fa-file-excel me-2"></i>Export Excel
                        </button>
                    </div>
                </div>
                
                <!-- Print-only header -->
                <div class="d-none" id="printHeader">
                    <div class="text-center mb-4">
                        <img src="../assets/images/logo_vibrant.png" alt="Vibrant Smile Dental Clinic Logo" style="max-width: 150px; margin-bottom: 15px;">
                        <h1>Vibrant Smile Dental Clinic</h1>
                        <p>Block A7, Yoho Center, Brgy Sanito, Ipil Zamboanga Sibugay</p>
                        <p>Contact: 09752425227 | Email: vibrantsmile07@gmail.com</p>
                        <h2 class="mt-4">Financial Report</h2>
                        <p>Period: <?php echo date('F d, Y', strtotime($start_date)); ?> - <?php echo date('F d, Y', strtotime($end_date)); ?></p>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Revenue</h6>
                                        <h3 class="mb-0">₱<?php echo number_format($revenue, 2); ?></h3>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-money-bill fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Appointments</h6>
                                        <h3 class="mb-0"><?php echo $appointments; ?></h3>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-calendar-check fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Patients</h6>
                                        <h3 class="mb-0"><?php echo $patients; ?></h3>
                                    </div>
                                    <div class="bg-info bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-users fa-2x text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Families</h6>
                                        <h3 class="mb-0"><?php echo $families; ?></h3>
                                    </div>
                                    <div class="bg-secondary bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-home fa-2x text-secondary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">Revenue by Service</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position: relative; height: 300px;">
                                    <canvas id="revenueByService"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">Payment Status Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position: relative; height: 300px;">
                                    <canvas id="paymentStatus"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Daily Revenue</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position: relative; height: 300px;">
                                    <canvas id="dailyRevenue"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">Family vs Individual Appointments</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position: relative; height: 300px;">
                                    <canvas id="familyVsIndividual"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">Family Appointment Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position: relative; height: 300px;">
                                    <canvas id="familyAppointmentStatus"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Helper function to check if data exists
        function hasData(data) {
            return data && data.labels && data.labels.length > 0 && data.values && data.values.length > 0;
        }

        // Helper function to get empty chart data
        function getEmptyChartData() {
            return {
                labels: ['No Data'],
                values: [0]
            };
        }

        try {
            // Revenue by Service Chart
            const revenueByServiceData = <?php echo json_encode($revenue_by_service_data); ?>;
            if(document.getElementById('revenueByService')) {
                if(hasData(revenueByServiceData)) {
                    new Chart(document.getElementById('revenueByService').getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: revenueByServiceData.labels,
                            datasets: [{
                                label: 'Revenue',
                                data: revenueByServiceData.values,
                                backgroundColor: 'rgba(78, 115, 223, 0.5)',
                                borderColor: 'rgba(78, 115, 223, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return '₱' + (context.raw || 0).toLocaleString();
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '₱' + value.toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    document.getElementById('revenueByService').parentNode.innerHTML = 
                        '<div class="text-center text-muted py-5">No revenue data available for the selected period</div>';
                }
            }

            // Payment Status Chart
            const paymentStatusData = <?php echo json_encode($payment_status_data); ?>;
            if(document.getElementById('paymentStatus')) {
                if(hasData(paymentStatusData)) {
                    new Chart(document.getElementById('paymentStatus').getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: paymentStatusData.labels,
                            datasets: [{
                                data: paymentStatusData.values,
                                backgroundColor: [
                                    'rgba(28, 200, 138, 0.8)',  // Success color for Paid
                                    'rgba(246, 194, 62, 0.8)',  // Warning color for Partial
                                    'rgba(231, 74, 59, 0.8)'    // Danger color for Pending
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0;
                                            return `${context.label}: ${context.raw} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    document.getElementById('paymentStatus').parentNode.innerHTML = 
                        '<div class="text-center text-muted py-5">No payment status data available for the selected period</div>';
                }
            }

            // Daily Revenue Chart
            const dailyRevenueData = <?php echo json_encode($daily_revenue_data); ?>;
            if(document.getElementById('dailyRevenue')) {
                if(hasData(dailyRevenueData)) {
                    new Chart(document.getElementById('dailyRevenue').getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: dailyRevenueData.labels,
                            datasets: [{
                                label: 'Daily Revenue',
                                data: dailyRevenueData.values,
                                fill: {
                                    target: 'origin',
                                    above: 'rgba(78, 115, 223, 0.1)'
                                },
                                borderColor: 'rgba(78, 115, 223, 1)',
                                borderWidth: 2,
                                tension: 0.4,
                                pointRadius: 4,
                                pointBackgroundColor: 'rgba(78, 115, 223, 1)'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return '₱' + (context.raw || 0).toLocaleString();
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '₱' + value.toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    document.getElementById('dailyRevenue').parentNode.innerHTML = 
                        '<div class="text-center text-muted py-5">No daily revenue data available for the selected period</div>';
                }
            }

            // Family vs Individual Chart
            const familyIndividualData = <?php echo json_encode($family_individual_data); ?>;
            if(document.getElementById('familyVsIndividual')) {
                if(hasData(familyIndividualData)) {
                    new Chart(document.getElementById('familyVsIndividual').getContext('2d'), {
                        type: 'pie',
                        data: {
                            labels: familyIndividualData.labels,
                            datasets: [{
                                data: familyIndividualData.values,
                                backgroundColor: [
                                    'rgba(78, 115, 223, 0.8)',
                                    'rgba(54, 185, 204, 0.8)'
                                ],
                                borderColor: [
                                    'rgba(78, 115, 223, 1)',
                                    'rgba(54, 185, 204, 1)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                            return `${label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    new Chart(document.getElementById('familyVsIndividual').getContext('2d'), {
                        type: 'pie',
                        data: {
                            labels: ['No Data'],
                            datasets: [{
                                data: [1],
                                backgroundColor: ['rgba(200, 200, 200, 0.8)'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }
            }

            // Family Appointment Status Chart
            const familyStatusData = <?php echo json_encode($family_status_data); ?>;
            if(document.getElementById('familyAppointmentStatus')) {
                if(hasData(familyStatusData)) {
                    new Chart(document.getElementById('familyAppointmentStatus').getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: familyStatusData.labels,
                            datasets: [{
                                data: familyStatusData.values,
                                backgroundColor: [
                                    'rgba(40, 167, 69, 0.8)',   // completed
                                    'rgba(255, 193, 7, 0.8)',   // pending
                                    'rgba(23, 162, 184, 0.8)',  // scheduled
                                    'rgba(220, 53, 69, 0.8)'    // cancelled
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                            return `${label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    new Chart(document.getElementById('familyAppointmentStatus').getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: ['No Data'],
                            datasets: [{
                                data: [1],
                                backgroundColor: ['rgba(200, 200, 200, 0.8)'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }
            }
        } catch (error) {
            console.error('Chart initialization error:', error);
            // Display error message in all chart containers
            const chartContainers = document.querySelectorAll('.chart-container');
            chartContainers.forEach(container => {
                container.innerHTML = '<div class="alert alert-danger">Error loading chart data. Please try refreshing the page.</div>';
            });
        }
    });

    // Add responsive behavior for mobile
    function adjustChartLayout() {
        const chartContainers = document.querySelectorAll('.chart-container');
        const isMobile = window.innerWidth < 768;
        
        chartContainers.forEach(container => {
            if (isMobile) {
                container.style.height = '250px';
            } else {
                container.style.height = '300px';
            }
        });
    }

    // Call on load and resize
    window.addEventListener('load', adjustChartLayout);
    window.addEventListener('resize', adjustChartLayout);

    // Add error handling
    window.addEventListener('error', function(e) {
        console.error('Chart.js Error:', e.error);
    });
    
    // Print functionality
    document.getElementById('printReport').addEventListener('click', function() {
        const printHeader = document.getElementById('printHeader');
        const mainContent = document.querySelector('.main-content');
        const originalTitle = document.title;
        
        // Show print header
        printHeader.classList.remove('d-none');
        
        // Set document title for print
        document.title = 'Financial Report - Vibrant Smile Dental Clinic';
        
        // Create print styles
        const style = document.createElement('style');
        style.id = 'print-styles';
        style.innerHTML = `
            @media print {
                body * {
                    visibility: hidden;
                }
                .navbar, .sidebar, #printReport, #exportExcel, form, .btn {
                    display: none !important;
                }
                .main-content, .main-content * {
                    visibility: visible;
                }
                .main-content {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                }
                #printHeader {
                    display: block !important;
                    visibility: visible;
                }
                #printHeader img {
                    display: block;
                    margin: 0 auto 15px auto;
                }
                .card {
                    break-inside: avoid;
                    page-break-inside: avoid;
                    margin-bottom: 20px;
                }
                .chart-container {
                    height: 400px !important;
                }
                @page {
                    size: portrait;
                    margin: 2cm;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Print and restore
        window.print();
        
        // Cleanup
        setTimeout(function() {
            printHeader.classList.add('d-none');
            document.head.removeChild(style);
            document.title = originalTitle;
        }, 1000);
    });
    
    // Export to Excel
    document.getElementById('exportExcel').addEventListener('click', function() {
        try {
            // Helper function to check if data exists - need to define it again since it's in a different scope
            function hasData(data) {
                return data && data.labels && data.labels.length > 0 && data.values && data.values.length > 0;
            }
            
            console.log('Starting Excel export...');
            
            // Create workbook
            const wb = XLSX.utils.book_new();
            wb.Props = {
                Title: "Financial Report",
                Subject: "Vibrant Smile Dental Clinic",
                Author: "Admin",
                CreatedDate: new Date()
            };
            
            // Get date range for filename
            const startDate = '<?php echo $start_date; ?>';
            const endDate = '<?php echo $end_date; ?>';
            
            // Get currency symbol for formatting
            const currencySymbol = '₱';
            
            // Add summary sheet first
            console.log('Creating summary sheet...');
            const summaryData = [
                ["Vibrant Smile Dental Clinic", "", ""],
                ["Financial Report Summary", "", ""],
                ["Period: <?php echo date('F d, Y', strtotime($start_date)); ?> - <?php echo date('F d, Y', strtotime($end_date)); ?>", "", ""],
                ["", "", ""],
                ["Metric", "Value", ""],
                ["Total Revenue", currencySymbol + "<?php echo number_format($revenue, 2); ?>", ""],
                ["Total Appointments", "<?php echo $appointments; ?>", ""],
                ["Total Patients", "<?php echo $patients; ?>", ""],
                ["Total Families", "<?php echo $families; ?>", ""],
                ["", "", ""],
                ["Report Generated On", "<?php echo date('F d, Y H:i:s'); ?>", ""]
            ];
            const summaryWS = XLSX.utils.aoa_to_sheet(summaryData);
            XLSX.utils.book_append_sheet(wb, summaryWS, "Summary");
            
            // Add revenue by service data
            console.log('Adding revenue by service data...');
            const revenueByServiceData = <?php echo json_encode($revenue_by_service_data); ?>;
            if (hasData(revenueByServiceData)) {
                // Create an array of arrays for the worksheet
                const serviceData = [
                    ["Revenue by Service", "", ""],
                    ["Service Name", "Revenue (₱)", ""]
                ];
                
                // Add service rows
                for (let i = 0; i < revenueByServiceData.labels.length; i++) {
                    serviceData.push([
                        revenueByServiceData.labels[i],
                        revenueByServiceData.values[i],
                        ""
                    ]);
                }
                
                // Add total row
                const totalRevenue = revenueByServiceData.values.reduce((a, b) => a + b, 0);
                serviceData.push(["Total", totalRevenue, ""]);
                
                // Create worksheet from the array
                const serviceWS = XLSX.utils.aoa_to_sheet(serviceData);
                XLSX.utils.book_append_sheet(wb, serviceWS, "Revenue by Service");
            }
            
            // Add payment status data
            console.log('Adding payment status data...');
            const paymentStatusData = <?php echo json_encode($payment_status_data); ?>;
            if (hasData(paymentStatusData)) {
                // Create an array of arrays for the worksheet
                const statusData = [
                    ["Payment Status Distribution", "", ""],
                    ["Status", "Count", "Percentage"]
                ];
                
                // Calculate total
                const totalCount = paymentStatusData.values.reduce((a, b) => a + b, 0);
                
                // Add status rows
                for (let i = 0; i < paymentStatusData.labels.length; i++) {
                    const percentage = totalCount > 0 ? ((paymentStatusData.values[i] / totalCount) * 100).toFixed(1) + "%" : "0%";
                    statusData.push([
                        paymentStatusData.labels[i],
                        paymentStatusData.values[i],
                        percentage
                    ]);
                }
                
                // Add total row
                statusData.push(["Total", totalCount, "100%"]);
                
                // Create worksheet from the array
                const statusWS = XLSX.utils.aoa_to_sheet(statusData);
                XLSX.utils.book_append_sheet(wb, statusWS, "Payment Status");
            }
            
            // Add daily revenue data
            console.log('Adding daily revenue data...');
            const dailyRevenueData = <?php echo json_encode($daily_revenue_data); ?>;
            if (hasData(dailyRevenueData)) {
                // Create an array of arrays for the worksheet
                const dailyData = [
                    ["Daily Revenue", "", ""],
                    ["Date", "Revenue (₱)", ""]
                ];
                
                // Add daily rows
                for (let i = 0; i < dailyRevenueData.labels.length; i++) {
                    dailyData.push([
                        dailyRevenueData.labels[i],
                        dailyRevenueData.values[i],
                        ""
                    ]);
                }
                
                // Add total row
                const totalDailyRevenue = dailyRevenueData.values.reduce((a, b) => a + b, 0);
                dailyData.push(["Total", totalDailyRevenue, ""]);
                
                // Create worksheet from the array
                const dailyWS = XLSX.utils.aoa_to_sheet(dailyData);
                XLSX.utils.book_append_sheet(wb, dailyWS, "Daily Revenue");
            }
            
            // Generate filename and save
            const fileName = `Financial_Report_${startDate}_to_${endDate}.xlsx`;
            console.log('Saving file:', fileName);
            
            // Convert string to ArrayBuffer (for binary write method)
            function s2ab(s) {
                const buf = new ArrayBuffer(s.length);
                const view = new Uint8Array(buf);
                for (let i = 0; i < s.length; i++) {
                    view[i] = s.charCodeAt(i) & 0xFF;
                }
                return buf;
            }
            
            // Try multiple methods for compatibility with different browsers
            try {
                console.log("Attempting export method 1: XLSX.writeFile");
                XLSX.writeFile(wb, fileName);
                console.log("Export successful using XLSX.writeFile");
                setTimeout(() => { alert('Report exported successfully!'); }, 500);
            } catch (err1) {
                console.error("Method 1 failed:", err1);
                try {
                    console.log("Attempting export method 2: Blob + saveAs");
                    // Create binary string
                    const wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'binary' });
                    // Create a blob from binary string
                    const blob = new Blob([s2ab(wbout)], { type: 'application/octet-stream' });
                    // Use FileSaver.js to save the file
                    saveAs(blob, fileName);
                    console.log("Export successful using Blob + saveAs");
                    setTimeout(() => { alert('Report exported successfully!'); }, 500);
                } catch (err2) {
                    console.error("Method 2 failed:", err2);
                    try {
                        console.log("Attempting export method 3: Data URL");
                        // Create data URL
                        const wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'base64' });
                        const url = 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' + wbout;
                        // Create and click a temporary link
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = fileName;
                        document.body.appendChild(a);
                        a.click();
                        setTimeout(() => { document.body.removeChild(a); }, 100);
                        console.log("Export successful using Data URL");
                        setTimeout(() => { alert('Report exported successfully!'); }, 500);
                    } catch (err3) {
                        console.error("All export methods failed:", err3);
                        alert('Error exporting to Excel. Please check browser console for details.');
                    }
                }
            }
        } catch (error) {
            console.error('Excel export error:', error);
            alert('Error exporting to Excel: ' + error.message);
        }
    });
    </script>
<?php require_once "includes/footer.php"; ?> 