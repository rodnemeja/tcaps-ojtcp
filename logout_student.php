<?php
session_start();
unset($_SESSION['student_id']);
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Logged out',
                text: 'You have been successfully logged out!',
                timer: 1000,
                showConfirmButton: false
            }).then(function() {
                window.location.href = 'index';
            });
        });
    </script>
</body>
</html>
