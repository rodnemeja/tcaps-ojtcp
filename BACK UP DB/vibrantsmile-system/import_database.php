<?php
require_once 'config.php';

echo "<h2>Database Import</h2>";

// Read the SQL file
$sql = file_get_contents('schema.sql');

// Split the SQL into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

// Execute each statement
$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    if (!empty($statement)) {
        if (mysqli_query($conn, $statement)) {
            $success_count++;
        } else {
            $error_count++;
            echo "Error executing statement: " . mysqli_error($conn) . "<br>";
            echo "Statement: " . substr($statement, 0, 100) . "...<br><br>";
        }
    }
}

echo "<h3>Import Results:</h3>";
echo "Successfully executed statements: " . $success_count . "<br>";
echo "Failed statements: " . $error_count . "<br><br>";

// Verify the import
echo "<h3>Verifying Import</h3>";

// List all tables
$tables = mysqli_query($conn, "SHOW TABLES");
echo "<h4>Tables in Database:</h4>";
echo "<table border='1'>";
echo "<tr><th>Table Name</th><th>Record Count</th></tr>";
while ($row = mysqli_fetch_row($tables)) {
    $table_name = $row[0];
    $count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM $table_name"))[0];
    echo "<tr>";
    echo "<td>" . $table_name . "</td>";
    echo "<td>" . $count . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check for any errors
if (mysqli_error($conn)) {
    echo "<h3>Errors Found:</h3>";
    echo mysqli_error($conn);
}

echo "<br><strong>Database import completed!</strong>";
?> 