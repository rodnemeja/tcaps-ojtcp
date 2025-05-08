<?php
require_once "config/database.php";

// Select the schema database
mysqli_select_db($conn, "schema");

// Get all tables
$sql = "SHOW TABLES";
$result = mysqli_query($conn, $sql);

echo "<h2>Current Database Structure</h2>";

while ($row = mysqli_fetch_row($result)) {
    $table = $row[0];
    echo "<h3>Table: $table</h3>";
    
    // Get table structure
    $structure = mysqli_query($conn, "DESCRIBE $table");
    echo "<pre>";
    while ($field = mysqli_fetch_assoc($structure)) {
        echo $field['Field'] . " - " . $field['Type'];
        if ($field['Null'] === 'NO') echo " NOT NULL";
        if ($field['Default']) echo " DEFAULT '" . $field['Default'] . "'";
        echo "\n";
    }
    echo "</pre>";
    
    // Get row count
    $count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM $table"))[0];
    echo "Total rows: $count<br><br>";
}
?> 