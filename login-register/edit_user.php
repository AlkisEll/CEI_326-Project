<?php
session_start();

require_once "get_config.php";
$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");


require_once "database.php";

if (!isset($_SESSION["user"]) || !in_array($_SESSION["user"]["role"], ['admin', 'owner'])) {

    header("Location: index.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: manage_users.php");
    exit();
}

$id = (int)$_GET["id"];

if (isset($_POST["save"])) {
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $role = $_POST["role"];

    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, role=? WHERE id=?");
    $stmt->bind_param("sssi", $full_name, $email, $role, $id);
    $stmt->execute();

    header("Location: manage_users.php");
    exit();
}

// Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) {
    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="indexstyle.css">
    <link rel="stylesheet" href="darkmode.css">
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="https://www.cut.ac.cy" target="_blank" title="Go to Cyprus University of Technology">
            <?php if (!empty($logo_path) && file_exists($logo_path)): ?>
            <img src="<?= $logo_path ?>" alt="Logo" style="height: 40px; margin-right: 10px;">
            <?php endif; ?>
            <span><?= htmlspecialchars($system_title) ?></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav ms-auto align-items-center">
                <a href="javascript:history.back()" class="btn btn-secondary">← Back</a>
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h2>Edit User</h2>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user["full_name"]) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user["email"]) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select" required>
                <option value="user" <?= $user["role"] === "user" ? "selected" : "" ?>>User</option>
                <option value="admin" <?= $user["role"] === "admin" ? "selected" : "" ?>>Admin</option>
            </select>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" name="save" class="btn btn-success">Save Changes</button>
            <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<script>
    const saved = localStorage.getItem('dark-mode');
    if (saved === 'true') document.body.classList.add('dark-mode');
</script>
</body>
</html>
