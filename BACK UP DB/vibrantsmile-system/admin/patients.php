<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Handle patient activation/deactivation
if(isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    
    // First check if user exists and is patient
    $sql = "SELECT u.active FROM users u JOIN patients p ON u.id = p.user_id WHERE p.id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)) {
            // Toggle the active status
            $new_status = $row['active'] ? 0 : 1;
            $sql = "UPDATE users u JOIN patients p ON u.id = p.user_id SET u.active = ? WHERE p.id = ?";
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ii", $new_status, $id);
                if(mysqli_stmt_execute($stmt)) {
                    $_SESSION['success_message'] = "Patient status updated successfully!";
                }
            }
        }
    }
    header("location: patients.php");
    exit;
}

// Handle patient edit action
if(isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    
    // Validate input
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $first_name = trim($_POST["first_name"]);
    $middle_name = trim($_POST["middle_name"]);
    $last_name = trim($_POST["last_name"]);
    $phone = trim($_POST["phone"]);
    $date_of_birth = trim($_POST["date_of_birth"]);
    $gender = trim($_POST["gender"]);
    $address = trim($_POST["address"]);
    $region = trim($_POST["region"]);
    $city = trim($_POST["city"]);
    $barangay = trim($_POST["barangay"]);
    $zipcode = trim($_POST["zipcode"]);
    
    $error = "";
    
    // Validate username
    if(empty($username)) {
        $error = "Please enter a username.";
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = "Username can only contain letters, numbers, and underscores.";
    } else {
        // Check if username exists for other users
        $sql = "SELECT u.id FROM users u JOIN patients p ON u.id = p.user_id WHERE u.username = ? AND p.id != ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $username, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) > 0) {
                $error = "This username is already taken.";
            }
        }
    }
    
    // Validate email
    if(empty($email)) {
        $error = "Please enter an email.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists for other users
        $sql = "SELECT u.id FROM users u JOIN patients p ON u.id = p.user_id WHERE u.email = ? AND p.id != ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $email, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) > 0) {
                $error = "This email is already registered.";
            }
        }
    }
    
    // Validate other fields
    if(empty($first_name)) {
        $error = "Please enter first name.";
    }
    if(empty($last_name)) {
        $error = "Please enter last name.";
    }
    if(empty($phone)) {
        $error = "Please enter phone number.";
    }
    if(empty($date_of_birth)) {
        $error = "Please enter date of birth.";
    }
    if(empty($gender)) {
        $error = "Please select gender.";
    }
    if(empty($address)) {
        $error = "Please enter address.";
    }
    if(empty($region)) {
        $error = "Please select region.";
    }
    if(empty($city)) {
        $error = "Please select city.";
    }
    if(empty($barangay)) {
        $error = "Please select barangay.";
    }
    if(empty($zipcode)) {
        $error = "Please enter zipcode.";
    }
    
    // If no errors, proceed with update
    if(empty($error)) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Get user_id for the patient
            $sql = "SELECT user_id FROM patients WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if($row = mysqli_fetch_assoc($result)) {
                    $user_id = $row['user_id'];
                    
                    // Update user information
                    $sql = "UPDATE users SET username = ?, email = ?, first_name = ?, middle_name = ?, last_name = ?, phone = ? WHERE id = ?";
                    if($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "ssssssi", $username, $email, $first_name, $middle_name, $last_name, $phone, $user_id);
                        if(!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Error updating user information: " . mysqli_error($conn));
                        }
                    }
                    
                    // Calculate age from date of birth
                    $dob = new DateTime($date_of_birth);
                    $today = new DateTime();
                    $age = $dob->diff($today)->y;
                    
                    // Update patient information
                    $sql = "UPDATE patients SET date_of_birth = ?, age = ?, gender = ?, address = ?, region = ?, city = ?, barangay = ?, zipcode = ? WHERE id = ?";
                    if($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "sissssssi", $date_of_birth, $age, $gender, $address, $region, $city, $barangay, $zipcode, $id);
                        if(!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Error updating patient information: " . mysqli_error($conn));
                        }
                    }
                    
                    // Commit transaction
                    mysqli_commit($conn);
                    $_SESSION['success_message'] = "Patient information updated successfully.";
                }
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $_SESSION['error_message'] = $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = $error;
    }
    
    header("location: patients.php");
    exit;
}

// Get total number of patients
$count_sql = "SELECT COUNT(*) as total FROM patients p 
              JOIN users u ON p.user_id = u.id 
              WHERE u.role = 'patient'";
$count_result = mysqli_query($conn, $count_sql);
$total_patients = mysqli_fetch_assoc($count_result)['total'];

