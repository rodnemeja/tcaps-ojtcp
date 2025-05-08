<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Handle staff activation/deactivation
if(isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    
    // First check if user exists and is staff/doctor/admin
    $sql = "SELECT role, active FROM users WHERE id = ? AND role IN ('doctor', 'staff', 'admin')";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)) {
            // Toggle the active status
            $new_status = $row['active'] ? 0 : 1;
            $sql = "UPDATE users SET active = ? WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ii", $new_status, $id);
                if(mysqli_stmt_execute($stmt)) {
                    $_SESSION['success_message'] = "Staff member status updated successfully!";
                }
            }
        }
    }
    header("location: staff.php");
    exit;
}

// Get total number of staff members
$count_sql = "SELECT COUNT(*) as total FROM users WHERE role IN ('doctor', 'staff', 'admin')";
$count_result = mysqli_query($conn, $count_sql);
$total_staff = mysqli_fetch_assoc($count_result)['total'];

// Pagination settings
$staff_per_page = 10;
$total_pages = ceil($total_staff / $staff_per_page);
$current_page = isset($_GET['page']) ? max(1, min($total_pages, intval($_GET['page']))) : 1;
$offset = ($current_page - 1) * $staff_per_page;

// Get paginated staff members with their details
$sql = "SELECT u.id, u.username, u.email, u.first_name, u.middle_name, u.last_name, u.phone, u.role, u.active, u.profile_picture,
        d.specialization, d.license_number, d.status as doctor_status 
        FROM users u 
        LEFT JOIN doctors d ON u.id = d.user_id 
        WHERE u.role IN ('doctor', 'staff', 'admin') 
        ORDER BY u.role, u.first_name, u.last_name
        LIMIT ? OFFSET ?";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $staff_per_page, $offset);
    mysqli_stmt_execute($stmt);
    $staff = mysqli_stmt_get_result($stmt);
} else {
    die("Error preparing statement: " . mysqli_error($conn));
}

