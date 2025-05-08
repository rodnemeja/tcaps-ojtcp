<?php
require_once 'config.php';

echo "<h2>Database Reset</h2>";

// Drop existing database
$drop_db = "DROP DATABASE IF EXISTS schema";
if (mysqli_query($conn, $drop_db)) {
    echo "Existing database dropped successfully.<br>";
} else {
    echo "Error dropping database: " . mysqli_error($conn) . "<br>";
}

// Create new database
$create_db = "CREATE DATABASE schema";
if (mysqli_query($conn, $create_db)) {
    echo "New database created successfully.<br>";
} else {
    echo "Error creating database: " . mysqli_error($conn) . "<br>";
}

// Select the database
mysqli_select_db($conn, "schema");

echo "<br><strong>Database reset completed!</strong>";
echo "<br><br>Now you can run <a href='import_database.php'>import_database.php</a> to import your schema.";
?> 