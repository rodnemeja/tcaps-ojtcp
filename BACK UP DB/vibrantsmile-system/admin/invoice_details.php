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
    header("location: invoices.php");
    exit;
}

$invoice_id = $_GET['id'];

// Get invoice details with patient and appointment info
$sql = "SELECT i.*, 
        CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) as patient_name,
        u.email as patient_email,
        u.phone as patient_phone,
        a.appointment_date,
        a.appointment_time,
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
    if($invoice = mysqli_fetch_assoc($result)) {
        // Check if invoice is paid and if payment record exists
        if($invoice['payment_status'] === 'paid') {
            // Check if payment record exists
            $check_payment_sql = "SELECT id FROM payments WHERE invoice_id = ?";
            if($stmt = mysqli_prepare($conn, $check_payment_sql)) {
                mysqli_stmt_bind_param($stmt, "i", $invoice_id);
                mysqli_stmt_execute($stmt);
                $payment_result = mysqli_stmt_get_result($stmt);
                
                // If no payment record exists, create one
                if(mysqli_num_rows($payment_result) === 0) {
                    $insert_payment_sql = "INSERT INTO payments (invoice_id, amount, payment_date, payment_method, transaction_id, notes) 
                                         VALUES (?, ?, NOW(), ?, ?, ?)";
                    
                    if($stmt = mysqli_prepare($conn, $insert_payment_sql)) {
                        // Use invoice number as transaction ID
                        $transaction_id = $invoice['invoice_number'];
                        $notes = 'Automatically recorded payment for paid invoice';
                        
                        mysqli_stmt_bind_param($stmt, "idsss", 
                            $invoice_id,
                            $invoice['total_amount'],
                            $invoice['payment_method'],
                            $transaction_id,
                            $notes
                        );
                        
                        if(mysqli_stmt_execute($stmt)) {
                            // Log the transaction
                            $staff_id = $_SESSION['id'];
                            $log_sql = "INSERT INTO staff_transactions (staff_id, transaction_type, details, patient_id, appointment_id, amount) 
                                       VALUES (?, 'payment', ?, ?, ?, ?)";
                            
                            if($stmt = mysqli_prepare($conn, $log_sql)) {
                                $details = "Automatically recorded payment for invoice #" . $invoice['invoice_number'];
                                mysqli_stmt_bind_param($stmt, "isiii", 
                                    $staff_id,
                                    $details,
                                    $invoice['patient_id'],
                                    $invoice['appointment_id'],
                                    $invoice['total_amount']
                                );
                                mysqli_stmt_execute($stmt);
                            }
                        }
                    }
                }
            }
        }

        // Get invoice items
        $sql = "SELECT ii.*, s.name as service_name, s.duration
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
        header("location: invoices.php");
        exit;
    }
}

$page_title = "Invoice Details";
$current_page = "invoices";
require_once "includes/header.php";
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Invoice #<?php echo $invoice['invoice_number']; ?></h5>
                        <div>
                            <?php if($remaining_balance > 0): ?>
                            <a href="payment_form.php?invoice_id=<?php echo $invoice_id; ?>" class="btn btn-success btn-sm me-2">
                                <i class="fas fa-money-bill-wave me-2"></i>Record Payment
                            </a>
                            <?php endif; ?>
                            <button onclick="printInvoice()" class="btn btn-primary btn-sm me-2">
                                <i class="fas fa-print me-2"></i>Print
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteInvoiceModal">
                                <i class="fas fa-trash me-2"></i>Delete Invoice
                            </button>
                            <a href="invoices.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Invoice Information</h6>
                            <p><strong>Invoice #:</strong> <?php echo $invoice['invoice_number']; ?></p>
                            <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($invoice['created_at'])); ?></p>
                            <p>
                                <strong>Status:</strong> 
                                <span class="badge bg-<?php 
                                    echo $invoice['payment_status'] === 'paid' ? 'success' : 
                                        ($invoice['payment_status'] === 'partial' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($invoice['payment_status']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Patient Information</h6>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($invoice['patient_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($invoice['patient_email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($invoice['patient_phone']); ?></p>
                            <p><strong>Appointment:</strong> <?php echo date('M d, Y g:i A', strtotime($invoice['appointment_date'] . ' ' . $invoice['appointment_time'])); ?></p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <h6>Services</h6>
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
                                        <?php while($item = mysqli_fetch_assoc($items)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['service_name']); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                                <td>₱<?php echo number_format($item['total_price'], 2); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                                            <td><strong>₱<?php echo number_format($invoice['total_amount'], 2); ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Amount Paid:</strong></td>
                                            <td><strong>₱<?php echo number_format($total_paid, 2); ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Remaining Balance:</strong></td>
                                            <td><strong>₱<?php echo number_format($remaining_balance, 2); ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h6>Payment History</h6>
                            <?php if(!empty($payment_history)): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Amount</th>
                                                <th>Method</th>
                                                <th>Reference Number</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($payment_history as $payment): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y g:i A', strtotime($payment['payment_date'])); ?></td>
                                                    <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                                    <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                                    <td><?php echo $payment['transaction_id'] ?: '-'; ?></td>
                                                    <td><?php echo $payment['notes'] ?: '-'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No payments recorded yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Invoice Modal -->
<div class="modal fade" id="deleteInvoiceModal" tabindex="-1" aria-labelledby="deleteInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteInvoiceModalLabel">Delete Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this invoice? This action cannot be undone.</p>
                <p class="text-danger"><strong>Warning:</strong> This will also delete all associated payment records.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="delete_invoice.php" method="POST" class="d-inline">
                    <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                    <button type="submit" class="btn btn-danger">Delete Invoice</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function printInvoice() {
    window.open('invoice_print.php?id=<?php echo $invoice_id; ?>', '_blank');
}

document.addEventListener('DOMContentLoaded', function() {
    // Show success message if it exists
    <?php if(isset($_SESSION['success'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '<?php echo $_SESSION['success']; ?>',
        timer: 2000,
        showConfirmButton: false
    });
    <?php unset($_SESSION['success']); endif; ?>
});
</script> 