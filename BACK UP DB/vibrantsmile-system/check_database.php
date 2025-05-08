<?php
require_once 'config.php';

echo "<h2>Database Structure Check</h2>";

// Check if database exists
$db_check = mysqli_query($conn, "SELECT DATABASE()");
$db_name = mysqli_fetch_row($db_check)[0];
echo "Current Database: " . $db_name . "<br><br>";

// List all tables
echo "<h3>Tables in Database:</h3>";
$tables = mysqli_query($conn, "SHOW TABLES");
echo "<table border='1'>";
echo "<tr><th>Table Name</th></tr>";
while ($row = mysqli_fetch_row($tables)) {
    echo "<tr><td>" . $row[0] . "</td></tr>";
}
echo "</table><br>";

// Check services table
echo "<h3>Services Table Structure:</h3>";
$services_structure = mysqli_query($conn, "DESCRIBE services");
if ($services_structure) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($services_structure)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Show services data
    echo "<h4>Services Data:</h4>";
    $services_data = mysqli_query($conn, "SELECT * FROM services");
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Duration</th><th>Price</th></tr>";
    while ($row = mysqli_fetch_assoc($services_data)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['duration'] . " mins</td>";
        echo "<td>₱" . number_format($row['price'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Services table does not exist.<br>";
}

// Check appointments table
echo "<h3>Appointments Table Structure:</h3>";
$appointments_structure = mysqli_query($conn, "DESCRIBE appointments");
if ($appointments_structure) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($appointments_structure)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check foreign keys
    echo "<h4>Foreign Key Constraints:</h4>";
    $foreign_keys = mysqli_query($conn, "SELECT * FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = 'schema' 
        AND TABLE_NAME = 'appointments' 
        AND REFERENCED_TABLE_NAME IS NOT NULL");
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
} else {
    echo "Appointments table does not exist.<br>";
}

// Check for any errors
if (mysqli_error($conn)) {
    echo "<h3>Errors Found:</h3>";
    echo mysqli_error($conn);
}
?> 