<?php
// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'schema');

// Create connection without database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if(!$conn){
    die("Connection failed: " . mysqli_connect_error());
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if(mysqli_query($conn, $sql)){
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . mysqli_error($conn) . "<br>";
}

// Select the database
mysqli_select_db($conn, DB_NAME);

// Create Users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'doctor', 'patient') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if(mysqli_query($conn, $sql)){
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . mysqli_error($conn) . "<br>";
}

// Create Doctors table
$sql = "CREATE TABLE IF NOT EXISTS doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    specialization VARCHAR(100),
    license_number VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if(mysqli_query($conn, $sql)){
    echo "Doctors table created successfully<br>";
} else {
    echo "Error creating doctors table: " . mysqli_error($conn) . "<br>";
}

// Create Patients table
$sql = "CREATE TABLE IF NOT EXISTS patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    address TEXT,
    medical_history TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if(mysqli_query($conn, $sql)){
    echo "Patients table created successfully<br>";
} else {
    echo "Error creating patients table: " . mysqli_error($conn) . "<br>";
}

// Create Services table
$sql = "CREATE TABLE IF NOT EXISTS services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    cost DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if(mysqli_query($conn, $sql)){
    echo "Services table created successfully<br>";
} else {
    echo "Error creating services table: " . mysqli_error($conn) . "<br>";
}

// Create Appointments table
$sql = "CREATE TABLE IF NOT EXISTS appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
)";

if(mysqli_query($conn, $sql)){
    echo "Appointments table created successfully<br>";
} else {
    echo "Error creating appointments table: " . mysqli_error($conn) . "<br>";
}

// Create Appointment Services table
$sql = "CREATE TABLE IF NOT EXISTS appointment_services (
    appointment_id INT NOT NULL,
    service_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (appointment_id, service_id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
)";

if(mysqli_query($conn, $sql)){
    echo "Appointment Services table created successfully<br>";
} else {
    echo "Error creating appointment_services table: " . mysqli_error($conn) . "<br>";
}

// Create Invoices table
$sql = "CREATE TABLE IF NOT EXISTS invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    payment_method ENUM('cash', 'card', 'insurance') DEFAULT 'cash',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
)";

if(mysqli_query($conn, $sql)){
    echo "Invoices table created successfully<br>";
} else {
    echo "Error creating invoices table: " . mysqli_error($conn) . "<br>";
}

// Create Invoice Items table
$sql = "CREATE TABLE IF NOT EXISTS invoice_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    service_id INT NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
)";

if(mysqli_query($conn, $sql)){
    echo "Invoice Items table created successfully<br>";
} else {
    echo "Error creating invoice_items table: " . mysqli_error($conn) . "<br>";
}

// Create Payments table
$sql = "CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method ENUM('cash', 'card', 'insurance') NOT NULL,
    transaction_id VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
)";

if(mysqli_query($conn, $sql)){
    echo "Payments table created successfully<br>";
} else {
    echo "Error creating payments table: " . mysqli_error($conn) . "<br>";
}

// Insert default admin user
$sql = "INSERT INTO users (username, password, role, full_name, email, phone) 
        VALUES ('admin', ?, 'admin', 'Admin User', 'admin@example.com', '1234567890')
        ON DUPLICATE KEY UPDATE id=id";

if($stmt = mysqli_prepare($conn, $sql)){
    $hashed_password = password_hash("password", PASSWORD_DEFAULT);
    mysqli_stmt_bind_param($stmt, "s", $hashed_password);
    if(mysqli_stmt_execute($stmt)){
        echo "Default admin user created/updated successfully<br>";
    } else {
        echo "Error creating admin user: " . mysqli_error($conn) . "<br>";
    }
}

// Insert sample services
$sql = "INSERT INTO services (name, description, cost, duration) VALUES
('Dental Check-up', 'Regular dental examination and cleaning', 50.00, 30),
('Teeth Whitening', 'Professional teeth whitening treatment', 200.00, 60),
('Dental Filling', 'Treatment for cavities', 150.00, 45),
('Root Canal', 'Root canal treatment', 500.00, 90),
('Dental Crown', 'Crown placement', 800.00, 120),
('Tooth Extraction', 'Simple tooth extraction', 200.00, 45),
('Dental Implant', 'Dental implant placement', 2000.00, 120),
('Orthodontic Consultation', 'Initial orthodontic assessment', 100.00, 30),
('X-Ray', 'Dental X-ray examination', 75.00, 15),
('Emergency Treatment', 'Emergency dental care', 150.00, 60)
ON DUPLICATE KEY UPDATE id=id";

if(mysqli_query($conn, $sql)){
    echo "Sample services created successfully<br>";
} else {
    echo "Error creating sample services: " . mysqli_error($conn) . "<br>";
}

echo "<br>Database setup completed!<br>";
echo "You can now login with:<br>";
echo "Username: admin<br>";
echo "Password: password<br>";

mysqli_close($conn);
?> 