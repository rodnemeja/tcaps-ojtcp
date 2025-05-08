<?php
require_once 'config.php';

// Read the SQL file
$sql = file_get_contents('update_database.sql');

// Split the SQL into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

// Execute each statement
foreach ($statements as $statement) {
    if (!empty($statement)) {
        if (mysqli_query($conn, $statement)) {
            echo "Successfully executed: " . substr($statement, 0, 50) . "...<br>";
        } else {
            echo "Error executing statement: " . mysqli_error($conn) . "<br>";
            echo "Statement: " . $statement . "<br><br>";
        }
    }
}

// Verify the changes
echo "<h2>Verifying Changes</h2>";

// Check services table
$services_check = mysqli_query($conn, "SELECT COUNT(*) as count FROM services");
$services_count = mysqli_fetch_assoc($services_check)['count'];
echo "Number of services: " . $services_count . "<br>";

// Check appointments table structure
$columns_check = mysqli_query($conn, "SHOW COLUMNS FROM appointments");
echo "<h3>Appointments Table Columns:</h3>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
while ($row = mysqli_fetch_assoc($columns_check)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check foreign keys
$foreign_keys = mysqli_query($conn, "SELECT * FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'schema' 
    AND TABLE_NAME = 'appointments' 
    AND REFERENCED_TABLE_NAME IS NOT NULL");
echo "<h3>Foreign Key Constraints:</h3>";
echo "<table border='1'>";
echo "<tr><th>Column</th><th>Referenced Table</th><th>Referenced Column</th></tr>";
while ($row = mysqli_fetch_assoc($foreign_keys)) {
    echo "<tr>";
    echo "<td>" . $row['COLUMN_NAME'] . "</td>";
    echo "<td>" . $row['REFERENCED_TABLE_NAME'] . "</td>";
    echo "<td>" . $row['REFERENCED_COLUMN_NAME'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><strong>Database update completed!</strong>";
?> 