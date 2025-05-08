<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Initialize page output
$output = '';

// Display session status
$sessionStatus = '';
switch(session_status()) {
    case PHP_SESSION_DISABLED:
        $sessionStatus = 'Sessions are disabled';
        break;
    case PHP_SESSION_NONE:
        $sessionStatus = 'Sessions are enabled but no session exists';
        break;
    case PHP_SESSION_ACTIVE:
        $sessionStatus = 'Sessions are enabled and a session exists';
        break;
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

// Output HTML header
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Debug - Vibrant Clinic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h1 class="mb-4">Session Debug Information</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Session Status</h5>
            </div>
            <div class="card-body">
                <p><strong>Session Status:</strong> ' . $sessionStatus . '</p>
                <p><strong>Session ID:</strong> ' . session_id() . '</p>
                <p><strong>Logged In:</strong> ' . ($isLoggedIn ? 'Yes' : 'No') . '</p>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Session Data</h5>
            </div>
            <div class="card-body">
                <pre>' . print_r($_SESSION, true) . '</pre>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Cookie Information</h5>
            </div>
            <div class="card-body">
                <pre>' . print_r($_COOKIE, true) . '</pre>
            </div>
        </div>';

// If logged in, test the connection to get_appointment_details.php
if ($isLoggedIn && isset($_SESSION['id'])) {
    echo '<div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">API Connection Test</h5>
            </div>
            <div class="card-body">
                <p>Testing connection to appointment details API...</p>
                <div id="apiTestResult" class="mt-3">Loading...</div>
            </div>
        </div>';
}

echo '
        <div class="mt-4">
            <a href="appointments.php" class="btn btn-primary">Return to Appointments</a>
        </div>
    </div>
    
    <script>
    // Test the connection to get_appointment_details.php if logged in
    document.addEventListener("DOMContentLoaded", function() {
        const apiTestResult = document.getElementById("apiTestResult");
        if (apiTestResult) {
            // Make a test request to get_appointment_details.php with a fake ID
            fetch("get_appointment_details.php?id=1")
                .then(response => response.text())
                .then(data => {
                    try {
                        const jsonData = JSON.parse(data);
                        apiTestResult.innerHTML = `<div class="alert ${jsonData.success ? "alert-success" : "alert-warning"}">
                            <h6>Response:</h6>
                            <pre>${JSON.stringify(jsonData, null, 2)}</pre>
                        </div>`;
                    } catch (e) {
                        apiTestResult.innerHTML = `<div class="alert alert-danger">
                            <h6>Error parsing JSON:</h6>
                            <p>${e.message}</p>
                            <h6>Raw response:</h6>
                            <pre>${data}</pre>
                        </div>`;
                    }
                })
                .catch(error => {
                    apiTestResult.innerHTML = `<div class="alert alert-danger">
                        <h6>Network Error:</h6>
                        <p>${error.message}</p>
                    </div>`;
                });
        }
    });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
?> 