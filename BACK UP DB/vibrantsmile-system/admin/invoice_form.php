<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

$invoice = array();
$edit_mode = false;

// Get invoice data if in edit mode
if(isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $sql = "SELECT i.*, 
            CONCAT(p.first_name, ' ', COALESCE(p.middle_name, ''), ' ', p.last_name) as patient_name, 
            a.appointment_date, 
            a.id as appointment_id, 
            a.patient_id 
            FROM invoices i 
            JOIN appointments a ON i.appointment_id = a.id 
            JOIN patients pt ON a.patient_id = pt.id 
            JOIN users p ON pt.user_id = p.id 
            WHERE i.id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)) {
            $invoice = $row;
            
            // Debug
            error_log("Loaded invoice data: " . json_encode($invoice));
        }
    }
}

// Get all patients
$sql = "SELECT p.id, CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) as full_name, u.email, u.phone 
        FROM patients p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY u.last_name, u.first_name";
$patients = mysqli_query($conn, $sql);
if(!$patients) {
    die("Error fetching patients: " . mysqli_error($conn));
}

// Get all services
$sql = "SELECT * FROM services ORDER BY name";
$services = mysqli_query($conn, $sql);

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_id = $_POST['patient_id'];
    $appointment_id = $_POST['appointment_id'];
    $payment_status = $_POST['payment_status'];
    $total_amount = 0;
    $service_items = array();
    $error = "";

    // Validate patient and appointment
    if(empty($patient_id) || empty($appointment_id)) {
        $error = "Please select both patient and appointment.";
    }

    // Validate services
    if(empty($_POST['services']) || empty($_POST['services'][0])) {
        $error = "Please add at least one service.";
    }

    if(empty($error)) {
        // Calculate total amount and prepare service items
        foreach($_POST['services'] as $key => $service_id) {
            if(!empty($service_id) && !empty($_POST['quantities'][$key])) {
                $quantity = $_POST['quantities'][$key];
                $price = $_POST['prices'][$key];
                $total_amount += $price * $quantity;
                $service_items[] = array(
                    'service_id' => $service_id,
                    'quantity' => $quantity,
                    'price' => $price
                );
            }
        }

        if($total_amount <= 0) {
            $error = "Total amount must be greater than zero.";
        }

        if(empty($error)) {
            if($edit_mode) {
                $invoice_id = $_GET['edit']; // Get the invoice ID from URL
                // Update invoice
                $sql = "UPDATE invoices SET appointment_id = ?, total_amount = ?, payment_status = ? WHERE id = ?";
                if($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "idss", $appointment_id, $total_amount, $payment_status, $invoice_id);
                    if(!mysqli_stmt_execute($stmt)) {
                        $error = "Error updating invoice: " . mysqli_error($conn);
                    }
                }

                if(empty($error)) {
                    // Delete existing invoice items
                    $sql = "DELETE FROM invoice_items WHERE invoice_id = ?";
                    if($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "i", $invoice_id);
                        if(!mysqli_stmt_execute($stmt)) {
                            $error = "Error deleting invoice items: " . mysqli_error($conn);
                        }
                    }
                }
            } else {
                // Insert new invoice
                // Generate a unique invoice number
                $invoice_prefix = "INV-";
                $invoice_date = date('Ymd');
                $invoice_random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
                $invoice_number = $invoice_prefix . $invoice_date . '-' . $invoice_random;
                
                // Check if the invoice number already exists
                $check_sql = "SELECT id FROM invoices WHERE invoice_number = ?";
                if($check_stmt = mysqli_prepare($conn, $check_sql)) {
                    mysqli_stmt_bind_param($check_stmt, "s", $invoice_number);
                    mysqli_stmt_execute($check_stmt);
                    mysqli_stmt_store_result($check_stmt);
                    
                    // If invoice number exists, generate a new one
                    if(mysqli_stmt_num_rows($check_stmt) > 0) {
                        $invoice_random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
                        $invoice_number = $invoice_prefix . $invoice_date . '-' . $invoice_random;
                    }
                    
                    mysqli_stmt_close($check_stmt);
                }
                
                // Insert with invoice number
                $sql = "INSERT INTO invoices (appointment_id, total_amount, payment_status, invoice_number) VALUES (?, ?, ?, ?)";
                if($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "idss", $appointment_id, $total_amount, $payment_status, $invoice_number);
                    if(!mysqli_stmt_execute($stmt)) {
                        $error = "Error creating invoice: " . mysqli_error($conn);
                    } else {
                        $invoice_id = mysqli_insert_id($conn);
                    }
                }
            }

            if(empty($error)) {
                // Insert invoice items
                foreach($service_items as $item) {
                    $sql = "INSERT INTO invoice_items (invoice_id, service_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)";
                    if($stmt = mysqli_prepare($conn, $sql)) {
                        $total_price = $item['price'] * $item['quantity'];
                        mysqli_stmt_bind_param($stmt, "iiidd", $invoice_id, $item['service_id'], $item['quantity'], $item['price'], $total_price);
                        if(!mysqli_stmt_execute($stmt)) {
                            $error = "Error adding invoice items: " . mysqli_error($conn);
                            break;
                        }
                    }
                }
            }

            if(empty($error)) {
                // Set success message in session and redirect
                $_SESSION['invoice_success'] = $edit_mode ? "Invoice updated successfully" : "Invoice created successfully";
                header("location: invoices.php");
                exit;
            }
        }
    }
}

