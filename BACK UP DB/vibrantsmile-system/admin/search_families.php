<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Check if search term is provided
if(!isset($_GET['term']) || empty($_GET['term'])){
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$search_term = trim($_GET['term']);

// Search for families by code, name, or member name
$results = [];

// First search by family code
$sql = "SELECT 
            fc.id, 
            fc.code, 
            fc.name, 
            fc.created_at,
            COUNT(p.id) as member_count
        FROM family_codes fc
        LEFT JOIN patients p ON fc.code = p.family_code
        WHERE fc.code LIKE ?
        GROUP BY fc.id
        ORDER BY fc.name
        LIMIT 10";

if($stmt = mysqli_prepare($conn, $sql)){
    $search_param = "%{$search_term}%";
    mysqli_stmt_bind_param($stmt, "s", $search_param);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while($row = mysqli_fetch_assoc($result)){
        $results[] = [
            'id' => $row['id'],
            'code' => $row['code'],
            'name' => $row['name'],
            'created_at' => date('M d, Y', strtotime($row['created_at'])),
            'member_count' => $row['member_count']
        ];
    }
}

// Then search by family name
if(count($results) < 10){
    $sql = "SELECT 
                fc.id, 
                fc.code, 
                fc.name, 
                fc.created_at,
                COUNT(p.id) as member_count
            FROM family_codes fc
            LEFT JOIN patients p ON fc.code = p.family_code
            WHERE fc.name LIKE ?
            GROUP BY fc.id
            ORDER BY fc.name
            LIMIT 10";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        $search_param = "%{$search_term}%";
        mysqli_stmt_bind_param($stmt, "s", $search_param);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)){
            // Check if this result is already in the array
            $exists = false;
            foreach($results as $existing){
                if($existing['id'] == $row['id']){
                    $exists = true;
                    break;
                }
            }
            
            if(!$exists){
                $results[] = [
                    'id' => $row['id'],
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'created_at' => date('M d, Y', strtotime($row['created_at'])),
                    'member_count' => $row['member_count']
                ];
            }
            
            if(count($results) >= 10){
                break;
            }
        }
    }
}

// Finally search by member name
if(count($results) < 10){
    $sql = "SELECT 
                fc.id, 
                fc.code, 
                fc.name, 
                fc.created_at,
                COUNT(p2.id) as member_count,
                GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') as member_names
            FROM family_codes fc
            JOIN patients p ON fc.code = p.family_code
            JOIN users u ON p.user_id = u.id
            LEFT JOIN patients p2 ON fc.code = p2.family_code
            WHERE CONCAT(u.first_name, ' ', u.last_name) LIKE ?
            GROUP BY fc.id
            ORDER BY fc.name
            LIMIT 10";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        $search_param = "%{$search_term}%";
        mysqli_stmt_bind_param($stmt, "s", $search_param);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)){
            // Check if this result is already in the array
            $exists = false;
            foreach($results as $existing){
                if($existing['id'] == $row['id']){
                    $exists = true;
                    break;
                }
            }
            
            if(!$exists){
                $results[] = [
                    'id' => $row['id'],
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'created_at' => date('M d, Y', strtotime($row['created_at'])),
                    'member_count' => $row['member_count'],
                    'member_names' => $row['member_names']
                ];
            }
            
            if(count($results) >= 10){
                break;
            }
        }
    }
}

// Return results as JSON
header('Content-Type: application/json');
echo json_encode($results); 