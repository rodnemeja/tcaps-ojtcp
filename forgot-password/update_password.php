<?php
session_start();

// Database connection (update with your actual database details)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tcaps_g8_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $email = $_SESSION['email'];

    // Update password in the database
    $sql = "UPDATE student SET student_password = '$new_password' WHERE student_username = '$email'";

    if ($conn->query($sql) === TRUE) {
        $message = 'success';
        
        // Clear session data
        session_unset();
        session_destroy();
    } else {
        $message = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Password</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php if ($message == 'success'): ?>
    <script>
        Swal.fire({
            title: 'Password Updated!',
            text: 'Your password has been updated successfully.',
            icon: 'success',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "../signin_student"; // Redirect to sign-in page
            }
        });
    </script>
<?php elseif ($message == 'error'): ?>
    <script>
        Swal.fire({
            title: 'Error',
            text: 'There was an error updating your password.',
            icon: 'error',
            confirmButtonText: 'Try Again'
        });
    </script>
<?php endif; ?>

</body>
</html>
