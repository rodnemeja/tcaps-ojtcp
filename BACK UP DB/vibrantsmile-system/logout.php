<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - Dental Clinic</title>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .animated {
            animation-duration: 0.8s;
            animation-fill-mode: both;
        }
        .fadeOut {
            animation-name: fadeOut;
        }
        @keyframes fadeOut {
            0% {
                opacity: 1;
                transform: scale(1);
            }
            100% {
                opacity: 0;
                transform: scale(0.95);
            }
        }
    </style>
</head>
<body>
<?php
// Initialize the session
session_start();

// Store the role before destroying session
$wasAdmin = isset($_SESSION["role"]) && $_SESSION["role"] === "admin";

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();
?>

<script>
Swal.fire({
    icon: 'success',
    title: 'Goodbye!',
    text: 'You have been successfully logged out.',
    timer: 2000,
    timerProgressBar: true,
    showConfirmButton: false,
    customClass: {
        popup: 'animated fadeOut'
    }
}).then(() => {
    // Redirect to login page
    window.location.href = "index.php";
});
</script>

</body>
</html> 