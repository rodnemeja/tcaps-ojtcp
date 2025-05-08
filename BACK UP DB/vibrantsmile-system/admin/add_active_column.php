<?php
require_once "config/database.php";

// Add active column if it doesn't exist
$sql = "SHOW COLUMNS FROM users LIKE 'active'";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) == 0) {
    $sql = "ALTER TABLE users ADD COLUMN active TINYINT(1) DEFAULT 1";
    if(mysqli_query($conn, $sql)) {
        echo "Active column added successfully!";
    } else {
        echo "Error adding active column: " . mysqli_error($conn);
    }
} else {
    echo "Active column already exists.";
}
?> 