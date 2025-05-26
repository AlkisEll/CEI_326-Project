<?php
declare(strict_types=1);

require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail(string $to, string $subject, string $htmlBody): bool
{
    $mail = new PHPMailer(true);
    try {
        // ———– Use the exact same working settings as in login.php ———–
        $mail->isSMTP();
        $mail->Host       = 'premium245.web-hosting.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admin@festival-web.com';
        $mail->Password   = '!g3$~8tYju*D';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('admin@festival-web.com', 'Special Scientists');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        return $mail->send();
    } catch (Exception $e) {
        // Optional: log $mail->ErrorInfo to your error log
        error_log('sendEmail failure: ' . $mail->ErrorInfo);
        return false;
    }
}
