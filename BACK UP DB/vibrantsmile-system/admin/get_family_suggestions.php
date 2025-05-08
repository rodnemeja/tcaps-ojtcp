<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get query parameter
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$suggestions = [];

if (!empty($query)) {
    // Prepare wildcard search
    $search_term = "%" . $query . "%";
    
    // Prepare query to search for families by name or code
    $sql = "SELECT id, code, name, 
            (SELECT COUNT(*) FROM patients WHERE family_code = fc.code) as member_count 
            FROM family_codes fc 
            WHERE fc.name LIKE ? OR fc.code LIKE ? 
            ORDER BY 
                CASE 
                    WHEN fc.name LIKE ? THEN 1 
                    WHEN fc.name LIKE ? THEN 2 
                    ELSE 3 
                END, 
                member_count DESC 
            LIMIT 10";
            
    if($stmt = mysqli_prepare($conn, $sql)) {
        // Exact match at start gets priority, then contains
        $exact_start = $query . "%";
        mysqli_stmt_bind_param($stmt, "ssss", $search_term, $search_term, $exact_start, $search_term);
        
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)) {
                $suggestions[] = [
                    'id' => $row['id'],
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'member_count' => $row['member_count']
                ];
            }
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($suggestions);
exit;
?> 