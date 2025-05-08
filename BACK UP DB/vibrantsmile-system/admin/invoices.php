<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Handle payment status update
if(isset($_POST['update_status']) && isset($_POST['invoice_id']) && isset($_POST['status'])) {
    $invoice_id = $_POST['invoice_id'];
    $status = $_POST['status'];
    $sql = "UPDATE invoices SET payment_status = ? WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $status, $invoice_id);
        mysqli_stmt_execute($stmt);
    }
    header("location: invoices.php");
    exit;
}

// Get all invoices with patient and appointment details
$sql = "SELECT i.*, 
        CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) as patient_name,
        u.email as patient_email,
        u.phone as patient_phone,
        a.appointment_date,
        a.appointment_time,
        GROUP_CONCAT(s.name) as services,
        GROUP_CONCAT(ii.quantity) as quantities,
        GROUP_CONCAT(ii.unit_price) as prices,
        (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = i.id) as total_paid
        FROM invoices i 
        JOIN appointments a ON i.appointment_id = a.id 
        JOIN patients p ON a.patient_id = p.id 
        JOIN users u ON p.user_id = u.id 
        LEFT JOIN invoice_items ii ON i.id = ii.invoice_id 
        LEFT JOIN services s ON ii.service_id = s.id 
        GROUP BY i.id 
        ORDER BY i.created_at DESC";
$invoices = mysqli_query($conn, $sql);
if(!$invoices) {
    die("Error: " . mysqli_error($conn));
}

$page_title = "Invoices";
$current_page = "invoices";
require_once "includes/header.php";

// Display success message if it exists in session
if(isset($_SESSION['success'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '" . $_SESSION['success'] . "',
                timer: 2000,
                showConfirmButton: false
            });
        });
    </script>";
    unset($_SESSION['success']);
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Invoices</h1>
        <a href="invoice_form.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create New Invoice
        </a>
    </div>

    <!-- Search Bar -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-white">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="searchInput" class="form-control" placeholder="Search invoices...">
            </div>
        </div>
    </div>

    <!-- No Results Message -->
    <div id="noResultsMessage" class="row mb-4" style="display: none;">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>No invoices found matching your search criteria.
            </div>
        </div>
    </div>

    <div class="row" id="invoiceCards">
        <?php if(mysqli_num_rows($invoices) > 0): ?>
            <?php while($invoice = mysqli_fetch_assoc($invoices)): 
                $remaining_balance = $invoice['total_amount'] - $invoice['total_paid'];
            ?>
                <div class="col-md-6 col-xl-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Invoice #<?php echo $invoice['invoice_number']; ?></h6>
                                <span class="badge bg-<?php 
                                    echo $invoice['payment_status'] === 'paid' ? 'success' : 
                                        ($invoice['payment_status'] === 'partial' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($invoice['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <p class="mb-1"><strong>Patient:</strong> <?php echo htmlspecialchars($invoice['patient_name']); ?></p>
                                <p class="mb-1"><strong>Date:</strong> <?php echo date('M d, Y', strtotime($invoice['created_at'])); ?></p>
                                <p class="mb-1"><strong>Appointment:</strong> <?php echo date('M d, Y g:i A', strtotime($invoice['appointment_date'] . ' ' . $invoice['appointment_time'])); ?></p>
                            </div>

                            <div class="mb-3">
                                <h6 class="mb-2">Services:</h6>
                                <?php 
                                if (!empty($invoice['services'])) {
                                    $services = explode(',', $invoice['services']);
                                    $prices = explode(',', $invoice['prices']);
                                    for($i = 0; $i < count($services); $i++): 
                                ?>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span><?php echo htmlspecialchars($services[$i]); ?></span>
                                        <span>₱<?php echo number_format($prices[$i], 2); ?></span>
                                    </div>
                                <?php 
                                    endfor;
                                } else {
                                    echo '<p class="text-muted mb-0">No services added</p>';
                                }
                                ?>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <p class="mb-0"><strong>Total Amount:</strong></p>
                                    <?php if($invoice['payment_status'] === 'partial'): ?>
                                    <p class="mb-0"><strong>Amount Paid:</strong></p>
                                    <p class="mb-0"><strong>Balance:</strong></p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <p class="mb-0">₱<?php echo number_format($invoice['total_amount'], 2); ?></p>
                                    <?php if($invoice['payment_status'] === 'partial'): ?>
                                    <p class="mb-0">₱<?php echo number_format($invoice['total_paid'], 2); ?></p>
                                    <p class="mb-0">₱<?php echo number_format($remaining_balance, 2); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="invoice_details.php?id=<?php echo $invoice['id']; ?>" class="btn btn-primary btn-sm flex-grow-1">
                                    <i class="fas fa-eye me-2"></i>View Details
                                </a>
                                <?php if($remaining_balance > 0): ?>
                                <a href="payment_form.php?invoice_id=<?php echo $invoice['id']; ?>" class="btn btn-success btn-sm flex-grow-1">
                                    <i class="fas fa-money-bill-wave me-2"></i>Record Payment
                                </a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteInvoiceModal<?php echo $invoice['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Modal for each invoice -->
                <div class="modal fade" id="deleteInvoiceModal<?php echo $invoice['id']; ?>" tabindex="-1" aria-labelledby="deleteInvoiceModalLabel<?php echo $invoice['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteInvoiceModalLabel<?php echo $invoice['id']; ?>">Delete Invoice</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete Invoice #<?php echo $invoice['invoice_number']; ?>?</p>
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
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <p class="text-muted mb-0">No invoices found. Click the "Create New Invoice" button to create one.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const invoiceCards = document.getElementById('invoiceCards');
    const noResultsMessage = document.getElementById('noResultsMessage');
    const cards = invoiceCards.getElementsByClassName('col-md-6');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        let visibleCount = 0;

        Array.from(cards).forEach(card => {
            const cardText = card.textContent.toLowerCase();
            const isVisible = cardText.includes(searchTerm);
            card.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        // Show/hide no results message
        noResultsMessage.style.display = visibleCount === 0 ? '' : 'none';
    });
});
</script>

<?php require_once "includes/footer.php"; ?>