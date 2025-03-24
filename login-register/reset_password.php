<?php
session_start();
require_once "database.php";

if (isset($_GET["token"])) {
    $token = trim($_GET["token"]); // Trim the token
    error_log("Token from URL: $token");

    // Check if the token is valid and not expired
    $sql = "SELECT * FROM users WHERE BINARY reset_token = ? AND reset_token_expiry > UTC_TIMESTAMP()";
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            error_log("Token found in database: " . $user['reset_token']);
            error_log("Token expiry: " . $user['reset_token_expiry']);

            if (isset($_POST["submit"])) {
                $newPassword = $_POST["new_password"];
                $confirmPassword = $_POST["confirm_password"];

                // Validate that the new password and confirm password match
                if ($newPassword !== $confirmPassword) {
                    $error = "Passwords do not match!";
                } else {
                    // Hash the new password
                    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

                    // Update the user's password and clear the reset token
                    $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?";
                    $stmt = mysqli_stmt_init($conn);
                    if (mysqli_stmt_prepare($stmt, $sql)) {
                        mysqli_stmt_bind_param($stmt, "ss", $newPasswordHash, $token);
                        mysqli_stmt_execute($stmt);
                        $success = "Your password has been successfully changed. You can now <a href='login.php'>log in here</a> with your new password.";
                    } else {
                        $error = "Something went wrong. Please try again later.";
                    }
                }
            }
        } else {
            $error = "The link has possibly expired. Please request a new password reset link.";
        }
    } else {
        $error = "Something went wrong. Please try again later.";
    }
} else {
    $error = "No reset link was provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Reset - CUT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom Styling -->
    <link rel="stylesheet" href="style.css">

    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <link rel="stylesheet" href="style.css">
 
</head>
<body>
    <div class="container">
        <a href="https://www.cut.ac.cy" class="logo-link" target="_blank" title="Go to the CUT website"></a>
        <h2>Password Reset</h2>

        <?php if (isset($error)): ?>
            <div class='alert alert-danger'><?= $error ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class='alert alert-success'><?= $success ?></div>
        <?php else: ?>
            <form action="reset_password.php?token=<?= htmlspecialchars($token) ?>" method="post" id="reset-password-form">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" placeholder="Enter new password" name="new_password" id="new_password" class="form-control" required>
                    <div class="password-strength-meter">
                        <div class="strength-bar" id="strength-bar"></div>
                    </div>
                    <div class="password-strength-text" id="strength-text"></div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" placeholder="Confirm new password" name="confirm_password" class="form-control" required>
                </div>
                <div class="form-btn">
                    <input type="submit" value="Reset Password" name="submit" class="btn btn-primary">
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- zxcvbn for password strength -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        $(document).ready(function () {
            // Password strength meter
            $("#new_password").on("input", function () {
                const password = $(this).val();
                const result = zxcvbn(password);
                const strength = result.score; // 0 (Weak) to 4 (Strong)

                // Update strength bar and text
                const strengthBar = $("#strength-bar");
                const strengthText = $("#strength-text");

                let strengthClass = "";
                let strengthLabel = "";

                switch (strength) {
                    case 0:
                        strengthClass = "weak";
                        strengthLabel = "Weak";
                        break;
                    case 1:
                        strengthClass = "fair";
                        strengthLabel = "Fair";
                        break;
                    case 2:
                        strengthClass = "good";
                        strengthLabel = "Good";
                        break;
                    case 3:
                        strengthClass = "very-good";
                        strengthLabel = "Very Good";
                        break;
                    case 4:
                        strengthClass = "strong";
                        strengthLabel = "Strong";
                        break;
                }

                // Update strength bar
                strengthBar.removeClass().addClass("strength-bar " + strengthClass);
                strengthBar.css("width", (strength + 1) * 25 + "%");

                // Update strength text and color
                strengthText.removeClass().addClass("password-strength-text " + strengthClass);
                strengthText.text("Password Strength: " + strengthLabel);
            });

            // Form submission validation
            $("#reset-password-form").submit(function (e) {
                const password = $("#new_password").val();
                const result = zxcvbn(password);
                const strength = result.score;

                if (strength < 1) { // Block submission if password is "Weak"
                    e.preventDefault();
                    toastr.error('Your password is too weak. Please choose a stronger one');
                }
            });
        });
    </script>
</body>
</html>
