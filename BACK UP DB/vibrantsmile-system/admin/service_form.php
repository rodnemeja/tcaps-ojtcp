<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

$service = array();
$edit_mode = false;

// Get service data if in edit mode
if(isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $sql = "SELECT * FROM services WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)) {
            $service = $row;
        }
    }
}

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['cost']; // Changed from cost to price to match database
    $duration_value = $_POST['duration_value'];
    $duration_unit = $_POST['duration_unit'];

    // Calculate final duration in minutes for scheduling purposes
    if(strpos($duration_value, '-') !== false) {
        // If it's a range, store the maximum value to ensure enough time is allocated
        $parts = explode('-', $duration_value);
        $duration = intval($parts[1]); // Use the maximum value of the range
        if($duration_unit === 'hours') {
            $duration *= 60; // Convert hours to minutes
        }
    } else {
        $duration = intval($duration_value);
        if($duration_unit === 'hours') {
            $duration *= 60; // Convert hours to minutes
        }
    }

    // Store the duration format separately
    $duration_format = $duration_value . ' ' . $duration_unit;

    if($edit_mode) {
        // Update service
        $sql = "UPDATE services SET name = ?, description = ?, price = ?, duration = ?, duration_format = ? WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssdisd", $name, $description, $price, $duration, $duration_format, $_GET['edit']);
            if(mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "Service updated successfully!";
            } else {
                $_SESSION['error_message'] = "Error updating service: " . mysqli_error($conn);
            }
        }
    } else {
        // Insert new service
        $sql = "INSERT INTO services (name, description, price, duration, duration_format) VALUES (?, ?, ?, ?, ?)";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssdis", $name, $description, $price, $duration, $duration_format);
            if(mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "Service added successfully!";
            } else {
                $_SESSION['error_message'] = "Error adding service: " . mysqli_error($conn);
            }
        }
    }

    header("location: services.php");
    exit;
}

$page_title = $edit_mode ? 'Edit Service' : 'New Service';
$current_page = 'services';
require_once 'includes/header.php';
?>

<!-- Main Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><?php echo $edit_mode ? 'Edit Service' : 'New Service'; ?></h5>
                        <a href="services.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-2"></i>Back to Services
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Service Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($service['name']) ? htmlspecialchars($service['name']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cost" class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="cost" name="cost" value="<?php echo isset($service['price']) ? htmlspecialchars($service['price']) : ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required><?php echo isset($service['description']) ? htmlspecialchars($service['description']) : ''; ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="duration_value" class="form-label">Duration</label>
                                <input type="text" class="form-control" id="duration_value" name="duration_value" value="<?php echo isset($service['duration_format']) ? explode(' ', $service['duration_format'])[0] : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="duration_unit" class="form-label">Unit</label>
                                <select class="form-select" id="duration_unit" name="duration_unit" required>
                                    <option value="minutes" <?php echo isset($service['duration_format']) && strpos($service['duration_format'], 'minutes') !== false ? 'selected' : ''; ?>>Minutes</option>
                                    <option value="hours" <?php echo isset($service['duration_format']) && strpos($service['duration_format'], 'hours') !== false ? 'selected' : ''; ?>>Hours</option>
                                </select>
                            </div>
                        </div>
                        <div class="text-end">
                            <a href="services.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Update Service' : 'Add Service'; ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 