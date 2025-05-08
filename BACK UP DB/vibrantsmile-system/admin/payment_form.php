<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Check if invoice_id is provided
if(!isset($_GET['invoice_id']) || !is_numeric($_GET['invoice_id'])) {
    $_SESSION['error'] = "Invalid invoice ID";
    header("location: invoices.php");
    exit;
}

$invoice_id = $_GET['invoice_id'];

// Get invoice details
$sql = "SELECT i.*, 
        CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) as patient_name,
        a.appointment_date
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
        // Get total payments made
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
        $_SESSION['error'] = "Invoice not found";
        header("location: invoices.php");
        exit;
    }
}

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $transaction_id = $_POST['transaction_id'] ?? null;
    $notes = $_POST['notes'] ?? null;
    $error = "";

    // Validate amount
    if(!is_numeric($amount) || $amount <= 0) {
        $error = "Please enter a valid amount";
    } else if($amount > $remaining_balance) {
        $error = "Payment amount cannot exceed the remaining balance";
    }

    if(empty($error)) {
        // Begin transaction
        mysqli_begin_transaction($conn);
        try {
            // Insert payment
            $sql = "INSERT INTO payments (invoice_id, amount, payment_method, transaction_id, notes) 
                    VALUES (?, ?, ?, ?, ?)";
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "idsss", $invoice_id, $amount, $payment_method, $transaction_id, $notes);
                mysqli_stmt_execute($stmt);
            }

            // Update invoice payment status
            $new_total_paid = $total_paid + $amount;
            $new_status = ($new_total_paid >= $invoice['total_amount']) ? 'paid' : 
                         ($new_total_paid > 0 ? 'partial' : 'pending');
            
            $sql = "UPDATE invoices SET payment_status = ?, payment_method = ? WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssi", $new_status, $payment_method, $invoice_id);
                mysqli_stmt_execute($stmt);
            }

            mysqli_commit($conn);
            $_SESSION['success'] = "Payment recorded successfully";
            header("location: invoice_details.php?id=" . $invoice_id);
            exit;
        } catch(Exception $e) {
            mysqli_rollback($conn);
            $error = "Error recording payment: " . $e->getMessage();
        }
    }
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

$page_title = "Record Payment";
$current_page = "invoices";
require_once "includes/header.php";
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Record Payment</h5>
                        <a href="invoice_details.php?id=<?php echo $invoice_id; ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-2"></i>Back to Invoice
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Invoice Details</h6>
                            <p><strong>Invoice #:</strong> <?php echo $invoice['invoice_number']; ?></p>
                            <p><strong>Patient:</strong> <?php echo htmlspecialchars($invoice['patient_name']); ?></p>
                            <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($invoice['appointment_date'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Payment Summary</h6>
                            <p><strong>Total Amount:</strong> ₱<?php echo number_format($invoice['total_amount'], 2); ?></p>
                            <p><strong>Amount Paid:</strong> ₱<?php echo number_format($total_paid, 2); ?></p>
                            <p><strong>Remaining Balance:</strong> ₱<?php echo number_format($remaining_balance, 2); ?></p>
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
                    </div>

                    <?php if($remaining_balance > 0): ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?invoice_id=" . $invoice_id; ?>" method="post">
                        <?php if(!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Payment Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" name="amount" class="form-control" step="0.01" max="<?php echo $remaining_balance; ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <select name="payment_method" class="form-select" required>
                                        <option value="cash">Cash</option>
                                        <option value="gcash">GCash</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3" id="gcashReferenceField" style="display: none;">
                            <label class="form-label">GCash Reference Number</label>
                            <input type="text" name="transaction_id" class="form-control" placeholder="Enter GCash reference number">
                            <div class="form-text">Required for GCash payments</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes (Optional)</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Record Payment
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>

                    <div class="mt-4">
                        <h6>Payment History</h6>
                        <?php if(!empty($payment_history)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Reference</th>
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

<script>
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

    // Show/hide GCash reference number field based on payment method
    const paymentMethodSelect = document.querySelector('select[name="payment_method"]');
    const gcashReferenceField = document.getElementById('gcashReferenceField');
    
    function toggleGcashField() {
        if (paymentMethodSelect.value === 'gcash') {
            gcashReferenceField.style.display = 'block';
            gcashReferenceField.querySelector('input').required = true;
        } else {
            gcashReferenceField.style.display = 'none';
            gcashReferenceField.querySelector('input').required = false;
        }
    }

    paymentMethodSelect.addEventListener('change', toggleGcashField);
    toggleGcashField(); // Initial state
});
</script> 