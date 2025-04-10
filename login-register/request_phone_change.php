<?php
session_start();
require_once "database.php";
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check login
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION["user"]["id"];
$email = $_SESSION["user"]["email"];
$fullName = $_SESSION["user"]["full_name"];
$newPhone = trim($_POST["new_phone"] ?? '');

// Fetch current phone from DB
$sql = "SELECT phone FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
$currentPhone = $user["phone"];

if ($newPhone === $currentPhone) {
    echo "<script>alert('Your new phone is the same as your current phone.'); window.location.href='my_profile.php';</script>";
    exit();
}

// Generate verification code
$verificationCode = rand(100000, 999999);

// Save in session for later verification
$_SESSION["phone_change_code"] = $verificationCode;
$_SESSION["pending_phone"] = $newPhone;

// Send email with the code
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'premium245.web-hosting.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'admin@festival-web.com';
    $mail->Password = '!g3$~8tYju*D';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('admin@festival-web.com', 'CUT System');
    $mail->addAddress($email, $fullName);
    $mail->isHTML(true);
    $mail->Subject = "Phone Number Change Verification";
    $mail->Body = "<p>To confirm your phone number change, enter the following code:</p><h2>$verificationCode</h2>";

    $mail->send();

    header("Location: verify_phone_change.php");
    exit();
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Failed to send email. Please try again later.</div>";
}
?>
