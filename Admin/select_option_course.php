<?php
include('../Includes/conn.php');

// Fetch all departments
$query = "SELECT * FROM department ORDER BY department_name ASC";
$result = mysqli_query($db, $query);

// Generate options for the dropdown
while ($row = mysqli_fetch_assoc($result)) {
    echo '<option value="' . $row['department_id'] . '">' . $row['department_name'] . '</option>';
}
?>