// Pagination settings
$patients_per_page = 10;
$total_pages = ceil($total_patients / $patients_per_page);
$current_page = isset($_GET['page']) ? max(1, min($total_pages, intval($_GET['page']))) : 1;
$offset = ($current_page - 1) * $patients_per_page;

// Get paginated patients
$sql = "SELECT p.id, p.user_id, p.date_of_birth, p.gender, p.address, p.is_minor,
        u.email, u.phone, u.first_name, u.middle_name, u.last_name, u.active, u.username, u.profile_picture 
        FROM patients p 
        JOIN users u ON p.user_id = u.id 
        WHERE u.role = 'patient'
        ORDER BY u.last_name ASC, u.first_name ASC
        LIMIT ? OFFSET ?";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $patients_per_page, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    die("Error preparing statement: " . mysqli_error($conn));
}

// Add debug information
if(mysqli_num_rows($result) === 0) {
    error_log("No patients found in the database");
}

$page_title = "Patients";
$current_page = "patients";
require_once "includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Patients</h2>
    <a href="add_patient.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add New Patient
    </a>
</div>

<?php if(isset($_SESSION['success_message'])): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: '<?php echo $_SESSION['success_message']; ?>',
        confirmButtonColor: '#4e73df',
        timer: 2000,
        timerProgressBar: true
    });
</script>
<?php 
unset($_SESSION['success_message']);
endif; 
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Manage Patients</h6>
        <div class="search-box">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search text-primary"></i>
                </span>
                <input type="text" id="searchInput" class="form-control border-start-0 ps-0" 
                       placeholder="Search patients..." style="max-width: 250px;">
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="patientsTable">
                <thead>
                    <tr>
                        <th>Profile</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Date of Birth</th>
                        <th>Gender</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(mysqli_num_rows($result) > 0) {
                        while($patient = mysqli_fetch_assoc($result)): 
                            // Set default profile picture if none exists
                            $profile_pic = $patient['profile_picture'] ? '../' . $patient['profile_picture'] : '../assets/img/default-profile.png';
                    ?>
                        <tr class="searchable-row <?php echo !$patient['active'] ? 'table-secondary' : ''; ?>">
                            <td>
                                <img src="<?php echo $profile_pic; ?>" alt="Profile" class="patient-profile-img rounded-circle">
                            </td>
                            <td class="searchable">
                                <?php 
                                $patient_name = $patient['last_name'] . ', ' . $patient['first_name'];
                                if (!empty($patient['middle_name'])) {
                                    $patient_name .= ' ' . $patient['middle_name'];
                                }
                                echo htmlspecialchars($patient_name); 
                                
                                // Show minor indicator
                                if(isset($patient['is_minor']) && $patient['is_minor'] == 1): 
                                ?>
                                <span class="badge bg-warning text-dark ms-2" data-bs-toggle="tooltip" title="Minor patient (under 18)">
                                    <i class="fas fa-child"></i> Minor
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="searchable"><?php echo htmlspecialchars($patient['username']); ?></td>
                            <td class="searchable">
                                <?php echo htmlspecialchars($patient['phone']); ?><br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($patient['address']); ?>
                                </small>
                            </td>
                            <td class="searchable"><?php echo htmlspecialchars($patient['email']); ?></td>
                            <td class="searchable"><?php echo date('M d, Y', strtotime($patient['date_of_birth'])); ?></td>
                            <td class="searchable"><?php echo ucfirst($patient['gender']); ?></td>
                            <td class="searchable">
                                <span class="badge bg-<?php echo $patient['active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $patient['active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="view_patient.php?id=<?php echo $patient['id']; ?>" 
                                   class="btn btn-sm btn-info" 
                                   data-bs-toggle="tooltip" 
                                   title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit_patient.php?id=<?php echo $patient['id']; ?>" 
                                   class="btn btn-sm btn-primary" 
                                   data-bs-toggle="tooltip" 
                                   title="Edit Patient">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <!-- <button onclick="toggleStatus(<?php echo $patient['id']; ?>, <?php echo $patient['active']; ?>)" 
                                        class="btn btn-sm btn-<?php echo $patient['active'] ? 'danger' : 'success'; ?>"
                                        data-bs-toggle="tooltip" 
                                        title="<?php echo $patient['active'] ? 'Deactivate' : 'Activate'; ?> Patient">
                                    <i class="fas fa-<?php echo $patient['active'] ? 'ban' : 'check'; ?>"></i>
                                </button> -->
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    }
                    ?>
                    <tr id="noResultsRow" style="display: none;">
                        <td colspan="7" class="text-center py-4">No patients found matching your search</td>
                    </tr>
                    <?php if(mysqli_num_rows($result) === 0): ?>
                    <tr id="noPatientsRow">
                        <td colspan="7" class="text-center py-4">No patients found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
        <div class="d-flex justify-content-center mt-4">
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <!-- Previous page link -->
                    <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                            <span class="sr-only">Previous</span>
                        </a>
                    </li>
                    
                    <!-- Page numbers -->
                    <?php 
                    // Display a limited number of pages
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    // Always show first page
                    if($start_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1">1</a>
                        </li>
                        <?php if($start_page > 2): ?>
                            <li class="page-item disabled"><a class="page-link" href="#">...</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Page links -->
                    <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Always show last page -->
                    <?php if($end_page < $total_pages): ?>
                        <?php if($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled"><a class="page-link" href="#">...</a></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Next page link -->
                    <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                            <span class="sr-only">Next</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
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

/* Pagination Styles */
.pagination {
    margin-bottom: 2rem;
}

.pagination .page-item .page-link {
    color: #4e73df;
    border-color: #e3e6f0;
    padding: 0.5rem 0.75rem;
    min-width: 38px;
    text-align: center;
}

.pagination .page-item.active .page-link {
    background-color: #4e73df;
    border-color: #4e73df;
    color: white;
}

.pagination .page-item.disabled .page-link {
    color: #858796;
}

.pagination .page-item:first-child .page-link {
    border-top-left-radius: 0.35rem;
    border-bottom-left-radius: 0.35rem;
}

.pagination .page-item:last-child .page-link {
    border-top-right-radius: 0.35rem;
    border-bottom-right-radius: 0.35rem;
}

.pagination .page-link:hover {
    background-color: #eaecf4;
    color: #224abe;
}

.pagination .page-link:focus {
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

/* Action Buttons */
.action-buttons .btn {
    margin: 0 2px;
}

/* Table Styles */
.table > :not(caption) > * > * {
    padding: 1rem 0.75rem;
}

.table > tbody > tr:hover {
    background-color: #f8f9fc;
}

/* Patient Profile Image */
.patient-profile-img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border: 1px solid #e3e6f0;
    box-shadow: 0 0 5px rgba(0,0,0,0.1);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Real-time search functionality
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('patientsTable');
        const rows = table.getElementsByClassName('searchable-row');
        const noResultsRow = document.getElementById('noResultsRow');
        const noPatientsRow = document.getElementById('noPatientsRow');

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

            // Show/hide no results message
            if (rows.length > 0) {
                noResultsRow.style.display = hasResults ? 'none' : '';
                if (noPatientsRow) {
                    noPatientsRow.style.display = 'none';
                }
            }
        });

        // Add clear search functionality
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                this.dispatchEvent(new Event('input'));
            }
        });
    });

    function toggleStatus(id, currentStatus) {
        const action = currentStatus ? 'deactivate' : 'activate';
        Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to ${action} this patient?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4e73df',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, proceed!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `patients.php?toggle_status=${id}`;
            }
        });
    }
