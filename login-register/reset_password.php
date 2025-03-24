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
                    $error = "Οι κωδικοί δεν αντιστοιχούν!";
                } else {
                    // Hash the new password
                    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

                    // Update the user's password and clear the reset token
                    $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?";
                    $stmt = mysqli_stmt_init($conn);
                    if (mysqli_stmt_prepare($stmt, $sql)) {
                        mysqli_stmt_bind_param($stmt, "ss", $newPasswordHash, $token);
                        mysqli_stmt_execute($stmt);
                        $success = "Ο κωδικός σας έχει αλλάξει επιτυχώς. Μπορείτε τώρα να <a href='login.php'>συνδεθείτε εδώ</a> με τον νέο σας κωδικό.";
                    } else {
                        $error = "Κάτι πήγε στραβά. Δοκιμάστε ξανά αργότερα.";
                    }
                }
            }
        } else {
            $error = "Ο σύνδεσμος έχει πιθανότητα λήξει. Δοκιμάστε να ξαναστείλετε νέο αίτημα για αλλαγή του κωδικού σας.";
        }
    } else {
        $error = "Κάτι πήγε στραβά. Δοκιμάστε ξανά αργότερα.";
    }
} else {
    $error = "Δεν έχει δοθεί σύνδεσμος";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Επαναφορά Κωδικού - ΤΕΠΑΚ</title>
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
        <a href="https://www.cut.ac.cy" class="logo-link" target="_blank" title="Μετάβαση στην ιστοσελίδα του ΤΕΠΑΚ"></a>
        <h2>Επαναφορά Κωδικού Πρόσβασης</h2>

        <?php if (isset($error)): ?>
            <div class='alert alert-danger'><?= $error ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class='alert alert-success'><?= $success ?></div>
        <?php else: ?>
            <form action="reset_password.php?token=<?= htmlspecialchars($token) ?>" method="post" id="reset-password-form">
                <div class="form-group">
                    <label for="new_password">Νέος Κωδικός</label>
                    <input type="password" placeholder="Εισάγετε νέο κωδικό" name="new_password" id="new_password" class="form-control" required>
                    <div class="password-strength-meter">
                        <div class="strength-bar" id="strength-bar"></div>
                    </div>
                    <div class="password-strength-text" id="strength-text"></div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Επαλήθευση Κωδικού</label>
                    <input type="password" placeholder="Επιβεβαιώστε τον νέο κωδικό" name="confirm_password" class="form-control" required>
                </div>
                <div class="form-btn">
                    <input type="submit" value="Επαναφορά Κωδικού" name="submit" class="btn btn-primary">
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
                        strengthLabel = "Αδύναμος";
                        break;
                    case 1:
                        strengthClass = "fair";
                        strengthLabel = "Μέτριος";
                        break;
                    case 2:
                        strengthClass = "good";
                        strengthLabel = "Καλός";
                        break;
                    case 3:
                        strengthClass = "very-good";
                        strengthLabel = "Πολύ Καλός";
                        break;
                    case 4:
                        strengthClass = "strong";
                        strengthLabel = "Δυνατός";
                        break;
                }

                // Update strength bar
                strengthBar.removeClass().addClass("strength-bar " + strengthClass);
                strengthBar.css("width", (strength + 1) * 25 + "%");

                // Update strength text and color
                strengthText.removeClass().addClass("password-strength-text " + strengthClass);
                strengthText.text("Ισχύς Κωδικού: " + strengthLabel);
            });

            // Form submission validation
            $("#reset-password-form").submit(function (e) {
                const password = $("#new_password").val();
                const result = zxcvbn(password);
                const strength = result.score;

                if (strength < 1) { // Block submission if password is "Weak"
                    e.preventDefault();
                    toastr.error('Ο κωδικός σας είναι πολύ αδύναμος. Παρακαλώ επιλέξτε έναν ισχυρότερο κωδικό');
                }
            });
        });
    </script>
</body>
</html>