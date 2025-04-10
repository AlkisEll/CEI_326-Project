<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["user"]) || !in_array($_SESSION["user"]["role"], ['admin', 'owner'])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST["create"])) {
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $role = $_POST["role"];

    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, is_verified) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("ssss", $full_name, $email, $password, $role);
    $stmt->execute();

    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h2>Add New User</h2>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required minlength="8">
        </div>
        <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" name="create" class="btn btn-primary">Create User</button>
            <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>
