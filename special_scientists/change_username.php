
<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION["user"];
$message = "";

// Fetch current user info
$stmt = $conn->prepare("SELECT username, password FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$currentUsername = $user["username"] ?? "";
$hashedPassword = $user["password"] ?? "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newUsername = trim($_POST["new_username"]);
    $enteredPassword = $_POST["password"];

    if (!password_verify($enteredPassword, $hashedPassword)) {
        $message = "<div class='alert alert-danger'>Incorrect password. Please try again.</div>";
    } else {
        // Check if username already exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->bind_param("si", $newUsername, $userId);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "<div class='alert alert-danger'>Username already taken.</div>";
        } else {
            $update = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $update->bind_param("si", $newUsername, $userId);
            if ($update->execute()) {
                $message = "<div class='alert alert-success'>Username updated successfully.</div>";
                $currentUsername = $newUsername;
            } else {
                $message = "<div class='alert alert-danger'>Something went wrong. Please try again.</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Username</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width: 500px;">
    <h3 class="mb-4">Change Username</h3>
    <?= $message ?>
    <form method="POST">
        <div class="mb-3">
            <label for="new_username" class="form-label">New Username</label>
            <input type="text" class="form-control" id="new_username" name="new_username"
                   value="<?= htmlspecialchars($currentUsername); ?>" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Enter your Password to confirm</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="d-flex justify-content-between">
            <a href="my_profile.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-danger">Update Username</button>
        </div>
    </form>
</div>
</body>
</html>
