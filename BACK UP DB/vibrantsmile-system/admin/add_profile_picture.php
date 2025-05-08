<?php
require_once "config/database.php";

// Add profile_picture column if it doesn't exist
$sql = "SHOW COLUMNS FROM users LIKE 'profile_picture'";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) == 0) {
    $sql = "ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL";
    if(mysqli_query($conn, $sql)) {
        echo "Profile picture column added successfully!";
    } else {
        echo "Error adding profile picture column: " . mysqli_error($conn);
    }
} else {
    echo "Profile picture column already exists.";
}
?> 