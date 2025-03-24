<?php
session_start();
require_once "database.php";

if (isset($_POST["verify"])) {
    $email = trim($_POST["email"]);
    $input_code = trim($_POST["verification_code"]);

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_stmt_init($conn);

    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if (trim($row["verification_code"]) === $input_code) {
                $updateSql = "UPDATE users SET is_verified = 1, verification_code = NULL WHERE email = ?";
                $stmtUpdate = mysqli_stmt_init($conn);

                if (mysqli_stmt_prepare($stmtUpdate, $updateSql)) {
                    mysqli_stmt_bind_param($stmtUpdate, "s", $email);
                    mysqli_stmt_execute($stmtUpdate);

                    // Automatically log the user in by setting session variables
                    $_SESSION["user"] = [
                        "id" => $row["id"],
                        "email" => $row["email"],
                        "full_name" => $row["full_name"]
                    ];

                    // Redirect instantly to dashboard (index.php)
                    header("Location: index.php");
                    exit();
                }
            } else {
                echo "<div class='alert alert-danger'>Incorrect verification code.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Email not found.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Something went wrong. Please try again later.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Your Email</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container mt-5">
    <a href="https://www.cut.ac.cy" class="logo-link" target="_blank" title="Go to the CUT website"></a>
    <form action="verify.php" method="post">
        <h2>Email Verification</h2>
        <div class="form-group">
            <input type="email" name="email" placeholder="Your Email" class="form-control" required>
        </div>
        <div class="form-group mt-2">
            <input type="text" name="verification_code" placeholder="Verification Code" class="form-control" required maxlength="6">
        </div>
        <div class="form-btn mt-3">
            <button type="submit" name="verify" class="btn btn-primary">Verify Email</button>
        </div>
    </form>
</div>
</body>
</html>
