<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Check if invoice ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid invoice ID");
}

$invoice_id = $_GET['id'];

// Get invoice details
$sql = "SELECT i.*, 
        u.first_name, u.middle_name, u.last_name,
        a.appointment_date, a.appointment_time,
        d.first_name as doctor_first_name, d.middle_name as doctor_middle_name, d.last_name as doctor_last_name
        FROM invoices i
        JOIN appointments a ON i.appointment_id = a.id
        JOIN patients p ON a.patient_id = p.id
        JOIN users u ON p.user_id = u.id
        JOIN doctors dr ON a.doctor_id = dr.id
        JOIN users d ON dr.user_id = d.id
        WHERE i.id = ?";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $invoice_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $invoice = mysqli_fetch_assoc($result);
}

// Get invoice items
$sql = "SELECT ii.*, s.name as service_name, s.duration
        FROM invoice_items ii
        JOIN services s ON ii.service_id = s.id
        WHERE ii.invoice_id = ?";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $invoice_id);
    mysqli_stmt_execute($stmt);
    $items_result = mysqli_stmt_get_result($stmt);
}

// Clinic information (hardcoded)
$clinic = [
    'name' => 'Vibrant Smile Dental Clinic',
    'address' => '123 Main Street, City, Province',
    'phone' => '(123) 456-7890',
    'email' => 'info@vibrantsmile.com'
];

// Format patient name
$patient_name = $invoice['first_name'];
if(!empty($invoice['middle_name'])) {
    $patient_name .= ' ' . $invoice['middle_name'];
}
$patient_name .= ' ' . $invoice['last_name'];

// Format doctor name
$doctor_name = $invoice['doctor_first_name'];
if(!empty($invoice['doctor_middle_name'])) {
    $doctor_name .= ' ' . $invoice['doctor_middle_name'];
}
$doctor_name .= ' ' . $invoice['doctor_last_name'];

// Format date and time
$appointment_date = date('F d, Y', strtotime($invoice['appointment_date']));
$appointment_time = date('h:i A', strtotime($invoice['appointment_time']));
?>

<div class="invoice-header">
    <h2><?php echo htmlspecialchars($clinic['name']); ?></h2>
    <p><?php echo htmlspecialchars($clinic['address']); ?></p>
    <p>Phone: <?php echo htmlspecialchars($clinic['phone']); ?></p>
    <p>Email: <?php echo htmlspecialchars($clinic['email']); ?></p>
    <h3>INVOICE</h3>
</div>

<div class="invoice-details">
    <div class="row">
        <div class="col-md-6">
            <p><strong>Invoice Number:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
            <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($invoice['created_at'])); ?></p>
            <p><strong>Patient:</strong> <?php echo htmlspecialchars($patient_name); ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>Appointment Date:</strong> <?php echo $appointment_date; ?></p>
            <p><strong>Appointment Time:</strong> <?php echo $appointment_time; ?></p>
            <p><strong>Doctor:</strong> <?php echo htmlspecialchars($doctor_name); ?></p>
        </div>
    </div>
</div>

<table class="invoice-items">
    <thead>
        <tr>
            <th>Service</th>
            <th>Duration</th>
            <th>Price</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        <?php while($item = mysqli_fetch_assoc($items_result)): ?>
        <tr>
            <td><?php echo htmlspecialchars($item['service_name']); ?></td>
            <td><?php echo htmlspecialchars($item['duration']); ?> minutes</td>
            <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
            <td>₱<?php echo number_format($item['total_price'], 2); ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<div class="invoice-total">
    <h4>Total Amount: ₱<?php echo number_format($invoice['total_amount'], 2); ?></h4>
    <p>Payment Status: <strong><?php echo ucfirst($invoice['payment_status']); ?></strong></p>
    <p>Payment Method: <strong><?php echo ucfirst($invoice['payment_method']); ?></strong></p>
</div>

<div class="invoice-footer">
    <p>Thank you for choosing <?php echo htmlspecialchars($clinic['name']); ?>!</p>
    <p>This is a computer-generated invoice. No signature is required.</p>
</div> 