<?php
require_once "config/database.php";

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    role ENUM('admin', 'doctor', 'staff', 'patient') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if(mysqli_query($conn, $sql)) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . mysqli_error($conn) . "<br>";
}

// Create patients table
$sql = "CREATE TABLE IF NOT EXISTS patients (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    address TEXT,
    medical_history TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if(mysqli_query($conn, $sql)) {
    echo "Patients table created successfully<br>";
} else {
    echo "Error creating patients table: " . mysqli_error($conn) . "<br>";
}

// Create doctors table
$sql = "CREATE TABLE IF NOT EXISTS doctors (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    specialization VARCHAR(100),
    years_of_experience INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if(mysqli_query($conn, $sql)) {
    echo "Doctors table created successfully<br>";
} else {
    echo "Error creating doctors table: " . mysqli_error($conn) . "<br>";
}

// Create staff table
$sql = "CREATE TABLE IF NOT EXISTS staff (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    position VARCHAR(50),
    department VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if(mysqli_query($conn, $sql)) {
    echo "Staff table created successfully<br>";
} else {
    echo "Error creating staff table: " . mysqli_error($conn) . "<br>";
}

// Create services table
$sql = "CREATE TABLE IF NOT EXISTS services (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    cost DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if(mysqli_query($conn, $sql)) {
    echo "Services table created successfully<br>";
} else {
    echo "Error creating services table: " . mysqli_error($conn) . "<br>";
}

// Create appointments table
$sql = "CREATE TABLE IF NOT EXISTS appointments (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
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

if(mysqli_query($conn, $sql)) {
    echo "Appointments table created successfully<br>";
} else {
    echo "Error creating appointments table: " . mysqli_error($conn) . "<br>";
}

// Create appointment_services table
$sql = "CREATE TABLE IF NOT EXISTS appointment_services (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    service_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
)";

if(mysqli_query($conn, $sql)) {
    echo "Appointment services table created successfully<br>";
} else {
    echo "Error creating appointment services table: " . mysqli_error($conn) . "<br>";
}

// Create invoices table
$sql = "CREATE TABLE IF NOT EXISTS invoices (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    patient_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
    payment_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
)";

if(mysqli_query($conn, $sql)) {
    echo "Invoices table created successfully<br>";
} else {
    echo "Error creating invoices table: " . mysqli_error($conn) . "<br>";
}

// Create invoice_items table
$sql = "CREATE TABLE IF NOT EXISTS invoice_items (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    service_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
)";

if(mysqli_query($conn, $sql)) {
    echo "Invoice items table created successfully<br>";
} else {
    echo "Error creating invoice items table: " . mysqli_error($conn) . "<br>";
}

// Create reports table
$sql = "CREATE TABLE IF NOT EXISTS reports (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    report_type ENUM('appointments', 'revenue', 'patients', 'services') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if(mysqli_query($conn, $sql)) {
    echo "Reports table created successfully<br>";
} else {
    echo "Error creating reports table: " . mysqli_error($conn) . "<br>";
}

// Insert default admin user
$admin_username = "admin";
$admin_password = password_hash("admin123", PASSWORD_DEFAULT);
$admin_email = "admin@example.com";
$admin_first_name = "System";
$admin_middle_name = null;
$admin_last_name = "Administrator";
$admin_phone = "1234567890";

$sql = "INSERT INTO users (username, password, email, first_name, middle_name, last_name, phone, role) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE password = VALUES(password)";

if($stmt = mysqli_prepare($conn, $sql)) {
    $role = "admin";
    mysqli_stmt_bind_param($stmt, "ssssssss", 
        $admin_username, 
        $admin_password, 
        $admin_email, 
        $admin_first_name,
        $admin_middle_name,
        $admin_last_name,
        $admin_phone, 
        $role
    );
    
    if(mysqli_stmt_execute($stmt)) {
        echo "Default admin user created/updated successfully<br>";
    } else {
        echo "Error creating admin user: " . mysqli_error($conn) . "<br>";
    }
    mysqli_stmt_close($stmt);
}

// Insert default services
$services = [
    ["General Checkup", "Regular dental examination and cleaning", 50.00, 30],
    ["Teeth Whitening", "Professional teeth whitening treatment", 150.00, 60],
    ["Dental Filling", "Treatment for cavities", 100.00, 45],
    ["Root Canal", "Root canal treatment", 300.00, 90],
    ["Dental Crown", "Crown placement", 400.00, 60],
    ["Tooth Extraction", "Simple tooth extraction", 150.00, 45],
    ["Dental Implant", "Single dental implant", 2000.00, 120],
    ["Orthodontic Consultation", "Initial orthodontic assessment", 80.00, 30]
];

$sql = "INSERT INTO services (name, description, cost, duration) VALUES (?, ?, ?, ?)";

if($stmt = mysqli_prepare($conn, $sql)) {
    foreach($services as $service) {
        mysqli_stmt_bind_param($stmt, "ssdi", $service[0], $service[1], $service[2], $service[3]);
        if(mysqli_stmt_execute($stmt)) {
            echo "Service '{$service[0]}' created successfully<br>";
        } else {
            echo "Error creating service '{$service[0]}': " . mysqli_error($conn) . "<br>";
        }
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?> 