$page_title = "Staff Management";
$current_page = "staff";
require_once "includes/header.php";
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Staff Management</h2>
        <a href="staff_form.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add New Staff
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
            <h6 class="m-0 font-weight-bold text-primary">Manage Staff</h6>
            <div class="search-box">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-primary"></i>
                    </span>
                    <input type="text" id="searchInput" class="form-control border-start-0 ps-0" 
                           placeholder="Search staff..." style="max-width: 250px;">
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="staffTable">
                    <thead>
                        <tr>
                            <th>Profile</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($staff) > 0): ?>
                            <?php while($member = mysqli_fetch_assoc($staff)): ?>
                                <tr class="searchable-row <?php echo !$member['active'] ? 'table-secondary' : ''; ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-2">
                                                <?php if(!empty($member['profile_picture'])): ?>
                                                    <img src="../<?php echo htmlspecialchars($member['profile_picture']); ?>" 
                                                         alt="Profile" 
                                                         class="rounded-circle"
                                                         style="width: 40px; height: 40px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                                         style="width: 40px; height: 40px;">
                                                        <?php echo strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="searchable">
                                        <?php 
                                        $full_name = $member['first_name'];
                                        if (!empty($member['middle_name'])) {
                                            $full_name .= ' ' . $member['middle_name'];
                                        }
                                        $full_name .= ' ' . $member['last_name'];
                                        echo htmlspecialchars($full_name);
                                        ?>
                                    </td>
                                    <td class="searchable"><?php echo htmlspecialchars($member['username']); ?></td>
                                    <td class="searchable"><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td class="searchable"><?php echo htmlspecialchars($member['phone']); ?></td>
                                    <td class="searchable">
                                        <span class="badge bg-<?php 
                                            if($member['role'] === 'doctor') {
                                                echo 'primary';
                                            } elseif($member['role'] === 'admin') {
                                                echo 'info';
                                            } else {
                                                echo 'secondary';
                                            }
                                        ?>">
                                            <?php echo ucfirst($member['role']); ?>
                                        </span>
                                    </td>
                                    <td class="searchable">
                                        <span class="badge bg-<?php echo $member['active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $member['active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button onclick="viewStaffDetails(
                                                <?php echo $member['id']; ?>, 
                                                '<?php echo addslashes($full_name); ?>', 
                                                '<?php echo addslashes($member['username']); ?>', 
                                                '<?php echo addslashes($member['email']); ?>', 
                                                '<?php echo addslashes($member['phone']); ?>', 
                                                '<?php echo $member['role']; ?>', 
                                                <?php echo $member['active']; ?>, 
                                                '<?php echo addslashes($member['first_name']); ?>', 
                                                '<?php echo addslashes($member['middle_name']); ?>', 
                                                '<?php echo addslashes($member['last_name']); ?>',
                                                '<?php echo isset($member['specialization']) ? addslashes($member['specialization']) : ''; ?>',
                                                '<?php echo isset($member['license_number']) ? addslashes($member['license_number']) : ''; ?>',
                                                '<?php echo isset($member['doctor_status']) ? addslashes($member['doctor_status']) : ''; ?>',
                                                '<?php echo isset($member['profile_picture']) ? addslashes($member['profile_picture']) : ''; ?>'
                                            )" 
                                            class="btn btn-sm btn-info"
                                            data-bs-toggle="tooltip" 
                                            title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="staff_form.php?edit=<?php echo $member['id']; ?>" 
                                               class="btn btn-sm btn-primary"
                                               data-bs-toggle="tooltip" 
                                               title="Edit Staff">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if($member['role'] !== 'admin'): ?>
                                                <button onclick="toggleStatus(<?php echo $member['id']; ?>, <?php echo $member['active']; ?>)" 
                                                        class="btn btn-sm btn-<?php echo $member['active'] ? 'danger' : 'success'; ?>"
                                                        data-bs-toggle="tooltip" 
                                                        title="<?php echo $member['active'] ? 'Deactivate' : 'Activate'; ?> Staff">
                                                    <i class="fas fa-<?php echo $member['active'] ? 'ban' : 'check'; ?>"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                        <tr id="noResultsRow" style="display: none;">
                            <td colspan="8" class="text-center py-4">No staff members found matching your search</td>
                        </tr>
                        <?php if(mysqli_num_rows($staff) === 0): ?>
                        <tr id="noStaffRow">
                            <td colspan="8" class="text-center py-4">No staff members found</td>
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
</div>

<!-- Staff Details Modal -->
<div class="modal fade" id="staffDetailsModal" tabindex="-1" aria-labelledby="staffDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="staffDetailsModalLabel">Staff Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-4">
                        <div class="avatar-container mb-3">
                            <img id="staffAvatar" src="../assets/img/default-profile.png" alt="Profile Picture" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                        </div>
                        <h4 id="staffName" class="mb-1">Staff Name</h4>
                        <span id="staffRole" class="badge bg-primary mb-3">Role</span>
                        <div id="staffStatus" class="mb-1">
                            <span class="badge bg-success">Active</span>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="m-0 font-weight-bold">Personal Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted small">Username</p>
                                        <p id="staffUsername" class="mb-3 fw-bold">username</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted small">Email</p>
                                        <p id="staffEmail" class="mb-3 fw-bold">email@example.com</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted small">Phone</p>
                                        <p id="staffPhone" class="mb-3 fw-bold">09XXXXXXXXX</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted small">Full Name</p>
                                        <p id="staffFullName" class="mb-3 fw-bold">Full Name</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="doctorInfoCard" class="card" style="display: none;">
                            <div class="card-header bg-light">
                                <h6 class="m-0 font-weight-bold">Doctor Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted small">Specialization</p>
                                        <p id="doctorSpecialization" class="mb-3 fw-bold">-</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted small">License Number</p>
                                        <p id="doctorLicense" class="mb-3 fw-bold">-</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted small">Doctor Status</p>
                                        <p id="doctorStatus" class="mb-3 fw-bold">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a id="editStaffBtn" href="" class="btn btn-primary">
                    <i class="fas fa-edit me-1"></i> Edit Profile
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Add this modal for profile picture upload -->
<div class="modal fade" id="uploadProfilePictureModal" tabindex="-1" aria-labelledby="uploadProfilePictureModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadProfilePictureModalLabel">Upload Profile Picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="upload_profile_picture.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="upload_user_id">
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Choose Image</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*" required>
                        <div class="form-text">Maximum file size: 5MB. Allowed formats: JPG, PNG, GIF</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
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

