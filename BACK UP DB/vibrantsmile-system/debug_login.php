<?php
require_once "config/database.php";

echo "<h2>Login System Debug</h2>";

// Debug 1: Database Connection
echo "<h3>Debug 1: Database Connection</h3>";
if($conn) {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    echo "<p>Connected to: " . DB_SERVER . "</p>";
    echo "<p>Database: " . DB_NAME . "</p>";
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
    die("Connection failed: " . mysqli_connect_error());
}

// Debug 2: Check Users Table
echo "<h3>Debug 2: Users Table</h3>";
$sql = "SHOW TABLES LIKE 'users'";
$result = mysqli_query($conn, $sql);
if(mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ Users table exists</p>";
    
    // Show table structure
    $sql = "DESCRIBE users";
    $result = mysqli_query($conn, $sql);
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>✗ Users table does not exist</p>";
}

// Debug 3: Check Admin User
echo "<h3>Debug 3: Admin User</h3>";
$sql = "SELECT * FROM users WHERE username = 'admin'";
$result = mysqli_query($conn, $sql);
if($row = mysqli_fetch_assoc($result)) {
    echo "<p style='color: green;'>✓ Admin user found</p>";
    echo "<pre>";
    print_r($row);
    echo "</pre>";
    
    // Test password verification
    $test_password = "password";
    $verify_result = password_verify($test_password, $row['password']);
    echo "<p>Password verification test: " . ($verify_result ? "✓ Success" : "✗ Failed") . "</p>";
    
    if(!$verify_result) {
        echo "<p>Attempting to fix password hash...</p>";
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE username = 'admin'";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $new_hash);
            if(mysqli_stmt_execute($stmt)) {
                echo "<p style='color: green;'>✓ Password hash updated</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to update password hash</p>";
            }
        }
    }
} else {
    echo "<p style='color: red;'>✗ Admin user not found</p>";
    echo "<p>Creating admin user...</p>";
    
    $sql = "INSERT INTO users (username, password, role, full_name, email, phone) 
            VALUES ('admin', ?, 'admin', 'Admin User', 'admin@example.com', '1234567890')";
    if($stmt = mysqli_prepare($conn, $sql)) {
        $hashed_password = password_hash("password", PASSWORD_DEFAULT);
        mysqli_stmt_bind_param($stmt, "s", $hashed_password);
        if(mysqli_stmt_execute($stmt)) {
            echo "<p style='color: green;'>✓ Admin user created</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create admin user: " . mysqli_error($conn) . "</p>";
        }
    }
}

// Debug 4: Test Login Process
echo "<h3>Debug 4: Login Process</h3>";
session_start();

// Simulate login attempt
$_POST['username'] = 'admin';
$_POST['password'] = 'password';

// Include index.php but capture output
ob_start();
require_once "index.php";
$output = ob_get_clean();

echo "<p>Login attempt output:</p>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Check session
echo "<p>Session status:</p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Debug 5: Check for PHP Errors
echo "<h3>Debug 5: PHP Errors</h3>";
$error_reporting = error_reporting();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test database queries
$sql = "SELECT * FROM users WHERE username = 'admin'";
$result = mysqli_query($conn, $sql);
if(!$result) {
    echo "<p style='color: red;'>✗ Query error: " . mysqli_error($conn) . "</p>";
}

echo "<h2>Debug Summary</h2>";
echo "<p>Please check the results above for any issues. Common problems include:</p>";
echo "<ul>";
echo "<li>Database connection issues</li>";
echo "<li>Missing or incorrect table structure</li>";
echo "<li>Incorrect password hash</li>";
echo "<li>Session handling problems</li>";
echo "<li>PHP errors or warnings</li>";
echo "</ul>";

// Restore error reporting
error_reporting($error_reporting);
?> 