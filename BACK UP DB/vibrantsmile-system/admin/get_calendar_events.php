<?php
require_once "../config/init.php";
require_once "../config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get start and end dates from request
$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d');
$end = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d', strtotime('+1 month'));

try {
    // Prepare the SQL query
    $sql = "SELECT 
        a.id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        u.full_name as patient_name,
        GROUP_CONCAT(s.name SEPARATOR ', ') as services,
        GROUP_CONCAT(s.duration) as durations
        FROM appointments a
        LEFT JOIN patients p ON a.patient_id = p.id
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN appointment_services aps ON a.id = aps.appointment_id
        LEFT JOIN services s ON aps.service_id = s.id
        WHERE a.appointment_date BETWEEN ? AND ?
        GROUP BY a.id, a.appointment_date, a.appointment_time, a.status, u.full_name
        ORDER BY a.appointment_date ASC, a.appointment_time ASC";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $start, $end);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $events = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Calculate total duration
            $durations = explode(',', $row['durations']);
            $total_duration = array_sum($durations);
            if (!$total_duration) $total_duration = 60; // Default duration if not set

            // Create start and end datetime
            $start_datetime = $row['appointment_date'] . 'T' . $row['appointment_time'];
            $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime . ' + ' . $total_duration . ' minutes'));

            // Set color based on status
            $color = '';
            switch ($row['status']) {
                case 'completed':
                    $color = '#1cc88a'; // green
                    break;
                case 'scheduled':
                    $color = '#f6c23e'; // yellow
                    break;
                case 'cancelled':
                    $color = '#e74a3b'; // red
                    break;
                default:
                    $color = '#4e73df'; // blue
            }

            $events[] = [
                'id' => $row['id'],
                'title' => $row['patient_name'] . ' - ' . $row['services'],
                'start' => $start_datetime,
                'end' => $end_datetime,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'status' => $row['status'],
                    'patient' => $row['patient_name'],
                    'services' => $row['services']
                ]
            ];
        }
        
        mysqli_stmt_close($stmt);
        echo json_encode($events);
    } else {
        throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    error_log("Calendar Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch calendar events']);
}

mysqli_close($conn); 