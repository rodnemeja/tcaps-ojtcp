<?php
require_once "config/database.php";

// Get all patients with their user details
$sql = "SELECT p.*, u.username, u.email, u.full_name, u.phone 
        FROM patients p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY u.full_name";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error: " . mysqli_error($conn));
}

echo "<h2>Patients in Database:</h2>";
echo "<table border='1'>";
echo "<tr><th>Name</th><th>Email</th><th>Phone</th><th>Date of Birth</th><th>Gender</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
    echo "<td>" . ($row['date_of_birth'] ? date('M d, Y', strtotime($row['date_of_birth'])) : 'Not set') . "</td>";
    echo "<td>" . ($row['gender'] ? ucfirst($row['gender']) : 'Not set') . "</td>";
    echo "</tr>";
}

echo "</table>";
?> 