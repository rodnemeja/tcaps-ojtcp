<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is a patient
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "patient"){
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["id"];

// Get patient ID for the logged-in user
$patient_sql = "SELECT id FROM patients WHERE user_id = ?";
if($stmt = mysqli_prepare($conn, $patient_sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $patient_result = mysqli_stmt_get_result($stmt);
    $patient = mysqli_fetch_assoc($patient_result);
    $patient_id = $patient['id'];
}

// Check if appointment_id is provided
if(!isset($_GET['appointment_id']) || !is_numeric($_GET['appointment_id'])) {
    header("location: ../appointments.php");
    exit;
}

$appointment_id = $_GET['appointment_id'];

// Verify the appointment belongs to the logged-in patient
$verify_sql = "SELECT a.* FROM appointments a WHERE a.id = ? AND a.patient_id = ?";
if($stmt = mysqli_prepare($conn, $verify_sql)){
    mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $patient_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if(mysqli_num_rows($result) == 0){
        // Appointment doesn't belong to this patient or doesn't exist
        header("location: ../appointments.php");
        exit;
    }
    $appointment = mysqli_fetch_assoc($result);
}

// Get invoice details
$invoice_sql = "SELECT i.*, a.appointment_date, a.appointment_time 
                FROM invoices i 
                JOIN appointments a ON i.appointment_id = a.id 
                WHERE i.appointment_id = ?";
$invoice = null;
if($stmt = mysqli_prepare($conn, $invoice_sql)){
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if(mysqli_num_rows($result) > 0){
        $invoice = mysqli_fetch_assoc($result);
        
        // Get total payments made
        $payments_sql = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE invoice_id = ?";
        if($stmt = mysqli_prepare($conn, $payments_sql)){
            mysqli_stmt_bind_param($stmt, "i", $invoice['id']);
            mysqli_stmt_execute($stmt);
            $payments_result = mysqli_stmt_get_result($stmt);
            $payments = mysqli_fetch_assoc($payments_result);
            $total_paid = $payments['total_paid'];
            $remaining_balance = $invoice['total_amount'] - $total_paid;
        }
    }
}

// Get invoice items
$items = [];
if($invoice){
    $items_sql = "SELECT ii.*, s.name as service_name 
                  FROM invoice_items ii 
                  JOIN services s ON ii.service_id = s.id 
                  WHERE ii.invoice_id = ?";
    if($stmt = mysqli_prepare($conn, $items_sql)){
        mysqli_stmt_bind_param($stmt, "i", $invoice['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $items[] = $row;
        }
    }
}

// Get doctor and patient details
if($invoice){
    $details_sql = "SELECT 
                    CONCAT(d_user.first_name, ' ', d_user.last_name) as doctor_name,
                    CONCAT(p_user.first_name, ' ', CASE WHEN p_user.middle_name != '' THEN CONCAT(p_user.middle_name, ' ') ELSE '' END, p_user.last_name) as patient_name,
                    p_user.email as patient_email,
                    p_user.phone as patient_phone,
                    d.specialization
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.id
                JOIN users d_user ON d.user_id = d_user.id
                JOIN patients p ON a.patient_id = p.id
                JOIN users p_user ON p.user_id = p_user.id
                WHERE a.id = ?";
    
    if($stmt = mysqli_prepare($conn, $details_sql)){
        mysqli_stmt_bind_param($stmt, "i", $appointment_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $details = mysqli_fetch_assoc($result);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Details - Vibrant Smile Dental Clinic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body {
            background: #f8f9fc;
        }
        .sidebar {
            min-height: 100vh;
            background: #4e73df;
            color: white;
            padding: 1rem;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 0.75rem 1rem;
            border-radius: 0.35rem;
            margin-bottom: 0.5rem;
        }
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,.1);
            color: white;
        }
        .main-content {
            padding: 2rem;
        }
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        .invoice-header {
            background: #4e73df;
            color: white;
            padding: 1.5rem;
            border-radius: 0.35rem 0.35rem 0 0;
        }
        .invoice-badge {
            padding: 0.25em 0.5em;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.85em;
        }
        .badge-paid {
            background-color: #28a745;
            color: white;
        }
        .badge-partial {
            background-color: #ffc107;
            color: black;
        }
        .badge-unpaid {
            background-color: #dc3545;
            color: white;
        }
        .invoice-details {
            padding: 1.5rem;
        }
        .invoice-table {
            margin-top: 1.5rem;
        }
        .invoice-total {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
            font-size: 1.1rem;
            font-weight: 500;
        }
        .text-danger {
            color: #dc3545 !important;
        }
        .user-status-container {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 8px 12px;
            margin: 10px auto;
            max-width: 90%;
        }
        .online-indicator {
            width: 10px;
            height: 10px;
            background-color: #4cd137;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            position: relative;
        }
        .online-indicator::after {
            content: '';
            position: absolute;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background-color: rgba(76, 209, 55, 0.3);
            left: -2px;
            top: -2px;
            animation: pulse 2s infinite;
        }
        .user-fullname {
            color: white;
            font-size: 0.95rem;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .user-role {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
            text-align: center;
            font-weight: 400;
            background-color: rgba(0, 0, 0, 0.15);
            border-radius: 10px;
            padding: 2px 8px;
            display: inline-block;
            margin: 4px auto 0;
        }
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.8;
            }
            70% {
                transform: scale(1.2);
                opacity: 0.4;
            }
            100% {
                transform: scale(1);
                opacity: 0.8;
            }
        }
        
        /* Additional styles for the logo in the invoice header */
        .clinic-logo-print {
            display: none; /* Hidden by default on screen */
            max-width: 150px;
            height: auto;
        }
        
        /* Print Styles */
        @media print {
            body {
                background: white;
                font-size: 12pt;
            }
            .sidebar, .btn, .nav, .header-buttons, .no-print {
                display: none !important;
            }
            .container-fluid {
                width: 100%;
                margin: 0;
                padding: 0;
            }
            .main-content {
                margin: 0;
                padding: 0;
                width: 100% !important;
                flex: 0 0 100% !important;
                max-width: 100% !important;
            }
            .card {
                border: 1px solid #ddd;
                box-shadow: none;
                margin: 0;
                padding: 0;
            }
            .invoice-header {
                background: white !important;
                color: black !important;
                padding: 15px;
                border-bottom: 2px solid #4e73df;
            }
            .clinic-logo-print {
                display: block !important;
                max-width: 150px;
                height: auto;
                margin-bottom: 15px;
            }
            .invoice-badge {
                border: 1px solid #ddd;
            }
            .badge-paid {
                background-color: #fff !important;
                color: #333 !important;
                border: 1px solid #28a745;
            }
            .badge-partial {
                background-color: #fff !important;
                color: #333 !important;
                border: 1px solid #ffc107;
            }
            .badge-unpaid {
                background-color: #fff !important;
                color: #333 !important;
                border: 1px solid #dc3545;
            }
            .invoice-details {
                padding: 15px;
            }
            .table {
                width: 100% !important;
                border-collapse: collapse;
            }
            .table th, .table td {
                border: 1px solid #ddd !important;
                padding: 8px !important;
            }
            .table thead th {
                background-color: #f8f9fc !important;
                color: black !important;
            }
            .invoice-total {
                text-align: right;
                font-weight: bold;
                font-size: 14pt;
                margin-top: 15px;
                padding: 10px;
                background: white !important;
                border-top: 1px solid #ddd;
            }
            .alert {
                border: 1px solid #ddd;
                background-color: white !important;
                color: black !important;
                padding: 10px;
            }
            .col-md-6 {
                width: 50% !important;
                float: left !important;
            }
            @page {
                size: A4;
                margin: 1cm;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar no-print">
                <div class="text-center mb-4">
                    <img src="../assets/images/logo_vibrant.png" alt="Vibrant Smile Dental Clinic Logo" class="img-fluid mb-3" style="max-width: 180px; height: auto; transition: transform 0.3s ease;">
                    <h4 class="text-white">Dental Clinic</h4>
                    <div class="user-status-container mt-2">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="online-indicator"></div>
                            <span class="user-fullname"><?php 
                                // Get user name from the database since session may not have first_name/last_name keys
                                $user_name_sql = "SELECT first_name, last_name FROM users WHERE id = ?";
                                $stmt = mysqli_prepare($conn, $user_name_sql);
                                mysqli_stmt_bind_param($stmt, "i", $user_id);
                                mysqli_stmt_execute($stmt);
                                $user_name_result = mysqli_stmt_get_result($stmt);
                                $user_name_data = mysqli_fetch_assoc($user_name_result);
                                echo htmlspecialchars($user_name_data["first_name"] . " " . $user_name_data["last_name"]); 
                            ?></span>
                        </div>
                        <div class="user-role">Patient</div>
                    </div>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php">
                            <i class="fas fa-home me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="appointments.php">
                            <i class="fas fa-calendar-alt me-2"></i> My Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="patient_medical_history.php">
                            <i class="fas fa-notes-medical me-2"></i> Medical History
                            <?php if(!$has_medical_history): ?>
                            <span class="badge bg-danger rounded-pill ms-2">Required</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../family_profile.php">
                            <i class="fas fa-users me-2"></i> Family Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../messaging.php">
                            <i class="fas fa-comments me-2"></i> Messages
                            <span class="badge bg-danger rounded-pill ms-2" id="unreadMessagesCount">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../profile.php">
                            <i class="fas fa-user me-2"></i> My Profile
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Invoice Details</h2>
                    <a href="../appointments.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Appointments
                    </a>
                </div>

                <?php if(!$invoice): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i> No invoice found for this appointment. This might be because the appointment is not completed yet or the invoice has not been generated.
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="invoice-header">
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Logo that will show in print -->
                                <img src="../assets/images/logo_vibrant.png" alt="Dental Clinic Logo" class="clinic-logo-print">
                                
                                <h3>Invoice #<?php echo $invoice['invoice_number'] ?: $invoice['id']; ?></h3>
                                <p class="mb-0">Date: <?php echo date('F j, Y', strtotime($invoice['created_at'])); ?></p>
                                <p>
                                    Status: 
                                    <span class="invoice-badge <?php echo 'badge-' . $invoice['payment_status']; ?>">
                                        <?php echo ucfirst($invoice['payment_status']); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <h5>Vibrant Smile Dental Clinic</h5>
                                <p class="mb-0">Block A7, Yoho Center, Brgy Sanito,</p>
                                <p class="mb-0">Ipil Zamboanga Sibugay</p>
                                <p class="mb-0">Contact: 09752425227</p>
                                <p class="mb-0">Email: vibrantsmile07@gmail.com</p>
                                <p class="mt-2 mb-0">Appointment Date: <?php echo date('F j, Y', strtotime($invoice['appointment_date'])); ?></p>
                                <p>Time: <?php echo date('g:i A', strtotime($invoice['appointment_time'])); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="invoice-details">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Patient Information</h5>
                                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($details['patient_name']); ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($details['patient_email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($details['patient_phone']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5>Doctor Information</h5>
                                <p class="mb-1"><strong>Name:</strong> Dr. <?php echo htmlspecialchars($details['doctor_name']); ?></p>
                                <p><strong>Specialization:</strong> <?php echo htmlspecialchars($details['specialization']); ?></p>
                            </div>
                        </div>

                        <div class="invoice-table">
                            <h5>Services</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Service</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['service_name']); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                            <td>₱<?php echo number_format($item['total_price'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="invoice-total">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Total Amount:</span>
                                    <span>₱<?php echo number_format($invoice['total_amount'], 2); ?></span>
                                </div>
                                <?php if($invoice['payment_status'] === 'partial'): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Amount Paid:</span>
                                    <span>₱<?php echo number_format($total_paid, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Remaining Balance:</span>
                                    <span class="text-danger">₱<?php echo number_format($remaining_balance, 2); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if($invoice['payment_status'] != 'paid'): ?>
                        <div class="mt-4">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Please visit the clinic to complete your payment. For inquiries, you can contact our customer service.
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="mt-4">
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i> This invoice has been fully paid. Thank you for your business!
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="text-center mt-4 no-print">
                            <button class="btn btn-primary" onclick="printInvoice()">
                                <i class="fas fa-print me-2"></i> Print Invoice
                            </button>
                            <button class="btn btn-success ms-2" onclick="downloadInvoice()">
                                <i class="fas fa-download me-2"></i> Download PDF
                            </button>
                            <a href="../appointments.php" class="btn btn-secondary ms-2">
                                <i class="fas fa-arrow-left me-2"></i> Back to Appointments
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function printInvoice() {
            // Set the document title for the print
            const originalTitle = document.title;
            document.title = "Invoice #<?php echo $invoice ? ($invoice['invoice_number'] ?: $invoice['id']) : 'Details'; ?>";
            
            // Print the document
            window.print();
            
            // Restore the original title
            setTimeout(function() {
                document.title = originalTitle;
            }, 1000);
        }
        
        function downloadInvoice() {
            // Hide elements we don't want in the PDF
            const sidebar = document.querySelector('.sidebar');
            const backButton = document.querySelector('.text-center.mt-4.no-print');
            const pageHeader = document.querySelector('.d-flex.justify-content-between.align-items-center.mb-4');
            
            // Hide elements temporarily
            if (sidebar) sidebar.style.display = 'none';
            if (backButton) backButton.style.display = 'none';
            if (pageHeader) pageHeader.style.display = 'none';
            
            // Get the invoice card
            const invoiceElement = document.querySelector('.card');
            
            // Show the logo that's normally hidden except during print
            const logo = document.querySelector('.clinic-logo-print');
            if (logo) logo.style.display = 'block';
            
            // Set up PDF options
            const opt = {
                margin: [10, 10, 10, 10],
                filename: 'Invoice-<?php echo $invoice ? ($invoice['invoice_number'] ?: $invoice['id']) : 'Details'; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, logging: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            // Generate and download the PDF
            html2pdf().set(opt).from(invoiceElement).save().then(() => {
                // Restore visibility after PDF is generated
                if (sidebar) sidebar.style.display = '';
                if (backButton) backButton.style.display = '';
                if (pageHeader) pageHeader.style.display = '';
                if (logo) logo.style.display = '';
            });
        }
        
        // Additional check to ensure images are loaded before printing
        window.onload = function() {
            const images = document.querySelectorAll('img');
            images.forEach(img => {
                if (!img.complete) {
                    img.onload = function() {
                        console.log('Image loaded:', img.src);
                    };
                }
            });
        };
    </script>
</body>
</html> 