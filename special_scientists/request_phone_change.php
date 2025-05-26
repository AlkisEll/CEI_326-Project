<?php
session_start();
require_once "database.php";

// Twilio credentials
$accountSid = 'ACed50809afda0163369b2505abc4354f7';
$authToken = 'a0e8ced97d0ab07db55e20f99fa7121e';
$twilioPhoneNumber = '+12182616825';

// Check login
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION["user"]["id"];
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

// Send SMS with the code
$body = "To confirm your phone number change, enter this code: $verificationCode";

$postData = http_build_query([
    'From' => $twilioPhoneNumber,
    'To' => $newPhone,
    'Body' => $body,
]);

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.twilio.com/2010-04-01/Accounts/$accountSid/Messages.json",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Basic ' . base64_encode("$accountSid:$authToken"),
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($httpCode === 201) {
    header("Location: verify_phone_change.php");
    exit();
} else {
    $responseData = json_decode($response, true);
    $errorMessage = $responseData['message'] ?? 'Failed to send SMS.';
    echo "<div class='alert alert-danger'>" . htmlspecialchars($errorMessage) . "</div>";
}
?>
