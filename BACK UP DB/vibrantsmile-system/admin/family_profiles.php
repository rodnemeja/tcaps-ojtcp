<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Handle family code actions
if(isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    
    if($action === 'delete' && is_numeric($id)) {
        // Get the family code before deletion
        $code_sql = "SELECT code FROM family_codes WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $code_sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if($row = mysqli_fetch_assoc($result)) {
                $family_code = $row['code'];
                
                // Start transaction
                mysqli_begin_transaction($conn);
                
                try {
                    // Remove family code from all patients first
                    $update_sql = "UPDATE patients SET family_code = NULL, family_role = NULL WHERE family_code = ?";
                    if($stmt = mysqli_prepare($conn, $update_sql)) {
                        mysqli_stmt_bind_param($stmt, "s", $family_code);
                        if(!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Error updating patients: " . mysqli_error($conn));
                        }
                    }
                    
                    // Delete the family code
                    $delete_sql = "DELETE FROM family_codes WHERE id = ?";
                    if($stmt = mysqli_prepare($conn, $delete_sql)) {
                        mysqli_stmt_bind_param($stmt, "i", $id);
                        if(!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Error deleting family code: " . mysqli_error($conn));
                        }
                    }
                    
                    // Commit transaction
                    mysqli_commit($conn);
                    $_SESSION['success_message'] = "Family group successfully deleted";
                } catch (Exception $e) {
                    // Rollback transaction on error
                    mysqli_rollback($conn);
                    $_SESSION['error_message'] = $e->getMessage();
                }
            }
        }
        header("location: family_profiles.php");
        exit;
    }
}

// Get total number of family codes
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = "";
$params = [];
$types = "";

if (!empty($search_term)) {
    $search_term_like = "%" . $search_term . "%";
    $where_clause = "WHERE fc.name LIKE ? OR fc.code LIKE ?";
    $params[] = $search_term_like;
    $params[] = $search_term_like;
    $types .= "ss";
}

// Debug search functionality
// Uncomment to debug search issues
// $_SESSION['debug_info'] = [
//     'search_term' => $search_term,
//     'where_clause' => $where_clause,
// ];

// Count total number of families
$count_sql = "SELECT COUNT(*) as total FROM family_codes fc $where_clause";
$count_stmt = mysqli_prepare($conn, $count_sql);

if (!empty($search_term) && $count_stmt) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}

if ($count_stmt && mysqli_stmt_execute($count_stmt)) {
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total_families = mysqli_fetch_assoc($count_result)['total'];
} else {
    $_SESSION['error_message'] = "Error executing count query: " . mysqli_error($conn);
    $total_families = 0;
}

// Pagination settings
$families_per_page = 10;
$total_pages = $total_families > 0 ? ceil($total_families / $families_per_page) : 1;
$current_page = isset($_GET['page']) ? max(1, min($total_pages, intval($_GET['page']))) : 1;
$offset = ($current_page - 1) * $families_per_page;

// Add pagination params
$params[] = $families_per_page;
$params[] = $offset;
$types .= "ii";

// Get paginated family codes with member count and latest visit
$sql = "SELECT fc.id, fc.code, fc.name, fc.created_at,
        (SELECT COUNT(*) FROM patients WHERE family_code = fc.code) as member_count,
        (SELECT MAX(appointment_date) FROM appointments a 
         JOIN patients p ON a.patient_id = p.id 
         WHERE p.family_code = fc.code) as latest_visit,
        (SELECT CONCAT(u.first_name, ' ', u.last_name) 
         FROM patients p 
         JOIN users u ON p.user_id = u.id 
         WHERE p.id = fc.created_by) as created_by
        FROM family_codes fc
        $where_clause
        ORDER BY member_count DESC, latest_visit DESC
        LIMIT ? OFFSET ?";

// For real-time filtering, also get all families without pagination
$all_families = [];
if(empty($search_term)) {
    $all_sql = "SELECT fc.id, fc.code, fc.name, fc.created_at,
            (SELECT COUNT(*) FROM patients WHERE family_code = fc.code) as member_count,
            (SELECT MAX(appointment_date) FROM appointments a 
             JOIN patients p ON a.patient_id = p.id 
             WHERE p.family_code = fc.code) as latest_visit,
            (SELECT CONCAT(u.first_name, ' ', u.last_name) 
             FROM patients p 
             JOIN users u ON p.user_id = u.id 
             WHERE p.id = fc.created_by) as created_by
            FROM family_codes fc
            ORDER BY member_count DESC, latest_visit DESC";
            
    if($all_stmt = mysqli_prepare($conn, $all_sql)) {
        mysqli_stmt_execute($all_stmt);
        $all_result = mysqli_stmt_get_result($all_stmt);
        while($row = mysqli_fetch_assoc($all_result)) {
            $all_families[] = $row;
        }
    }
}

$families = [];
if($stmt = mysqli_prepare($conn, $sql)) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)) {
            $families[] = $row;
        }
    } else {
        $_SESSION['error_message'] = "Error executing search query: " . mysqli_error($conn);
    }
} else {
    $_SESSION['error_message'] = "Error preparing statement: " . mysqli_error($conn);
}

