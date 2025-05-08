<?php
require_once 'config.php';

// Check services table
echo "<h2>Checking Services Table</h2>";
$services_sql = "SHOW TABLES LIKE 'services'";
$services_result = mysqli_query($conn, $services_sql);

if (mysqli_num_rows($services_result) > 0) {
    echo "Services table exists.<br>";
    
    // Check services table structure
    $services_structure = mysqli_query($conn, "DESCRIBE services");
    echo "<h3>Services Table Structure:</h3>";
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
    
    // Check services data
    $services_data = mysqli_query($conn, "SELECT * FROM services");
    echo "<h3>Services Data:</h3>";
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
    echo "Services table does not exist. Creating it now...<br>";
    
    // Create services table
    $create_services = "CREATE TABLE IF NOT EXISTS services (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        duration INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $create_services)) {
        echo "Services table created successfully.<br>";
        
        // Insert default services
        $insert_services = "INSERT INTO services (name, description, duration, price) VALUES
            ('General Check-up', 'Comprehensive dental examination and cleaning', 30, 500.00),
            ('Teeth Whitening', 'Professional teeth whitening treatment', 60, 1500.00),
            ('Dental Filling', 'Treatment for cavities and tooth decay', 45, 800.00),
            ('Root Canal', 'Treatment for infected tooth pulp', 90, 3000.00),
            ('Tooth Extraction', 'Removal of damaged or problematic teeth', 60, 1200.00),
            ('Dental Crown', 'Restoration of damaged teeth', 75, 2500.00),
            ('Dental Implant', 'Replacement of missing teeth', 120, 5000.00),
            ('Orthodontic Consultation', 'Evaluation for teeth alignment treatment', 45, 1000.00)";
        
        if (mysqli_query($conn, $insert_services)) {
            echo "Default services inserted successfully.<br>";
        } else {
            echo "Error inserting default services: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "Error creating services table: " . mysqli_error($conn) . "<br>";
    }
}

// Check appointments table structure
echo "<h2>Checking Appointments Table</h2>";
$appointments_structure = mysqli_query($conn, "DESCRIBE appointments");
echo "<h3>Appointments Table Structure:</h3>";
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

// Check if service_id and end_time columns exist
$check_columns = mysqli_query($conn, "SHOW COLUMNS FROM appointments LIKE 'service_id'");
if (mysqli_num_rows($check_columns) == 0) {
    echo "Adding service_id and end_time columns to appointments table...<br>";
    
    $alter_appointments = "ALTER TABLE appointments
        ADD COLUMN service_id INT AFTER doctor_id,
        ADD COLUMN end_time TIME AFTER appointment_time,
        ADD FOREIGN KEY (service_id) REFERENCES services(id)";
    
    if (mysqli_query($conn, $alter_appointments)) {
        echo "Columns added successfully.<br>";
    } else {
        echo "Error adding columns: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "service_id and end_time columns already exist in appointments table.<br>";
}

// Check foreign key constraints
echo "<h3>Foreign Key Constraints:</h3>";
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
?> 