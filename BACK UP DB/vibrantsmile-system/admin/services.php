<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Handle service deletion
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM services WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Service deleted successfully!";
        }
    }
    header("location: services.php");
    exit;
}

// Get search parameter
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get total number of services
$count_sql = "SELECT COUNT(*) as total FROM services";
if (!empty($search_term)) {
    $search_term_escaped = mysqli_real_escape_string($conn, $search_term);
    $count_sql .= " WHERE name LIKE '%{$search_term_escaped}%' OR description LIKE '%{$search_term_escaped}%'";
}
$count_result = mysqli_query($conn, $count_sql);
$total_services = mysqli_fetch_assoc($count_result)['total'];

// Pagination settings
$services_per_page = 12;
$total_pages = ceil($total_services / $services_per_page);
$current_page = isset($_GET['page']) ? max(1, min($total_pages, intval($_GET['page']))) : 1;
$offset = ($current_page - 1) * $services_per_page;

// Get paginated services
$sql = "SELECT * FROM services ";
if (!empty($search_term)) {
    $search_term_escaped = mysqli_real_escape_string($conn, $search_term);
    $sql .= "WHERE name LIKE '%{$search_term_escaped}%' OR description LIKE '%{$search_term_escaped}%' ";
}
$sql .= "ORDER BY name LIMIT ? OFFSET ?";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $services_per_page, $offset);
    mysqli_stmt_execute($stmt);
    $services = mysqli_stmt_get_result($stmt);
} else {
    // Fallback to original query if prepare fails
    $sql = "SELECT * FROM services ORDER BY name";
    $services = mysqli_query($conn, $sql);
}

$page_title = "Services Management";
$current_page = "services";
require_once "includes/header.php";
?>

    <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Services Management</h2>
                    <a href="service_form.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add New Service
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
            <h6 class="m-0 font-weight-bold text-primary">Manage Services</h6>
            <div class="search-box">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-primary"></i>
                    </span>
                    <input type="text" id="searchInput" class="form-control border-start-0 ps-0" 
                           placeholder="Search services..." style="max-width: 250px;">
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row" id="servicesContainer">
                    <?php if(mysqli_num_rows($services) > 0): ?>
                        <?php while($service = mysqli_fetch_assoc($services)): ?>
                        <div class="col-md-6 col-lg-4 mb-4 searchable-item">
                                <div class="card service-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title mb-0 searchable"><?php echo htmlspecialchars($service['name']); ?></h5>
                                            <div class="btn-group">
                                            <a href="service_form.php?edit=<?php echo $service['id']; ?>" 
                                               class="btn btn-sm btn-primary"
                                               data-bs-toggle="tooltip" 
                                               title="Edit Service">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <button onclick="deleteService(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['name']); ?>')" 
                                                    class="btn btn-sm btn-danger"
                                                    data-bs-toggle="tooltip" 
                                                    title="Delete Service">
                                                    <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <p class="card-text text-muted mb-2 searchable"><?php echo htmlspecialchars($service['description']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                            <span class="badge bg-info searchable">
                                                <?php
                                                if(!empty($service['duration_format'])) {
                                                    echo "Duration: " . htmlspecialchars($service['duration_format']);
                                                } else {
                                                    // Fallback to the old format
                                                    $duration = $service['duration'];
                                                    if ($duration >= 60) {
                                                        $hours = floor($duration / 60);
                                                        if ($duration % 60 == 0) {
                                                            echo "Duration: {$hours} hour" . ($hours > 1 ? "s" : "");
                                                        } else {
                                                            echo "Duration: {$hours}-" . ($hours + 1) . " hours";
                                                        }
                                                    } else {
                                                        echo "Duration: {$duration} minutes";
                                                    }
                                                }
                                                ?>
                                            </span>
                                            </div>
                                        <div class="text-primary fw-bold searchable">
                                                ₱<?php echo number_format($service['price'], 2); ?>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                <?php endif; ?>
                <div id="noResultsMessage" class="col-12" style="display: none;">
                    <div class="card">
                        <div class="card-body text-center">
                            <p class="text-muted mb-0">No services found matching your search.</p>
                        </div>
                    </div>
                </div>
                <?php if(mysqli_num_rows($services) === 0): ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center">
                                    <p class="text-muted mb-0">No services found. Click the "Add New Service" button to create one.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <div class="d-flex justify-content-center mt-4">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <!-- Previous page link -->
                            <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                    <span class="sr-only">Previous</span>
                                </a>
                            </li>
                            
                            <?php
                            // Calculate which pages to show (target is 10 pages)
                            if ($total_pages <= 10) {
                                // Less than or equal to 10 pages, show all
                                $start = 1;
                                $end = $total_pages;
                            } else {
                                // More than 10 pages, determine the range
                                $offset = floor(10 / 2);
                                
                                if ($current_page <= $offset) {
                                    // Near the beginning
                                    $start = 1;
                                    $end = 10;
                                } elseif ($current_page > $total_pages - $offset) {
                                    // Near the end
                                    $start = $total_pages - 9;
                                    $end = $total_pages;
                                } else {
                                    // In the middle
                                    $start = $current_page - $offset;
                                    $end = $current_page + 10 - $offset - 1;
                                }
                            }
                            
                            // Display page links
                            for ($i = $start; $i <= $end; $i++) {
                                echo '<li class="page-item ' . ($current_page == $i ? 'active' : '') . '">';
                                echo '<a class="page-link" href="?page=' . $i . (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . '">' . $i . '</a>';
                                echo '</li>';
                            }
                            ?>
                            
                            <!-- Next page link -->
                            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" aria-label="Next">
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

/* Service Card Styles */
.service-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #e3e6f0;
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.service-card .card-title {
    color: #2e59d9;
    font-weight: 600;
}

.service-card .btn-group {
    opacity: 0.8;
    transition: opacity 0.2s;
}

.service-card:hover .btn-group {
    opacity: 1;
}

/* Badge Styles */
.badge {
    padding: 0.5em 0.8em;
    font-weight: 500;
}

/* Button Group Styles */
.btn-group .btn {
    margin: 0 2px;
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
        const servicesContainer = document.getElementById('servicesContainer');
        const items = servicesContainer.getElementsByClassName('searchable-item');
        const noResultsMessage = document.getElementById('noResultsMessage');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            let hasResults = false;

            Array.from(items).forEach(item => {
                const searchableFields = item.getElementsByClassName('searchable');
                let itemText = '';
                
                Array.from(searchableFields).forEach(field => {
                    itemText += field.textContent.toLowerCase() + ' ';
                });

                if (itemText.includes(searchTerm)) {
                    item.style.display = '';
                    hasResults = true;
                } else {
                    item.style.display = 'none';
                }
            });

            // Show/hide no results message
            noResultsMessage.style.display = hasResults ? 'none' : '';
            
            // Update pagination links with search parameter
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

    function deleteService(id, name) {
        Swal.fire({
            title: 'Delete Service?',
            text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `services.php?delete=${id}`;
            }
        });
    }
</script>

<?php require_once "includes/footer.php"; ?> 