$page_title = "Family Profiles";
$current_page = "family_profiles";
require_once "includes/header.php";
?>


<?php if(isset($_SESSION['success_message'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['success_message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php unset($_SESSION['success_message']); endif; ?>

<?php if(isset($_SESSION['error_message'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['error_message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php unset($_SESSION['error_message']); endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Manage Family Profiles</h6>
        <div class="d-flex">
            <a href="add_family.php" class="btn btn-primary me-3">
                <i class="fas fa-plus me-1"></i>Add New Family
            </a>
            <div class="search-box">
                <form method="GET" action="family_profiles.php" class="input-group">
                    <input type="text" class="form-control" name="search" id="searchInput" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Search by name or code..." autocomplete="off">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <div id="searchSuggestions" class="search-suggestions"></div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="familyTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Family Name</th>
                        <th>Family Code</th>
                        <th>Members</th>
                        <th>Created By</th>
                        <th>Created Date</th>
                        <th>Latest Visit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($families)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No family profiles found</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($families as $family): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($family['name']); ?></td>
                            <td><?php echo htmlspecialchars($family['code']); ?></td>
                            <td><?php echo $family['member_count']; ?></td>
                            <td><?php echo htmlspecialchars($family['created_by'] ?? 'Unknown'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($family['created_at'])); ?></td>
                            <td>
                                <?php if($family['latest_visit']): ?>
                                    <?php echo date('M d, Y', strtotime($family['latest_visit'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">No visits</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="view_family.php?code=<?php echo $family['code']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_family.php?id=<?php echo $family['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $family['id']; ?>)" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mt-4">
                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this family group? This will remove all family connections for its members.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    document.getElementById('confirmDeleteBtn').href = 'family_profiles.php?action=delete&id=' + id;
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

// Real-time search with suggestions
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchSuggestions = document.getElementById('searchSuggestions');
    const tableBody = document.querySelector('#familyTable tbody');
    const paginationNav = document.querySelector('.pagination')?.closest('nav');
    let typingTimer;
    const doneTypingInterval = 300; // Time in ms after user stops typing
    
    // Store all family data for client-side filtering
    const allFamilies = <?php echo empty($all_families) ? '[]' : json_encode($all_families); ?>;
    let isClientSideFiltering = false;

    // Add styles for suggestions
    const style = document.createElement('style');
    style.textContent = `
        .search-box {
            position: relative;
        }
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1000;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 4px 4px;
            max-height: 200px;
            overflow-y: auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            display: none;
        }
        .suggestion-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        }
        .suggestion-item:hover, .suggestion-item.active {
            background-color: #f8f9fa;
        }
        .suggestion-item.active {
            border-left: 3px solid #0d6efd;
        }
        .suggestion-item:last-child {
            border-bottom: none;
        }
        .highlight {
            font-weight: bold;
            color: #0d6efd;
        }
    `;
    document.head.appendChild(style);

    // Handle keyup event on search input
    searchInput.addEventListener('keyup', function(e) {
        // Don't trigger on navigation keys
        if (e.key === 'ArrowUp' || e.key === 'ArrowDown' || e.key === 'Enter') {
            return;
        }
        
        clearTimeout(typingTimer);
        if (searchInput.value) {
            typingTimer = setTimeout(() => {
                fetchSuggestions();
                filterTableRows(searchInput.value.trim());
            }, doneTypingInterval);
        } else {
            searchSuggestions.style.display = 'none';
            // Redirect to base URL when field is cleared only if we're not doing client-side filtering
            if ((e.key === 'Backspace' || e.key === 'Delete') && !isClientSideFiltering) {
                window.location.href = 'family_profiles.php';
            } else if (isClientSideFiltering) {
                // Reset the table to show all rows
                filterTableRows('');
            }
        }
    });
    
    // Handle all input changes (including paste operations)
    searchInput.addEventListener('input', function(e) {
        // Skip if triggered by keyup event
        if (e.inputType && e.inputType.includes('key')) {
            return;
        }
        
        clearTimeout(typingTimer);
        if (searchInput.value) {
            typingTimer = setTimeout(() => {
                fetchSuggestions();
                filterTableRows(searchInput.value.trim());
            }, doneTypingInterval);
        } else {
            searchSuggestions.style.display = 'none';
            // Redirect to base URL when field is cleared only if we're not doing client-side filtering
            if (!isClientSideFiltering) {
                window.location.href = 'family_profiles.php';
            } else {
                // Reset the table to show all rows
                filterTableRows('');
            }
        }
    });

    // Handle keyboard navigation
    let activeIndex = -1;
    const handleKeydown = function(e) {
        const items = searchSuggestions.querySelectorAll('.suggestion-item:not(:last-child)');
        if (!items.length || searchSuggestions.style.display === 'none') return;
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
            updateActiveItem(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, -1);
            updateActiveItem(items);
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            items[activeIndex].click();
        } else if (e.key === 'Escape') {
            searchSuggestions.style.display = 'none';
        }
    };
    
    searchInput.addEventListener('keydown', handleKeydown);

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target !== searchInput && !searchSuggestions.contains(e.target)) {
            searchSuggestions.style.display = 'none';
        }
    });

    // Client-side table filtering function
    function filterTableRows(query) {
        // If we have all families data available, use client-side filtering
        if (allFamilies.length > 0) {
            isClientSideFiltering = true;
            
            // If pagination is visible, hide it during client-side filtering
            if (paginationNav) {
                paginationNav.style.display = query ? 'none' : '';
            }
            
            if (!query) {
                // No query - display the current server-side filtered results
                return;
            }
            
            // Clear the table body
            tableBody.innerHTML = '';
            
            // Filter the data
            const filterRegex = new RegExp(query.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&'), 'i');
            const filteredFamilies = allFamilies.filter(family => 
                filterRegex.test(family.name) || filterRegex.test(family.code)
            );
            
            if (filteredFamilies.length === 0) {
                // No matches found
                const noResultsRow = document.createElement('tr');
                noResultsRow.innerHTML = '<td colspan="7" class="text-center">No family profiles found</td>';
                tableBody.appendChild(noResultsRow);
            } else {
                // Create rows for filtered data
                filteredFamilies.forEach(family => {
                    const row = document.createElement('tr');
                    
                    // Format the date
                    const createdDate = new Date(family.created_at);
                    const formattedCreatedDate = createdDate.toLocaleDateString('en-US', {
                        month: 'short', day: 'numeric', year: 'numeric'
                    });
                    
                    let latestVisitText = '<span class="text-muted">No visits</span>';
                    if (family.latest_visit) {
                        const visitDate = new Date(family.latest_visit);
                        latestVisitText = visitDate.toLocaleDateString('en-US', {
                            month: 'short', day: 'numeric', year: 'numeric'
                        });
                    }
                    
                    // Highlight the matching text
                    const highlightedName = highlightMatch(family.name, query);
                    const highlightedCode = highlightMatch(family.code, query);
                    
                    row.innerHTML = `
                        <td>${highlightedName}</td>
                        <td>${highlightedCode}</td>
                        <td>${family.member_count}</td>
                        <td>${family.created_by || 'Unknown'}</td>
                        <td>${formattedCreatedDate}</td>
                        <td>${latestVisitText}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="view_family.php?code=${family.code}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit_family.php?id=${family.id}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="javascript:void(0);" onclick="confirmDelete(${family.id})" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
            }
        }
    }

    // Fetch suggestions from server
    function fetchSuggestions() {
        const query = searchInput.value.trim();
        if (!query) {
            searchSuggestions.style.display = 'none';
            return;
        }

        fetch(`get_family_suggestions.php?query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    displaySuggestions(data, query);
                } else {
                    searchSuggestions.innerHTML = '<div class="suggestion-item">No matching families found</div>';
                    searchSuggestions.style.display = 'block';
                }
            })
            .catch(error => console.error('Error fetching suggestions:', error));
    }

    // Display suggestions in dropdown
    function displaySuggestions(suggestions, query) {
        searchSuggestions.innerHTML = '';
        
        suggestions.forEach(family => {
            const item = document.createElement('div');
            item.className = 'suggestion-item';
            
            // Highlight matching text
            const nameHighlighted = highlightMatch(family.name, query);
            const codeHighlighted = highlightMatch(family.code, query);
            
            item.innerHTML = `
                <div class="d-flex justify-content-between">
                    <div><strong>${nameHighlighted}</strong></div>
                    <div><span class="badge bg-primary">${family.member_count} member${family.member_count !== 1 ? 's' : ''}</span></div>
                </div>
                <small class="text-muted">Code: ${codeHighlighted}</small>
            `;
            
            item.addEventListener('click', function() {
                searchInput.value = family.name;
                searchSuggestions.style.display = 'none';
                // Auto-submit the form
                searchInput.form.submit();
            });
            
            searchSuggestions.appendChild(item);
        });
        
        // Add a footer with hint
        const footer = document.createElement('div');
        footer.className = 'suggestion-item text-center text-muted';
        footer.style.fontSize = '0.8rem';
        footer.style.backgroundColor = '#f8f9fa';
        footer.innerHTML = 'Click a suggestion or press Enter to search';
        searchSuggestions.appendChild(footer);
        
        searchSuggestions.style.display = 'block';
    }

    // Highlight matching part of text
    function highlightMatch(text, query) {
        if (!text) return '';
        const regex = new RegExp('(' + query.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ')', 'gi');
        return text.replace(regex, '<span class="highlight">$1</span>');
    }

    function updateActiveItem(items) {
        items.forEach(item => item.classList.remove('active'));
        if (activeIndex >= 0) {
            items[activeIndex].classList.add('active');
            items[activeIndex].scrollIntoView({ block: 'nearest' });
        }
    }
});
</script>

<?php require_once "includes/footer.php"; ?> 