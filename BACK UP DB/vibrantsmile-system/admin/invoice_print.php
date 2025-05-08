<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Get invoice ID from URL
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid invoice ID");
}

$invoice_id = $_GET['id'];

// Get invoice details
$sql = "SELECT i.*, 
        CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) as patient_name,
        u.email as patient_email,
        u.phone as patient_phone,
        a.appointment_date,
        a.appointment_time
        FROM invoices i 
        JOIN appointments a ON i.appointment_id = a.id 
        JOIN patients p ON a.patient_id = p.id 
        JOIN users u ON p.user_id = u.id 
        WHERE i.id = ?";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $invoice_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($invoice = mysqli_fetch_assoc($result)) {
        // Get invoice items
        $sql = "SELECT ii.*, s.name as service_name 
                FROM invoice_items ii 
                JOIN services s ON ii.service_id = s.id 
                WHERE ii.invoice_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $invoice_id);
            mysqli_stmt_execute($stmt);
            $items = mysqli_stmt_get_result($stmt);
        }

        // Get payment history
        $sql = "SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC";
        $payment_history = [];
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $invoice_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)) {
                $payment_history[] = $row;
            }
        }

        // Calculate total paid amount
        $sql = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE invoice_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $invoice_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $payments = mysqli_fetch_assoc($result);
            $total_paid = $payments['total_paid'];
            $remaining_balance = $invoice['total_amount'] - $total_paid;
        }
    } else {
        die("Invoice not found");
    }
} else {
    die("Error: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $invoice['invoice_number']; ?> - Print</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: white;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .clinic-logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 20px;
        }
        .invoice-details {
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
        .total-section {
            text-align: right;
            margin-top: 20px;
        }
        .payment-history {
            margin-top: 30px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        .status-paid {
            background-color: #28a745;
            color: white;
        }
        .status-partial {
            background-color: #ffc107;
            color: black;
        }
        .status-unpaid {
            background-color: #dc3545;
            color: white;
        }
        @media print {
            @page {
                margin: 0.5cm;
            }
            body {
                padding: 0;
            }
            .invoice-container {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <img src="../assets/images/logo_vibrant.png" alt="Vibrant Smile Dental Clinic Logo" class="clinic-logo">
            <h1>Vibrant Smile Dental Clinic</h1>
            <p>Block A7, Yoho Center, Brgy Sanito, Ipil Zamboanga Sibugay</p>
            <p>Contact: 09752425227 | Email: vibrantsmile07@gmail.com</p>
        </div>

        <div class="invoice-details">
            <div style="float: left; width: 50%;">
                <h4>Invoice Details</h4>
                <p><strong>Invoice #:</strong> <?php echo $invoice['invoice_number']; ?></p>
                <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($invoice['created_at'])); ?></p>
                <p>
                    <strong>Status:</strong> 
                    <span class="status-badge status-<?php echo $invoice['payment_status']; ?>">
                        <?php echo ucfirst($invoice['payment_status']); ?>
                    </span>
                </p>
            </div>
            <div style="float: right; width: 50%;">
                <h4>Patient Information</h4>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($invoice['patient_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($invoice['patient_email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($invoice['patient_phone']); ?></p>
                <p><strong>Appointment:</strong> <?php echo date('M d, Y g:i A', strtotime($invoice['appointment_date'] . ' ' . $invoice['appointment_time'])); ?></p>
            </div>
            <div style="clear: both;"></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                mysqli_data_seek($items, 0);
                while($item = mysqli_fetch_assoc($items)): 
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['service_name']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                    <td>₱<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Total Amount:</strong></td>
                    <td><strong>₱<?php echo number_format($invoice['total_amount'], 2); ?></strong></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Amount Paid:</strong></td>
                    <td>₱<?php echo number_format($total_paid, 2); ?></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Balance:</strong></td>
                    <td>₱<?php echo number_format($remaining_balance, 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <?php if (!empty($payment_history)): ?>
        <div class="payment-history">
            <h4>Payment History</h4>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payment_history as $payment): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                        <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                        <td><?php echo ucfirst($payment['payment_method']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>Thank you for choosing Vibrant Smile Dental Clinic!</p>
        </div>
    </div>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html> 