<?php
// Include database connection
require_once "config/database.php";

// SQL to create the reschedule_suggestions table
$sql = "CREATE TABLE IF NOT EXISTS reschedule_suggestions (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT(11) NOT NULL,
    suggested_by INT(11) NOT NULL,
    suggested_date DATE NOT NULL,
    suggested_time TIME NOT NULL,
    suggested_end_time TIME NOT NULL,
    reason TEXT,
    status ENUM('pending', 'accepted', 'declined') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (appointment_id),
    INDEX (suggested_by),
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
)";

// Execute the SQL
if (mysqli_query($conn, $sql)) {
    echo "Table 'reschedule_suggestions' created successfully!";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}

// Close connection
mysqli_close($conn);
?> 