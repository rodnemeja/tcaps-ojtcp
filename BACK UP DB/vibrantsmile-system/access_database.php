<?php
require_once 'config.php';

echo "<h2>Database Access</h2>";

// Check database connection
if ($conn) {
    echo "Successfully connected to database.<br>";
    
    // Get database name
    $db_check = mysqli_query($conn, "SELECT DATABASE()");
    $db_name = mysqli_fetch_row($db_check)[0];
    echo "Current Database: " . $db_name . "<br><br>";
    
    // List all tables
    echo "<h3>Tables in Database:</h3>";
    $tables = mysqli_query($conn, "SHOW TABLES");
    echo "<table border='1'>";
    echo "<tr><th>Table Name</th><th>Action</th></tr>";
    while ($row = mysqli_fetch_row($tables)) {
        echo "<tr>";
        echo "<td>" . $row[0] . "</td>";
        echo "<td><a href='?table=" . $row[0] . "'>View Contents</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show table contents if requested
    if (isset($_GET['table'])) {
        $table = mysqli_real_escape_string($conn, $_GET['table']);
        echo "<h3>Contents of Table: " . $table . "</h3>";
        
        // Get table structure
        $structure = mysqli_query($conn, "DESCRIBE $table");
        echo "<h4>Table Structure:</h4>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = mysqli_fetch_assoc($structure)) {
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
        
        // Get table data
        $data = mysqli_query($conn, "SELECT * FROM $table");
        if ($data) {
            echo "<h4>Table Data:</h4>";
            echo "<table border='1'>";
            
            // Get column names
            $columns = mysqli_fetch_fields($data);
            echo "<tr>";
            foreach ($columns as $column) {
                echo "<th>" . $column->name . "</th>";
            }
            echo "</tr>";
            
            // Reset pointer
            mysqli_data_seek($data, 0);
            
            // Get data rows
            while ($row = mysqli_fetch_assoc($data)) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    }
} else {
    echo "Failed to connect to database: " . mysqli_connect_error();
}
?> 