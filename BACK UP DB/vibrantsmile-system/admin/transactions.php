<?php
session_start();
require_once "../config/database.php";
require_once "../includes/activity_logger.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Process filter form if submitted
$filter_type = null;
$date_from = null;
$date_to = null;

if(isset($_GET['filter'])) {
    if(!empty($_GET['transaction_type'])) {
        $filter_type = $_GET['transaction_type'];
    }
    
    if(!empty($_GET['date_from'])) {
        $date_from = $_GET['date_from'];
    }
    
    if(!empty($_GET['date_to'])) {
        $date_to = $_GET['date_to'];
    }
}

// Get transaction types for filter dropdown
$sql = "SELECT DISTINCT transaction_type FROM staff_transactions ORDER BY transaction_type";
$transaction_types = mysqli_query($conn, $sql);

// Get staff transactions
$transactions = get_all_staff_transactions($filter_type, $date_from, $date_to);

$page_title = "Staff Transactions";
$current_page = "transactions";
require_once "includes/header.php";
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Staff Transactions</h2>
    </div>

    <!-- Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Transactions</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="transaction_type" class="form-label">Transaction Type</label>
                    <select class="form-select" id="transaction_type" name="transaction_type">
                        <option value="">All Types</option>
                        <?php if(mysqli_num_rows($transaction_types) > 0): ?>
                            <?php while($type = mysqli_fetch_assoc($transaction_types)): ?>
                                <option value="<?php echo htmlspecialchars($type['transaction_type']); ?>"
                                    <?php echo $filter_type === $type['transaction_type'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $type['transaction_type'])); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo $date_from ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to"
                           value="<?php echo $date_to ?? ''; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div>
                        <button type="submit" name="filter" value="1" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>Apply Filter
                        </button>
                        <a href="transactions.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-sync-alt me-2"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Recent Transactions</h6>
            <div class="search-box">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-primary"></i>
                    </span>
                    <input type="text" id="searchInput" class="form-control border-start-0 ps-0" 
                           placeholder="Search transactions..." style="max-width: 250px;">
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="transactionsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Staff/Doctor</th>
                            <th>Role</th>
                            <th>Type</th>
                            <th>Details</th>
                            <th>Patient</th>
                            <th>Amount</th>
                            <th>Date/Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($transactions)): ?>
                            <?php foreach($transactions as $transaction): ?>
                                <tr class="searchable-row">
                                    <td class="searchable"><?php echo $transaction['id']; ?></td>
                                    <td class="searchable">
                                        <?php echo htmlspecialchars($transaction['staff_first_name'] . ' ' . $transaction['staff_last_name']); ?>
                                    </td>
                                    <td class="searchable">
                                        <span class="badge bg-<?php echo $transaction['role'] === 'doctor' ? 'primary' : 'secondary'; ?>">
                                            <?php echo ucfirst($transaction['role']); ?>
                                        </span>
                                    </td>
                                    <td class="searchable">
                                        <?php 
                                        $type_class = match($transaction['transaction_type']) {
                                            'appointment' => 'info',
                                            'payment' => 'success',
                                            'prescription' => 'warning',
                                            'medical_record' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $type_class; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $transaction['transaction_type'])); ?>
                                        </span>
                                    </td>
                                    <td class="searchable">
                                        <?php echo htmlspecialchars($transaction['details']); ?>
                                    </td>
                                    <td class="searchable">
                                        <?php if($transaction['patient_id']): ?>
                                            <a href="patient_profile.php?id=<?php echo $transaction['patient_id']; ?>">
                                                <?php echo htmlspecialchars($transaction['patient_first_name'] . ' ' . $transaction['patient_last_name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="searchable">
                                        <?php if($transaction['amount']): ?>
                                            ₱<?php echo number_format($transaction['amount'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="searchable">
                                        <?php echo date('M d, Y h:i A', strtotime($transaction['transaction_date'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">No transactions found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
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

/* Table Styles */
.table > :not(caption) > * > * {
    padding: 1rem 0.75rem;
}

.table > tbody > tr:hover {
    background-color: #f8f9fc;
}

/* Badge Styles */
.badge {
    padding: 0.5em 0.8em;
    font-weight: 500;
}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Real-time search functionality
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('transactionsTable');
        const rows = table.getElementsByClassName('searchable-row');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            let hasResults = false;

            Array.from(rows).forEach(row => {
                const searchableFields = row.getElementsByClassName('searchable');
                let rowText = '';
                
                Array.from(searchableFields).forEach(field => {
                    rowText += field.textContent.toLowerCase() + ' ';
                });

                if (rowText.includes(searchTerm)) {
                    row.style.display = '';
                    hasResults = true;
                } else {
                    row.style.display = 'none';
                }
            });

            const noResultsRow = document.getElementById('noResultsRow');
            if(noResultsRow) {
                noResultsRow.style.display = hasResults ? 'none' : '';
            }
        });

        // Date validation
        const dateFrom = document.getElementById('date_from');
        const dateTo = document.getElementById('date_to');

        if(dateFrom && dateTo) {
            dateFrom.addEventListener('change', function() {
                if(dateTo.value && this.value > dateTo.value) {
                    dateTo.value = this.value;
                }
            });

            dateTo.addEventListener('change', function() {
                if(dateFrom.value && this.value < dateFrom.value) {
                    dateFrom.value = this.value;
                }
            });
        }
    });
</script>

<?php require_once "includes/footer.php"; ?> 