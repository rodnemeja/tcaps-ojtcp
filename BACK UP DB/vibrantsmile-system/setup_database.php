<?php
require_once "config/database.php";

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS schema";
if (mysqli_query($conn, $sql)) {
    echo "Database created successfully or already exists<br>";
} else {
    die("Error creating database: " . mysqli_error($conn));
}

// Select the database
mysqli_select_db($conn, "schema");

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'doctor', 'staff', 'patient') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (mysqli_query($conn, $sql)) {
    echo "Users table created successfully<br>";
} else {
    die("Error creating users table: " . mysqli_error($conn));
}

// Create patients table
$sql = "CREATE TABLE IF NOT EXISTS patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    address TEXT,
    medical_history TEXT,
    family_code VARCHAR(10) NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if (mysqli_query($conn, $sql)) {
    echo "Patients table created successfully<br>";
} else {
    die("Error creating patients table: " . mysqli_error($conn));
}

// Create doctors table
$sql = "CREATE TABLE IF NOT EXISTS doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    specialization VARCHAR(100),
    years_of_experience INT,
    qualification VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if (mysqli_query($conn, $sql)) {
    echo "Doctors table created successfully<br>";
} else {
    die("Error creating doctors table: " . mysqli_error($conn));
}

// Create services table
$sql = "CREATE TABLE IF NOT EXISTS services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    cost DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if (mysqli_query($conn, $sql)) {
    echo "Services table created successfully<br>";
} else {
    die("Error creating services table: " . mysqli_error($conn));
}

// Create appointments table
$sql = "CREATE TABLE IF NOT EXISTS appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
)";
if (mysqli_query($conn, $sql)) {
    echo "Appointments table created successfully<br>";
} else {
    die("Error creating appointments table: " . mysqli_error($conn));
}

// Create appointment_services table
$sql = "CREATE TABLE IF NOT EXISTS appointment_services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    service_id INT NOT NULL,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
)";
if (mysqli_query($conn, $sql)) {
    echo "Appointment services table created successfully<br>";
} else {
    die("Error creating appointment_services table: " . mysqli_error($conn));
}

// Create invoices table
$sql = "CREATE TABLE IF NOT EXISTS invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    invoice_number VARCHAR(50),
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    payment_method ENUM('cash', 'card', 'insurance'),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
)";
if (mysqli_query($conn, $sql)) {
    echo "Invoices table created successfully<br>";
} else {
    die("Error creating invoices table: " . mysqli_error($conn));
}

// Create invoice_items table
$sql = "CREATE TABLE IF NOT EXISTS invoice_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    service_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
)";
if (mysqli_query($conn, $sql)) {
    echo "Invoice items table created successfully<br>";
} else {
    die("Error creating invoice_items table: " . mysqli_error($conn));
}

// Create family_relationships table
$sql = "CREATE TABLE IF NOT EXISTS `family_relationships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `related_patient_id` int(11) NOT NULL,
  `relationship_type` enum('parent','child','spouse','sibling') NOT NULL,
  `is_emergency_contact` tinyint(1) DEFAULT 0,
  `is_guardian` tinyint(1) DEFAULT 0,
  `is_insurance_holder` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `related_patient_id` (`related_patient_id`),
  CONSTRAINT `family_relationships_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `family_relationships_ibfk_2` FOREIGN KEY (`related_patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($sql) !== TRUE) {
    echo "Error creating family_relationships table: " . $conn->error . "<br>";
}

// Add family_code to patients table
$sql = "ALTER TABLE `patients` 
        ADD COLUMN `family_code` VARCHAR(10) NULL DEFAULT NULL,
        ADD INDEX `idx_family_code` (`family_code`)";
if ($conn->query($sql) !== TRUE) {
    // If error is not about duplicate column, report it
    if (strpos($conn->error, "Duplicate column name") === false) {
        echo "Error adding family_code to patients table: " . $conn->error . "<br>";
    }
}

// Create family_codes table
$sql = "CREATE TABLE IF NOT EXISTS `family_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `family_codes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($sql) !== TRUE) {
    echo "Error creating family_codes table: " . $conn->error . "<br>";
}

// Check if admin user exists
$sql = "SELECT id FROM users WHERE username = 'admin'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    // Create default admin user with secure password
    $username = 'admin';
    $password = password_hash('Admin@123456', PASSWORD_DEFAULT);
    $email = 'admin@dentalclinic.com';
    $full_name = 'Administrator';
    $role = 'admin';

    $sql = "INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "sssss", $username, $password, $email, $full_name, $role);
        if (mysqli_stmt_execute($stmt)) {
            echo "Default admin user created successfully. Please change the password after first login.<br>";
            echo "Username: admin<br>";
            echo "Initial Password: Admin@123456<br>";
        } else {
            echo "Error creating admin user: " . mysqli_error($conn) . "<br>";
        }
    }
} else {
    echo "Admin user already exists<br>";
}

// Insert sample services if none exist
$sql = "SELECT id FROM services";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    $services = array(
        array('General Checkup', 'Comprehensive dental examination including cleaning and X-rays', 100.00, 60),
        array('Teeth Whitening', 'Professional teeth whitening treatment', 200.00, 90),
        array('Dental Filling', 'Treatment for cavities and tooth decay', 150.00, 45),
        array('Root Canal', 'Treatment for infected or damaged tooth pulp', 500.00, 120),
        array('Dental Crown', 'Restoration for damaged or weakened teeth', 400.00, 90),
        array('Tooth Extraction', 'Removal of damaged or problematic teeth', 300.00, 60)
    );

    $sql = "INSERT INTO services (name, description, cost, duration) VALUES (?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        foreach ($services as $service) {
            mysqli_stmt_bind_param($stmt, "ssdi", $service[0], $service[1], $service[2], $service[3]);
            if (mysqli_stmt_execute($stmt)) {
                echo "Service added: " . $service[0] . "<br>";
            } else {
                echo "Error adding service: " . mysqli_error($conn) . "<br>";
            }
        }
    }
} else {
    echo "Sample services already exist<br>";
}

// Add family_role to patients table
echo "<h2>Adding family_role to patients table</h2>";
$sql = "ALTER TABLE patients 
ADD COLUMN `family_role` VARCHAR(50) NULL DEFAULT NULL,
ADD INDEX `idx_family_role` (`family_role`)";

if ($conn->query($sql) === TRUE) {
    echo "Successfully added family_role to patients table.<br>";
} else {
    // Check if error is because the column already exists
    if (strpos($conn->error, "Duplicate column name") !== false) {
        echo "family_role column already exists in patients table.<br>";
    } else {
        echo "Error adding family_role to patients table: " . $conn->error . "<br>";
    }
}

echo "<br>Database setup completed successfully!";
?> 