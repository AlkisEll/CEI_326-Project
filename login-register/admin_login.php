<?php
session_start();
require_once "database.php";

// Redirect if already logged in as admin or owner
if (isset($_SESSION["admin"])) {
    header("Location: admin_dashboard.php");
    exit();
}

// Auto-fill email from session user (if logged in)
$prefilled_email = $_SESSION["user"]["email"] ?? "";

$login_error = "";

if (isset($_POST["login"])) {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Allow login for admin or owner
    $sql = "SELECT * FROM users WHERE email = ? AND role IN ('admin', 'owner')";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($admin = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $admin["password"])) {
            $_SESSION["admin"] = [
                "id" => $admin["id"],
                "email" => $admin["email"],
                "full_name" => $admin["full_name"],
                "role" => $admin["role"]
            ];
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $login_error = "Incorrect password.";
        }
    } else {
        $login_error = "You do not have permission to access Admin Mode.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - CUT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="indexstyle.css">
</head>
<body>
<div class="container py-5">
    <div class="card p-4 mx-auto" style="max-width: 400px;">
        <h2 class="mb-4 text-center">Admin Login</h2>

        <?php if (!empty($login_error)): ?>
            <div class="alert alert-danger"><?= $login_error ?></div>
        <?php endif; ?>

        <form method="post" action="admin_login.php">
            <div class="mb-3">
                <label class="form-label">Admin Email</label>
                <input type="email" name="email" class="form-control" required readonly
                       value="<?= htmlspecialchars($prefilled_email) ?>" />
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Enter password">
            </div>
            <div class="d-grid">
                <input type="submit" value="Login" name="login" class="btn btn-primary">
            </div>
        </form>
    </div>
</div>
</body>
</html>