</script>

<script>
// Add search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('patientsTable');
    const rows = table.getElementsByTagName('tr');
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        
        for (let i = 1; i < rows.length; i++) { // Start from 1 to skip header row
            const row = rows[i];
            const cells = row.getElementsByTagName('td');
            let found = false;
            
            for (let j = 0; j < cells.length; j++) {
                const cellText = cells[j].textContent.toLowerCase();
                if (cellText.includes(searchTerm)) {
                    found = true;
                    break;
                }
            }
            
            row.style.display = found ? '' : 'none';
        }
        
        // Update pagination links
        updatePaginationLinks();
    }
    
    searchInput.addEventListener('input', filterTable);
    
    // Preserve search in pagination links
    function updatePaginationLinks() {
        const searchValue = searchInput.value;
        const paginationLinks = document.querySelectorAll('.pagination .page-link');
        
        if (paginationLinks.length > 0) {
            paginationLinks.forEach(link => {
                let url = new URL(link.href);
                
                // Don't modify disabled links with href="#"
                if (url.hash === '#') return;
                
                if (searchValue) {
                    url.searchParams.set('search', searchValue);
                } else {
                    url.searchParams.delete('search');
                }
                
                link.href = url.toString();
            });
        }
    }
    
    // Update pagination links when search changes
    searchInput.addEventListener('input', updatePaginationLinks);
    
    // Set search value from URL parameter on page load
    function setSearchFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('search');
        
        if (searchParam) {
            searchInput.value = searchParam;
            filterTable();
        }
    }
    
    // Initialize search from URL
    setSearchFromURL();
});
</script>

<?php require_once "includes/footer.php"; ?> 