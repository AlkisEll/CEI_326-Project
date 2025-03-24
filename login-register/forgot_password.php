<?php
session_start();
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = "";
$success = "";

if (isset($_POST["submit"])) {
    $email = $_POST["email"];
    require_once "database.php";

    try {
        // Check if the email exists in the database
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt, $sql)) {
            throw new Exception("Something went wrong.");
        }
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            throw new Exception("Email not found!");
        }

        // Generate a unique token for password reset
        $resetToken = bin2hex(random_bytes(50));
        $expiry = gmdate("Y-m-d H:i:s", time() + 3600); // Token valid for 1 hour in UTC

        // Store the token in the database
        $sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?";
        $stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt, $sql)) {
            throw new Exception("Something went wrong.");
        }
        mysqli_stmt_bind_param($stmt, "sss", $resetToken, $expiry, $email);
        mysqli_stmt_execute($stmt);

        // Send password reset email
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->SMTPDebug = 0; // Enable verbose debug output
            $mail->isSMTP();
            $mail->Host = 'premium245.web-hosting.com'; // Set your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'admin@festival-web.com'; // SMTP username
            $mail->Password = '!g3$~8tYju*D'; // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587; // Use TLS port
        
            // Recipients
            $mail->setFrom('admin@festival-web.com', 'Festival-web Admin');
            $mail->addAddress($email, $user["full_name"]);
        
            // Content
            $mail->CharSet = 'UTF-8'; // Set charset to UTF-8
            $mail->isHTML(false); // Disable HTML for testing
            $mail->Subject = 'Password Reset Request'; 
            $userName = !empty($user['full_name']) ? $user['full_name'] : 'User';
            $mail->Body = "Dear $userName,\n\nWe received a request to reset your password. Click the link below to change it:\nhttp://localhost/login-register/reset_password.php?token=$resetToken\n\nIf you did not request this change, please ignore this email.";
        
            // Send the email
            if (!$mail->send()) {
                throw new Exception("Email could not be sent. Error: " . $mail->ErrorInfo);
            }
        
            $success = "The password reset link has been sent to your email. Please check your inbox or your Spam Folder if you don’t see it.";
        } catch (Exception $e) {
            throw new Exception("Failed to send email: " . $e->getMessage());
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Forgot Password - CUT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom Styling -->
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <a href="https://www.cut.ac.cy" class="logo-link" target="_blank" title="Go to the CUT website"></a>
        <h2>Password Recovery</h2>

        <?php if (!empty($error)): ?>
            <div class='alert alert-danger'><?= $error ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class='alert alert-success'><?= $success ?></div>
        <?php else: ?>
            <form action="forgot_password.php" method="post">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" placeholder="e.g. user@cut.ac.cy" name="email" class="form-control" required>
                </div>
                <div class="form-btn">
                    <input type="submit" value="Send Link" name="submit" class="btn btn-primary">
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>
