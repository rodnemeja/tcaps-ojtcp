<?php
require_once "config/database.php";

// Check if admin user exists
$sql = "SELECT id FROM users WHERE username = 'admin'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    // Create admin user
    $username = 'admin';
    $password = password_hash('password', PASSWORD_DEFAULT);
    $email = 'admin@dentalclinic.com';
    $full_name = 'Administrator';
    $role = 'admin';

    $sql = "INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "sssss", $username, $password, $email, $full_name, $role);
        if (mysqli_stmt_execute($stmt)) {
            echo "Admin user created successfully<br>";
            echo "Username: admin<br>";
            echo "Password: password<br>";
        } else {
            echo "Error creating admin user: " . mysqli_error($conn) . "<br>";
        }
    }
} else {
    echo "Admin user already exists<br>";
    echo "Username: admin<br>";
    echo "Password: password<br>";
}
?> 