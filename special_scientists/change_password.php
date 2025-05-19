<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION["user"]["id"];

if (isset($_POST["submit"])) {
    $currentPassword = $_POST["current_password"];
    $newPassword = $_POST["new_password"];
    $confirmPassword = $_POST["confirm_password"];

    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    $currentHash = $user["password"];

    if (!password_verify($currentPassword, $currentHash)) {
        $_SESSION["error"] = "Your current password is incorrect.";
    } elseif ($newPassword !== $confirmPassword) {
        $_SESSION["error"] = "New passwords do not match.";
    } elseif (strlen($newPassword) < 8) {
        $_SESSION["error"] = "New password must be at least 8 characters.";
    } else {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $newHash, $userId);
        mysqli_stmt_execute($stmt);

        $_SESSION["success"] = "Password updated successfully.";
        header("Location: my_profile.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap, Toastr, and Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .input-group .form-control {
            position: relative;
        }
        .input-group-text {
            background: #f8f9fa;
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <h2 class="mb-4">Change Password</h2>
    <form method="post" id="change-password-form">
        <!-- Current Password -->
        <div class="mb-3">
            <label for="current_password" class="form-label">Current Password</label>
            <div class="input-group">
                <input type="password" name="current_password" id="current_password" class="form-control" required>
                <span class="input-group-text toggle-password" data-target="#current_password">
                    <i class="bi bi-eye"></i>
                </span>
            </div>
        </div>

        <!-- New Password -->
        <div class="mb-3">
            <label for="new_password" class="form-label">New Password</label>
            <div class="input-group">
                <input type="password" name="new_password" id="new_password" class="form-control" required>
                <span class="input-group-text toggle-password" data-target="#new_password">
                    <i class="bi bi-eye"></i>
                </span>
            </div>
            <div class="password-strength-meter mt-1">
                <div class="strength-bar" id="strength-bar"></div>
            </div>
            <div class="password-strength-text" id="strength-text"></div>
        </div>

        <!-- Confirm Password -->
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm New Password</label>
            <div class="input-group">
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                <span class="input-group-text toggle-password" data-target="#confirm_password">
                    <i class="bi bi-eye"></i>
                </span>
            </div>
        </div>

        <input type="submit" name="submit" class="btn btn-primary" value="Update Password">
    </form>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    // Toastr Notifications
    <?php if (isset($_SESSION["error"])): ?>
        toastr.error("<?= $_SESSION["error"]; ?>");
        <?php unset($_SESSION["error"]); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION["success"])): ?>
        toastr.success("<?= $_SESSION["success"]; ?>");
        <?php unset($_SESSION["success"]); ?>
    <?php endif; ?>

    // Password Visibility Toggle
    $(".toggle-password").on("click", function () {
        const input = $($(this).data("target"));
        const icon = $(this).find("i");
        const type = input.attr("type") === "password" ? "text" : "password";
        input.attr("type", type);
        icon.toggleClass("bi-eye bi-eye-slash");
    });

    // Password Strength Meter
    $("#new_password").on("input", function () {
        const password = $(this).val();
        const result = zxcvbn(password);
        const score = result.score;

        const bar = $("#strength-bar");
        const text = $("#strength-text");

        const labels = ["Weak", "Fair", "Good", "Very Good", "Strong"];
        const classes = ["weak", "fair", "good", "very-good", "strong"];

        bar.removeClass().addClass("strength-bar " + classes[score]);
        bar.css("width", (score + 1) * 20 + "%");
        text.text("Password Strength: " + labels[score]).removeClass().addClass("password-strength-text " + classes[score]);
    });
</script>
</body>
</html>
