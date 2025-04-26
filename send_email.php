<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Autoload PHPMailer using Composer

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = 0; // Set to 2 for detailed debug output
    $mail->isSMTP(); // Use SMTP
    $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
    $mail->SMTPAuth = true; // Enable SMTP authentication
    $mail->Username = 'xxxxxxxx@gmail.com'; // Your Gmail address
    $mail->Password = 'XXXX XXXX XXXX XXXX'; // Your app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable TLS encryption; `PHPMailer::ENCRYPTION_STARTTLS` also accepted
    $mail->Port = 465; // TCP port to connect to

    // Recipients
    $mail->setFrom('xxxxxxxx@gmail.com', 'Your Name'); // Your Gmail address and name
    $mail->addAddress('yyyyyyyyyy@gmail.com', 'Recipient Name'); // Add a recipient

    // Content
    $mail->isHTML(true); // Set email format to HTML
    $mail->Subject = 'Subject of the email';
    $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
