<?php
session_start();

require_once "database.php";

// Get user info from session safely
$userId = $_SESSION["user_id"] ?? ($_SESSION["user"]["id"] ?? null);
$email = $_SESSION["email"] ?? ($_SESSION["user"]["email"] ?? '');
$full_name = $_SESSION["full_name"] ?? ($_SESSION["user"]["full_name"] ?? '');

if (!$userId) {
    header("Location: registration.php");
    exit();
}

// Load PHPMailer
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Twilio credentials
$accountSid = 'ACed50809afda0163369b2505abc4354f7';
$authToken = 'a0e8ced97d0ab07db55e20f99fa7121e';
$twilioPhoneNumber = '+12182616825';

// Get user's phone from DB
$userPhone = '';
$stmt = $conn->prepare("SELECT phone FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($userPhone);
$stmt->fetch();
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["select_method"])) {
    $verificationMethod = $_POST["verification_method"];
    $verification_code = rand(100000, 999999);

    $stmt = $conn->prepare("UPDATE users SET verification_code = ? WHERE id = ?");
    $stmt->bind_param("si", $verification_code, $userId);
    $stmt->execute();
    $stmt->close();

    if ($verificationMethod === 'email') {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'premium245.web-hosting.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'admin@festival-web.com';
            $mail->Password = '!g3$~8tYju*D';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('admin@festival-web.com', 'Festival-web Admin');
            $mail->addAddress($email, $full_name);

            $mail->isHTML(true);
            $mail->Subject = 'Verify your email';
            $mail->Body = "<p>Your verification code is: <b>$verification_code</b></p>";
            $mail->send();

            echo "<div class='alert alert-success'>Verification code sent to your email. <a href='verify.php'>Click here to verify.</a></div>";
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>Failed to send email. Please try again later.</div>";
        }

    } elseif ($verificationMethod === 'phone') {
        if (empty($userPhone)) {
            echo "<div class='alert alert-danger'>Phone number is missing. You cannot verify by phone.</div>";
        } else {
            $_SESSION["phone"] = $userPhone;
            $_SESSION["verification_code"] = $verification_code;

            $body = "Your verification code is: $verification_code";

            $postData = http_build_query([
                'From' => $twilioPhoneNumber,
                'To' => $userPhone,
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
                header("Location: verify_phone.php");
                exit();
            } else {
                $responseData = json_decode($response, true);
                $errorMessage = $responseData['message'] ?? 'Failed to send SMS.';
                echo "<div class='alert alert-danger'>$errorMessage</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Verification Method</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <a href="https://www.cut.ac.cy" class="logo-link" target="_blank" title="Go to the CUT website"></a>
    <h2>Select Verification Method</h2>

    <form method="post" action="select_verification_method.php" id="verification-form">
        <input type="hidden" name="fullname" value="<?= htmlspecialchars($full_name) ?>">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

        <div class="form-group">
            <label for="verification_method">Verification Method</label>
            <select class="form-control" name="verification_method" id="verification_method" required>
                <option value="email">Email Verification</option>
                <option value="phone">Phone Verification</option>
            </select>
        </div>

        <div class="form-group" id="email-display" style="display: none;">
            <label for="email">Registered Email</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($email) ?>" readonly>
        </div>

        <div class="form-group" id="phone-display" style="display: none;">
            <label for="phone">Registered Phone</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($userPhone) ?>" readonly>
        </div>

        <div class="form-btn">
            <input type="submit" class="btn btn-primary" value="Continue" name="select_method">
        </div>
    </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
    $(document).ready(function () {
        $('#verification_method').on('change', function () {
            const method = $(this).val();
            $('#email-display').toggle(method === 'email');
            $('#phone-display').toggle(method === 'phone');
        }).trigger('change');
    });
</script>
</body>
</html>
