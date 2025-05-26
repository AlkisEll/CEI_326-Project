<?php
session_start();
require_once "database.php";
require_once "send_email.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$userId    = $_SESSION["user"]["id"];
$newEmail  = trim($_POST["new_email"] ?? '');

if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    $_SESSION["profile_success"] = "Invalid email address.";
    header("Location: my_profile.php");
    exit();
}

// Generate 6-digit code
$code = random_int(100000, 999999);

// Insert into DB
$stmt = $conn->prepare("INSERT INTO email_verifications (user_id, email, code) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $userId, $newEmail, $code);
$stmt->execute();

// Send email
$subject = "Email Verification Code";
$body = "<p>Hello,</p><p>Your verification code is: <strong>$code</strong></p><p>Use this code to confirm your new email address.</p>";

if (sendEmail($newEmail, $subject, $body)) {
    $_SESSION["pending_email"] = $newEmail;
    $_SESSION["profile_success"] = "Verification code sent to $newEmail.";
    header("Location: verify_email_code.php");
    exit();
} else {
    $_SESSION["profile_success"] = "Failed to send email. Try again.";
    header("Location: my_profile.php");
    exit();
}