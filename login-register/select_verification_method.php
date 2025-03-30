<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: registration.php");
    exit();
}

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once "database.php";

// Twilio credentials
$accountSid = 'ACed50809afda0163369b2505abc4354f7';
$authToken = '978bd899503cb48316db96598ee2662c';
$twilioPhoneNumber = '+12182616825';

// Get the user's phone number from the database
$userPhone = '';
$sql = "SELECT phone FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION["user_id"]);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $userPhone);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (isset($_POST["select_method"])) {
    $verificationMethod = $_POST["verification_method"];
    $phone = $userPhone;
    $verification_code = rand(100000, 999999);

    if ($verificationMethod === 'email') {
        $sql = "UPDATE users SET verification_code = ? WHERE id = ?";
        $stmt = mysqli_stmt_init($conn);
        if (mysqli_stmt_prepare($stmt, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $verification_code, $_SESSION["user_id"]);
            mysqli_stmt_execute($stmt);

            $mail = new PHPMailer(true);
            try {
                $mail->SMTPDebug = 0;
                $mail->isSMTP();
                $mail->Host = 'premium245.web-hosting.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'admin@festival-web.com';
                $mail->Password = '!g3$~8tYju*D';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('admin@festival-web.com', 'Festival-web Admin');
                $mail->addAddress($_SESSION["email"], $_SESSION["full_name"]);

                $mail->isHTML(true);
                $mail->Subject = 'Verify your email';
                $mail->Body = "<p>Your verification code is: <b>$verification_code</b></p>";
                $mail->send();

                echo "<div class='alert alert-success'>The verification code has been sent to your email. <a href='verify.php'>Click here to enter your verification code.</a></div>";
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>Failed to send verification code. Please try again later.</div>";
            }
        } else {
            die("Something went wrong. Please try again later.");
        }
    } elseif ($verificationMethod === 'phone') {
        if (empty($phone)) {
            echo "<div class='alert alert-danger'>A phone number is required for phone verification!</div>";
        } else {
            $sql = "UPDATE users SET verification_code = ? WHERE id = ?";
            $stmt = mysqli_stmt_init($conn);
            if (mysqli_stmt_prepare($stmt, $sql)) {
                mysqli_stmt_bind_param($stmt, "si", $verification_code, $_SESSION["user_id"]);
                mysqli_stmt_execute($stmt);

                $_SESSION["phone"] = $phone;
                $_SESSION["verification_code"] = $verification_code;

                $body = "Your verification code is: $verification_code";

                $postData = http_build_query([
                    'From' => $twilioPhoneNumber,
                    'To' => $phone,
                    'Body' => $body,
                ]);

                $curl = curl_init();

                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://api.twilio.com/2010-04-01/Accounts/$accountSid/Messages.json",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $postData,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/x-www-form-urlencoded',
                        'Authorization: Basic ' . base64_encode("$accountSid:$authToken"),
                    ],
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]);

                $response = curl_exec($curl);

                if (curl_errno($curl)) {
                    echo "<div class='alert alert-danger'>Failed to send SMS: " . curl_error($curl) . "</div>";
                } else {
                    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    if ($httpCode === 201) {
                        header("Location: verify_phone.php");
                        exit();
                    } else {
                        $responseData = json_decode($response, true);
                        $errorMessage = $responseData['message'] ?? 'Failed to send SMS.';
                        echo "<div class='alert alert-danger'>$errorMessage</div>";
                    }
                }

                curl_close($curl);
            } else {
                die("Something went wrong. Please try again later.");
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
        <input type="hidden" name="fullname" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>">

        <div class="form-group">
            <label for="verification_method">Verification Method</label>
            <select class="form-control" name="verification_method" id="verification_method" required>
                <option value="email">Email Verification</option>
                <option value="phone">Phone Verification</option>
            </select>
        </div>

        <!-- Email Display -->
        <div class="form-group" id="email-display" style="display: none;">
            <label for="email">Registered Email</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly>
        </div>

        <!-- Phone Display -->
        <div class="form-group" id="phone-display" style="display: none;">
            <label for="phone">Registered Phone</label>
            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($userPhone); ?>" readonly>
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
