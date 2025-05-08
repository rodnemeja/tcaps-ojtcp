<?php
require_once "config/database.php";

// Update appointments table status field
$sql = "ALTER TABLE appointments MODIFY COLUMN status ENUM('pending', 'scheduled', 'approved', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'";

if(mysqli_query($conn, $sql)) {
    echo "Successfully updated appointments table status field";
} else {
    echo "Error updating appointments table: " . mysqli_error($conn);
}

mysqli_close($conn);
?> 