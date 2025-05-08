<?php
require_once "config/database.php";

echo "<h2>Running database fixes for family tables...</h2>";

// SQL to fix family tables
$sql = "
-- Check if family_codes table exists
CREATE TABLE IF NOT EXISTS `family_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Check if family_code column exists in patients table
";

// Execute first part
if(mysqli_multi_query($conn, $sql)) {
    do {
        // Store result
        if($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while(mysqli_more_results($conn) && mysqli_next_result($conn));
}

// Check if family_code column exists
$check_query = "SELECT COUNT(*) as column_exists FROM information_schema.columns 
                WHERE table_schema = DATABASE() 
                AND table_name = 'patients' 
                AND column_name = 'family_code'";
$result = mysqli_query($conn, $check_query);
$row = mysqli_fetch_assoc($result);
$family_code_exists = $row['column_exists'] > 0;

// Add family_code column if it doesn't exist
if(!$family_code_exists) {
    $alter_query = "ALTER TABLE `patients` ADD `family_code` varchar(10) DEFAULT NULL AFTER `zipcode`";
    if(mysqli_query($conn, $alter_query)) {
        echo "<p>Added family_code column to patients table</p>";
    } else {
        echo "<p>Error adding family_code column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>family_code column already exists in patients table</p>";
}

// Check if family_role column exists
$check_query = "SELECT COUNT(*) as column_exists FROM information_schema.columns 
                WHERE table_schema = DATABASE() 
                AND table_name = 'patients' 
                AND column_name = 'family_role'";
$result = mysqli_query($conn, $check_query);
$row = mysqli_fetch_assoc($result);
$family_role_exists = $row['column_exists'] > 0;

// Add family_role column if it doesn't exist
if(!$family_role_exists) {
    $alter_query = "ALTER TABLE `patients` ADD `family_role` varchar(50) DEFAULT NULL AFTER `family_code`";
    if(mysqli_query($conn, $alter_query)) {
        echo "<p>Added family_role column to patients table</p>";
    } else {
        echo "<p>Error adding family_role column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>family_role column already exists in patients table</p>";
}

// Check if foreign key constraint exists
$check_query = "SELECT COUNT(*) as constraint_exists FROM information_schema.table_constraints 
                WHERE table_schema = DATABASE() 
                AND table_name = 'family_codes' 
                AND constraint_name = 'family_codes_ibfk_1'";
$result = mysqli_query($conn, $check_query);
$row = mysqli_fetch_assoc($result);
$constraint_exists = $row['constraint_exists'] > 0;

// Add constraint if it doesn't exist
if(!$constraint_exists) {
    $alter_query = "ALTER TABLE `family_codes` ADD CONSTRAINT `family_codes_ibfk_1` 
                    FOREIGN KEY (`created_by`) REFERENCES `patients` (`id`) ON DELETE CASCADE";
    if(mysqli_query($conn, $alter_query)) {
        echo "<p>Added foreign key constraint to family_codes table</p>";
    } else {
        echo "<p>Error adding constraint: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>Foreign key constraint already exists</p>";
}

echo "<h3>All fixes completed</h3>";
echo "<p><a href='admin/family_profiles.php'>Go to Family Profiles</a></p>";
?> 