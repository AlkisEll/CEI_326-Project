<?php
session_start();
if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

require_once "database.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>System Login - CUT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom Styling -->
    <link rel="stylesheet" href="style.css">

</head>

<body>
    <div class="container">
        <a href="https://www.cut.ac.cy" class="logo-link" target="_blank" title="Go to the CUT website"></a>
        <h2>System Login</h2>

        <?php
        if (isset($_POST["login"])) {
            $email = trim($_POST["email"]);
            $password = $_POST["password"];
            
            $sql = "SELECT * FROM users WHERE email = ?";
            $stmt = mysqli_stmt_init($conn);
            if (mysqli_stmt_prepare($stmt, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if ($user = mysqli_fetch_assoc($result)) {
                    if (password_verify($password, $user["password"])) {
                        $now = new DateTime();
                        $twofa_expired = empty($user["twofa_expires"]) || new DateTime($user["twofa_expires"]) < $now;

                        if ($twofa_expired) {
                            // Generate new 2FA code
                            $twofa_code = rand(100000, 999999);
                            $expires = $now->modify('+48 hours')->format('Y-m-d H:i:s');

                            // Save 2FA code and expiry
                            $updateSql = "UPDATE users SET twofa_code=?, twofa_expires=? WHERE email=?";
                            $stmtUpdate = mysqli_stmt_init($conn);
                            mysqli_stmt_prepare($stmtUpdate, $updateSql);
                            mysqli_stmt_bind_param($stmtUpdate, "sss", $twofa_code, $expires, $email);
                            mysqli_stmt_execute($stmtUpdate);

                            // Send email with 2FA code
                            $mail = new PHPMailer(true);
                            $mail->isSMTP();
                            $mail->Host = 'premium245.web-hosting.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'admin@festival-web.com';
                            $mail->Password = '!g3$~8tYju*D';
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = 587;

                            $mail->setFrom('admin@festival-web.com', 'Festival-web Admin');
                            $mail->addAddress($email, $user['full_name']);
                            $mail->isHTML(true);
                            $mail->Subject = "Your 2FA Login Code";
                            $mail->Body = "<p>Your 2FA code is: <b>$twofa_code</b></p>";

                            $mail->send();

                            $_SESSION['temp_user_id'] = $user['id'];
                            header("Location: twofa_verify.php");
                            exit();
                        } else {
                            // Log the user in without 2FA if still within 48 hours
                            $_SESSION["user"] = [
                                "id" => $user["id"],
                                "email" => $user["email"],
                                "full_name" => $user["full_name"],
                                "role" => $user["role"]
                            ];
                            
                            header("Location: index.php");
                            exit();
                        }
                    } else {
                        echo "<div class='alert alert-danger'>Incorrect Password.</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger'>Email not found.</div>";
                }
            }
        }
        ?>

        <form action="login.php" method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" placeholder="e.g. user@cut.ac.cy" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" placeholder="Enter your password" name="password" class="form-control" required>
            </div>
            <div class="form-btn">
                <input type="submit" value="Login" name="login" class="btn btn-primary">
            </div>
        </form>

        <div class="form-footer">Don't have an account? <a href="registration.php">Register</a><br>
            <a href="forgot_password.php">Forgot your password?</a>
        </div>
    </div>
</body>
</html>
