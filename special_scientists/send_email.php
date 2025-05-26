<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Adjust path if needed

function sendEmail($to, $subject, $htmlBody) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.example.com'; // Replace with your SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'you@example.com';
        $mail->Password   = 'your_password';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('you@example.com', 'Special Scientists');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}