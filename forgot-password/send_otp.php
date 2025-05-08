<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

// Database connection (update with your actual database details)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tcaps_g8_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Generate OTP
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['email'] = $email;


    $sql = "SELECT * FROM student WHERE student_username = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'rikemarwim@gmail.com';
            $mail->Password = 'azqdpqrpyjndkwkn';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            //Recipients
            $mail->setFrom('rikermarwim@gmail.com', 'TCAS FORGOT PASSWORD');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP';
            $mail->Body    = "Your OTP for password reset is: <b>$otp</b>";

            $mail->send();
            echo 'OTP has been sent to your email.';
            header("Location: verify_otp.php");
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo 'Email not found.';
    }
}
?>
