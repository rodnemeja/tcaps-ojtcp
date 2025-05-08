<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendVerificationEmail($to, $name, $verificationCode) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'vibrantsmile07@gmail.com';
        $mail->Password = 'iovcqaxtgsfdqkwz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Disable SSL verification for testing
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Enable debug output
        $mail->SMTPDebug = 2; // Set to 2 for detailed debug output
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP Debug: $str");
        };

        // Recipients
        $mail->setFrom('vibrantsmile07@gmail.com', 'Vibrant Smile Dental Clinic System');
        $mail->addAddress($to, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email - Vibrant Smile Dental Clinic System';
        
        // Email body with verification code
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <div style="background: linear-gradient(135deg, #4e73df 0%, #36b9cc 100%); padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
                    <img src="assets/images/logo_vibrant.png" alt="Vibrant Smile Dental Clinic" style="max-width: 200px;">
                </div>
                <div style="padding: 20px; background-color: #ffffff; border-radius: 0 0 5px 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <h2 style="color: #4e73df; margin-bottom: 20px;">Welcome to Vibrant Smile Dental Clinic System!</h2>
                    <p style="color: #6c757d; line-height: 1.6;">Dear ' . htmlspecialchars($name) . ',</p>
                    <p style="color: #6c757d; line-height: 1.6;">Thank you for registering with our Vibrant Smile Dental Clinic System. To complete your registration, please use the following verification code:</p>
                    <div style="background-color: #f8f9fc; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px;">
                        <h1 style="color: #4e73df; letter-spacing: 5px; font-size: 32px; margin: 0;">' . $verificationCode . '</h1>
                    </div>
                    <p style="color: #6c757d; line-height: 1.6;">This code will expire in 10 minutes for security purposes.</p>
                    <p style="color: #6c757d; line-height: 1.6;">If you did not create an account, please ignore this email.</p>
                    <hr style="border: none; border-top: 1px solid #e9ecef; margin: 20px 0;">
                    <p style="color: #6c757d; font-size: 12px;">This is an automated email, please do not reply.</p>
                </div>
            </div>
        ';
        
        $mail->AltBody = "Your verification code is: $verificationCode\n\nThis code will expire in 10 minutes.";

        // Add retry mechanism
        $maxRetries = 3;
        $retryDelay = 2; // seconds
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                error_log("Attempting to send email (attempt " . ($attempt + 1) . " of $maxRetries)");
                if ($mail->send()) {
                    error_log("Verification email sent successfully to: $to");
                    return true;
                }
            } catch (Exception $e) {
                $attempt++;
                error_log("Email sending attempt $attempt failed: " . $mail->ErrorInfo);
                if ($attempt < $maxRetries) {
                    error_log("Waiting $retryDelay seconds before retry...");
                    sleep($retryDelay);
                    $retryDelay *= 2; // Exponential backoff
                }
            }
        }
        
        error_log("Failed to send verification email after $maxRetries attempts");
        return false;
    } catch (Exception $e) {
        error_log("Critical error in sendVerificationEmail: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

function sendPasswordResetEmail($to, $name, $resetLink) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'vibrantsmile07@gmail.com';
        $mail->Password = 'iovcqaxtgsfdqkwz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Disable SSL verification for testing
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Enable debug output
        $mail->SMTPDebug = 2; // Set to 2 for detailed debug output
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP Debug: $str");
        };

        // Recipients
        $mail->setFrom('vibrantsmile07@gmail.com', 'Vibrant Smile Dental Clinic System');
        $mail->addAddress($to, $name);
        
        // Email body
        $body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background: linear-gradient(to right, #4e73df, #36b9cc); padding: 20px; text-align: center;">
                <img src="assets/images/logo_vibrant.png" alt="Vibrant Smile Dental Clinic" style="max-width: 200px;">
            </div>
            <div style="padding: 20px; background-color: #ffffff; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h2 style="color: #2e384d; margin-bottom: 20px;">Reset Your Password</h2>
                <p style="color: #6c757d; line-height: 1.6;">Dear ' . $name . ',</p>
                <p style="color: #6c757d; line-height: 1.6;">We received a request to reset your password. Click the button below to create a new password:</p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $resetLink . '" style="background: linear-gradient(to right, #4e73df, #36b9cc); color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">Reset Password</a>
                </div>
                <p style="color: #6c757d; line-height: 1.6;">This link will expire in 1 hour for security reasons.</p>
                <p style="color: #6c757d; line-height: 1.6;">If you did not request a password reset, please ignore this email or contact us if you have concerns.</p>
                <hr style="border: none; border-top: 1px solid #e9ecef; margin: 20px 0;">
                <p style="color: #6c757d; font-size: 12px;">This is an automated email, please do not reply.</p>
            </div>
        </div>';

        $mail->Body = $body;
        $mail->AltBody = "Reset your password at: " . $resetLink;

        return $mail->send();
    } catch (Exception $e) {
        error_log("Error sending password reset email: " . $mail->ErrorInfo);
        return false;
    }
}

function sendAppointmentEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'vibrantsmile07@gmail.com';
        $mail->Password = 'iovcqaxtgsfdqkwz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Disable SSL verification for testing
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Enable debug output
        $mail->SMTPDebug = 2; // Set to 2 for detailed debug output
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP Debug: $str");
        };
        // Recipients
        $mail->setFrom('vibrantsmile07@gmail.com', 'Vibrant Smile Dental Clinic');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

function getAppointmentEmailBody($appointment, $status) {
    $date = date('F j, Y', strtotime($appointment['appointment_date']));
    $time = date('g:i A', strtotime($appointment['appointment_time']));
    $doctor = $appointment['doctor_name'];
    $service = $appointment['service_name'];
    
    $body = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
    
    if ($status === 'approved') {
        $body .= "
            <h2 style='color: #4e73df;'>Appointment Approved!</h2>
            <p>Dear {$appointment['patient_name']},</p>
            <p>Your appointment has been approved by Dr. {$doctor}.</p>
            <div style='background-color: #f8f9fc; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                <h3 style='color: #4e73df; margin-top: 0;'>Appointment Details:</h3>
                <p><strong>Date:</strong> {$date}</p>
                <p><strong>Time:</strong> {$time}</p>
                <p><strong>Service:</strong> {$service}</p>
                <p><strong>Doctor:</strong> Dr. {$doctor}</p>
            </div>
            <p>Please arrive 15 minutes before your scheduled appointment time.</p>
            <p>If you need to reschedule or cancel your appointment, please contact us at least 24 hours in advance.</p>
            <p>Best regards,<br> Vibrant Smile Dental Clinic Team</p>";
    } else if ($status === 'cancelled') {
        $body .= "
            <h2 style='color: #e74a3b;'>Appointment Cancelled</h2>
            <p>Dear {$appointment['patient_name']},</p>
            <p>Your appointment has been cancelled.</p>
            <div style='background-color: #f8f9fc; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                <h3 style='color: #4e73df; margin-top: 0;'>Cancelled Appointment Details:</h3>
                <p><strong>Date:</strong> {$date}</p>
                <p><strong>Time:</strong> {$time}</p>
                <p><strong>Service:</strong> {$service}</p>
                <p><strong>Doctor:</strong> Dr. {$doctor}</p>
            </div>
            <p>If you would like to schedule a new appointment, please visit our website or contact us.</p>
            <p>Best regards,<br> Vibrant Smile Dental Clinic Team</p>";
    } else if ($status === 'completed') {
        $body .= "
            <h2 style='color: #4e73df;'>Appointment Completed</h2>
            <p>Dear {$appointment['patient_name']},</p>
            <p>Your appointment has been completed successfully.</p>
            <div style='background-color: #f8f9fc; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                <h3 style='color: #4e73df; margin-top: 0;'>Appointment Details:</h3>
                <p><strong>Date:</strong> {$date}</p>
                <p><strong>Time:</strong> {$time}</p>
                <p><strong>Service:</strong> {$service}</p>
                <p><strong>Doctor:</strong> Dr. {$doctor}</p>
            </div>
            <div style='margin-bottom: 20px;'>
                <h3 style='color: #4e73df;'>Follow-up Information</h3>
                <p>We hope your dental visit was comfortable and satisfactory. Here are some helpful reminders:</p>
                <ul>
                    <li>Follow any post-procedure care instructions provided by your dentist.</li>
                    <li>If you have any concerns or experience any issues, please contact us immediately.</li>
                    <li>Your next recommended check-up should be scheduled in 6 months.</li>
                    <li>You can view your treatment history and schedule your next appointment through your patient portal.</li>
                </ul>
            </div>
            <p>Best regards,<br> Vibrant Smile Dental Clinic Team</p>";
    }
    
    $body .= '
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
            <p style="color: #666; font-size: 14px;">Thank you for choosing Vibrant Smile Dental Clinic for your dental care needs.</p>
            <p style="color: #666; font-size: 12px;">© ' . date('Y') . ' Vibrant Smile Dental Clinic. All rights reserved.</p>
        </div>
    </div>';
    
    return $body;
}  