/* Action Buttons */
.btn-group .btn {
    margin: 0 2px;
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
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Real-time search functionality
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('staffTable');
        const rows = table.getElementsByClassName('searchable-row');
        const noResultsRow = document.getElementById('noResultsRow');
        const noStaffRow = document.getElementById('noStaffRow');

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
                if (noStaffRow) {
                    noStaffRow.style.display = 'none';
                }
            }
            
            // Update pagination links
            updatePaginationLinks();
        });

        // Add clear search functionality
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                this.dispatchEvent(new Event('input'));
            }
        });
        
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
        
        // Set search value from URL parameter on page load
        function setSearchFromURL() {
            const urlParams = new URLSearchParams(window.location.search);
            const searchParam = urlParams.get('search');
            
            if (searchParam) {
                searchInput.value = searchParam;
                searchInput.dispatchEvent(new Event('input'));
            }
        }
        
        // Initialize search from URL
        setSearchFromURL();
    });

    function toggleStatus(id, currentStatus) {
        const action = currentStatus ? 'deactivate' : 'activate';
        Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to ${action} this staff member?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4e73df',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, proceed!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `staff.php?toggle_status=${id}`;
            }
        });
    }

    function viewStaffDetails(staffId, name, username, email, phone, role, active, first, middle, last, specialization, license, doctorStatus, profilePicture) {
        // Set basic info
        document.getElementById('staffName').textContent = name;
        document.getElementById('staffUsername').textContent = username;
        document.getElementById('staffEmail').textContent = email;
        document.getElementById('staffPhone').textContent = phone;
        document.getElementById('staffFullName').textContent = name;
        
        // Set profile picture
        const profilePictureElement = document.getElementById('staffAvatar');
        if (profilePicture) {
            profilePictureElement.src = '../' + profilePicture;
        } else {
            profilePictureElement.src = '../assets/img/default-profile.png';
        }
        
        // Set role badge
        const roleElement = document.getElementById('staffRole');
        roleElement.textContent = role.charAt(0).toUpperCase() + role.slice(1);
        
        if (role === 'doctor') {
            roleElement.className = 'badge bg-primary mb-3';
        } else if (role === 'admin') {
            roleElement.className = 'badge bg-info mb-3';
        } else {
            roleElement.className = 'badge bg-secondary mb-3';
        }
        
        // Set status badge
        const statusElement = document.getElementById('staffStatus');
        statusElement.innerHTML = active ? 
            '<span class="badge bg-success">Active</span>' : 
            '<span class="badge bg-danger">Inactive</span>';
        
        // Handle doctor-specific information
        const doctorInfoCard = document.getElementById('doctorInfoCard');
        if (role === 'doctor') {
            document.getElementById('doctorSpecialization').textContent = specialization || 'Not specified';
            document.getElementById('doctorLicense').textContent = license || 'Not specified';
            document.getElementById('doctorStatus').textContent = doctorStatus || 'Not specified';
            doctorInfoCard.style.display = 'block';
        } else {
            doctorInfoCard.style.display = 'none';
        }
        
        // Set edit button link
        document.getElementById('editStaffBtn').href = `staff_form.php?edit=${staffId}`;
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('staffDetailsModal'));
        modal.show();
    }

    function openUploadModal(userId) {
        document.getElementById('upload_user_id').value = userId;
        var modal = new bootstrap.Modal(document.getElementById('uploadProfilePictureModal'));
        modal.show();
    }
</script>

<?php require_once "includes/footer.php"; ?> 