// Get invoice items if in edit mode
$invoice_items = array();
if($edit_mode) {
    $sql = "SELECT * FROM invoice_items WHERE invoice_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $_GET['edit']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)) {
            $invoice_items[] = $row;
        }
    }
}

$page_title = $edit_mode ? 'Edit Invoice' : 'New Invoice';
$current_page = 'invoices';
require_once 'includes/header.php';

// Ensure SweetAlert2 is loaded
echo '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

// Display error message if any
$alert_message = "";
if(!empty($error)) {
    $alert_message = "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '" . addslashes($error) . "',
                confirmButtonColor: '#d33'
            });
        });
    </script>";
}
echo $alert_message;
?>

<!-- Main Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><?php echo $edit_mode ? 'Edit Invoice' : 'New Invoice'; ?></h5>
                        <a href="invoices.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-2"></i>Back to Invoices
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . ($edit_mode ? "?edit=" . $_GET['edit'] : ""); ?>" method="post" id="invoiceForm">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Patient</label>
                                <select name="patient_id" class="form-select" required onchange="loadAppointments(this.value)">
                                    <option value="">Select Patient</option>
                                    <?php 
                                    mysqli_data_seek($patients, 0);
                                    while($patient = mysqli_fetch_assoc($patients)): 
                                    ?>
                                        <option value="<?php echo $patient['id']; ?>" 
                                            <?php echo isset($invoice['patient_id']) && $invoice['patient_id'] == $patient['id'] ? 'selected' : ''; ?> 
                                            data-email="<?php echo htmlspecialchars($patient['email']); ?>" 
                                            data-phone="<?php echo htmlspecialchars($patient['phone']); ?>">
                                            <?php echo htmlspecialchars($patient['full_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Appointment</label>
                                <div class="mb-2 small text-danger">
                                    <i class="fas fa-info-circle"></i> Only completed appointments can be invoiced
                                </div>
                                <select name="appointment_id" class="form-select" required>
                                    <option value="">Select Appointment</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5>Services</h5>
                            <div id="servicesContainer">
                                <?php if(!empty($invoice_items)): ?>
                                    <?php foreach($invoice_items as $item): ?>
                                        <div class="row mb-2 service-row">
                                            <div class="col-md-5">
                                                <select name="services[]" class="form-select service-select" required onchange="updatePrice(this)">
                                                    <option value="">Select Service</option>
                                                    <?php 
                                                    mysqli_data_seek($services, 0);
                                                    while($service = mysqli_fetch_assoc($services)): 
                                                    ?>
                                                        <option value="<?php echo $service['id']; ?>" data-price="<?php echo $service['price']; ?>"
                                                                <?php echo $item['service_id'] == $service['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($service['name']); ?> - ₱<?php echo number_format($service['price'], 2); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="number" name="quantities[]" class="form-control quantity-input" 
                                                       value="<?php echo $item['quantity']; ?>" min="1" required onchange="updateTotal()">
                                            </div>
                                            <div class="col-md-3">
                                                <input type="number" name="prices[]" class="form-control price-input" 
                                                       value="<?php echo $item['unit_price']; ?>" step="0.01" required readonly>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-danger btn-sm remove-service">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="row mb-2 service-row">
                                        <div class="col-md-5">
                                            <select name="services[]" class="form-select service-select" required onchange="updatePrice(this)">
                                                <option value="">Select Service</option>
                                                <?php 
                                                mysqli_data_seek($services, 0);
                                                while($service = mysqli_fetch_assoc($services)): 
                                                ?>
                                                    <option value="<?php echo $service['id']; ?>" data-price="<?php echo $service['price']; ?>">
                                                        <?php echo htmlspecialchars($service['name']); ?> - ₱<?php echo number_format($service['price'], 2); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                                        <div class="col-md-3">
                                                            <input type="number" name="quantities[]" class="form-control quantity-input" value="1" min="1" required onchange="updateTotal()">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <input type="number" name="prices[]" class="form-control price-input" step="0.01" required readonly>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <button type="button" class="btn btn-danger btn-sm remove-service">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="addService()">
                                        <i class="fas fa-plus me-2"></i>Add Service
                                    </button>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Payment Status</label>
                                        <select name="payment_status" class="form-select" required>
                                            <option value="pending" <?php echo isset($invoice['payment_status']) && $invoice['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="partial" <?php echo isset($invoice['payment_status']) && $invoice['payment_status'] === 'partial' ? 'selected' : ''; ?>>Partial</option>
                                            <option value="paid" <?php echo isset($invoice['payment_status']) && $invoice['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Total Amount</label>
                                        <input type="number" id="total_amount" class="form-control" readonly>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Invoice
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function loadAppointments(patientId) {
        if(!patientId) {
            document.querySelector('select[name="appointment_id"]').innerHTML = '<option value="">Select Appointment</option>';
            return;
        }

        console.log("Loading appointments for patient ID:", patientId);
        
        const appointmentSelect = document.querySelector('select[name="appointment_id"]');
        appointmentSelect.innerHTML = '<option value="">Loading appointments...</option>';
        
        fetch(`get_appointments.php?patient_id=${patientId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log("Appointments data received:", data);
                
                appointmentSelect.innerHTML = '<option value="">Select Appointment</option>';
                
                if(data.error) {
                    console.error("Error from server:", data.error);
                    appointmentSelect.innerHTML = `<option value="">Error: ${data.error}</option>`;
                    return;
                }
                
                if(data.length === 0) {
                    appointmentSelect.innerHTML = '<option value="">No appointments available for this patient</option>';
                    return;
                }
                
                let hasValidAppointments = false;
                
                data.forEach(appointment => {
                    const option = document.createElement('option');
                    option.value = appointment.id;
                    option.textContent = `${appointment.appointment_date} ${appointment.appointment_time} (${appointment.status})`;
                    
                    // Mark appointments that already have invoices or are not completed/approved
                    if(appointment.has_invoice) {
                        option.disabled = true;
                        option.style.color = "#999";
                    } else if(!['completed'].includes(appointment.status.toLowerCase().replace(' (has invoice)', ''))) {
                        option.disabled = true;
                        option.style.color = "#d9534f";
                    } else {
                        hasValidAppointments = true;
                    }
                    
                    if(appointment.id == <?php echo isset($invoice['appointment_id']) ? $invoice['appointment_id'] : 'null'; ?>) {
                        option.selected = true;
                    }
                    
                    appointmentSelect.appendChild(option);
                });
                
                // Add message if no valid appointments
                if(!hasValidAppointments && data.length > 0) {
                    const messageOption = document.createElement('option');
                    messageOption.value = "";
                    messageOption.disabled = true;
                    messageOption.style.fontStyle = "italic";
                    messageOption.textContent = "Only completed appointments can be invoiced";
                    appointmentSelect.insertBefore(messageOption, appointmentSelect.firstChild);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.querySelector('select[name="appointment_id"]').innerHTML = 
                    `<option value="">Error loading appointments: ${error.message}</option>`;
            });
    }

    function loadAppointmentServices(appointmentId) {
        if(!appointmentId) {
            clearServices();
            return;
        }

        console.log("Loading services for appointment ID:", appointmentId);
        
        // Show loading indicator
        const container = document.getElementById('servicesContainer');
        container.innerHTML = '<div class="text-center my-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading appointment services...</p></div>';
        
        fetch(`get_appointment_services.php?appointment_id=${appointmentId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log("Services data received:", data);
                
                // Clear existing services container
                container.innerHTML = '';
                
                // Make sure data is an array
                if (!Array.isArray(data)) {
                    console.error("Expected array but received:", typeof data);
                    if (data && data.error) {
                        throw new Error(data.error);
                    }
                    // Convert to empty array if not array
                    data = [];
                }
                
                // Add services from the appointment
                if (data.length === 0) {
                    console.log("No services found, adding empty row");
                    // If no services found, add an empty service row
                    clearServices();
                    
                    // Show a message that no services were found
                    Swal.fire({
                        title: 'No Services Found',
                        text: 'No services were found for this appointment. Please add services manually.',
                        icon: 'info',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }
                
                // Add each service as a row
                data.forEach(service => {
                    // Create a new row
                    const row = document.createElement('div');
                    row.className = 'row mb-2 service-row';
                    
                    // Set the inner HTML for the row
                    row.innerHTML = `
                        <div class="col-md-5">
                            <select name="services[]" class="form-select service-select" required onchange="updatePrice(this)">
                                <option value="">Select Service</option>
                                <?php 
                                mysqli_data_seek($services, 0);
                                while($service = mysqli_fetch_assoc($services)): 
                                ?>
                                    <option value="<?php echo $service['id']; ?>" 
                                            data-price="<?php echo $service['price']; ?>">
                                        <?php echo htmlspecialchars($service['name']); ?> - ₱<?php echo number_format($service['price'], 2); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" name="quantities[]" class="form-control quantity-input" 
                                   value="1" min="1" required onchange="updateTotal()">
                        </div>
                        <div class="col-md-3">
                            <input type="number" name="prices[]" class="form-control price-input" 
                                   value="" step="0.01" required readonly>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-sm remove-service">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                    
                    // Add the row to the container
                    container.appendChild(row);
                    
                    // Now that the row is in the DOM, set its values
                    setTimeout(() => {
                        try {
                            // Find and set the service
                            const selectElement = row.querySelector('.service-select');
                            if (selectElement) {
                                console.log(`Setting service_id to ${service.service_id}`);
                                selectElement.value = service.service_id;
                            }
                            
                            // Set the quantity
                            const quantityInput = row.querySelector('.quantity-input');
                            if (quantityInput) {
                                console.log(`Setting quantity to ${service.quantity}`);
                                quantityInput.value = service.quantity || 1;
                            }
                            
                            // Set the price
                            const priceInput = row.querySelector('.price-input');
                            if (priceInput) {
                                console.log(`Setting price to ${service.price}`);
                                priceInput.value = service.price || 0;
                            }
                            
                            // Trigger update price based on select
                            updatePrice(selectElement);
                            
                            // Update the total
                            updateTotal();
                        } catch (e) {
                            console.error("Error setting service values:", e);
                        }
                    }, 0);
                });
                
                // Show success notification
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                
                Toast.fire({
                    icon: 'success',
                    title: `Loaded ${data.length} service(s) from the appointment`
                });
            })
            .catch(error => {
                console.error('Error loading services:', error);
                clearServices();
                
                // Show error notification
                Swal.fire({
                    title: 'Error',
                    text: `Failed to load services: ${error.message}`,
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            });
    }

    function clearServices() {
        console.log("Clearing services and adding empty row");
        const container = document.getElementById('servicesContainer');
        
        // Clear existing content
        container.innerHTML = '';
        
        // Add a new empty row
        container.innerHTML = `
            <div class="row mb-2 service-row">
                <div class="col-md-5">
                    <select name="services[]" class="form-select service-select" required onchange="updatePrice(this)">
                        <option value="">Select Service</option>
                        <?php 
                        mysqli_data_seek($services, 0);
                        while($service = mysqli_fetch_assoc($services)): 
                        ?>
                            <option value="<?php echo $service['id']; ?>" data-price="<?php echo $service['price']; ?>">
                                <?php echo htmlspecialchars($service['name']); ?> - ₱<?php echo number_format($service['price'], 2); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" name="quantities[]" class="form-control quantity-input" value="1" min="1" required onchange="updateTotal()">
                </div>
                <div class="col-md-3">
                    <input type="number" name="prices[]" class="form-control price-input" step="0.01" required readonly>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm remove-service">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        updateTotal();
    }

    function addServiceWithValues(serviceData) {
        const container = document.getElementById('servicesContainer');
        const newRow = container.querySelector('.service-row').cloneNode(true);
        
        // Set service
        const serviceSelect = newRow.querySelector('.service-select');
        serviceSelect.value = serviceData.service_id;
        
        // Set quantity
        const quantityInput = newRow.querySelector('.quantity-input');
        quantityInput.value = serviceData.quantity || 1;
        
        // Set price
        const priceInput = newRow.querySelector('.price-input');
        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
        priceInput.value = selectedOption.dataset.price;
        
        container.appendChild(newRow);
    }

    function addService() {
        const container = document.getElementById('servicesContainer');
        const newRow = container.querySelector('.service-row').cloneNode(true);
        
        // Reset values
        newRow.querySelector('.service-select').value = '';
        newRow.querySelector('.quantity-input').value = 1;
        newRow.querySelector('.price-input').value = '';
        
        container.appendChild(newRow);
    }

    function updatePrice(select) {
        const row = select.closest('.service-row');
        const priceInput = row.querySelector('.price-input');
        const option = select.options[select.selectedIndex];
        if(option.value) {
            priceInput.value = option.dataset.price;
            updateTotal();
        } else {
            priceInput.value = '';
        }
    }

    function updateTotal() {
        let total = 0;
        document.querySelectorAll('.service-row').forEach(row => {
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            total += quantity * price;
        });
        document.getElementById('total_amount').value = total.toFixed(2);
    }

    // Load appointments if in edit mode and patient is selected
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize total amount
        updateTotal();
        
        // Debug selected patient
        const patientSelect = document.querySelector('select[name="patient_id"]');
        console.log("Selected patient on load:", patientSelect.value);
        
        // Load appointments if in edit mode and patient is selected
        if(patientSelect.value) {
            console.log("Loading appointments for patient:", patientSelect.value);
            loadAppointments(patientSelect.value);
            
            <?php if(isset($invoice['appointment_id'])): ?>
            // If we're in edit mode and have an appointment ID, select it after appointments are loaded
            const appointmentId = <?php echo $invoice['appointment_id']; ?>;
            console.log("Will select appointment ID:", appointmentId);
            
            // Wait for appointments to load then select the right one
            setTimeout(function() {
                const appointmentSelect = document.querySelector('select[name="appointment_id"]');
                appointmentSelect.value = appointmentId;
                
                // If appointment services exist, load them
                if(appointmentId) {
                    loadAppointmentServices(appointmentId);
                }
            }, 1000); // Wait for the AJAX request to complete
            <?php endif; ?>
        }

        // Add appointment selection listener
        document.querySelector('select[name="appointment_id"]').addEventListener('change', function() {
            if(this.value) {
                loadAppointmentServices(this.value);
            } else {
                clearServices();
            }
        });

        // Add service removal listener
        document.addEventListener('click', function(e) {
            if(e.target.closest('.remove-service')) {
                const row = e.target.closest('.service-row');
                if(document.querySelectorAll('.service-row').length > 1) {
                    row.remove();
                    updateTotal();
                }
            }
        });
        
        // Pre-select service option values in edit mode
        <?php if($edit_mode && !empty($invoice_items)): ?>
        setTimeout(function() {
            const serviceRows = document.querySelectorAll('.service-row');
            serviceRows.forEach((row, index) => {
                const select = row.querySelector('.service-select');
                const quantity = row.querySelector('.quantity-input');
                const price = row.querySelector('.price-input');
                
                <?php foreach($invoice_items as $index => $item): ?>
                if(index === <?php echo $index; ?>) {
                    select.value = '<?php echo $item['service_id']; ?>';
                    quantity.value = '<?php echo $item['quantity']; ?>';
                    price.value = '<?php echo $item['unit_price']; ?>';
                }
                <?php endforeach; ?>
                
                // Update total
                updateTotal();
            });
        }, 500);
        <?php endif; ?>
    });
</script>
</body>
</html> 