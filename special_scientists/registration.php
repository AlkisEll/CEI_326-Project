<?php
session_start();
require_once "database.php";

// If user already logged in, redirect to index
if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

// Handle manual email submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["manual_email"])) {
    $manual_email = trim($_POST["manual_email"]);

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $manual_email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION["registration_error"] = "An account with this email already exists!";
        header("Location: registration.php");
        exit();
    } else {
        $_SESSION["manual_email"] = $manual_email;
        header("Location: complete_profile.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register or Continue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="registration_page">
<div class="container text-center py-5">

    <!-- Display registration errors if any -->
    <?php if (isset($_SESSION["registration_error"])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION["registration_error"]; unset($_SESSION["registration_error"]); ?>
        </div>
    <?php endif; ?>

    <a href="https://www.cut.ac.cy" class="logo-link" target="_blank" title="Go to CUT website"></a>

    <h2 class="mb-4">Create Your Account</h2>

    <!-- Google Sign-In -->
    <div class="mb-3">
        <a href="https://accounts.google.com/o/oauth2/v2/auth?client_id=298135741411-6gbhvfmubpk1vjgbeervmma5mntarggk.apps.googleusercontent.com&redirect_uri=http://cei326-omada7.cut.ac.cy/special_scientists/google_callback.php&response_type=code&scope=email%20profile&access_type=online" 
           class="btn btn-light border d-flex align-items-center justify-content-center gap-2 mx-auto" style="max-width: 300px;">
            <img src="https://developers.google.com/identity/images/g-logo.png" style="height: 20px;">
            Continue with Google
        </a>
    </div>

    <!-- Microsoft Sign-In -->
    <a href="https://login.microsoftonline.com/common/oauth2/v2.0/authorize?client_id=56612818-b196-4abd-944f-198689fee50c&response_type=code&redirect_uri=http%3A%2F%2Fcei326-omada7.cut.ac.cy%2Fspecial_scientists%2Fmicrosoft_callback.php&response_mode=query&scope=https%3A%2F%2Fgraph.microsoft.com%2Fuser.read&state=12345"
    class="btn btn-light border d-flex align-items-center justify-content-center gap-2 mt-3" style="max-width: 300px; margin: auto;">
        <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" alt="Microsoft" style="height: 20px;">
        Continue with Microsoft
    </a>

    <!-- Manual Email Entry -->
    <form method="POST" action="registration.php" class="mt-4" style="max-width: 300px; margin: auto;">
        <div class="form-group mb-3">
            <input type="email" name="manual_email" class="form-control" placeholder="Enter your email" required>
        </div>
        <button type="submit" class="btn btn-primary">Continue</button>
    </form>

    <div class="form-footer mt-4">
        Already have an account? <a href="login.php">Login</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
