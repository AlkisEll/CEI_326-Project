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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
<div class="container">
    <a href="https://www.cut.ac.cy" class="logo-link" target="_blank" title="Go to the CUT website"></a>
    <h2>System Login</h2>

    <!-- Social Sign-In -->
    <div class="mb-4 text-center">
        <!-- Google -->
        <a href="https://accounts.google.com/o/oauth2/v2/auth?client_id=298135741411-6gbhvfmubpk1vjgbeervmma5mntarggk.apps.googleusercontent.com&redirect_uri=http://localhost/special-scientists/google_callback.php&response_type=code&scope=email%20profile&access_type=online" 
           class="btn btn-light border d-flex align-items-center justify-content-center gap-2"
           style="max-width: 300px; margin: auto;">
            <img src="https://developers.google.com/identity/images/g-logo.png" style="height: 20px;">
            Continue with Google
        </a>

        <!-- Microsoft -->
        <a href="https://login.microsoftonline.com/common/oauth2/v2.0/authorize?client_id=56612818-b196-4abd-944f-198689fee50c&response_type=code&redirect_uri=http%3A%2F%2Flocalhost%2Fspecial-scientists%2Fmicrosoft_callback.php&response_mode=query&scope=https%3A%2F%2Fgraph.microsoft.com%2Fuser.read&state=12345"
           class="btn btn-light border d-flex align-items-center justify-content-center gap-2 mt-3"
           style="max-width: 300px; margin: auto;">
            <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" 
                 alt="Microsoft" style="height: 20px;">
            Continue with Microsoft
        </a>
    </div>

<?php
if (isset($_POST["login"])) {
    $loginInput = trim($_POST["login_input"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE LOWER(email) = LOWER(?) OR LOWER(username) = LOWER(?)";
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $loginInput, $loginInput);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user["password"])) {
                $now = new DateTime();
                $twofa_expired = empty($user["twofa_expires"]) || new DateTime($user["twofa_expires"]) < $now;

                if ($twofa_expired) {
                    // Generate 2FA
                    $twofa_code = rand(100000, 999999);
                    $expires = $now->modify('+48 hours')->format('Y-m-d H:i:s');

                    $updateSql = "UPDATE users SET twofa_code=?, twofa_expires=? WHERE email=?";
                    $stmtUpdate = mysqli_stmt_init($conn);
                    mysqli_stmt_prepare($stmtUpdate, $updateSql);
                    mysqli_stmt_bind_param($stmtUpdate, "sss", $twofa_code, $expires, $user['email']);
                    mysqli_stmt_execute($stmtUpdate);

                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = 'premium245.web-hosting.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'admin@festival-web.com';
                    $mail->Password = '!g3$~8tYju*D';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('admin@festival-web.com', 'Festival-web Admin');
                    $mail->addAddress($user['email'], $user['full_name']);
                    $mail->isHTML(true);
                    $mail->Subject = "Your 2FA Login Code";
                    $mail->Body = "<p>Your 2FA code is: <b>$twofa_code</b></p>";

                    $mail->send();

                    $_SESSION['temp_user_id'] = $user['id'];
                    header("Location: twofa_verify.php");
                    exit();
                } else {
                    // ✅ Get previous last_login before updating it
                    $prevLogin = null;
                    $getLogin = $conn->prepare("SELECT last_login FROM users WHERE id = ?");
                    $getLogin->bind_param("i", $user['id']);
                    $getLogin->execute();
                    $loginResult = $getLogin->get_result();
                    if ($row = $loginResult->fetch_assoc()) {
                        $prevLogin = $row['last_login'];
                    }

                    // ✅ Update last_login to now
                    $updateLogin = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $updateLogin->bind_param("i", $user['id']);
                    $updateLogin->execute();

                    // ✅ Set session with previous login time
                    $_SESSION["user"] = [
                        "id" => $user["id"],
                        "email" => $user["email"],
                        "full_name" => $user["full_name"],
                        "role" => $user["role"],
                        "last_login" => $prevLogin
                    ];
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];

                    header("Location: index.php");
                    exit();
                }
            } else {
                echo "<div class='alert alert-danger'>Incorrect Password.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Email or Username not found.</div>";
        }
    }
}
?>

<form action="login.php" method="post">
    <div class="form-group">
        <label for="login_input">Username or Email Address</label>
        <input type="text" class="form-control" id="login_input" name="login_input" placeholder="e.g. user150 or user@cut.ac.cy" required>
    </div>

    <div class="form-group">
        <label for="password">Password</label>
        <div class="input-group">
            <input type="password" class="form-control" name="password" id="password" required>
            <span class="input-group-text toggle-password" data-target="#password"><i class="bi bi-eye"></i></span>
        </div>
    </div>

    <div class="form-btn">
        <input type="submit" value="Login" name="login" class="btn btn-primary">
    </div>
</form>

<div class="form-footer">
    Don't have an account? <a href="registration.php">Register</a><br>
    <a href="forgot_password.php">Forgot your password?</a>
</div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
    $(document).ready(function () {
        $(".toggle-password").click(function () {
            const input = $($(this).data("target"));
            const type = input.attr("type") === "password" ? "text" : "password";
            input.attr("type", type);
            $(this).find("i").toggleClass("bi-eye bi-eye-slash");
        });
    });
</script>

</body>
</html>