function sendRescheduleEmail($to, $subject, $appointment_data) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'vibrantsmile07@gmail.com';
        $mail->Password = 'iovcqaxtgsfdqkwz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Disable SSL verification for testing
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Enable debug output
        $mail->SMTPDebug = 2; // Set to 2 for detailed debug output
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP Debug: $str");
        };

        // Recipients
        $mail->setFrom('vibrantsmile07@gmail.com', 'Vibrant Smile Dental Clinic');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;

        // Format dates
        $new_date = date('F j, Y', strtotime($appointment_data['new_date']));
        $new_time = date('g:i A', strtotime($appointment_data['new_time']));
        $old_date = date('F j, Y', strtotime($appointment_data['old_date']));
        $old_time = date('g:i A', strtotime($appointment_data['old_time']));

        // Create email body based on recipient type and subject
        if ($appointment_data['recipient_type'] === 'patient') {
            if (strpos($subject, 'Suggestion') !== false) {
                // Reschedule suggestion email
                $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background: linear-gradient(135deg, #4e73df 0%, #36b9cc 100%); padding: 20px; text-align: center; border-radius: 5px 5px 0 0;'>
                        <img src='assets/images/logo_vibrant.png' alt='Vibrant Smile Dental Clinic' style='max-width: 200px;'>
                    </div>
                    <div style='padding: 20px; background-color: #ffffff; border-radius: 0 0 5px 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>
                        <h2 style='color: #4e73df;'>Appointment Reschedule Suggestion</h2>
                        <p>Dear {$appointment_data['recipient_name']},</p>
                        <p>Dr. {$appointment_data['doctor_name']} has suggested to reschedule your appointment for {$appointment_data['service_name']}.</p>
                        
                        <div style='background-color: #f8f9fc; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <h3 style='color: #4e73df; margin-top: 0;'>Current Appointment Details:</h3>
                            <p style='margin: 5px 0;'><strong>Date:</strong> {$old_date}</p>
                            <p style='margin: 5px 0;'><strong>Time:</strong> {$old_time}</p>
                            <p style='margin: 5px 0;'><strong>Service:</strong> {$appointment_data['service_name']}</p>
                        </div>
                        
                        <div style='background-color: #e8f4f8; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <h3 style='color: #4e73df; margin-top: 0;'>Suggested New Appointment:</h3>
                            <p style='margin: 5px 0;'><strong>Date:</strong> {$new_date}</p>
                            <p style='margin: 5px 0;'><strong>Time:</strong> {$new_time}</p>
                        </div>
                        
                        <div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <h3 style='color: #856404; margin-top: 0;'>Reason for Rescheduling:</h3>
                            <p style='margin: 5px 0;'>{$appointment_data['notes']}</p>
                        </div>
                        
                        <p>Please log in to your account to respond to this suggestion. You can either accept or decline the proposed new appointment time.</p>
                        
                        <div style='margin-top: 30px; text-align: center;'>
                            <a href='{$appointment_data['website_url']}/appointments.php' style='background-color: #4e73df; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                                View Appointment Details
                            </a>
                        </div>
                        
                        <hr style='border: none; border-top: 1px solid #e9ecef; margin: 20px 0;'>
                        <p style='color: #6c757d; font-size: 12px;'>This is an automated message. Please do not reply to this email.</p>
                    </div>
                </div>";
            } else {
                // Reschedule confirmation email
                $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background: linear-gradient(135deg, #4e73df 0%, #36b9cc 100%); padding: 20px; text-align: center; border-radius: 5px 5px 0 0;'>
                        <img src='assets/images/logo_vibrant.png' alt='Vibrant Smile Dental Clinic' style='max-width: 200px;'>
                    </div>
                    <div style='padding: 20px; background-color: #ffffff; border-radius: 0 0 5px 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>
                        <h2 style='color: #4e73df;'>Appointment Rescheduled Successfully</h2>
                        <p>Dear {$appointment_data['recipient_name']},</p>
                        <p>Your appointment has been successfully rescheduled.</p>
                        
                        <div style='background-color: #f8f9fc; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <h3 style='color: #4e73df; margin-top: 0;'>Previous Appointment:</h3>
                            <p style='margin: 5px 0;'><strong>Date:</strong> {$old_date}</p>
                            <p style='margin: 5px 0;'><strong>Time:</strong> {$old_time}</p>
                        </div>
                        
                        <div style='background-color: #e8f4f8; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <h3 style='color: #4e73df; margin-top: 0;'>New Appointment Details:</h3>
                            <p style='margin: 5px 0;'><strong>Date:</strong> {$new_date}</p>
                            <p style='margin: 5px 0;'><strong>Time:</strong> {$new_time}</p>
                        </div>
                        
                        <div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <h3 style='color: #856404; margin-top: 0;'>Important Reminders:</h3>
                            <ul style='margin: 5px 0; padding-left: 20px;'>
                                <li>Please arrive 15 minutes before your scheduled appointment time</li>
                                <li>Bring your valid ID and any previous dental records</li>
                                <li>If you need to cancel or reschedule, please notify us at least 24 hours in advance</li>
                            </ul>
                        </div>
                        
                        <p>If you have any questions or concerns, please don't hesitate to contact us.</p>
                        
                        <div style='margin-top: 30px; text-align: center;'>
                            <a href='{$appointment_data['website_url']}/appointments.php' style='background-color: #4e73df; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                                View Appointment Details
                            </a>
                        </div>
                        
                        <hr style='border: none; border-top: 1px solid #e9ecef; margin: 20px 0;'>
                        <p style='color: #6c757d; font-size: 12px;'>This is an automated message. Please do not reply to this email.</p>
                    </div>
                </div>";
            }
        } else {
            // Doctor's email
            if (strpos($subject, 'Declined') !== false) {
                // Reschedule declined email
                $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background: linear-gradient(135deg, #4e73df 0%, #36b9cc 100%); padding: 20px; text-align: center; border-radius: 5px 5px 0 0;'>
                        <img src='assets/images/logo_vibrant.png' alt='Vibrant Smile Dental Clinic' style='max-width: 200px;'>
                    </div>
                    <div style='padding: 20px; background-color: #ffffff; border-radius: 0 0 5px 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>
                        <h2 style='color: #4e73df;'>Reschedule Suggestion Declined</h2>
                        <p>Dear Dr. {$appointment_data['recipient_name']},</p>
                        <p>The patient has declined your suggested reschedule.</p>
                        
                        <div style='background-color: #f8f9fc; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <h3 style='color: #4e73df; margin-top: 0;'>Original Appointment Details:</h3>
                            <p style='margin: 5px 0;'><strong>Date:</strong> {$old_date}</p>
                            <p style='margin: 5px 0;'><strong>Time:</strong> {$old_time}</p>
                            <p style='margin: 5px 0;'><strong>Patient:</strong> {$appointment_data['patient_name']}</p>
                            <p style='margin: 5px 0;'><strong>Service:</strong> {$appointment_data['service_name']}</p>
                        </div>
                        
                        <div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <h3 style='color: #856404; margin-top: 0;'>Important Notes:</h3>
                            <ul style='margin: 5px 0; padding-left: 20px;'>
                                <li>The original appointment remains unchanged</li>
                                <li>Please prepare for the appointment at the original scheduled time</li>
                            </ul>
                        </div>
                        
                        <div style='margin-top: 30px; text-align: center;'>
                            <a href='{$appointment_data['website_url']}/appointments.php' style='background-color: #4e73df; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                                View Appointment Details
                            </a>
                        </div>
                        
                        <hr style='border: none; border-top: 1px solid #e9ecef; margin: 20px 0;'>
                        <p style='color: #6c757d; font-size: 12px;'>This is an automated message. Please do not reply to this email.</p>
                    </div>
                </div>";
            } else {
                // Reschedule accepted email
                $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background: linear-gradient(135deg, #4e73df 0%, #36b9cc 100%); padding: 20px; text-align: center; border-radius: 5px 5px 0 0;'>
                        <img src='assets/images/logo_vibrant.png' alt='Vibrant Smile Dental Clinic' style='max-width: 200px;'>
                    </div>
                    <div style='padding: 20px; background-color: #ffffff; border-radius: 0 0 5px 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>
                        <h2 style='color: #4e73df;'>Reschedule Suggestion Accepted</h2>
                        <p>Dear Dr. {$appointment_data['recipient_name']},</p>
                        <p>The patient has accepted your suggested reschedule.</p>
                        
                        <div style='background-color: #f8f9fc; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <h3 style='color: #4e73df; margin-top: 0;'>Previous Appointment:</h3>
                            <p style='margin: 5px 0;'><strong>Date:</strong> {$old_date}</p>
                            <p style='margin: 5px 0;'><strong>Time:</strong> {$old_time}</p>
                            <p style='margin: 5px 0;'><strong>Patient:</strong> {$appointment_data['patient_name']}</p>
                        </div>
                        
                        <div style='background-color: #e8f4f8; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <h3 style='color: #4e73df; margin-top: 0;'>New Appointment Details:</h3>
                            <p style='margin: 5px 0;'><strong>Date:</strong> {$new_date}</p>
                            <p style='margin: 5px 0;'><strong>Time:</strong> {$new_time}</p>
                            <p style='margin: 5px 0;'><strong>Service:</strong> {$appointment_data['service_name']}</p>
                            <p style='margin: 5px 0;'><strong>Patient:</strong> {$appointment_data['patient_name']}</p>
                        </div>
                        
                        <div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <h3 style='color: #856404; margin-top: 0;'>Important Notes:</h3>
                            <ul style='margin: 5px 0; padding-left: 20px;'>
                                <li>The appointment has been updated in the system</li>
                                <li>The patient has been notified of the new schedule</li>
                                <li>Please prepare for the appointment accordingly</li>
                            </ul>
                        </div>
                        
                        <div style='margin-top: 30px; text-align: center;'>
                            <a href='{$appointment_data['website_url']}/appointments.php' style='background-color: #4e73df; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                                View Appointment Details
                            </a>
                        </div>
                        
                        <hr style='border: none; border-top: 1px solid #e9ecef; margin: 20px 0;'>
                        <p style='color: #6c757d; font-size: 12px;'>This is an automated message. Please do not reply to this email.</p>
                    </div>
                </div>";
            }
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
} 